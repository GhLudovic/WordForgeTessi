<?php

declare(strict_types=1);

namespace App\Validation;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Une contrainte appliquée à la valeur d'un mot proposé. Toute implémentation est
 * automatiquement taguée : ajouter une règle = ajouter une classe, sans autre config.
 */
#[AutoconfigureTag('app.word_constraint')]
interface WordConstraintInterface
{
    /**
     * @return list<string> violations (liste vide = le mot satisfait cette contrainte)
     */
    public function validate(string $value): array;
}
