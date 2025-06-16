<?php

namespace App\Tests\Unit\Entity\Enum;

use App\Entity\Enum\UserRole; // The enum under test
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    /**
     * Data provider for testing the label() method.
     * [enumCase, expectedLabel]
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
