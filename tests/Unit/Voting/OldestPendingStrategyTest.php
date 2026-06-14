<?php

declare(strict_types=1);

namespace App\Tests\Unit\Voting;

use App\Entity\Player;
use App\Entity\Word;
use App\Enum\WordStatus;
use App\Voting\Selection\OldestPendingStrategy;
use PHPUnit\Framework\TestCase;

final class OldestPendingStrategyTest extends TestCase
{
    public function testReturnsNullWhenNoCandidate(): void
    {
        self::assertNull((new OldestPendingStrategy())->select([]));
    }

    public function testSelectsTheOldestCandidate(): void
    {
        $author = new Player('author', 'token-author');
        $recent = new Word('recent', $author, WordStatus::PENDING, new \DateTimeImmutable('2026-01-02'));
        $oldest = new Word('oldest', $author, WordStatus::PENDING, new \DateTimeImmutable('2026-01-01'));
        $newest = new Word('newest', $author, WordStatus::PENDING, new \DateTimeImmutable('2026-01-03'));

        self::assertSame($oldest, (new OldestPendingStrategy())->select([$recent, $oldest, $newest]));
    }
}
