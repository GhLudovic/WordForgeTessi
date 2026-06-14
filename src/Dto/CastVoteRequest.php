<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\VoteValue;

final readonly class CastVoteRequest
{
    public function __construct(
        public VoteValue $value,
    ) {
    }
}
