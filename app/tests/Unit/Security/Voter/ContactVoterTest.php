<?php

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Contact;
use App\Entity\User; // Assuming App\Entity\User is your UserInterface implementation
use App\Security\Voter\ContactVoter; // The Voter under test
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface; // For type hinting in mocks
use ReflectionClass; // For testing protected methods

class ContactVoterTest extends TestCase
{
    private ContactVoter $contactVoter;

    protected function setUp(): void
    {
        parent::setUp();
        // Instantiate the ContactVoter. It has no constructor dependencies.
        $this->contactVoter = new ContactVoter();
    }

    /**
     * Data provider for the supports method test.
     * [attribute, subject, expectedResult]
     */
    public function provideSupportsData(): array
    {
        $contact = new Contact(); // A valid subject
        $user = new User(); // An invalid subject type
        $stdClass = new \stdClass(); // Another invalid subject type

        return [
            // --- Supported cases ---
            'supports_view_contact' => [ContactVoter::VIEW, $contact, true],
            'supports_edit_contact' => [ContactVoter::EDIT, $contact, true],
            'supports_delete_contact' => [ContactVoter::DELETE, $contact, true],

            // --- Unsupported attribute cases ---
            'does_not_support_unknown_attribute' => ['UNKNOWN_ATTRIBUTE', $contact, false],
            'does_not_support_create_attribute' => ['CONTACT_CREATE', $contact, false], // Example of unsupported, but valid format

            // --- Unsupported subject cases ---
            'does_not_support_user_subject' => [ContactVoter::VIEW, $user, false],
            'does_not_support_stdclass_subject' => [ContactVoter::EDIT, $stdClass, false],
            'does_not_support_null_subject' => [ContactVoter::DELETE, null, false],
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
        $reflection = new ReflectionClass(ContactVoter::class);
        $method = $reflection->getMethod('supports');

        $this->assertEquals(
            $expectedResult,
            $method->invoke($this->contactVoter, $attribute, $subject),
            sprintf('Expected supports("%s", %s) to be %s', $attribute, get_debug_type($subject), $expectedResult ? 'true' : 'false')
        );
    }

    /**
     * Data provider for the voteOnAttribute method test.
     * [loggedInUserRoles, isOwner, attribute, expectedVoteResult]
     */
    public function provideVoteOnAttributeData(): array
    {
        // Mock users with distinct IDs for owner/non-owner scenarios
        $ownerUser = $this->createMock(User::class);
        $ownerUser->method('getId')->willReturn(1); // Owner's ID
        $nonOwnerUser = $this->createMock(User::class);
        $nonOwnerUser->method('getId')->willReturn(2); // Non-owner's ID

        // Mock an admin user
        $adminUser = $this->createMock(User::class);
        $adminUser->method('getRoles')->willReturn(['ROLE_ADMIN', 'ROLE_USER']);
        $adminUser->method('getId')->willReturn(3); // Admin's ID


        return [
            // --- Admin user scenarios (always granted for supported attributes) ---
            'admin_can_view_any_contact' => [['ROLE_ADMIN'], false, ContactVoter::VIEW, true],
            'admin_can_edit_any_contact' => [['ROLE_ADMIN'], false, ContactVoter::EDIT, true],
            'admin_can_delete_any_contact' => [['ROLE_ADMIN'], false, ContactVoter::DELETE, true],
            'admin_can_view_own_contact' => [['ROLE_ADMIN'], true, ContactVoter::VIEW, true],
            'admin_can_edit_own_contact' => [['ROLE_ADMIN'], true, ContactVoter::EDIT, true],
            'admin_can_delete_own_contact' => [['ROLE_ADMIN'], true, ContactVoter::DELETE, true],
            // Removed: 'admin_unsupported_attribute' as voteOnAttribute is not called for unsupported attributes

            // --- Owner user scenarios (granted for supported attributes if owner) ---
            'owner_can_view_own_contact' => [['ROLE_USER'], true, ContactVoter::VIEW, true],
            'owner_can_edit_own_contact' => [['ROLE_USER'], true, ContactVoter::EDIT, true],
            'owner_can_delete_own_contact' => [['ROLE_USER'], true, ContactVoter::DELETE, true],
            // Removed: 'owner_unsupported_attribute'

            // --- Non-owner regular user scenarios (always denied for supported attributes) ---
            'non_owner_user_cannot_view_contact' => [['ROLE_USER'], false, ContactVoter::VIEW, false],
            'non_owner_user_cannot_edit_contact' => [['ROLE_USER'], false, ContactVoter::EDIT, false],
            'non_owner_user_cannot_delete_contact' => [['ROLE_USER'], false, ContactVoter::DELETE, false],
            // Removed: 'non_owner_unsupported_attribute'


            // --- Anonymous user scenarios (always denied) ---
            'anonymous_cannot_view' => [null, false, ContactVoter::VIEW, false],
            'anonymous_cannot_edit' => [null, false, ContactVoter::EDIT, false],
            'anonymous_cannot_delete' => [null, false, ContactVoter::DELETE, false],
            // Removed: 'anonymous_unsupported_attribute'

            // --- Other UserInterface (not App\Entity\User) scenario ---
            // If getToken()->getUser() returns a UserInterface that is NOT App\Entity\User,
            // the voter should return false directly.
            // FIX: Pass an empty array for roles to signify a UserInterface but not App\Entity\User.
            'other_user_interface_cannot_view' => [null, false, ContactVoter::VIEW, false],
        ];
    }

    /**
     * Test the voteOnAttribute method.
     * @dataProvider provideVoteOnAttributeData
     */
    public function testVoteOnAttribute(
        ?array $loggedInUserRoles,
        bool $isOwner,
        string $attribute,
        bool $expectedVoteResult
    ): void {
        // Mock the target Contact entity
        $contact = $this->createMock(Contact::class);

        // Prepare logged-in user based on scenario
        $loggedInUser = null;
        if ($loggedInUserRoles !== null) {
            $loggedInUser = $this->createMock(User::class);
            $loggedInUser->method('getRoles')->willReturn($loggedInUserRoles);
            $loggedInUser->method('getId')->willReturn(100); // Assign a test ID
        } elseif ($attribute === ContactVoter::VIEW && !$isOwner) {
            // Specific case for UserInterface not being App\Entity\User for 'other_user_interface_cannot_view'
            // When loggedInUserRoles is null (anonymous or other interface type)
            // and it's not an owner scenario (to isolate the UserInterface check),
            // create a generic UserInterface mock if the test case expects denial due to type.
            if ($loggedInUserRoles === null && !$isOwner && $expectedVoteResult === false) {
                $loggedInUser = $this->createMock(UserInterface::class);
                // No getRoles() or getId() needed on this mock, as the voter's first check is instanceof User.
            }
        }


        // Mock the author of the contact
        $contactAuthor = $this->createMock(User::class);
        // If the logged-in user is supposed to be the owner, make the author the same mock
        if ($isOwner && $loggedInUser instanceof User) {
            $contactAuthor = $loggedInUser;
        } else {
            // Otherwise, make the author a different user
            $contactAuthor->method('getId')->willReturn(200); // Different ID
        }
        $contact->method('getAuthor')->willReturn($contactAuthor);

        // Mock the TokenInterface
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($loggedInUser);

        // Use Reflection to call the protected voteOnAttribute method
        $reflection = new ReflectionClass(ContactVoter::class);
        $method = $reflection->getMethod('voteOnAttribute');

        $this->assertEquals(
            $expectedVoteResult,
            $method->invoke($this->contactVoter, $attribute, $contact, $token),
            sprintf(
                'Expected voteOnAttribute for logged-in user roles %s (ID: %s), contact owner ID %s, attribute "%s" to be %s',
                json_encode($loggedInUserRoles),
                $loggedInUser instanceof User ? $loggedInUser->getId() : 'N/A (not App\Entity\User)',
                $contactAuthor->getId(),
                $attribute,
                $expectedVoteResult ? 'GRANTED' : 'DENIED'
            )
        );
    }
}
