<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Player;
use App\Entity\Vote;
use App\Entity\Word;
use App\Enum\VoteValue;
use App\Enum\WordStatus;
use App\Event\VoteCastEvent;
use App\Exception\WordNotPendingException;
use App\Repository\WordRepository;
use App\Security\Voter\VoteEligibilityVoter;
use App\Voting\Selection\WordSelectionStrategyInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class VoteService
{
    public function __construct(
        private readonly WordRepository $words,
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $dispatcher,
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

    /**
     * Enregistre le vote du joueur de façon atomique : verrou pessimiste sur le
     * mot, re-vérification du statut dans le verrou (garde anti-concurrence),
     * puis émission de VoteCastEvent — dont le listener tranche le mot au quota,
     * de façon synchrone et donc dans la même transaction.
     */
    public function castVote(Player $player, Word $word, VoteValue $value): Vote
    {
        return $this->em->wrapInTransaction(function () use ($player, $word, $value): Vote {
            $this->em->lock($word, LockMode::PESSIMISTIC_WRITE);

            if (WordStatus::PENDING !== $word->getStatus()) {
                throw new WordNotPendingException();
            }

            $vote = new Vote($player, $word, $value);
            $this->em->persist($vote);
            $this->em->flush();

            $this->dispatcher->dispatch(new VoteCastEvent($word));

            return $vote;
        });
    }
}
