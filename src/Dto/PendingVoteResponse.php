<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class PendingVoteResponse
{
    public function __construct(
        public ?int $id,
        public string $value,
    ) {
    }
}
