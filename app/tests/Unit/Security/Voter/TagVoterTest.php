<?php

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Tag;
use App\Entity\User;
use App\Security\Voter\TagVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class TagVoterTest extends TestCase
{
    private function createVoter(): TagVoter
    {
        return new TagVoter();
    }

    private function createMockToken(?User $user = null): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        return $token;
    }

    public function provideSupportsData(): array
    {
        $tag = $this->createMock(Tag::class);
        $otherObject = new \stdClass();

        // Corrected: Separating User instantiation from setRoles to avoid "void method result used" warning
        $adminUserForSupports = new User();
        $adminUserForSupports->setRoles(['ROLE_ADMIN']);

        // Corrected: Separate instantiation for ROLE_USER
        $userUserForSupports = new User();
        $userUserForSupports->setRoles(['ROLE_USER']);

        return [
            // Supported attributes/subject -> should return ACCESS_ABSTAIN if not voted on, or ACCESS_GRANTED/DENIED by voteOnAttribute
            // For testing 'supports', we are interested in ACCESS_ABSTAIN if conditions are not met
            // or if the voter decides it doesn't apply to the given subject/attribute before voteOnAttribute logic.
            // When supports() returns true, vote() then calls voteOnAttribute().
            // For these cases, we'll check the final vote from vote().
            'supported_view_tag_admin' => [TagVoter::VIEW, $tag, $adminUserForSupports, VoterInterface::ACCESS_GRANTED],
            'supported_edit_tag_admin' => [TagVoter::EDIT, $tag, $adminUserForSupports, VoterInterface::ACCESS_GRANTED],
            'supported_delete_tag_admin' => [TagVoter::DELETE, $tag, $adminUserForSupports, VoterInterface::ACCESS_GRANTED],
            'supported_view_tag_user' => [TagVoter::VIEW, $tag, $userUserForSupports, VoterInterface::ACCESS_DENIED], // Used the separately instantiated user

            // Unsupported attributes/subject -> should return ACCESS_ABSTAIN directly from vote()
            'unsupported_create_tag' => [TagVoter::CREATE, $tag, $adminUserForSupports, VoterInterface::ACCESS_ABSTAIN],
            'unsupported_attribute' => ['UNKNOWN_ATTRIBUTE', $tag, $adminUserForSupports, VoterInterface::ACCESS_ABSTAIN],
            'unsupported_subject_null' => [TagVoter::VIEW, null, $adminUserForSupports, VoterInterface::ACCESS_ABSTAIN],
            'unsupported_subject_other_object' => [TagVoter::VIEW, $otherObject, $adminUserForSupports, VoterInterface::ACCESS_ABSTAIN],
        ];
    }

    /**
     * @dataProvider provideSupportsData
     */
    public function testSupportsThroughVote(string $attribute, $subject, ?User $user, int $expectedVote): void
    {
        $voter = $this->createVoter();
        $token = $this->createMockToken($user);

        // Test the behavior of the public vote method, which internally calls supports()
        $this->assertEquals($expectedVote, $voter->vote($token, $subject, [$attribute]));
    }

    public function provideVoteOnAttributeData(): array
    {
        $adminUser = new User();
        $adminUser->setRoles(['ROLE_ADMIN']);

        $userUser = new User();
        $userUser->setRoles(['ROLE_USER']);

        $noRoleUser = new User();
        $noRoleUser->setRoles([]);

        $tag = $this->createMock(Tag::class);

        return [
            'admin_can_view' => [TagVoter::VIEW, $tag, $adminUser, VoterInterface::ACCESS_GRANTED],
            'admin_can_edit' => [TagVoter::EDIT, $tag, $adminUser, VoterInterface::ACCESS_GRANTED],
            'admin_can_delete' => [TagVoter::DELETE, $tag, $adminUser, VoterInterface::ACCESS_GRANTED],
            'user_cannot_view' => [TagVoter::VIEW, $tag, $userUser, VoterInterface::ACCESS_DENIED],
            'user_cannot_edit' => [TagVoter::EDIT, $tag, $userUser, VoterInterface::ACCESS_DENIED],
            'user_cannot_delete' => [TagVoter::DELETE, $tag, $userUser, VoterInterface::ACCESS_DENIED],
            'no_role_user_cannot_view' => [TagVoter::VIEW, $tag, $noRoleUser, VoterInterface::ACCESS_DENIED],
            'null_user_cannot_view' => [TagVoter::VIEW, $tag, null, VoterInterface::ACCESS_DENIED],
            // Corrected: When 'CREATE' is passed, supports() returns false, so vote() returns ACCESS_ABSTAIN (0).
            'admin_unsupported_create_abstained_if_called_directly' => [TagVoter::CREATE, $tag, $adminUser, VoterInterface::ACCESS_ABSTAIN],
        ];
    }

    /**
     * @dataProvider provideVoteOnAttributeData
     */
    public function testVoteOnAttribute(string $attribute, $subject, ?User $user, int $expectedVote): void
    {
        $voter = $this->createVoter();
        $token = $this->createMockToken($user);

        // For voteOnAttribute, we usually assert the specific ACCESS_GRANTED/DENIED return value
        $this->assertEquals($expectedVote, $voter->vote($token, $subject, [$attribute]));
    }

    public function testVoteOnAttributeWithUnsupportedAttribute(): void
    {
        $voter = $this->createVoter();
        $user = new User(); // Corrected: Separated instantiation
        $user->setRoles(['ROLE_ADMIN']);
        $token = $this->createMockToken($user);
        $tag = $this->createMock(Tag::class);

        // If the attribute is not supported, vote() should return ACCESS_ABSTAIN
        // before even calling voteOnAttribute().
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $tag, ['UNSUPPORTED_ATTRIBUTE']));
    }

    public function testVoteOnAttributeWithUnsupportedSubject(): void
    {
        $voter = $this->createVoter();
        $user = new User(); // Corrected: Separated instantiation
        $user->setRoles(['ROLE_ADMIN']);
        $token = $this->createMockToken($user);
        $otherObject = new \stdClass();

        // If the subject is not supported, vote() should return ACCESS_ABSTAIN
        // before even calling voteOnAttribute().
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $otherObject, [TagVoter::VIEW]));
    }
}
