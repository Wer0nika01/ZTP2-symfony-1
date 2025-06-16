<?php

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Event;
use App\Entity\User; // Assuming App\Entity\User is your UserInterface implementation
use App\Security\Voter\EventVoter; // The Voter under test
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface; // For type hinting in mocks
use ReflectionClass; // For testing protected methods

class EventVoterTest extends TestCase
{
    private EventVoter $eventVoter;

    protected function setUp(): void
    {
        parent::setUp();
        // Instantiate the EventVoter. It has no constructor dependencies.
        $this->eventVoter = new EventVoter();
    }

    /**
     * Data provider for the supports method test.
     * [attribute, subject, expectedResult]
     */
    public function provideSupportsData(): array
    {
        $event = new Event(); // A valid subject
        $user = new User(); // An invalid subject type
        $stdClass = new \stdClass(); // Another invalid subject type

        return [
            // --- Supported cases ---
            'supports_delete_event' => [EventVoter::DELETE, $event, true],
            'supports_edit_event' => [EventVoter::EDIT, $event, true],
            'supports_view_event' => [EventVoter::VIEW, $event, true],

            // --- Unsupported attribute cases ---
            'does_not_support_unknown_attribute' => ['UNKNOWN_ATTRIBUTE', $event, false],
            'does_not_support_create_attribute' => ['EVENT_CREATE', $event, false], // Example of unsupported attribute

            // --- Unsupported subject cases ---
            'does_not_support_user_subject' => [EventVoter::VIEW, $user, false],
            'does_not_support_stdclass_subject' => [EventVoter::EDIT, $stdClass, false],
            'does_not_support_null_subject' => [EventVoter::DELETE, null, false],
            'does_not_support_invalid_subject_and_attribute' => ['SOME_ACTION', $user, false],
        ];
    }

    /**
     * Test the protected supports method.
     * @dataProvider provideSupportsData
     */
    public function testSupports(string $attribute, mixed $subject, bool $expectedResult): void
    {
        // Use Reflection to call the protected supports method for testing
        $reflection = new ReflectionClass(EventVoter::class);
        $method = $reflection->getMethod('supports');

        $this->assertEquals(
            $expectedResult,
            $method->invoke($this->eventVoter, $attribute, $subject),
            sprintf('Expected supports("%s", %s) to be %s', $attribute, get_debug_type($subject), $expectedResult ? 'true' : 'false')
        );
    }

    /**
     * Data provider for the voteOnAttribute method test.
     * [loggedInUser, eventAuthorId, attribute, expectedVoteResult]
     */
    public function provideVoteOnAttributeData(): array
    {
        // Mock a User with ID 1 for testing ownership scenarios
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);

        // Mock a User with ID 2 for non-ownership scenarios
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);

        // FIX: Change to MockObject|User to ensure getId() can be mocked.
        // This mock represents a UserInterface that is not the App\Entity\User.
        // If the voter expects getId() to be present, this mock will satisfy it.
        $genericUserInterface = $this->createMock(User::class);
        // FIX: Mock getId() on the generic User mock.
        // It's given a distinct ID so it typically won't match existing authors, leading to DENIED.
        $genericUserInterface->method('getId')->willReturn(999);

        return [
            // --- Owner can perform all actions ---
            'owner_can_view' => [$user1, 1, EventVoter::VIEW, true],
            'owner_can_edit' => [$user1, 1, EventVoter::EDIT, true],
            'owner_can_delete' => [$user1, 1, EventVoter::DELETE, true],

            // --- Non-owner cannot perform any action ---
            'non_owner_cannot_view' => [$user1, 2, EventVoter::VIEW, false],
            'non_owner_cannot_edit' => [$user1, 2, EventVoter::EDIT, false],
            'non_owner_cannot_delete' => [$user1, 2, EventVoter::DELETE, false],

            // --- Anonymous user (token->getUser() is null) ---
            'anonymous_cannot_view' => [null, 1, EventVoter::VIEW, false],
            'anonymous_cannot_edit' => [null, 1, EventVoter::EDIT, false],
            'anonymous_cannot_delete' => [null, 1, EventVoter::DELETE, false],

            // --- Generic UserInterface (now a mocked App\Entity\User) ---
            // These cases should still result in false because the user's ID won't match the author's ID (1 vs 999).
            'generic_user_interface_cannot_view' => [$genericUserInterface, 1, EventVoter::VIEW, false],
            'generic_user_interface_cannot_edit' => [$genericUserInterface, 1, EventVoter::EDIT, false],
            'generic_user_interface_cannot_delete' => [$genericUserInterface, 1, EventVoter::DELETE, false],
        ];
    }

    /**
     * Test the protected voteOnAttribute method.
     * @dataProvider provideVoteOnAttributeData
     */
    public function testVoteOnAttribute(
        ?UserInterface $loggedInUser,
        ?int $eventAuthorId,
        string $attribute,
        bool $expectedResult
    ): void {
        // Mock the Event subject
        $event = $this->createMock(Event::class);

        // Mock the author of the event only if eventAuthorId is provided
        $eventAuthor = null;
        if ($eventAuthorId !== null) {
            $eventAuthor = $this->createMock(User::class);
            $eventAuthor->method('getId')->willReturn($eventAuthorId);
        }
        $event->method('getAuthor')->willReturn($eventAuthor);

        // Mock the TokenInterface
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($loggedInUser);

        // Use Reflection to call the protected voteOnAttribute method
        $reflection = new ReflectionClass(EventVoter::class);
        $method = $reflection->getMethod('voteOnAttribute');

        $this->assertEquals(
            $expectedResult,
            $method->invoke($this->eventVoter, $attribute, $event, $token),
            sprintf(
                'Expected vote for attribute "%s" with logged-in user ID %s and event author ID %s to be %s',
                $attribute,
                $loggedInUser instanceof User ? $loggedInUser->getId() : (
                $loggedInUser instanceof UserInterface && method_exists($loggedInUser, 'getId') ? $loggedInUser->getId() : 'N/A' // This part is now safer due to MockObject|User
                ),
                $eventAuthorId ?? 'N/A',
                $expectedResult ? 'GRANTED' : 'DENIED'
            )
        );
    }
}
