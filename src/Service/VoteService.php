<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Word;
use App\Repository\WordRepository;
use App\Security\Voter\VoteEligibilityVoter;
use App\Voting\Selection\WordSelectionStrategyInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class VoteService
{
    public function __construct(
        private readonly WordRepository $words,
        private readonly Security $security,
        private readonly WordSelectionStrategyInterface $selectionStrategy,
    ) {
    }

    /**
     * Mot que le joueur courant peut voter (éligibilité déléguée au Voter,
     * sélection à la stratégie active), ou null si aucun.
     */
    public function findVotableWord(): ?Word
    {
        $eligible = array_values(array_filter(
            $this->words->findPending(),
            fn (Word $word): bool => $this->security->isGranted(VoteEligibilityVoter::CAST, $word),
        ));

        return $this->selectionStrategy->select($eligible);
    }
}
