<?php

declare(strict_types=1);

namespace App\Validation;

final class SingleWordConstraint implements WordConstraintInterface
{
    public function validate(string $value): array
    {
        if (1 === preg_match('/\s/', $value)) {
            return ['Word must not contain whitespace.'];
        }

        return [];
    }
}
