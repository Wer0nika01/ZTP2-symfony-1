<?php

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Category;
use App\Entity\User; // Assuming App\Entity\User is your UserInterface implementation
use App\Security\Voter\CategoryVoter; // The Voter under test
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CategoryVoterTest extends TestCase
{
    private CategoryVoter $categoryVoter;

    protected function setUp(): void
    {
        parent::setUp();
        // Instantiate the CategoryVoter. It has no constructor dependencies.
        $this->categoryVoter = new CategoryVoter();
    }

    /**
     * Data provider for the supports method test.
     * [attribute, subject, expectedResult]
     */
    public function provideSupportsData(): array
    {
        $category = new Category(); // A valid subject
        $user = new User(); // An invalid subject type
        $stdClass = new \stdClass(); // Another invalid subject type

        return [
            // --- Supported cases ---
            'supports_view_category' => [CategoryVoter::VIEW, $category, true],
            'supports_edit_category' => [CategoryVoter::EDIT, $category, true],
            'supports_delete_category' => [CategoryVoter::DELETE, $category, true],

            // --- Unsupported attribute cases ---
            'does_not_support_create_attribute' => [CategoryVoter::CREATE, $category, false],
            'does_not_support_unknown_attribute' => ['UNKNOWN_ATTRIBUTE', $category, false],

            // --- Unsupported subject cases ---
            'does_not_support_user_subject' => [CategoryVoter::VIEW, $user, false],
            'does_not_support_stdclass_subject' => [CategoryVoter::EDIT, $stdClass, false],
            'does_not_support_null_subject' => [CategoryVoter::DELETE, null, false],
            'does_not_support_invalid_subject_and_attribute' => ['ANOTHER_ACTION', $user, false],
        ];
    }

    /**
     * Test the protected supports method.
     * @dataProvider provideSupportsData
     */
    public function testSupports(string $attribute, mixed $subject, bool $expectedResult): void
    {
        // Use Reflection to call the protected supports method for testing
        $reflection = new \ReflectionClass(CategoryVoter::class);
        $method = $reflection->getMethod('supports');

        $this->assertEquals(
            $expectedResult,
            $method->invoke($this->categoryVoter, $attribute, $subject),
            sprintf('Expected supports("%s", %s) to be %s', $attribute, get_debug_type($subject), $expectedResult ? 'true' : 'false')
        );
    }

    /**
     * Data provider for the voteOnAttribute method test.
     * [loggedInUser, expectedVoteResult]
     */
    public function provideVoteOnAttributeData(): array
    {
        // Mock a User with ROLE_ADMIN
        $adminUser = $this->createMock(User::class);
        $adminUser->method('getRoles')->willReturn(['ROLE_ADMIN', 'ROLE_USER']);

        // Mock a User without ROLE_ADMIN
        $regularUser = $this->createMock(User::class);
        $regularUser->method('getRoles')->willReturn(['ROLE_USER']);

        // Mock a generic UserInterface (not App\Entity\User)
        $genericUserInterface = $this->createMock(UserInterface::class);
        $genericUserInterface->method('getRoles')->willReturn(['ROLE_USER']); // Even if it had ROLE_ADMIN, voter returns false for non-User entity

        return [
            // --- Access Granted cases ---
            'admin_user_can_view' => [$adminUser, CategoryVoter::VIEW, true],
            'admin_user_can_edit' => [$adminUser, CategoryVoter::EDIT, true],
            'admin_user_can_delete' => [$adminUser, CategoryVoter::DELETE, true],

            // --- Access Denied cases ---
            'regular_user_cannot_view' => [$regularUser, CategoryVoter::VIEW, false],
            'regular_user_cannot_edit' => [$regularUser, CategoryVoter::EDIT, false],
            'regular_user_cannot_delete' => [$regularUser, CategoryVoter::DELETE, false],
            'anonymous_user_cannot_access' => [null, CategoryVoter::VIEW, false],
            'generic_user_interface_cannot_access' => [$genericUserInterface, CategoryVoter::VIEW, false],
        ];
    }

    /**
     * Test the protected voteOnAttribute method.
     * @dataProvider provideVoteOnAttributeData
     */
    public function testVoteOnAttribute(
        ?UserInterface $loggedInUser,
        string $attribute, // The attribute being voted on (e.g., CATEGORY_VIEW)
        bool $expectedResult // Expected boolean outcome of the vote (true for granted, false for denied)
    ): void {
        // The subject is a Category entity, but its specific state doesn't affect the voter's logic.
        $subject = $this->createMock(Category::class);

        // Mock the TokenInterface to return the specified user
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($loggedInUser);

        // Use Reflection to call the protected voteOnAttribute method for testing
        $reflection = new \ReflectionClass(CategoryVoter::class);
        $method = $reflection->getMethod('voteOnAttribute');

        $this->assertEquals(
            $expectedResult,
            $method->invoke($this->categoryVoter, $attribute, $subject, $token),
            sprintf(
                'Expected vote for attribute "%s" with user type %s (admin status: %s) to be %s',
                $attribute,
                get_debug_type($loggedInUser),
                $loggedInUser instanceof User && in_array('ROLE_ADMIN', $loggedInUser->getRoles()) ? 'true' : 'false',
                $expectedResult ? 'true' : 'false'
            )
        );
    }
}
