<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Enum\VoteValue;
use App\Enum\WordStatus;
use App\Event\VoteCastEvent;
use App\Repository\VoteRepository;
use App\Voting\VotingPolicy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Résout le mot dès que le quota de votes est atteint : accepted si majorité de
 * yes, sinon rejected. Exécuté de façon synchrone, donc dans la transaction et
 * sous le verrou pessimiste ouverts par VoteService::castVote (résolution atomique).
 */
#[AsEventListener]
final class ResolveWordOnVoteCastListener
{
    public function __construct(
        private readonly VoteRepository $votes,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(VoteCastEvent $event): void
    {
        $word = $event->word;

        if (VotingPolicy::QUOTA !== $this->votes->countByWord($word)) {
            return;
        }

        $yesCount = $this->votes->countByWordAndValue($word, VoteValue::YES);
        $word->setStatus($yesCount * 2 > VotingPolicy::QUOTA ? WordStatus::ACCEPTED : WordStatus::REJECTED);
        $this->em->flush();
    }
}
