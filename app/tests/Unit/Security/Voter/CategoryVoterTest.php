<?php

/**
 * Category voter Test.
 */

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Category;
use App\Entity\User;
use App\Security\Voter\CategoryVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class Category voter Test.
 */
class CategoryVoterTest extends TestCase
{
    private CategoryVoter $categoryVoter;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryVoter = new CategoryVoter();
    }

    /**
     * Data provider for the supports method test.
     * [attribute, subject, expectedResult].
     *
     * @return array[]
     */
    public function provideSupportsData(): array
    {
        $category = new Category();
        $user = new User();
        $stdClass = new \stdClass();

        return [
            'supports_view_category' => [CategoryVoter::VIEW, $category, true],
            'supports_edit_category' => [CategoryVoter::EDIT, $category, true],
            'supports_delete_category' => [CategoryVoter::DELETE, $category, true],

            'does_not_support_create_attribute' => [CategoryVoter::CREATE, $category, false],
            'does_not_support_unknown_attribute' => ['UNKNOWN_ATTRIBUTE', $category, false],

            'does_not_support_user_subject' => [CategoryVoter::VIEW, $user, false],
            'does_not_support_stdclass_subject' => [CategoryVoter::EDIT, $stdClass, false],
            'does_not_support_null_subject' => [CategoryVoter::DELETE, null, false],
            'does_not_support_invalid_subject_and_attribute' => ['ANOTHER_ACTION', $user, false],
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
     * [loggedInUser, expectedVoteResult].
     *
     * @return array[]
     */
    public function provideVoteOnAttributeData(): array
    {
        $adminUser = $this->createMock(User::class);
        $adminUser->method('getRoles')->willReturn(['ROLE_ADMIN', 'ROLE_USER']);

        $regularUser = $this->createMock(User::class);
        $regularUser->method('getRoles')->willReturn(['ROLE_USER']);

        $genericUserInterface = $this->createMock(UserInterface::class);
        $genericUserInterface->method('getRoles')->willReturn(['ROLE_USER']);

        return [
            'admin_user_can_view' => [$adminUser, CategoryVoter::VIEW, true],
            'admin_user_can_edit' => [$adminUser, CategoryVoter::EDIT, true],
            'admin_user_can_delete' => [$adminUser, CategoryVoter::DELETE, true],

            'regular_user_cannot_view' => [$regularUser, CategoryVoter::VIEW, false],
            'regular_user_cannot_edit' => [$regularUser, CategoryVoter::EDIT, false],
            'regular_user_cannot_delete' => [$regularUser, CategoryVoter::DELETE, false],
            'anonymous_user_cannot_access' => [null, CategoryVoter::VIEW, false],
            'generic_user_interface_cannot_access' => [$genericUserInterface, CategoryVoter::VIEW, false],
        ];
    }

    /**
     * Test the protected voteOnAttribute method.
     *
     * @dataProvider provideVoteOnAttributeData
     *
     * @throws \ReflectionException
     */
    public function testVoteOnAttribute(?UserInterface $loggedInUser, string $attribute, bool $expectedResult): void
    {
        $subject = $this->createMock(Category::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($loggedInUser);

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
