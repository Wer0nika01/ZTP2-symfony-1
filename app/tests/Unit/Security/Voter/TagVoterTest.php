<?php

/**
 * Tag voter Test.
 */

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Tag;
use App\Entity\User;
use App\Security\Voter\TagVoter;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Class Tag voter Test.
 */
class TagVoterTest extends TestCase
{
    /**
     * Provide supports data.
     *
     * @return array[]
     */
    public function provideSupportsData(): array
    {
        $tag = $this->createMock(Tag::class);
        $otherObject = new stdClass();

        $adminUserForSupports = new User();
        $adminUserForSupports->setRoles(['ROLE_ADMIN']);

        $userUserForSupports = new User();
        $userUserForSupports->setRoles(['ROLE_USER']);

        return [
            'supported_view_tag_admin' => [TagVoter::VIEW, $tag, $adminUserForSupports, VoterInterface::ACCESS_GRANTED],
            'supported_edit_tag_admin' => [TagVoter::EDIT, $tag, $adminUserForSupports, VoterInterface::ACCESS_GRANTED],
            'supported_delete_tag_admin' => [TagVoter::DELETE, $tag, $adminUserForSupports, VoterInterface::ACCESS_GRANTED],
            'supported_view_tag_user' => [TagVoter::VIEW, $tag, $userUserForSupports, VoterInterface::ACCESS_DENIED], // Used the separately instantiated user

            'unsupported_create_tag' => [TagVoter::CREATE, $tag, $adminUserForSupports, VoterInterface::ACCESS_ABSTAIN],
            'unsupported_attribute' => ['UNKNOWN_ATTRIBUTE', $tag, $adminUserForSupports, VoterInterface::ACCESS_ABSTAIN],
            'unsupported_subject_null' => [TagVoter::VIEW, null, $adminUserForSupports, VoterInterface::ACCESS_ABSTAIN],
            'unsupported_subject_other_object' => [TagVoter::VIEW, $otherObject, $adminUserForSupports, VoterInterface::ACCESS_ABSTAIN],
        ];
    }

    /**
     * Test supports through vote.
     *
     * @dataProvider provideSupportsData
     *
     * @param string    $attribute
     * @param $subject
     * @param User|null $user
     * @param int       $expectedVote
     */
    public function testSupportsThroughVote(string $attribute, $subject, ?User $user, int $expectedVote): void
    {
        $voter = $this->createVoter();
        $token = $this->createMockToken($user);

        $this->assertEquals($expectedVote, $voter->vote($token, $subject, [$attribute]));
    }

    /**
     * Provide vote on attribute data
     *
     * @return array[]
     */
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
            'admin_unsupported_create_abstained_if_called_directly' => [TagVoter::CREATE, $tag, $adminUser, VoterInterface::ACCESS_ABSTAIN],
        ];
    }

    /**
     * Test vote on attribute.
     *
     * @dataProvider provideVoteOnAttributeData
     *
     * @param string    $attribute
     * @param $subject
     * @param User|null $user
     * @param int       $expectedVote
     */
    public function testVoteOnAttribute(string $attribute, $subject, ?User $user, int $expectedVote): void
    {
        $voter = $this->createVoter();
        $token = $this->createMockToken($user);

        $this->assertEquals($expectedVote, $voter->vote($token, $subject, [$attribute]));
    }

    /**
     * Test vote on attribute with unsupported attribute.
     */
    public function testVoteOnAttributeWithUnsupportedAttribute(): void
    {
        $voter = $this->createVoter();
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = $this->createMockToken($user);
        $tag = $this->createMock(Tag::class);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $tag, ['UNSUPPORTED_ATTRIBUTE']));
    }

    /**
     * Test vote on attribute with unsupported subject.
     */
    public function testVoteOnAttributeWithUnsupportedSubject(): void
    {
        $voter = $this->createVoter();
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = $this->createMockToken($user);
        $otherObject = new stdClass();

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $otherObject, [TagVoter::VIEW]));
    }

    /**
     * Create voter.
     *
     * @return TagVoter
     */
    private function createVoter(): TagVoter
    {
        return new TagVoter();
    }

    /**
     * Create mock token.
     *
     * @param User|null $user
     *
     * @return TokenInterface
     */
    private function createMockToken(?User $user = null): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
