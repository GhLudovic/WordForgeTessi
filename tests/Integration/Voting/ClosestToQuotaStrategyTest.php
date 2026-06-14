<?php

declare(strict_types=1);

namespace App\Tests\Integration\Voting;

use App\Entity\Player;
use App\Entity\Vote;
use App\Entity\Word;
use App\Enum\VoteValue;
use App\Enum\WordStatus;
use App\Repository\VoteRepository;
use App\Voting\Selection\ClosestToQuotaStrategy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ClosestToQuotaStrategyTest extends KernelTestCase
{
    public function testReturnsNullWhenNoCandidate(): void
    {
        self::assertNull($this->strategy()->select([]));
    }

    public function testSelectsTheWordClosestToTheQuota(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $author = new Player('author', 'token-author');
        $em->persist($author);

        $few = new Word('few', $author, WordStatus::PENDING);
        $many = new Word('many', $author, WordStatus::PENDING);
        $em->persist($few);
        $em->persist($many);
        $this->addVotes($em, $few, 1);
        $this->addVotes($em, $many, 3);
        $em->flush();

        self::assertSame($many, $this->strategy()->select([$few, $many]));
    }

    private function strategy(): ClosestToQuotaStrategy
    {
        return new ClosestToQuotaStrategy(static::getContainer()->get(VoteRepository::class));
    }

    private function addVotes(EntityManagerInterface $em, Word $word, int $count): void
    {
        for ($i = 0; $i < $count; ++$i) {
            $player = new Player(uniqid('player'), uniqid('token-'));
            $em->persist($player);
            $em->persist(new Vote($player, $word, VoteValue::YES));
        }
    }
}
