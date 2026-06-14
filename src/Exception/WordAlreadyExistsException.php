<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

final class WordAlreadyExistsException extends DomainException
{
    public function __construct(string $value)
    {
        parent::__construct(\sprintf('Word "%s" already exists.', $value));
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_CONFLICT;
    }
}
