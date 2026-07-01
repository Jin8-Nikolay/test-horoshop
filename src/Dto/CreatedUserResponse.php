<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\ApiToken;

final class CreatedUserResponse
{
    public function __construct(
        public int $id,
        public string $login,
        public string $phone,
        public string $apiToken,
    ) {
    }

    public static function fromIssuedToken(ApiToken $apiToken): self
    {
        $user = $apiToken->getUser();

        return new self(
            id: (int)$user->getId(),
            login: $user->getLogin(),
            phone: $user->getPhone(),
            apiToken: $apiToken->getToken(),
        );
    }
}
