<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\DomainException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Traduit toute exception levée sous /api en réponse JSON cohérente.
 */
final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 64]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $throwable = $event->getThrowable();

        if ($throwable instanceof AuthenticationException || $throwable instanceof AccessDeniedException) {
            return;
        }

        [$status, $payload] = $this->toPayload($throwable);

        $event->setResponse(new JsonResponse($payload, $status));
    }

    /**
     * @return array{int, array<string, mixed>}
     */
    private function toPayload(\Throwable $throwable): array
    {
        if ($throwable instanceof DomainException) {
            $payload = ['error' => $throwable->getMessage()];
            if ([] !== $throwable->getViolations()) {
                $payload['violations'] = $throwable->getViolations();
            }

            return [$throwable->getStatusCode(), $payload];
        }

        // Échec de validation d'un DTO mappé via #[MapRequestPayload].
        $previous = $throwable->getPrevious();
        if ($throwable instanceof HttpExceptionInterface && $previous instanceof ValidationFailedException) {
            $violations = [];
            foreach ($previous->getViolations() as $violation) {
                $violations[] = (string) $violation->getMessage();
            }

            return [$throwable->getStatusCode(), ['error' => 'Validation failed.', 'violations' => $violations]];
        }

        if ($throwable instanceof HttpExceptionInterface) {
            return [$throwable->getStatusCode(), ['error' => $throwable->getMessage()]];
        }

        return [Response::HTTP_INTERNAL_SERVER_ERROR, ['error' => 'Internal server error.']];
    }
}
