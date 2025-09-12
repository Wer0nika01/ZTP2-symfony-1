<?php

/**
 * User role enum Test.
 */

namespace App\Tests\Unit\Entity\Enum;

use App\Entity\Enum\UserRole;
use PHPUnit\Framework\TestCase;

/**
 * Class User role enum Test.
 */
class UserRoleTest extends TestCase
{
    /**
     * Data provider for testing the label() method.
     * [enumCase, expectedLabel].
     *
     * @return array[]
     */
    public function provideLabels(): array
    {
        return [
            [UserRole::ROLE_USER, 'label.role_user'],
            [UserRole::ROLE_ADMIN, 'label.role_admin'],
        ];
    }

    /**
     * Test the label() method for each enum case.
     *
     * @dataProvider provideLabels
     */
    public function testLabel(UserRole $enumCase, string $expectedLabel): void
    {
        $this->assertEquals($expectedLabel, $enumCase->label());
    }

    /**
     * Test that the enum is a backed enum with string values.
     */
    public function testEnumIsBackedWithString(): void
    {
        $this->assertEquals('ROLE_USER', UserRole::ROLE_USER->value);
        $this->assertEquals('ROLE_ADMIN', UserRole::ROLE_ADMIN->value);
        $this->assertIsString(UserRole::ROLE_USER->value);
        $this->assertIsString(UserRole::ROLE_ADMIN->value);
    }
}
