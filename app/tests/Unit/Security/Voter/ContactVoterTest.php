<?php

/**
 * Contact voter Test.
 */

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Contact;
use App\Entity\User;
use App\Security\Voter\ContactVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class Contact voter Test.
 */
class ContactVoterTest extends TestCase
{
    private ContactVoter $contactVoter;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->contactVoter = new ContactVoter();
    }

    /**
     * Data provider for the supports method test.
     * [attribute, subject, expectedResult].
     *
     * @return array[]
     */
    public function provideSupportsData(): array
    {
        $contact = new Contact();
        $user = new User();
        $stdClass = new \stdClass();

        return [
            'supports_view_contact' => [ContactVoter::VIEW, $contact, true],
            'supports_edit_contact' => [ContactVoter::EDIT, $contact, true],
            'supports_delete_contact' => [ContactVoter::DELETE, $contact, true],

            'does_not_support_unknown_attribute' => ['UNKNOWN_ATTRIBUTE', $contact, false],
            'does_not_support_create_attribute' => ['CONTACT_CREATE', $contact, false],

            'does_not_support_user_subject' => [ContactVoter::VIEW, $user, false],
            'does_not_support_stdclass_subject' => [ContactVoter::EDIT, $stdClass, false],
            'does_not_support_null_subject' => [ContactVoter::DELETE, null, false],
            'does_not_support_invalid_subject_and_attribute' => ['SOME_ACTION', $user, false],
        ];
    }

    /**
     * Test the protected supports' method.
     *
     * @dataProvider provideSupportsData
     *
     * @throws \ReflectionException
     */
    public function testSupports(string $attribute, mixed $subject, bool $expectedResult): void
    {
        $reflection = new \ReflectionClass(ContactVoter::class);
        $method = $reflection->getMethod('supports');

        $this->assertEquals(
            $expectedResult,
            $method->invoke($this->contactVoter, $attribute, $subject),
            sprintf('Expected supports("%s", %s) to be %s', $attribute, get_debug_type($subject), $expectedResult ? 'true' : 'false')
        );
    }

    /**
     * Data provider for the voteOnAttribute method test.
     * [loggedInUserRoles, isOwner, attribute, expectedVoteResult].
     *
     * @return array[]
     */
    public function provideVoteOnAttributeData(): array
    {
        $ownerUser = $this->createMock(User::class);
        $ownerUser->method('getId')->willReturn(1);
        $nonOwnerUser = $this->createMock(User::class);
        $nonOwnerUser->method('getId')->willReturn(2);

        $adminUser = $this->createMock(User::class);
        $adminUser->method('getRoles')->willReturn(['ROLE_ADMIN', 'ROLE_USER']);
        $adminUser->method('getId')->willReturn(3);


        return [
            'admin_can_view_any_contact' => [['ROLE_ADMIN'], false, ContactVoter::VIEW, true],
            'admin_can_edit_any_contact' => [['ROLE_ADMIN'], false, ContactVoter::EDIT, true],
            'admin_can_delete_any_contact' => [['ROLE_ADMIN'], false, ContactVoter::DELETE, true],
            'admin_can_view_own_contact' => [['ROLE_ADMIN'], true, ContactVoter::VIEW, true],
            'admin_can_edit_own_contact' => [['ROLE_ADMIN'], true, ContactVoter::EDIT, true],
            'admin_can_delete_own_contact' => [['ROLE_ADMIN'], true, ContactVoter::DELETE, true],

            'owner_can_view_own_contact' => [['ROLE_USER'], true, ContactVoter::VIEW, true],
            'owner_can_edit_own_contact' => [['ROLE_USER'], true, ContactVoter::EDIT, true],
            'owner_can_delete_own_contact' => [['ROLE_USER'], true, ContactVoter::DELETE, true],

            'non_owner_user_cannot_view_contact' => [['ROLE_USER'], false, ContactVoter::VIEW, false],
            'non_owner_user_cannot_edit_contact' => [['ROLE_USER'], false, ContactVoter::EDIT, false],
            'non_owner_user_cannot_delete_contact' => [['ROLE_USER'], false, ContactVoter::DELETE, false],


            'anonymous_cannot_view' => [null, false, ContactVoter::VIEW, false],
            'anonymous_cannot_edit' => [null, false, ContactVoter::EDIT, false],
            'anonymous_cannot_delete' => [null, false, ContactVoter::DELETE, false],

            'other_user_interface_cannot_view' => [null, false, ContactVoter::VIEW, false],
        ];
    }

    /**
     * Test the voteOnAttribute method.
     *
     * @dataProvider provideVoteOnAttributeData
     *
     * @throws \ReflectionException
     */
    public function testVoteOnAttribute(?array $loggedInUserRoles, bool $isOwner, string $attribute, bool $expectedVoteResult): void
    {
        $contact = $this->createMock(Contact::class);

        $loggedInUser = null;
        if (null !== $loggedInUserRoles) {
            $loggedInUser = $this->createMock(User::class);
            $loggedInUser->method('getRoles')->willReturn($loggedInUserRoles);
            $loggedInUser->method('getId')->willReturn(100);
        } elseif (ContactVoter::VIEW === $attribute && !$isOwner) {
            if (false === $expectedVoteResult) {
                $loggedInUser = $this->createMock(UserInterface::class);
            }
        }

        $contactAuthor = $this->createMock(User::class);
        if ($isOwner && $loggedInUser instanceof User) {
            $contactAuthor = $loggedInUser;
        } else {
            $contactAuthor->method('getId')->willReturn(200);
        }
        $contact->method('getAuthor')->willReturn($contactAuthor);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($loggedInUser);

        $reflection = new \ReflectionClass(ContactVoter::class);
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
