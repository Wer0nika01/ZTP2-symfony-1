<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Tag;
use PHPUnit\Framework\TestCase;

/**
 * Class TagTest.
 */
class TagTest extends TestCase
{
    public function testGetAndSetId(): void
    {
        $tag = new Tag();
        // ID is typically set by the ORM, so we can't directly set it.
        // We can only assert it's initially null or check its type.
        $this->assertNull($tag->getId());
    }

    public function testGetAndSetName(): void
    {
        $tag = new Tag();
        $name = 'Test Tag Name';
        $tag->setName($name);

        $this->assertEquals($name, $tag->getName());
    }

    public function testGetAndSetCreatedAt(): void
    {
        $tag = new Tag();
        $createdAt = new \DateTimeImmutable();
        $tag->setCreatedAt($createdAt);

        $this->assertEquals($createdAt, $tag->getCreatedAt());
    }

    public function testGetAndSetUpdatedAt(): void
    {
        $tag = new Tag();
        $updatedAt = new \DateTimeImmutable();
        $tag->setUpdatedAt($updatedAt);

        $this->assertEquals($updatedAt, $tag->getUpdatedAt());
    }

    public function testGetAndSetSlug(): void
    {
        $tag = new Tag();
        $slug = 'test-tag-slug';
        $tag->setSlug($slug);

        $this->assertEquals($slug, $tag->getSlug());
        $this->assertInstanceOf(Tag::class, $tag->setSlug($slug), 'Setter for slug should return $this');
    }

    /**
     * Test initial values.
     */
    public function testInitialValues(): void
    {
        $tag = new Tag();

        $this->assertNull($tag->getId());
        $this->assertNull($tag->getName());
        $this->assertNull($tag->getCreatedAt());
        $this->assertNull($tag->getUpdatedAt());
        $this->assertNull($tag->getSlug());
    }
}
