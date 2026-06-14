<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\WordStatusResponse;
use App\Service\WordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class WordCheckController extends AbstractController
{
    #[Route('/api/words/{value}', name: 'api_word_check', methods: ['GET'])]
    public function __invoke(string $value, WordService $words): JsonResponse
    {
        return $this->json(new WordStatusResponse($value, $words->check($value)));
    }
}
