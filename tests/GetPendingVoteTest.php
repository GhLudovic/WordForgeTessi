<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Player;
use App\Entity\Word;
use App\Enum\WordStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetPendingVoteTest extends WebTestCase
{
    public function testReturnsAnEligibleWordToVoteOn(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $alice = new Player('alice', 'token-alice');
        $bob = new Player('bob', 'token-bob');
        $em->persist($alice);
        $em->persist($bob);
        $em->persist(new Word('pomme', $alice, WordStatus::PENDING));
        $em->flush();

        $client->request('GET', '/api/votes/pending', server: ['HTTP_AUTHORIZATION' => 'Bearer token-bob']);

        self::assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertSame('pomme', $data['value']);
    }

    public function testReturnsNoContentWhenNoEligibleWord(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $alice = new Player('alice', 'token-alice');
        $em->persist($alice);

        $em->persist(new Word('pomme', $alice, WordStatus::PENDING));
        $em->flush();

        $client->request('GET', '/api/votes/pending', server: ['HTTP_AUTHORIZATION' => 'Bearer token-alice']);

        self::assertResponseStatusCodeSame(204);
    }
}
