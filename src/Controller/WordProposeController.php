<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ProposeWordRequest;
use App\Dto\WordResponse;
use App\Entity\Player;
use App\Service\WordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class WordProposeController extends AbstractController
{
    #[Route('/api/words', name: 'api_word_propose', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] ProposeWordRequest $request,
        #[CurrentUser] Player $player,
        WordService $words,
    ): JsonResponse {
        $word = $words->propose($request->value, $player);

        return $this->json(WordResponse::fromWord($word), Response::HTTP_CREATED);
    }
}
