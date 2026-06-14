<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validation;

use App\Validation\SingleWordConstraint;
use PHPUnit\Framework\TestCase;

final class SingleWordConstraintTest extends TestCase
{
    public function testSingleWordHasNoViolation(): void
    {
        self::assertSame([], (new SingleWordConstraint())->validate('hello'));
    }

    public function testWordContainingASpaceIsRejected(): void
    {
        $violations = (new SingleWordConstraint())->validate('hello world');

        self::assertNotEmpty($violations);
    }
}
