<?php

declare(strict_types=1);

namespace App\Tests\Integration\Vote;

use App\Entity\Player;
use App\Entity\Word;
use App\Enum\VoteValue;
use App\Enum\WordStatus;
use App\Service\VoteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class VoteResolutionTest extends KernelTestCase
{
    public function testWordStaysPendingUntilQuotaIsReached(): void
    {
        $word = $this->pendingWord();

        $this->cast($word, [VoteValue::YES, VoteValue::YES, VoteValue::YES, VoteValue::NO, VoteValue::NO, VoteValue::NO]);

        self::assertSame(WordStatus::PENDING, $word->getStatus());
    }

    public function testSeventhVoteWithYesMajorityAcceptsTheWord(): void
    {
        $word = $this->pendingWord();

        // 4 yes / 3 no => accepted au 7e vote.
        $this->cast($word, [VoteValue::YES, VoteValue::YES, VoteValue::YES, VoteValue::NO, VoteValue::NO, VoteValue::NO]);
        $this->cast($word, [VoteValue::YES]);

        self::assertSame(WordStatus::ACCEPTED, $word->getStatus());
    }

    public function testSeventhVoteWithNoMajorityRejectsTheWord(): void
    {
        $word = $this->pendingWord();

        // 3 yes / 4 no => rejected au 7e vote.
        $this->cast($word, [VoteValue::YES, VoteValue::YES, VoteValue::YES, VoteValue::NO, VoteValue::NO, VoteValue::NO]);
        $this->cast($word, [VoteValue::NO]);

        self::assertSame(WordStatus::REJECTED, $word->getStatus());
    }

    private function pendingWord(): Word
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $author = new Player('author', 'token-author');
        $word = new Word('mot', $author, WordStatus::PENDING);
        $em->persist($author);
        $em->persist($word);
        $em->flush();

        return $word;
    }

    /**
     * @param list<VoteValue> $values
     */
    private function cast(Word $word, array $values): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $service = static::getContainer()->get(VoteService::class);
        foreach ($values as $value) {
            $player = new Player(uniqid('player'), uniqid('token-'));
            $em->persist($player);
            $em->flush();
            $service->castVote($player, $word, $value);
        }
    }
}
