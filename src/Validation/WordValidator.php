<?php

declare(strict_types=1);

namespace App\Validation;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Agrège toutes les contraintes taguées et fusionne leurs violations.
 * Service public : point d'entrée de la validation, consommé par l'API.
 */
#[Autoconfigure(public: true)]
final class WordValidator
{
    /**
     * @param iterable<WordConstraintInterface> $constraints
     */
    public function __construct(
        #[AutowireIterator('app.word_constraint')]
        private readonly iterable $constraints,
    ) {
    }

    /**
     * @return list<string> violations (liste vide = le mot est valide)
     */
    public function validate(string $value): array
    {
        $violations = [];
        foreach ($this->constraints as $constraint) {
            $violations = [...$violations, ...$constraint->validate($value)];
        }

        return $violations;
    }
}
