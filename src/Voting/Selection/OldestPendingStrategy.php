<?php

declare(strict_types=1);

namespace App\Voting\Selection;

use App\Entity\Word;

/**
 * Sélectionne le mot pending le plus ancien (équité : premier proposé, premier voté).
 */
final class OldestPendingStrategy implements WordSelectionStrategyInterface
{
    public function select(array $candidates): ?Word
    {
        $oldest = null;
        foreach ($candidates as $word) {
            if (null === $oldest || $word->getCreatedAt() < $oldest->getCreatedAt()) {
                $oldest = $word;
            }
        }

        return $oldest;
    }
}
