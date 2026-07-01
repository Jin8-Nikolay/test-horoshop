<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\User;
use App\Security\RootUser;
use App\Security\Voter\UserVoter;
use Codeception\Test\Unit;
use ReflectionProperty;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class UserVoterTest extends Unit
{
    private UserVoter $voter;

    protected function _before(): void
    {
        $this->voter = new UserVoter();
    }

    public function testRootMayDoEverything(): void
    {
        $securityToken = $this->tokenFor(new RootUser());
        $target = $this->userWithId(42);

        $this->assertGranted($this->voter->vote($securityToken, $target, [UserVoter::VIEW]));
        $this->assertGranted($this->voter->vote($securityToken, $target, [UserVoter::EDIT]));
        $this->assertGranted($this->voter->vote($securityToken, $target, [UserVoter::DELETE]));
        $this->assertGranted($this->voter->vote($securityToken, null, [UserVoter::CREATE]));
    }

    public function testUserMayViewAndEditOnlyItself(): void
    {
        $self = $this->userWithId(7);
        $other = $this->userWithId(8);
        $securityToken = $this->tokenFor($self);

        $this->assertGranted($this->voter->vote($securityToken, $self, [UserVoter::VIEW]));
        $this->assertGranted($this->voter->vote($securityToken, $self, [UserVoter::EDIT]));
        $this->assertDenied($this->voter->vote($securityToken, $other, [UserVoter::VIEW]));
        $this->assertDenied($this->voter->vote($securityToken, $other, [UserVoter::EDIT]));
    }

    public function testUserMayCreateButNeverDelete(): void
    {
        $securityToken = $this->tokenFor($this->userWithId(7));

        $this->assertGranted($this->voter->vote($securityToken, null, [UserVoter::CREATE]));
        $this->assertDenied($this->voter->vote($securityToken, $this->userWithId(7), [UserVoter::DELETE]));
    }

    private function tokenFor(object $user): TokenInterface
    {
        $securityToken = $this->createMock(TokenInterface::class);
        $securityToken->method('getUser')->willReturn($user);

        return $securityToken;
    }

    private function userWithId(int $id): User
    {
        $user = new User();
        $reflection = new ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }

    private function assertGranted(int $result): void
    {
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    private function assertDenied(int $result): void
    {
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }
}
