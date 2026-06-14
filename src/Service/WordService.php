<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Player;
use App\Entity\Word;
use App\Exception\WordAlreadyExistsException;
use App\Exception\WordValidationException;
use App\Repository\WordRepository;
use App\Validation\WordValidator;
use Doctrine\ORM\EntityManagerInterface;

final class WordService
{
    public const string UNKNOWN_STATUS = 'unknown';

    public function __construct(
        private readonly WordRepository $words,
        private readonly EntityManagerInterface $em,
        private readonly WordValidator $validator,
    ) {
    }

    /**
     * Statut réel du mot dans le dictionnaire, ou "unknown" s'il n'existe pas.
     */
    public function check(string $value): string
    {
        return $this->words->findOneByValue($value)?->getStatus()->value ?? self::UNKNOWN_STATUS;
    }

    /**
     * Crée un mot inconnu en attente de votes (auteur = joueur courant).
     *
     * @throws WordAlreadyExistsException si le mot existe déjà, quel que soit son statut
     * @throws WordValidationException    si le mot enfreint une contrainte de validation
     */
    public function propose(string $value, Player $author): Word
    {
        if (null !== $this->words->findOneByValue($value)) {
            throw new WordAlreadyExistsException($value);
        }

        $violations = $this->validator->validate($value);
        if ([] !== $violations) {
            throw new WordValidationException($violations);
        }

        $word = new Word($value, $author);
        $this->em->persist($word);
        $this->em->flush();

        return $word;
    }
}
