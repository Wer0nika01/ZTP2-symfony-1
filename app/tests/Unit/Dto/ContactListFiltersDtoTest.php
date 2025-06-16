<?php

namespace App\Tests\Unit\Dto;

use App\Dto\ContactListFiltersDto; // The DTO under test
use App\Entity\Tag; // Assuming the Tag entity exists
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContactListFiltersDtoTest extends TestCase
{
    private ContactListFiltersDto $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dto = new ContactListFiltersDto();
    }

    /**
     * Test constructor initializes tags as an empty ArrayCollection.
     */
    public function testConstructorInitializesTags(): void
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->dto->getTags());
        $this->assertTrue($this->dto->getTags()->isEmpty());
    }

    /**
     * Test setting and getting the tags collection.
     */
    public function testSetAndGetTags(): void
    {
        $tag1 = $this->createMock(Tag::class);
        $tag2 = $this->createMock(Tag::class);
        $tagsCollection = new ArrayCollection([$tag1, $tag2]);

        $result = $this->dto->setTags($tagsCollection);

        // Test fluent interface
        $this->assertSame($this->dto, $result);
        // Test that the collection is correctly set and retrieved
        $this->assertSame($tagsCollection, $this->dto->getTags());
        $this->assertCount(2, $this->dto->getTags());
    }

    /**
     * Test adding a single tag to the collection.
     */
    public function testAddTag(): void
    {
        $tag1 = $this->createMock(Tag::class);
        $tag2 = $this->createMock(Tag::class);

        $this->assertCount(0, $this->dto->getTags());

        $result1 = $this->dto->addTag($tag1);
        // Test fluent interface
        $this->assertSame($this->dto, $result1);
        $this->assertTrue($this->dto->getTags()->contains($tag1));
        $this->assertCount(1, $this->dto->getTags());

        // Attempt to add the same tag again, should not increase count
        $result2 = $this->dto->addTag($tag1);
        $this->assertSame($this->dto, $result2);
        $this->assertCount(1, $this->dto->getTags());

        // Add a different tag
        $this->dto->addTag($tag2);
        $this->assertTrue($this->dto->getTags()->contains($tag2));
        $this->assertCount(2, $this->dto->getTags());
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
        $this->dto->setTags($initialTags);
        $this->assertCount(2, $this->dto->getTags());
        $this->assertTrue($this->dto->getTags()->contains($tag1));
        $this->assertTrue($this->dto->getTags()->contains($tag2));

        $result1 = $this->dto->removeTag($tag1);
        // Test fluent interface
        $this->assertSame($this->dto, $result1);
        $this->assertFalse($this->dto->getTags()->contains($tag1));
        $this->assertCount(1, $this->dto->getTags());
        $this->assertTrue($this->dto->getTags()->contains($tag2));

        // Attempt to remove a tag that is not present, should not change count
        $result2 = $this->dto->removeTag($tag3);
        $this->assertSame($this->dto, $result2);
        $this->assertCount(1, $this->dto->getTags());

        // Remove the last tag
        $this->dto->removeTag($tag2);
        $this->assertFalse($this->dto->getTags()->contains($tag2));
        $this->assertCount(0, $this->dto->getTags());
        $this->assertTrue($this->dto->getTags()->isEmpty());
    }
}
