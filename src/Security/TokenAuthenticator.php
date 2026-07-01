<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\ApiTokenRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class TokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly ApiTokenRepository $apiTokenRepository,
        #[Autowire('%env(ROOT_API_TOKEN)%')]
        private readonly string $rootToken,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authorizationHeader = (string)$request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(\S+)$/', $authorizationHeader, $matches)) {
            throw new CustomUserMessageAuthenticationException('Expected an "Authorization: Bearer <token>" header.');
        }

        $bearerToken = $matches[1];
        if ($this->rootToken && hash_equals($this->rootToken, $bearerToken)) {
            return new SelfValidatingPassport(new UserBadge(RootUser::IDENTIFIER, static fn (): UserInterface => new RootUser()));
        }

        return new SelfValidatingPassport(new UserBadge($bearerToken, function (string $bearerToken): UserInterface {
            $apiToken = $this->apiTokenRepository->findOneByToken($bearerToken);
            if (!$apiToken) {
                throw new UserNotFoundException();
            }

            return $apiToken->getUser();
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $securityToken, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'Authentication failed.'], Response::HTTP_UNAUTHORIZED);
    }
}
