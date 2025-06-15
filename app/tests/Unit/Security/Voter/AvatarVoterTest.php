<?php

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Avatar;
use App\Entity\User;
use App\Security\Voter\AvatarVoter; // The Voter under test
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface; // For type hinting in mocks
use ReflectionClass; // For testing protected methods

class AvatarVoterTest extends TestCase
{
    private AvatarVoter $avatarVoter;

    protected function setUp(): void
    {
        parent::setUp();
        // Instantiate the AvatarVoter without any dependencies if it doesn't have any in its constructor.
        // Based on the provided code, AvatarVoter has no constructor dependencies.
        $this->avatarVoter = new AvatarVoter();
    }

    /**
     * Data provider for the supports method test.
     * [attribute, subject, expectedResult]
     */
    public function provideSupportsData(): array
    {
        $avatar = new Avatar();
        return [
            // --- Supported cases ---
            // Correct attribute and subject
            [AvatarVoter::DELETE, $avatar, true],

            // --- Unsupported attribute cases ---
            // Wrong attribute
            ['EDIT', $avatar, false],
            ['VIEW', $avatar, false],
            ['SOME_OTHER_ATTRIBUTE', $avatar, false],

            // --- Unsupported subject cases ---
            // Correct attribute, wrong subject type
            [AvatarVoter::DELETE, new User(), false],
            [AvatarVoter::DELETE, new \stdClass(), false],
            [AvatarVoter::DELETE, null, false], // Null subject
        ];
    }

    /**
     * Test the supports method.
     * @dataProvider provideSupportsData
     */
    public function testSupports(string $attribute, mixed $subject, bool $expectedResult): void
    {
        // Use Reflection to call the protected supports method
        $reflection = new ReflectionClass(AvatarVoter::class);
        $method = $reflection->getMethod('supports');

        $this->assertEquals(
            $expectedResult,
            $method->invoke($this->avatarVoter, $attribute, $subject),
            sprintf('Expected supports(%s, %s) to be %s', $attribute, get_debug_type($subject), $expectedResult ? 'true' : 'false')
        );
    }

    /**
     * Data provider for the voteOnAttribute method test.
     * [loggedInUser, avatarOwner, expectedVote]
     *
     * Note: expectedVote is now boolean, reflecting the return type of voteOnAttribute.
     * true for ACCESS_GRANTED, false for ACCESS_DENIED.
     */
    public function provideVoteOnAttributeData(): array
    {
        // Create mock users for consistent IDs in test cases
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);

        // A mock that is UserInterface but not App\Entity\User
        $otherUserInterface = $this->createMock(UserInterface::class);
        // Note: For UserInterface mock, getId() might not exist. If the voter attempts to use it,
        // it should be mocked or the voter should handle UserInterface without getId().
        // In this case, the voter specifically checks 'instanceof User', so getId() won't be called on UserInterface directly by voter.
        // The error was in the sprintf itself, now fixed.


        return [
            // --- Access Granted cases ---
            // Logged-in user is the avatar owner
            'owner_can_delete_avatar' => [$user1, $user1, true], // Expected true from voteOnAttribute

            // --- Access Denied cases ---
            // Logged-in user is NOT the avatar owner
            'non_owner_cannot_delete_avatar' => [$user1, $user2, false], // Expected false from voteOnAttribute
            // Anonymous user (getUser() returns null, so !$user instanceof User will be true)
            'anonymous_cannot_delete_avatar' => [null, $user1, false], // Expected false from voteOnAttribute
            // User type not App\Entity\User (e.g., another UserInterface implementation)
            // Voter will return false because !$user instanceof User is true
            'other_user_interface_cannot_delete' => [$otherUserInterface, $user1, false], // Expected false from voteOnAttribute
        ];
    }

    /**
     * Test the voteOnAttribute method.
     * @dataProvider provideVoteOnAttributeData
     */
    public function testVoteOnAttribute(
        ?UserInterface $loggedInUser,
        User $avatarOwner, // This is the user entity that owns the avatar
        bool $expectedVote // FIX: Changed type to bool to match voteOnAttribute's return
    ): void {
        // Mock the Avatar subject
        $avatar = $this->createMock(Avatar::class);
        $avatar->method('getUser')->willReturn($avatarOwner);

        // Mock the TokenInterface
        $token = $this->createMock(TokenInterface::class);
        // If loggedInUser is null, token->getUser() returns null (anonymous)
        // Otherwise, it returns the mock loggedInUser
        $token->method('getUser')->willReturn($loggedInUser);

        // Use Reflection to call the protected voteOnAttribute method
        $reflection = new ReflectionClass(AvatarVoter::class);
        $method = $reflection->getMethod('voteOnAttribute');

        $this->assertEquals(
            $expectedVote, // Directly compare boolean result
            $method->invoke($this->avatarVoter, AvatarVoter::DELETE, $avatar, $token),
            // FIX: Conditional call to getId() in sprintf to prevent error
            sprintf(
                'Expected voteOnAttribute for logged-in user ID %s and avatar owner ID %s to be %s',
                $loggedInUser instanceof User ? $loggedInUser->getId() : 'N/A (not App\Entity\User)',
                $avatarOwner->getId(),
                $expectedVote ? 'GRANTED (true)' : 'DENIED (false)'
            )
        );
    }
}
