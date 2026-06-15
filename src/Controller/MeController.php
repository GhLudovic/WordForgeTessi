<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\PlayerResponse;
use App\Entity\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class MeController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] Player $player): JsonResponse
    {
        return $this->json(PlayerResponse::fromPlayer($player));
    }
}
