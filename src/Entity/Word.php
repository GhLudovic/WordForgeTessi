<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\WordStatus;
use App\Repository\WordRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WordRepository::class)]
#[ORM\Table(name: 'word')]
class Word
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $value;

    #[ORM\Column(enumType: WordStatus::class)]
    private WordStatus $status;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Player $author;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $value,
        Player $author,
        WordStatus $status = WordStatus::PENDING,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->value = $value;
        $this->author = $author;
        $this->status = $status;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getStatus(): WordStatus
    {
        return $this->status;
    }

    public function setStatus(WordStatus $status): void
    {
        $this->status = $status;
    }

    public function getAuthor(): Player
    {
        return $this->author;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
