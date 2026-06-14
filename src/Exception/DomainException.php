<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Erreur métier traduisible en réponse HTTP par l'ApiExceptionSubscriber.
 */
abstract class DomainException extends \RuntimeException
{
    abstract public function getStatusCode(): int;

    /**
     * @return list<string>
     */
    public function getViolations(): array
    {
        return [];
    }
}
