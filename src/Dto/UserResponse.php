<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\User;

final class UserResponse
{
    public function __construct(
        public string $login,
        public string $phone,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            login: $user->getLogin(),
            phone: $user->getPhone(),
        );
    }
}
