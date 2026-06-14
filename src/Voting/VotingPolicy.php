<?php

declare(strict_types=1);

namespace App\Voting;

/**
 * Règles partagées du processus de vote (source unique).
 */
final class VotingPolicy
{
    /**
     * Nombre de votes qui tranche le sort d'un mot. Impair : pas d'ex-aequo.
     */
    public const int QUOTA = 7;
}
