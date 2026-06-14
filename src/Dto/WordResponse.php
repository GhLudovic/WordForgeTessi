<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Word;

final readonly class WordResponse
{
    public function __construct(
        public ?int $id,
        public string $value,
        public string $status,
    ) {
    }

    public static function fromWord(Word $word): self
    {
        return new self($word->getId(), $word->getValue(), $word->getStatus()->value);
    }
}
