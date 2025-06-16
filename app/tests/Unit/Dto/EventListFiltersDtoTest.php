<?php

namespace App\Tests\Unit\Dto;

use App\Dto\EventListFiltersDto; // The DTO under test
use App\Entity\Category; // Assuming Category entity exists
use App\Entity\Enum\EventStatus; // Assuming EventStatus enum exists
use App\Entity\Tag; // Assuming Tag entity exists
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventListFiltersDtoTest extends TestCase
{
    /**
     * Test constructor initializes tags as an empty ArrayCollection
     * and correctly sets category and status to null by default.
     */
    public function testConstructorInitialization(): void
    {
        // Test with default nulls for category and status
        $initialTags = new ArrayCollection();
        $dto = new EventListFiltersDto($initialTags);

        $this->assertInstanceOf(ArrayCollection::class, $dto->getTags());
        $this->assertTrue($dto->getTags()->isEmpty());
        $this->assertSame($initialTags, $dto->getTags()); // Ensure it's the exact instance passed
        $this->assertNull($dto->getCategory());
        $this->assertNull($dto->getStatus());

        // Test with specific values
        $mockCategory = $this->createMock(Category::class);
        $mockStatus = EventStatus::WORK;
        $initialTagsWithData = new ArrayCollection([$this->createMock(Tag::class)]);
        $dtoWithData = new EventListFiltersDto($initialTagsWithData, $mockCategory, $mockStatus);

        $this->assertSame($initialTagsWithData, $dtoWithData->getTags());
        $this->assertSame($mockCategory, $dtoWithData->getCategory());
        $this->assertSame($mockStatus, $dtoWithData->getStatus());
    }

    /**
     * Test setting and getting the category.
     */
    public function testSetAndGetCategory(): void
    {
        $initialTags = new ArrayCollection();
        $dto = new EventListFiltersDto($initialTags);

        $mockCategory = $this->createMock(Category::class);
        $result = $dto->setCategory($mockCategory);

        // Test fluent interface
        $this->assertSame($dto, $result);
        $this->assertSame($mockCategory, $dto->getCategory());

        // Test setting to null
        $dto->setCategory(null);
        $this->assertNull($dto->getCategory());
    }

    /**
     * Test setting and getting the status.
     */
    public function testSetAndGetStatus(): void
    {
        $initialTags = new ArrayCollection();
        $dto = new EventListFiltersDto($initialTags);

        $status = EventStatus::IMPORTANT;
        $result = $dto->setStatus($status);

        // Test fluent interface
        $this->assertSame($dto, $result);
        $this->assertSame($status, $dto->getStatus());

        // Test setting to null
        $dto->setStatus(null);
        $this->assertNull($dto->getStatus());
    }

    /**
     * Test setting and getting the tags collection.
     */
    public function testSetAndGetTags(): void
    {
        $initialTags = new ArrayCollection();
        $dto = new EventListFiltersDto($initialTags);

        $tag1 = $this->createMock(Tag::class);
        $tag2 = $this->createMock(Tag::class);
        $tagsCollection = new ArrayCollection([$tag1, $tag2]);

        $result = $dto->setTags($tagsCollection);

        // Test fluent interface
        $this->assertSame($dto, $result);
        $this->assertSame($tagsCollection, $dto->getTags());
        $this->assertCount(2, $dto->getTags());
    }

    /**
     * Test adding a single tag to the collection.
     */
    public function testAddTag(): void
    {
        $initialTags = new ArrayCollection();
        $dto = new EventListFiltersDto($initialTags);

        $tag1 = $this->createMock(Tag::class);
        $tag2 = $this->createMock(Tag::class);

        $this->assertCount(0, $dto->getTags());

        $result1 = $dto->addTag($tag1);
        // Test fluent interface
        $this->assertSame($dto, $result1);
        $this->assertTrue($dto->getTags()->contains($tag1));
        $this->assertCount(1, $dto->getTags());

        // Attempt to add the same tag again, should not increase count
        $result2 = $dto->addTag($tag1);
        $this->assertSame($dto, $result2);
        $this->assertCount(1, $dto->getTags());

        // Add a different tag
        $dto->addTag($tag2);
        $this->assertTrue($dto->getTags()->contains($tag2));
        $this->assertCount(2, $dto->getTags());
    }

    /**
     * Test removing a single tag from the collection.
     */
    public function testRemoveTag(): void
    {
        $tag1 = $this->createMock(Tag::class);
        $tag2 = $this->createMock(Tag::class);
        $tag3 = $this->createMock(Tag::class); // A tag not in the collection

        // Initialize DTO with some tags
        $initialTags = new ArrayCollection([$tag1, $tag2]);
        $dto = new EventListFiltersDto($initialTags);
        $this->assertCount(2, $dto->getTags());
        $this->assertTrue($dto->getTags()->contains($tag1));
        $this->assertTrue($dto->getTags()->contains($tag2));

        $result1 = $dto->removeTag($tag1);
        // Test fluent interface
        $this->assertSame($dto, $result1);
        $this->assertFalse($dto->getTags()->contains($tag1));
        $this->assertCount(1, $dto->getTags());
        $this->assertTrue($dto->getTags()->contains($tag2));

        // Attempt to remove a tag that is not present, should not change count
        $result2 = $dto->removeTag($tag3);
        $this->assertSame($dto, $result2);
        $this->assertCount(1, $dto->getTags());

        // Remove the last tag
        $dto->removeTag($tag2);
        $this->assertFalse($dto->getTags()->contains($tag2));
        $this->assertCount(0, $dto->getTags());
        $this->assertTrue($dto->getTags()->isEmpty());
    }
}
