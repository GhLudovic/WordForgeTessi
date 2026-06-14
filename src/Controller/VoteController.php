<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CastVoteRequest;
use App\Dto\VoteResponse;
use App\Entity\Player;
use App\Repository\WordRepository;
use App\Security\Voter\VoteEligibilityVoter;
use App\Service\VoteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class VoteController extends AbstractController
{
    #[Route('/api/words/{id}/votes', name: 'api_vote_cast', methods: ['POST'])]
    public function __invoke(
        int $id,
        #[MapRequestPayload] CastVoteRequest $request,
        #[CurrentUser] Player $player,
        WordRepository $words,
        VoteService $votes,
    ): JsonResponse {
        $word = $words->find($id);
        if (null === $word) {
            throw new NotFoundHttpException('Word not found.');
        }

        $this->denyAccessUnlessGranted(VoteEligibilityVoter::CAST, $word);

        $votes->castVote($player, $word, $request->value);

        return $this->json(new VoteResponse($word->getId(), $word->getStatus()->value), Response::HTTP_CREATED);
    }
}
