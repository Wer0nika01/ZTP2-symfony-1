<?php

/**
 * Event voter Test.
 */

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Event;
use App\Entity\User;
use App\Security\Voter\EventVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class Event voter Test.
 */
class EventVoterTest extends TestCase
{
    private EventVoter $eventVoter;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->eventVoter = new EventVoter();
    }

    /**
     * Data provider for the supports method test.
     * [attribute, subject, expectedResult].
     *
     * @return array[]
     */
    public function provideSupportsData(): array
    {
        $event = new Event();
        $user = new User();
        $stdClass = new \stdClass();

        return [
            'supports_delete_event' => [EventVoter::DELETE, $event, true],
            'supports_edit_event' => [EventVoter::EDIT, $event, true],
            'supports_view_event' => [EventVoter::VIEW, $event, true],

            'does_not_support_unknown_attribute' => ['UNKNOWN_ATTRIBUTE', $event, false],
            'does_not_support_create_attribute' => ['EVENT_CREATE', $event, false], // Example of unsupported attribute

            'does_not_support_user_subject' => [EventVoter::VIEW, $user, false],
            'does_not_support_stdclass_subject' => [EventVoter::EDIT, $stdClass, false],
            'does_not_support_null_subject' => [EventVoter::DELETE, null, false],
            'does_not_support_invalid_subject_and_attribute' => ['SOME_ACTION', $user, false],
        ];
    }

    /**
     * Test the protected supports' method.
     *
     * @param string $attribute      Attribute
     * @param mixed  $subject        Subject
     * @param bool   $expectedResult Expected result
     *
     * @throws \ReflectionException
     *
     * @dataProvider provideSupportsData
     */
    public function testSupports(string $attribute, mixed $subject, bool $expectedResult): void
    {
        $reflection = new \ReflectionClass(EventVoter::class);
        $method = $reflection->getMethod('supports');

        $this->assertEquals(
            $expectedResult,
            $method->invoke($this->eventVoter, $attribute, $subject),
            sprintf('Expected supports("%s", %s) to be %s', $attribute, get_debug_type($subject), $expectedResult ? 'true' : 'false')
        );
    }

    /**
     * Data provider for the voteOnAttribute method test.
     * [loggedInUser, eventAuthorId, attribute, expectedVoteResult].
     *
     * @return array[]
     */
    public function provideVoteOnAttributeData(): array
    {
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);

        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);

        $genericUserInterface = $this->createMock(User::class);
        $genericUserInterface->method('getId')->willReturn(999);

        return [
            'owner_can_view' => [$user1, 1, EventVoter::VIEW, true],
            'owner_can_edit' => [$user1, 1, EventVoter::EDIT, true],
            'owner_can_delete' => [$user1, 1, EventVoter::DELETE, true],

            'non_owner_cannot_view' => [$user1, 2, EventVoter::VIEW, false],
            'non_owner_cannot_edit' => [$user1, 2, EventVoter::EDIT, false],
            'non_owner_cannot_delete' => [$user1, 2, EventVoter::DELETE, false],

            'anonymous_cannot_view' => [null, 1, EventVoter::VIEW, false],
            'anonymous_cannot_edit' => [null, 1, EventVoter::EDIT, false],
            'anonymous_cannot_delete' => [null, 1, EventVoter::DELETE, false],

            'generic_user_interface_cannot_view' => [$genericUserInterface, 1, EventVoter::VIEW, false],
            'generic_user_interface_cannot_edit' => [$genericUserInterface, 1, EventVoter::EDIT, false],
            'generic_user_interface_cannot_delete' => [$genericUserInterface, 1, EventVoter::DELETE, false],
        ];
    }

    /**
     * Test the voteOnAttribute method.
     *
     * @param UserInterface|null $loggedInUser   Logged-in user
     * @param int|null           $eventAuthorId  Event author id
     * @param string             $attribute      Attribute
     * @param bool               $expectedResult Expected result
     *
     * @throws \ReflectionException
     *
     * @dataProvider provideVoteOnAttributeData
     */
    public function testVoteOnAttribute(?UserInterface $loggedInUser, ?int $eventAuthorId, string $attribute, bool $expectedResult): void
    {
        $event = $this->createMock(Event::class);

        $eventAuthor = null;
        if (null !== $eventAuthorId) {
            $eventAuthor = $this->createMock(User::class);
            $eventAuthor->method('getId')->willReturn($eventAuthorId);
        }
        $event->method('getAuthor')->willReturn($eventAuthor);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($loggedInUser);

        $reflection = new \ReflectionClass(EventVoter::class);
        $method = $reflection->getMethod('voteOnAttribute');

        $this->assertEquals(
            $expectedResult,
            $method->invoke($this->eventVoter, $attribute, $event, $token),
            sprintf(
                'Expected vote for attribute "%s" with logged-in user ID %s and event author ID %s to be %s',
                $attribute,
                $loggedInUser instanceof User ? $loggedInUser->getId() : (
                    $loggedInUser instanceof UserInterface && method_exists($loggedInUser, 'getId') ? $loggedInUser->getId() : 'N/A'
                ),
                $eventAuthorId ?? 'N/A',
                $expectedResult ? 'GRANTED' : 'DENIED'
            )
        );
    }
}
