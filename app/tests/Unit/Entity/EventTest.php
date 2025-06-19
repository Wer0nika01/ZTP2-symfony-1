<?php

/**
 * Event entity Test.
 */

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\Enum\EventStatus;
use App\Entity\Event;
use App\Entity\Tag;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * Class Event entity Test.
 */
class EventTest extends TestCase
{
    private Event $event;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->event = new Event();
        // Initialize the 'status' property to prevent "accessed before initialization" error
        // Assuming EventStatus::PERSONAL is a valid default or initial state
        $this->event->setStatus(EventStatus::PERSONAL);
    }

    /**
     * Test if the ID is initially null for a new entity.
     */
    public function testGetId(): void
    {
        $this->assertNull($this->event->getId());
    }

    /**
     * Test setting and getting the title.
     */
    public function testTitleGetAndSet(): void
    {
        $title = 'Test Event Title';
        $this->event->setTitle($title);

        $this->assertEquals($title, $this->event->getTitle());
        $this->assertIsString($this->event->getTitle());

        $this->event->setTitle(null);
        $this->assertNull($this->event->getTitle());
    }

    /**
     * Test setting and getting the description.
     */
    public function testDescriptionGetAndSet(): void
    {
        $description = 'This is a detailed description of the event.';
        $result = $this->event->setDescription($description);

        $this->assertSame($this->event, $result); // Test fluent interface
        $this->assertEquals($description, $this->event->getDescription());
        $this->assertIsString($this->event->getDescription());

        $this->event->setDescription(null);
        $this->assertNull($this->event->getDescription());
    }

    /**
     * Test setting and getting the start time.
     */
    public function testStartTimeGetAndSet(): void
    {
        $startTime = new DateTimeImmutable('2024-07-01 09:00:00');
        $result = $this->event->setStartTime($startTime);

        $this->assertSame($this->event, $result);
        $this->assertEquals($startTime, $this->event->getStartTime());

        $this->event->setStartTime(null);
        $this->assertNull($this->event->getStartTime());
    }

    /**
     * Test setting and getting the end time.
     */
    public function testEndTimeGetAndSet(): void
    {
        $endTime = new DateTimeImmutable('2024-07-01 17:00:00');
        $result = $this->event->setEndTime($endTime);

        $this->assertSame($this->event, $result);
        $this->assertEquals($endTime, $this->event->getEndTime());

        $this->event->setEndTime(null);
        $this->assertNull($this->event->getEndTime());
    }

    /**
     * Test setting and getting the location.
     */
    public function testLocationGetAndSet(): void
    {
        $location = 'Conference Room A';
        $result = $this->event->setLocation($location);

        $this->assertSame($this->event, $result);
        $this->assertEquals($location, $this->event->getLocation());
        $this->assertIsString($this->event->getLocation());

        $this->event->setLocation(null);
        $this->assertNull($this->event->getLocation());
    }

    /**
     * Test setting and getting the isAllDay status.
     */
    public function testIsAllDayGetAndSet(): void
    {
        $this->assertFalse($this->event->isAllDay());

        $result = $this->event->setIsAllDay(true);
        $this->assertSame($this->event, $result);
        $this->assertTrue($this->event->isAllDay());

        $this->event->setIsAllDay(false);
        $this->assertFalse($this->event->isAllDay());
    }

    /**
     * Test setting and getting the category.
     */
    public function testCategoryGetAndSet(): void
    {
        $category = $this->createMock(Category::class);
        $result = $this->event->setCategory($category);

        $this->assertSame($this->event, $result);
        $this->assertSame($category, $this->event->getCategory());

        $this->event->setCategory(null);
        $this->assertNull($this->event->getCategory());
    }

    /**
     * Test tags collection initialization.
     */
    public function testTagsInitialization(): void
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->event->getTags());
        $this->assertTrue($this->event->getTags()->isEmpty());
    }

    /**
     * Test addTag method.
     */
    public function testAddTag(): void
    {
        $tag1 = $this->createMock(Tag::class);
        $tag2 = $this->createMock(Tag::class);

        $this->assertCount(0, $this->event->getTags());

        $result1 = $this->event->addTag($tag1);
        $this->assertSame($this->event, $result1);
        $this->assertTrue($this->event->getTags()->contains($tag1));
        $this->assertCount(1, $this->event->getTags());

        $result2 = $this->event->addTag($tag1);
        $this->assertSame($this->event, $result2);
        $this->assertCount(1, $this->event->getTags());

        $this->event->addTag($tag2);
        $this->assertTrue($this->event->getTags()->contains($tag2));
        $this->assertCount(2, $this->event->getTags());
    }

    /**
     * Test removeTag method.
     */
    public function testRemoveTag(): void
    {
        $tag1 = $this->createMock(Tag::class);
        $tag2 = $this->createMock(Tag::class);
        $tag3 = $this->createMock(Tag::class);

        $this->event->addTag($tag1);
        $this->event->addTag($tag2);
        $this->assertCount(2, $this->event->getTags());

        $result1 = $this->event->removeTag($tag1);
        $this->assertSame($this->event, $result1);
        $this->assertFalse($this->event->getTags()->contains($tag1));
        $this->assertCount(1, $this->event->getTags());
        $this->assertTrue($this->event->getTags()->contains($tag2));

        $result2 = $this->event->removeTag($tag3);
        $this->assertSame($this->event, $result2);
        $this->assertCount(1, $this->event->getTags());

        $this->event->removeTag($tag2);
        $this->assertFalse($this->event->getTags()->contains($tag2));
        $this->assertCount(0, $this->event->getTags());
        $this->assertTrue($this->event->getTags()->isEmpty());
    }

    /**
     * Test setting and getting the author.
     */
    public function testAuthorGetAndSet(): void
    {
        $author = $this->createMock(User::class);
        $result = $this->event->setAuthor($author);

        $this->assertSame($this->event, $result);
        $this->assertSame($author, $this->event->getAuthor());

        $this->event->setAuthor(null);
        $this->assertNull($this->event->getAuthor());
    }

    /**
     * Test setting and getting the status.
     */
    public function testStatusGetAndSet(): void
    {
        // The status is now initialized in setUp(), so this initial assertion is valid.
        $this->assertEquals(EventStatus::PERSONAL, $this->event->getStatus());

        $status = EventStatus::WORK;
        $result = $this->event->setStatus($status);

        $this->assertSame($this->event, $result);
        $this->assertEquals($status, $this->event->getStatus());

        $status = EventStatus::IMPORTANT;
        $this->event->setStatus($status);
        $this->assertEquals($status, $this->event->getStatus());
    }
}
