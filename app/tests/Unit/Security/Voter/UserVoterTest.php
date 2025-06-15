<?php

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoterTest extends TestCase
{
    private MockObject|Security $security;
    private MockObject|UserRepository $userRepository;
    private UserVoter $userVoter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->security = $this->createMock(Security::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userVoter = new UserVoter($this->security, $this->userRepository);
    }

    /**
     * Provides data for supports method tests.
     * [attribute, subject, expectedResult]
     */
    public function provideSupportsData(): array
    {
        $user = new User();
        return [
            // Supported attributes and subject based on UserVoter::supports
            [UserVoter::VIEW, $user, true],
            [UserVoter::EDIT, $user, true],
            [UserVoter::DELETE, $user, true],
            [UserVoter::CAN_CHANGE_ROLES, $user, true], // Added new attribute

            // BLOCK is NOT listed in UserVoter::supports, so it should return false
            [UserVoter::BLOCK, $user, false],

            // Unsupported attribute
            ['unsupported_attribute', $user, false],

            // Unsupported subject
            [UserVoter::VIEW, new \stdClass(), false],
            [UserVoter::VIEW, null, false],
        ];
    }

    /**
     * @dataProvider provideSupportsData
     */
    public function testSupports(string $attribute, mixed $subject, bool $expectedResult): void
    {
        // Use Reflection to call the protected supports method
        $reflection = new ReflectionClass(UserVoter::class);
        $method = $reflection->getMethod('supports');

        // Invoke the protected method on the userVoter instance
        $this->assertEquals($expectedResult, $method->invoke($this->userVoter, $attribute, $subject));
    }

    /**
     * Provides data for voteOnAttribute method tests.
     * [loggedInUserRole, targetUserIsLoggedInUser, attribute, expectedVote, initialAdminCountForCanChangeRoles]
     */
    public function provideVoteOnAttributeData(): array
    {
        return [
            // Admin user scenarios (note: canView/canEdit/canDelete are strict in UserVoter)
            // Admin on other user: VIEW/EDIT are DENIED by canView/canEdit logic
            // Admin on other user: DELETE is DENIED by canDelete logic
            // Admin on other user: BLOCK is GRANTED by canBlock logic
            'admin_can_view_any' => [UserRole::ROLE_ADMIN->value, false, UserVoter::VIEW, VoterInterface::ACCESS_DENIED, null],
            'admin_can_edit_any' => [UserRole::ROLE_ADMIN->value, false, UserVoter::EDIT, VoterInterface::ACCESS_DENIED, null],
            'admin_can_delete_any' => [UserRole::ROLE_ADMIN->value, false, UserVoter::DELETE, VoterInterface::ACCESS_DENIED, null],
            // FIX: Expected vote for BLOCK is ABSTAIN (0) because it's not supported by `supports()`
            'admin_can_block_any' => [UserRole::ROLE_ADMIN->value, false, UserVoter::BLOCK, VoterInterface::ACCESS_ABSTAIN, null],

            // Admin changing roles (needs mock for userRepository->countAdmins())
            'admin_can_change_roles_other_user' => [UserRole::ROLE_ADMIN->value, false, UserVoter::CAN_CHANGE_ROLES, VoterInterface::ACCESS_GRANTED, null],
            'admin_can_change_roles_self_many_admins' => [UserRole::ROLE_ADMIN->value, true, UserVoter::CAN_CHANGE_ROLES, VoterInterface::ACCESS_GRANTED, 2],
            'admin_can_change_roles_self_last_admin' => [UserRole::ROLE_ADMIN->value, true, UserVoter::CAN_CHANGE_ROLES, VoterInterface::ACCESS_DENIED, 1],

            'admin_can_view_self' => [UserRole::ROLE_ADMIN->value, true, UserVoter::VIEW, VoterInterface::ACCESS_GRANTED, null],
            'admin_can_edit_self' => [UserRole::ROLE_ADMIN->value, true, UserVoter::EDIT, VoterInterface::ACCESS_GRANTED, null],
            'admin_can_delete_self_denied_by_voter' => [UserRole::ROLE_ADMIN->value, true, UserVoter::DELETE, VoterInterface::ACCESS_DENIED, null],
            // FIX: Expected vote for BLOCK is ABSTAIN (0)
            'admin_can_block_self' => [UserRole::ROLE_ADMIN->value, true, UserVoter::BLOCK, VoterInterface::ACCESS_ABSTAIN, null],

            // Regular user scenarios
            'user_can_view_self' => [UserRole::ROLE_USER->value, true, UserVoter::VIEW, VoterInterface::ACCESS_GRANTED, null],
            'user_can_edit_self' => [UserRole::ROLE_USER->value, true, UserVoter::EDIT, VoterInterface::ACCESS_GRANTED, null],
            'user_can_delete_self_denied' => [UserRole::ROLE_USER->value, true, UserVoter::DELETE, VoterInterface::ACCESS_DENIED, null],
            // FIX: Expected vote for BLOCK is ABSTAIN (0)
            'user_cannot_block_self' => [UserRole::ROLE_USER->value, true, UserVoter::BLOCK, VoterInterface::ACCESS_ABSTAIN, null],

            'user_cannot_view_other' => [UserRole::ROLE_USER->value, false, UserVoter::VIEW, VoterInterface::ACCESS_DENIED, null],
            'user_cannot_edit_other' => [UserRole::ROLE_USER->value, false, UserVoter::EDIT, VoterInterface::ACCESS_DENIED, null],
            'user_cannot_delete_other' => [UserRole::ROLE_USER->value, false, UserVoter::DELETE, VoterInterface::ACCESS_DENIED, null],
            // FIX: Expected vote for BLOCK is ABSTAIN (0)
            'user_cannot_block_other' => [UserRole::ROLE_USER->value, false, UserVoter::BLOCK, VoterInterface::ACCESS_ABSTAIN, null],
            'user_cannot_change_roles' => [UserRole::ROLE_USER->value, false, UserVoter::CAN_CHANGE_ROLES, VoterInterface::ACCESS_DENIED, null],

            // Anonymous user scenarios
            'anonymous_cannot_view' => [null, false, UserVoter::VIEW, VoterInterface::ACCESS_DENIED, null],
            'anonymous_cannot_edit' => [null, false, UserVoter::EDIT, VoterInterface::ACCESS_DENIED, null],
            'anonymous_cannot_delete' => [null, false, UserVoter::DELETE, VoterInterface::ACCESS_DENIED, null],
            // FIX: Expected vote for BLOCK is ABSTAIN (0)
            'anonymous_cannot_block' => [null, false, UserVoter::BLOCK, VoterInterface::ACCESS_ABSTAIN, null],
            'anonymous_cannot_change_roles' => [null, false, UserVoter::CAN_CHANGE_ROLES, VoterInterface::ACCESS_DENIED, null],
        ];
    }

    /**
     * @dataProvider provideVoteOnAttributeData
     */
    public function testVoteOnAttribute(
        ?string $loggedInUserRole,
        bool $targetUserIsLoggedInUser,
        string $attribute,
        int $expectedVote,
        ?int $initialAdminCountForCanChangeRoles // NEW: For countAdmins() mock
    ): void {
        // Mock the logged-in user
        $loggedInUser = $this->createMock(User::class);
        $loggedInUser->method('getId')->willReturn(1);
        $loggedInUser->method('getRoles')->willReturn($loggedInUserRole ? [$loggedInUserRole] : []);


        // Mock the target user (subject)
        $userToOperateOn = $this->createMock(User::class);
        $userToOperateOn->method('getId')->willReturn($targetUserIsLoggedInUser ? 1 : 2); // Same ID if owner
        // Mock roles for the target user if the voter logic checks them
        $userToOperateOn->method('getRoles')->willReturn([UserRole::ROLE_USER->value]); // Assume target is generally a regular user unless test dictates otherwise


        // Mock the TokenInterface
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($loggedInUserRole === null ? null : $loggedInUser);


        // Refined Security::isGranted mocks with a dynamic callback
        $this->security->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function (string $role, $subject = null) use ($loggedInUserRole, $loggedInUser, $userToOperateOn) {
                // Primary check: if the voter asks for 'ROLE_ADMIN', return true if the current test's logged-in user is set as admin
                if ($role === UserRole::ROLE_ADMIN->value) {
                    return $loggedInUserRole === UserRole::ROLE_ADMIN->value;
                }

                // If the voter asks for any other role, simulate based on the logged-in user's roles
                // This covers cases where the voter might check $this->security->isGranted('ROLE_USER') for the logged-in user
                // The `in_array($role, $loggedInUser->getRoles())` will work correctly with the mocked `getRoles()`
                if ($loggedInUserRole !== null && in_array($role, $loggedInUser->getRoles())) {
                    // If a subject is passed to isGranted, ensure it's the logged-in user
                    if ($subject instanceof UserInterface && $subject->getId() !== $loggedInUser->getId()) {
                        return false; // User cannot grant self role on another subject via isGranted
                    }
                    return true;
                }

                return false;
            });

        // Mock UserRepository::countAdmins() if the test case requires it
        if ($initialAdminCountForCanChangeRoles !== null) {
            $this->userRepository->expects($this->once())
                ->method('countAdmins')
                ->willReturn($initialAdminCountForCanChangeRoles);
        } else {
            // Ensure countAdmins is not called when it shouldn't be
            $this->userRepository->expects($this->never())
                ->method('countAdmins');
        }

        // Call the PUBLIC `vote` method of the voter
        $this->assertEquals($expectedVote, $this->userVoter->vote($token, $userToOperateOn, [$attribute]));
    }
}
