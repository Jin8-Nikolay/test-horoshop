<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreatedUserResponse;
use App\Dto\CreateUserRequest;
use App\Dto\IdentifyUserRequest;
use App\Dto\UpdatedUserResponse;
use App\Dto\UpdateUserRequest;
use App\Dto\UserResponse;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Role;
use App\Security\Voter\UserVoter;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/%app.api_version%/api/users', name: 'user_')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserService $service,
    ) {
    }

    #[Route('', name: 'get', methods: [Request::METHOD_GET])]
    public function get(#[MapQueryString] IdentifyUserRequest $query): JsonResponse
    {
        $user = $this->getAccessibleUser($query->id);
        $this->denyAccessUnlessGranted(UserVoter::VIEW, $user);

        return $this->json(UserResponse::fromEntity($user));
    }

    #[Route('', name: 'create', methods: [Request::METHOD_POST])]
    public function create(#[MapRequestPayload] CreateUserRequest $payload): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::CREATE);
        $apiToken = $this->service->create($payload);

        return $this->json(CreatedUserResponse::fromIssuedToken($apiToken), Response::HTTP_CREATED);
    }

    #[Route('', name: 'update', methods: [Request::METHOD_PUT])]
    public function update(#[MapRequestPayload] UpdateUserRequest $payload): JsonResponse
    {
        $user = $this->getAccessibleUser($payload->id);
        $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);
        $this->service->update($user, $payload);

        return $this->json(UpdatedUserResponse::fromEntity($user));
    }

    #[Route('', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(#[MapQueryString] IdentifyUserRequest $query): Response
    {
        $user = $this->getAccessibleUser($query->id);
        $this->denyAccessUnlessGranted(UserVoter::DELETE, $user);
        $this->service->delete($user);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private function getAccessibleUser(int $id): User
    {
        $user = $this->userRepository->find($id);
        if ($user) {
            return $user;
        }

        // A non-root caller must not learn whether an id exists, so a missing
        // record is 403 for them and only a real 404 for root.
        if (!$this->isGranted(Role::ROOT)) {
            throw $this->createAccessDeniedException();
        }

        throw $this->createNotFoundException('User not found.');
    }
}
