<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Word;

/**
 * Émis (de façon synchrone, dans la transaction de vote) après l'enregistrement
 * d'un vote sur un mot.
 */
final class VoteCastEvent
{
    public function __construct(
        public readonly Word $word,
    ) {
    }
}
