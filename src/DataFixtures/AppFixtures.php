<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Player;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    /**
     * Joueurs de test avec des tokens fixes et lisibles (documentés dans le README).
     *
     * @var array<string, string>
     */
    private const array PLAYERS = [
        'alice' => 'token-alice',
        'bob' => 'token-bob',
        'carol' => 'token-carol',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PLAYERS as $username => $token) {
            $manager->persist(new Player($username, $token));
        }

        $manager->flush();
    }
}
