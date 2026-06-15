<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Player;

final readonly class PlayerResponse
{
    public function __construct(
        public ?int $id,
        public string $username,
    ) {
    }

    public static function fromPlayer(Player $player): self
    {
        return new self($player->getId(), $player->getUsername());
    }
}
