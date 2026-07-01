<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class LoginAlreadyTakenException extends RuntimeException
{
    public function __construct(string $login)
    {
        parent::__construct(sprintf('Login "%s" is already taken.', $login));
    }
}
