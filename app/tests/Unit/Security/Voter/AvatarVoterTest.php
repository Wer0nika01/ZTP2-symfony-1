<?php

/**
 * Avatar voter Test.
 */

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Avatar;
use App\Entity\User;
use App\Security\Voter\AvatarVoter;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ReflectionClass;

/**
 * Class Avatar voter Test.
 */
class AvatarVoterTest extends TestCase
{
    private AvatarVoter $avatarVoter;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->avatarVoter = new AvatarVoter();
    }

    /**
     * Data provider for the supports method test.
     * [attribute, subject, expectedResult]
     *
     * @return array[]
     */
    public function provideSupportsData(): array
    {
        $avatar = new Avatar();

        return [
            [AvatarVoter::DELETE, $avatar, true],

            ['EDIT', $avatar, false],
            ['VIEW', $avatar, false],
            ['SOME_OTHER_ATTRIBUTE', $avatar, false],

            [AvatarVoter::DELETE, new User(), false],
            [AvatarVoter::DELETE, new stdClass(), false],
            [AvatarVoter::DELETE, null, false],
        ];
    }

    /**
     * Test the supports' method.
     *
     * @dataProvider provideSupportsData
     *
     * @param string $attribute
     * @param mixed  $subject
     * @param bool   $expectedResult
     *
     * @throws ReflectionException
     */
    public function testSupports(string $attribute, mixed $subject, bool $expectedResult): void
    {
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
     * @return array[]
     */
    public function provideVoteOnAttributeData(): array
    {
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);

        $otherUserInterface = $this->createMock(UserInterface::class);

        return [

            'owner_can_delete_avatar' => [$user1, $user1, true],

            'non_owner_cannot_delete_avatar' => [$user1, $user2, false],
            'anonymous_cannot_delete_avatar' => [null, $user1, false],

            'other_user_interface_cannot_delete' => [$otherUserInterface, $user1, false],
        ];
    }

    /**
     * Test the voteOnAttribute method.
     *
     * @dataProvider provideVoteOnAttributeData
     *
     * @param UserInterface|null $loggedInUser
     * @param User               $avatarOwner
     * @param bool               $expectedVote
     *
     * @throws ReflectionException
     */
    public function testVoteOnAttribute(?UserInterface $loggedInUser, User $avatarOwner, bool $expectedVote): void
    {
        $avatar = $this->createMock(Avatar::class);
        $avatar->method('getUser')->willReturn($avatarOwner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($loggedInUser);

        $reflection = new ReflectionClass(AvatarVoter::class);
        $method = $reflection->getMethod('voteOnAttribute');

        $this->assertEquals(
            $expectedVote,
            $method->invoke($this->avatarVoter, AvatarVoter::DELETE, $avatar, $token),
            sprintf(
                'Expected voteOnAttribute for logged-in user ID %s and avatar owner ID %s to be %s',
                $loggedInUser instanceof User ? $loggedInUser->getId() : 'N/A (not App\Entity\User)',
                $avatarOwner->getId(),
                $expectedVote ? 'GRANTED (true)' : 'DENIED (false)'
            )
        );
    }
}
