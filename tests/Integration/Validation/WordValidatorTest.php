<?php

declare(strict_types=1);

namespace App\Tests\Integration\Validation;

use App\Validation\WordValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WordValidatorTest extends KernelTestCase
{
    public function testValidWordHasNoViolations(): void
    {
        self::assertSame([], $this->validator()->validate('hello'));
    }

    public function testCollectsAndAggregatesAllTaggedConstraints(): void
    {
        // Un mot avec un espace ET de plus de 32 caractères viole les deux
        // contraintes taguées : l'agrégateur doit remonter les deux violations.
        $value = str_repeat('a', 20).' '.str_repeat('b', 20);

        self::assertCount(2, $this->validator()->validate($value));
    }

    private function validator(): WordValidator
    {
        self::bootKernel();

        return static::getContainer()->get(WordValidator::class);
    }
}
