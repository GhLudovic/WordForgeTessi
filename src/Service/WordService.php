<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\WordRepository;

final class WordService
{
    public const string UNKNOWN_STATUS = 'unknown';

    public function __construct(private readonly WordRepository $words)
    {
    }

    /**
     * Statut réel du mot dans le dictionnaire, ou "unknown" s'il n'existe pas.
     */
    public function check(string $value): string
    {
        return $this->words->findOneByValue($value)?->getStatus()->value ?? self::UNKNOWN_STATUS;
    }
}
