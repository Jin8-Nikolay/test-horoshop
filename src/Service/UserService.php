<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateUserRequest;
use App\Dto\UpdateUserRequest;
use App\Entity\ApiToken;
use App\Entity\User;
use App\Exception\LoginAlreadyTakenException;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserService
{
    private const int TOKEN_BYTES = 24;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function create(CreateUserRequest $request): ApiToken
    {
        $this->assertLoginIsFree($request->login);

        $user = new User();
        $user->setLogin($request->login);
        $user->setPhone($request->phone);
        $user->setPassword($this->hasher->hashPassword($user, $request->pass));

        $apiToken = new ApiToken();
        $apiToken->setUser($user);
        $apiToken->setToken(bin2hex(random_bytes(self::TOKEN_BYTES)));
        $apiToken->setCreatedAt(new DateTimeImmutable());

        $this->em->persist($user);
        $this->em->persist($apiToken);
        $this->em->flush();

        return $apiToken;
    }

    public function update(User $user, UpdateUserRequest $request): User
    {
        $this->assertLoginIsFree($request->login, $user);

        $user->setLogin($request->login);
        $user->setPhone($request->phone);
        $user->setPassword($this->hasher->hashPassword($user, $request->pass));

        $this->em->flush();

        return $user;
    }

    public function delete(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }

    private function assertLoginIsFree(string $login, ?User $currentUser = null): void
    {
        $existingUser = $this->userRepository->findOneByLogin($login);

        if ($existingUser && $existingUser->getId() !== $currentUser?->getId()) {
            throw new LoginAlreadyTakenException($login);
        }
    }
}
