<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\VoteValue;
use App\Repository\VoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoteRepository::class)]
#[ORM\Table(name: 'vote')]
#[ORM\UniqueConstraint(name: 'uniq_vote_player_word', columns: ['player_id', 'word_id'])]
class Vote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Word $word;

    #[ORM\Column(enumType: VoteValue::class)]
    private VoteValue $value;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Player $player,
        Word $word,
        VoteValue $value,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->player = $player;
        $this->word = $word;
        $this->value = $value;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getWord(): Word
    {
        return $this->word;
    }

    public function getValue(): VoteValue
    {
        return $this->value;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
