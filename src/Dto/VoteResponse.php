<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class VoteResponse
{
    public function __construct(
        public ?int $wordId,
        public string $status,
    ) {
    }
}
