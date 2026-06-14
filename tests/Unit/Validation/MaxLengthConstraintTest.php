<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validation;

use App\Validation\MaxLengthConstraint;
use PHPUnit\Framework\TestCase;

final class MaxLengthConstraintTest extends TestCase
{
    public function testWordUnderTheLimitIsValid(): void
    {
        self::assertSame([], (new MaxLengthConstraint())->validate(str_repeat('a', 31)));
    }

    public function testWordAtTheExactLimitIsValid(): void
    {
        self::assertSame([], (new MaxLengthConstraint())->validate(str_repeat('a', 32)));
    }

    public function testWordAboveTheLimitIsRejected(): void
    {
        $violations = (new MaxLengthConstraint())->validate(str_repeat('a', 33));

        self::assertNotEmpty($violations);
    }
}
