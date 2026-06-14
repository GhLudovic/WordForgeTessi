<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\PlayerRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * @see https://symfony.com/doc/current/security/custom_authenticator.html
 */

/**
 * Authentifie chaque requête via "Authorization: Bearer <token>" en résolvant
 * le Player propriétaire du token. Toute requête sans token valide reçoit un 401 JSON.
 */
class TokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private const string PREFIX = 'Bearer ';

    public function __construct(private readonly PlayerRepository $players)
    {
    }

    public function supports(Request $request): bool
    {
        return str_starts_with((string) $request->headers->get('Authorization'), self::PREFIX);
    }

    public function authenticate(Request $request): Passport
    {
        $token = substr((string) $request->headers->get('Authorization'), \strlen(self::PREFIX));

        if ('' === $token) {
            throw new CustomUserMessageAuthenticationException('Missing bearer token.');
        }

        return new SelfValidatingPassport(
            new UserBadge($token, fn (string $identifier) => $this->players->findOneBy(['token' => $identifier])),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
    }
}
