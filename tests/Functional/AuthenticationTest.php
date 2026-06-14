<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthenticationTest extends WebTestCase
{
    public function testRequestWithoutTokenIsRejected(): void
    {
        $client = $this->bootClientWithAlice();

        $client->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(401);
        self::assertJson((string) $client->getResponse()->getContent());
    }

    public function testRequestWithValidTokenSucceeds(): void
    {
        $client = $this->bootClientWithAlice();

        $client->request('GET', '/api/me', server: ['HTTP_AUTHORIZATION' => 'Bearer token-alice']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertSame('alice', $data['username'] ?? null);
    }

    public function testRequestWithInvalidTokenIsRejected(): void
    {
        $client = $this->bootClientWithAlice();

        $client->request('GET', '/api/me', server: ['HTTP_AUTHORIZATION' => 'Bearer unknown-token']);

        self::assertResponseStatusCodeSame(401);
    }

    private function bootClientWithAlice(): KernelBrowser
    {
        $client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist(new Player('alice', 'token-alice'));
        $em->flush();

        return $client;
    }
}
