<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Player;
use App\Entity\Word;
use App\Enum\WordStatus;
use App\Repository\VoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class VoteTest extends WebTestCase
{
    public function testVotingRecordsTheVoteAndKeepsWordPending(): void
    {
        $client = static::createClient();
        [$word, $bob] = $this->seed();

        $this->vote($client, $word->getId(), 'yes', 'token-bob');

        self::assertResponseStatusCodeSame(201);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertSame('pending', $data['status']);

        self::assertTrue(static::getContainer()->get(VoteRepository::class)->hasVoted($bob, $word));
    }

    public function testCannotVoteOnOwnWord(): void
    {
        $client = static::createClient();
        [$word] = $this->seed();

        $this->vote($client, $word->getId(), 'yes', 'token-alice');

        self::assertResponseStatusCodeSame(403);
        self::assertJson((string) $client->getResponse()->getContent());
    }

    public function testCannotVoteTwiceOnTheSameWord(): void
    {
        $client = static::createClient();
        [$word] = $this->seed();

        $this->vote($client, $word->getId(), 'yes', 'token-bob');
        self::assertResponseStatusCodeSame(201);

        $this->vote($client, $word->getId(), 'no', 'token-bob');
        self::assertResponseStatusCodeSame(403);
    }

    public function testVotingOnUnknownWordReturns404(): void
    {
        $client = static::createClient();
        $this->seed();

        $this->vote($client, 999999, 'yes', 'token-bob');

        self::assertResponseStatusCodeSame(404);
    }

    public function testInvalidVoteValueReturns422(): void
    {
        $client = static::createClient();
        [$word] = $this->seed();

        $this->vote($client, $word->getId(), 'maybe', 'token-bob');

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * @return array{Word, Player}
     */
    private function seed(): array
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $alice = new Player('alice', 'token-alice');
        $bob = new Player('bob', 'token-bob');
        $word = new Word('pomme', $alice, WordStatus::PENDING);
        $em->persist($alice);
        $em->persist($bob);
        $em->persist($word);
        $em->flush();

        return [$word, $bob];
    }

    private function vote(KernelBrowser $client, ?int $wordId, string $value, string $token): void
    {
        $client->request(
            'POST',
            "/api/words/{$wordId}/votes",
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer {$token}"],
            content: (string) json_encode(['value' => $value]),
        );
    }
}
