<?php

/**
 * Event status enum Test.
 */

namespace App\Tests\Unit\Entity\Enum;

use App\Entity\Enum\EventStatus;
use PHPUnit\Framework\TestCase;

/**
 * Class Event status enum Test.
 */
class EventStatusTest extends TestCase
{
    /**
     * Data provider for testing getLabel method.
     * [enumCase, expectedLabel].
     *
     * @return array[]
     */
    public function provideLabels(): array
    {
        return [
            [EventStatus::PERSONAL, 'label.personal'],
            [EventStatus::IMPORTANT, 'label.important'],
            [EventStatus::WORK, 'label.work'],
        ];
    }

    /**
     * Test the getLabel method for each enum case.
     *
     * @param EventStatus $enumCase      Enum case
     * @param string      $expectedLabel Expected label
     */
    public function testGetLabel(EventStatus $enumCase, string $expectedLabel): void
    {
        $this->assertEquals($expectedLabel, $enumCase->getLabel());
    }

    /**
     * Data provider for testing getButtonClass method.
     * [enumCase, expectedButtonClass].
     *
     * @return array[]
     */
    public function provideButtonClasses(): array
    {
        return [
            [EventStatus::PERSONAL, 'btn-success'],
            [EventStatus::IMPORTANT, 'btn-danger'],
            [EventStatus::WORK, 'btn-primary'],
        ];
    }

    /**
     * Test the getButtonClass method for each enum case.
     *
     * @param EventStatus $enumCase            Enum case
     * @param string      $expectedButtonClass Expected button class
     */
    public function testGetButtonClass(EventStatus $enumCase, string $expectedButtonClass): void
    {
        $this->assertEquals($expectedButtonClass, $enumCase->getButtonClass());
    }

    /**
     * Test the static getLabels method.
     */
    public function testGetLabels(): void
    {
        $expectedLabels = [
            EventStatus::PERSONAL->value => 'label.personal',
            EventStatus::IMPORTANT->value => 'label.important',
            EventStatus::WORK->value => 'label.work',
        ];

        $this->assertEquals($expectedLabels, EventStatus::getLabels());
    }
}
