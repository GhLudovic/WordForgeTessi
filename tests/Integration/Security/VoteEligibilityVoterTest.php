<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security;

use App\Entity\Player;
use App\Entity\Vote;
use App\Entity\Word;
use App\Enum\VoteValue;
use App\Enum\WordStatus;
use App\Security\Voter\VoteEligibilityVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class VoteEligibilityVoterTest extends KernelTestCase
{
    public function testEligibleWhenPendingNotAuthorNotVotedAndUnderQuota(): void
    {
        $author = new Player('author', 'token-author');
        $voter = new Player('voter', 'token-voter');
        $word = new Word('eligible', $author, WordStatus::PENDING);
        $this->persist($author, $voter, $word);

        self::assertTrue($this->mayVote($voter, $word));
    }

    public function testNotEligibleForOwnWord(): void
    {
        $author = new Player('author', 'token-author');
        $word = new Word('mine', $author, WordStatus::PENDING);
        $this->persist($author, $word);

        self::assertFalse($this->mayVote($author, $word));
    }

    public function testNotEligibleWhenWordIsNotPending(): void
    {
        $author = new Player('author', 'token-author');
        $voter = new Player('voter', 'token-voter');
        $word = new Word('done', $author, WordStatus::ACCEPTED);
        $this->persist($author, $voter, $word);

        self::assertFalse($this->mayVote($voter, $word));
    }

    public function testNotEligibleWhenAlreadyVoted(): void
    {
        $author = new Player('author', 'token-author');
        $voter = new Player('voter', 'token-voter');
        $word = new Word('voted', $author, WordStatus::PENDING);
        $this->persist($author, $voter, $word, new Vote($voter, $word, VoteValue::YES));

        self::assertFalse($this->mayVote($voter, $word));
    }

    public function testNotEligibleWhenQuotaIsReached(): void
    {
        $author = new Player('author', 'token-author');
        $voter = new Player('voter', 'token-voter');
        $word = new Word('full', $author, WordStatus::PENDING);
        $this->persist($author, $voter, $word);
        for ($i = 0; $i < 7; ++$i) {
            $other = new Player("p{$i}", "token-p{$i}");
            $this->persist($other, new Vote($other, $word, VoteValue::YES));
        }

        self::assertFalse($this->mayVote($voter, $word));
    }

    private function persist(object ...$entities): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        foreach ($entities as $entity) {
            $em->persist($entity);
        }
        $em->flush();
    }

    private function mayVote(Player $player, Word $word): bool
    {
        $container = static::getContainer();
        $container->get(TokenStorageInterface::class)
            ->setToken(new UsernamePasswordToken($player, 'main', $player->getRoles()));

        return $container->get(AuthorizationCheckerInterface::class)->isGranted(VoteEligibilityVoter::CAST, $word);
    }
}
