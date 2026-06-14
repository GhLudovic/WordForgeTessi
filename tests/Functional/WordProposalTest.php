<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Player;
use App\Entity\Word;
use App\Enum\WordStatus;
use App\Repository\WordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WordProposalTest extends WebTestCase
{
    public function testProposingValidWordCreatesPendingWordOwnedByCurrentPlayer(): void
    {
        $client = static::createClient();
        $this->persistPlayer('alice', 'token-alice');

        $this->propose($client, 'licorne');

        self::assertResponseStatusCodeSame(201);
        $data = $this->json($client);
        self::assertSame('licorne', $data['value']);
        self::assertSame('pending', $data['status']);

        $word = static::getContainer()->get(WordRepository::class)->findOneByValue('licorne');
        self::assertNotNull($word);
        self::assertSame('alice', $word->getAuthor()->getUsername());
        self::assertSame(WordStatus::PENDING, $word->getStatus());
    }

    public function testProposingAnExistingWordIsRejected(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $alice = new Player('alice', 'token-alice');
        $em->persist($alice);
        $em->persist(new Word('dragon', $alice, WordStatus::REJECTED));
        $em->flush();

        $this->propose($client, 'dragon');

        self::assertResponseStatusCodeSame(409);
    }

    public function testProposingAWordWithASpaceIsRejectedWithViolations(): void
    {
        $client = static::createClient();
        $this->persistPlayer('alice', 'token-alice');

        $this->propose($client, 'deux mots');

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('violations', $this->json($client));
    }

    public function testProposingATooLongWordIsRejectedWithViolations(): void
    {
        $client = static::createClient();
        $this->persistPlayer('alice', 'token-alice');

        $this->propose($client, str_repeat('a', 33));

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('violations', $this->json($client));
    }

    public function testProposingABlankValueIsRejected(): void
    {
        $client = static::createClient();
        $this->persistPlayer('alice', 'token-alice');

        $this->propose($client, '');

        self::assertResponseStatusCodeSame(422);
    }

    private function persistPlayer(string $username, string $token): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist(new Player($username, $token));
        $em->flush();
    }

    private function propose(KernelBrowser $client, string $value): void
    {
        $client->request(
            'POST',
            '/api/words',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer token-alice'],
            content: (string) json_encode(['value' => $value]),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function json(KernelBrowser $client): array
    {
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($data);

        return $data;
    }
}
