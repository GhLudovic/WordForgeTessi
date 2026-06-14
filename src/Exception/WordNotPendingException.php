<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

final class WordNotPendingException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This word is no longer open to votes.');
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_CONFLICT;
    }
}
