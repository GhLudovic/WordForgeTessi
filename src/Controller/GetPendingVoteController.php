<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\PendingVoteResponse;
use App\Service\VoteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetPendingVoteController extends AbstractController
{
    #[Route('/api/votes/pending', name: 'api_vote_pending', methods: ['GET'])]
    public function __invoke(VoteService $votes): Response
    {
        $word = $votes->findVotableWord();

        if (null === $word) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        return $this->json(new PendingVoteResponse($word->getId(), $word->getValue()));
    }
}
