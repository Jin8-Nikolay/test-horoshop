<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\User;

final class UpdatedUserResponse
{
    public function __construct(
        public int $id,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(id: (int)$user->getId());
    }
}
