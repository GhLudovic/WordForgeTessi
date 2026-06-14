<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

final class WordValidationException extends DomainException
{
    /**
     * @param list<string> $violations
     */
    public function __construct(private readonly array $violations)
    {
        parent::__construct('The proposed word is invalid.');
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    /**
     * @return list<string>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
