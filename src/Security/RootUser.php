<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class RootUser implements UserInterface
{
    public const string IDENTIFIER = 'root';

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return [Role::ROOT];
    }

    public function getUserIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function eraseCredentials(): void
    {
    }
}
