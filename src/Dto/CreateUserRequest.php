<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 8)]
        public string $login = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 8)]
        public string $phone = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 8)]
        public string $pass = '',
    ) {
    }
}
