<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Player;
use App\Entity\Word;
use App\Enum\WordStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WordCheckTest extends WebTestCase
{
    public function testReturnsRealStatusOfExistingWordsAndUnknownOtherwise(): void
    {
        $client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $author = new Player('author', 'token-author');
        $em->persist($author);
        $em->persist(new Word('accepted-word', $author, WordStatus::ACCEPTED));
        $em->persist(new Word('pending-word', $author, WordStatus::PENDING));
        $em->persist(new Word('rejected-word', $author, WordStatus::REJECTED));
        $em->flush();

        self::assertSame('accepted', $this->checkStatus($client, 'accepted-word'));
        self::assertSame('pending', $this->checkStatus($client, 'pending-word'));
        self::assertSame('rejected', $this->checkStatus($client, 'rejected-word'));
        self::assertSame('unknown', $this->checkStatus($client, 'does-not-exist'));
    }

    private function checkStatus(KernelBrowser $client, string $value): string
    {
        $client->request('GET', '/api/words/'.$value, server: ['HTTP_AUTHORIZATION' => 'Bearer token-author']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($data);

        return (string) $data['status'];
    }
}
