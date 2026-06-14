<?php

declare(strict_types=1);

namespace App\Voting\Selection;

use App\Entity\Word;
use App\Repository\VoteRepository;

/**
 * Sélectionne le mot le plus proche du quota (le plus de votes). Fait converger
 * les votes vers une résolution rapide plutôt que de les éparpiller.
 */
final class ClosestToQuotaStrategy implements WordSelectionStrategyInterface
{
    public function __construct(private readonly VoteRepository $votes)
    {
    }

    public function select(array $candidates): ?Word
    {
        $best = null;
        $bestCount = -1;
        foreach ($candidates as $word) {
            $count = $this->votes->countByWord($word);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $word;
            }
        }

        return $best;
    }
}
