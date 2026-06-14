<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Player;
use App\Entity\Word;
use App\Enum\WordStatus;
use App\Repository\VoteRepository;
use App\Voting\VotingPolicy;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class VoteEligibilityVoter extends Voter
{
    public const string CAST = 'CAST_VOTE';

    public function __construct(private readonly VoteRepository $votes)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::CAST === $attribute && $subject instanceof Word;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $player = $token->getUser();
        if (!$player instanceof Player || !$subject instanceof Word) {
            return false;
        }

        return WordStatus::PENDING === $subject->getStatus()
            && $subject->getAuthor()->getId() !== $player->getId()
            && !$this->votes->hasVoted($player, $subject)
            && $this->votes->countByWord($subject) < VotingPolicy::QUOTA;
    }
}
