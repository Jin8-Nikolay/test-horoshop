<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use App\Security\RootUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class UserVoter extends Voter
{
    public const string VIEW = 'USER_VIEW';
    public const string EDIT = 'USER_EDIT';
    public const string DELETE = 'USER_DELETE';
    public const string CREATE = 'USER_CREATE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::CREATE) {
            return !$subject;
        }

        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $securityToken): bool
    {
        $currentUser = $securityToken->getUser();

        if ($currentUser instanceof RootUser) {
            return true;
        }

        if (!$currentUser instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::CREATE => true,
            self::VIEW, self::EDIT => $subject instanceof User && $subject->getId() === $currentUser->getId(),
            self::DELETE => false,
            default => false,
        };
    }
}
