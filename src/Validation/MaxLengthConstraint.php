<?php

declare(strict_types=1);

namespace App\Validation;

final class MaxLengthConstraint implements WordConstraintInterface
{
    public function __construct(private readonly int $maxLength = 32)
    {
    }

    public function validate(string $value): array
    {
        if (mb_strlen($value) > $this->maxLength) {
            return [sprintf('Word must not exceed %d characters.', $this->maxLength)];
        }

        return [];
    }
}
