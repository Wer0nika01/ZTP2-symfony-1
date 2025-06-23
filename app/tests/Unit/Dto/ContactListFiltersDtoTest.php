<?php

/**
 * Contact list filters dto Test.
 */

namespace App\Tests\Unit\Dto;

use App\Dto\ContactListFiltersDto;
use App\Entity\Tag;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * Class Contact list filters dto Test.
 */
class ContactListFiltersDtoTest extends TestCase
{
    private ContactListFiltersDto $dto;

    /**
     * Set up.
     */
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
     * Test setting and getting the tags' collection.
     */
    public function testSetAndGetTags(): void
    {
        $tag1 = $this->createMock(Tag::class);
        $tag2 = $this->createMock(Tag::class);
        $tagsCollection = new ArrayCollection([$tag1, $tag2]);

        $result = $this->dto->setTags($tagsCollection);

        $this->assertSame($this->dto, $result);
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
        $this->assertSame($this->dto, $result1);
        $this->assertTrue($this->dto->getTags()->contains($tag1));
        $this->assertCount(1, $this->dto->getTags());

        $result2 = $this->dto->addTag($tag1);
        $this->assertSame($this->dto, $result2);
        $this->assertCount(1, $this->dto->getTags());

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
        $tag3 = $this->createMock(Tag::class);

        $initialTags = new ArrayCollection([$tag1, $tag2]);
        $this->dto->setTags($initialTags);
        $this->assertCount(2, $this->dto->getTags());
        $this->assertTrue($this->dto->getTags()->contains($tag1));
        $this->assertTrue($this->dto->getTags()->contains($tag2));

        $result1 = $this->dto->removeTag($tag1);
        $this->assertSame($this->dto, $result1);
        $this->assertFalse($this->dto->getTags()->contains($tag1));
        $this->assertCount(1, $this->dto->getTags());
        $this->assertTrue($this->dto->getTags()->contains($tag2));

        $result2 = $this->dto->removeTag($tag3);
        $this->assertSame($this->dto, $result2);
        $this->assertCount(1, $this->dto->getTags());

        $this->dto->removeTag($tag2);
        $this->assertFalse($this->dto->getTags()->contains($tag2));
        $this->assertCount(0, $this->dto->getTags());
        $this->assertTrue($this->dto->getTags()->isEmpty());
    }
}
