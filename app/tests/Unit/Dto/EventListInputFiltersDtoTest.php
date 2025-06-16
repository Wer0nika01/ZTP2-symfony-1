<?php

namespace App\Tests\Unit\Dto;

use App\Dto\EventListInputFiltersDto; // The DTO under test
use PHPUnit\Framework\TestCase;

class EventListInputFiltersDtoTest extends TestCase
{
    /**
     * Test the constructor and ensure properties are correctly assigned.
     *
     * @dataProvider provideConstructorData
     */
    public function testConstructor(?int $categoryId, ?int $tagId, ?int $statusId): void
    {
        $dto = new EventListInputFiltersDto($categoryId, $tagId, $statusId);

        // Assert that the public readonly properties are correctly assigned
        $this->assertEquals($categoryId, $dto->categoryId);
        $this->assertEquals($tagId, $dto->tagId);
        $this->assertEquals($statusId, $dto->statusId);
    }

    /**
     * Data provider for testConstructor.
     * [categoryId, tagId, statusId]
     */
    public function provideConstructorData(): array
    {
        return [
            'all_null' => [null, null, null],
            'all_provided' => [1, 2, 3],
            'only_category_id' => [10, null, null],
            'only_tag_id' => [null, 20, null],
            'only_status_id' => [null, null, 30],
            'category_and_tag' => [100, 200, null],
            'category_and_status' => [100, null, 300],
            'tag_and_status' => [null, 200, 300],
            'zero_values' => [0, 0, 0], // Test with zero which is a valid int
            'negative_values' => [-1, -2, -3], // Test with negative integers if applicable
        ];
    }
}

