<?php

/**
 * User voter Test.
 */

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class User voter Test.
 */
class UserVoterTest extends TestCase
{
    private MockObject|Security $security;
    private MockObject|UserRepository $userRepository;
    private UserVoter $userVoter;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->security = $this->createMock(Security::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userVoter = new UserVoter($this->security, $this->userRepository);
    }

    /**
     * Provides data for supports method tests.
     * [attribute, subject, expectedResult].
     *
     * @return array[]
     */
    public function provideSupportsData(): array
    {
        $user = new User();

        return [
            [UserVoter::VIEW, $user, true],
            [UserVoter::EDIT, $user, true],
            [UserVoter::DELETE, $user, true],
            [UserVoter::CAN_CHANGE_ROLES, $user, true],

            [UserVoter::BLOCK, $user, true],

            ['unsupported_attribute', $user, false],

            [UserVoter::VIEW, new \stdClass(), false],
            [UserVoter::VIEW, null, false],
        ];
    }

    /**
     * Test supports.
     *
     * @param string $attribute      Attribute
     * @param mixed  $subject        Subject
     * @param bool   $expectedResult Expected result
     *
     * @dataProvider provideSupportsData
     *
     * @throws \ReflectionException
     */
    public function testSupports(string $attribute, mixed $subject, bool $expectedResult): void
    {
        $reflection = new \ReflectionClass(UserVoter::class);
        $method = $reflection->getMethod('supports');

        $this->assertEquals($expectedResult, $method->invoke($this->userVoter, $attribute, $subject));
    }

    /**
     * Provides data for voteOnAttribute method tests.
     * [loggedInUserRole, targetUserIsLoggedInUser, attribute, expectedVote, initialAdminCountForCanChangeRoles].
     *
     * @return array[]
     */
    public function provideVoteOnAttributeData(): array
    {
        return [
            'admin_can_view_any' => [UserRole::ROLE_ADMIN->value, false, UserVoter::VIEW, VoterInterface::ACCESS_DENIED, null],
            'admin_can_edit_any' => [UserRole::ROLE_ADMIN->value, false, UserVoter::EDIT, VoterInterface::ACCESS_DENIED, null],
            'admin_can_delete_any' => [UserRole::ROLE_ADMIN->value, false, UserVoter::DELETE, VoterInterface::ACCESS_DENIED, null],
            'admin_can_block_any' => [UserRole::ROLE_ADMIN->value, false, UserVoter::BLOCK, VoterInterface::ACCESS_GRANTED, null],

            'admin_can_change_roles_other_user' => [UserRole::ROLE_ADMIN->value, false, UserVoter::CAN_CHANGE_ROLES, VoterInterface::ACCESS_GRANTED, null],
            'admin_can_change_roles_self_many_admins' => [UserRole::ROLE_ADMIN->value, true, UserVoter::CAN_CHANGE_ROLES, VoterInterface::ACCESS_GRANTED, 2],
            'admin_can_change_roles_self_last_admin' => [UserRole::ROLE_ADMIN->value, true, UserVoter::CAN_CHANGE_ROLES, VoterInterface::ACCESS_DENIED, 1],

            'admin_can_view_self' => [UserRole::ROLE_ADMIN->value, true, UserVoter::VIEW, VoterInterface::ACCESS_GRANTED, null],
            'admin_can_edit_self' => [UserRole::ROLE_ADMIN->value, true, UserVoter::EDIT, VoterInterface::ACCESS_GRANTED, null],
            'admin_can_delete_self_denied_by_voter' => [UserRole::ROLE_ADMIN->value, true, UserVoter::DELETE, VoterInterface::ACCESS_DENIED, null],
            'admin_can_block_self' => [UserRole::ROLE_ADMIN->value, true, UserVoter::BLOCK, VoterInterface::ACCESS_DENIED, null],

            'user_can_view_self' => [UserRole::ROLE_USER->value, true, UserVoter::VIEW, VoterInterface::ACCESS_GRANTED, null],
            'user_can_edit_self' => [UserRole::ROLE_USER->value, true, UserVoter::EDIT, VoterInterface::ACCESS_GRANTED, null],
            'user_can_delete_self_denied' => [UserRole::ROLE_USER->value, true, UserVoter::DELETE, VoterInterface::ACCESS_DENIED, null],
            'user_cannot_block_self' => [UserRole::ROLE_USER->value, true, UserVoter::BLOCK, VoterInterface::ACCESS_DENIED, null],

            'user_cannot_view_other' => [UserRole::ROLE_USER->value, false, UserVoter::VIEW, VoterInterface::ACCESS_DENIED, null],
            'user_cannot_edit_other' => [UserRole::ROLE_USER->value, false, UserVoter::EDIT, VoterInterface::ACCESS_DENIED, null],
            'user_cannot_delete_other' => [UserRole::ROLE_USER->value, false, UserVoter::DELETE, VoterInterface::ACCESS_DENIED, null],
            'user_cannot_block_other' => [UserRole::ROLE_USER->value, false, UserVoter::BLOCK, VoterInterface::ACCESS_DENIED, null],
            'user_cannot_change_roles' => [UserRole::ROLE_USER->value, false, UserVoter::CAN_CHANGE_ROLES, VoterInterface::ACCESS_DENIED, null],

            'anonymous_cannot_view' => [null, false, UserVoter::VIEW, VoterInterface::ACCESS_DENIED, null],
            'anonymous_cannot_edit' => [null, false, UserVoter::EDIT, VoterInterface::ACCESS_DENIED, null],
            'anonymous_cannot_delete' => [null, false, UserVoter::DELETE, VoterInterface::ACCESS_DENIED, null],
            'anonymous_cannot_block' => [null, false, UserVoter::BLOCK, VoterInterface::ACCESS_DENIED, null],
            'anonymous_cannot_change_roles' => [null, false, UserVoter::CAN_CHANGE_ROLES, VoterInterface::ACCESS_DENIED, null],
        ];
    }

    /**
     * Test vote on attribute.
     *
     * @param string|null $loggedInUserRole                   Logged-in user role
     * @param bool        $targetUserIsLoggedInUser           True of false
     * @param string      $attribute                          Attribute
     * @param int         $expectedVote                       Expected vote
     * @param int|null    $initialAdminCountForCanChangeRoles How many admins left
     *
     * @dataProvider provideVoteOnAttributeData
     */
    public function testVoteOnAttribute(?string $loggedInUserRole, bool $targetUserIsLoggedInUser, string $attribute, int $expectedVote, ?int $initialAdminCountForCanChangeRoles): void
    {
        $loggedInUser = $this->createMock(User::class);
        $loggedInUser->method('getId')->willReturn(1);
        $loggedInUser->method('getRoles')->willReturn($loggedInUserRole ? [$loggedInUserRole] : []);

        $userToOperateOn = $this->createMock(User::class);
        $userToOperateOn->method('getId')->willReturn($targetUserIsLoggedInUser ? 1 : 2);
        $userToOperateOn->method('getRoles')->willReturn([UserRole::ROLE_USER->value]);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null === $loggedInUserRole ? null : $loggedInUser);

        $this->security->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function (string $role, $subject = null) use ($loggedInUserRole, $loggedInUser) {
                if (UserRole::ROLE_ADMIN->value === $role) {
                    return $loggedInUserRole === UserRole::ROLE_ADMIN->value;
                }

                if (null !== $loggedInUserRole && in_array($role, $loggedInUser->getRoles())) {
                    if ($subject instanceof UserInterface && $subject->getId() !== $loggedInUser->getId()) {
                        return false;
                    }

                    return true;
                }

                return false;
            });

        if (null !== $initialAdminCountForCanChangeRoles) {
            $this->userRepository->expects($this->once())
                ->method('countAdmins')
                ->willReturn($initialAdminCountForCanChangeRoles);
        } else {
            $this->userRepository->expects($this->never())
                ->method('countAdmins');
        }

        $this->assertEquals($expectedVote, $this->userVoter->vote($token, $userToOperateOn, [$attribute]));
    }
}
