<?php

declare(strict_types=1);

namespace App\Voting\Selection;

use App\Entity\Word;

interface WordSelectionStrategyInterface
{
    /**
     * Choisit le mot à proposer au vote parmi des candidats déjà éligibles.
     *
     * @param list<Word> $candidates
     */
    public function select(array $candidates): ?Word;
}
