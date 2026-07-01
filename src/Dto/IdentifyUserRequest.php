<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class IdentifyUserRequest
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public ?int $id = null,
    ) {
    }
}
