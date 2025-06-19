<?php

/**
 * Tag entity Test.
 */

namespace App\Tests\Unit\Entity;

use App\Entity\Tag;
use PHPUnit\Framework\TestCase;

/**
 * Class TagTest.
 */
class TagTest extends TestCase
{
    /**
     * Test get and set ID.
     */
    public function testGetAndSetId(): void
    {
        $tag = new Tag();
        $this->assertNull($tag->getId());
    }

    /**
     * Test get and set name.
     */
    public function testGetAndSetName(): void
    {
        $tag = new Tag();
        $name = 'Test Tag Name';
        $tag->setName($name);

        $this->assertEquals($name, $tag->getName());
    }

    /**
     * Test get and set createdAt.
     */
    public function testGetAndSetCreatedAt(): void
    {
        $tag = new Tag();
        $createdAt = new \DateTimeImmutable();
        $tag->setCreatedAt($createdAt);

        $this->assertEquals($createdAt, $tag->getCreatedAt());
    }

    /**
     * Test get and set updatedAt.
     */
    public function testGetAndSetUpdatedAt(): void
    {
        $tag = new Tag();
        $updatedAt = new \DateTimeImmutable();
        $tag->setUpdatedAt($updatedAt);

        $this->assertEquals($updatedAt, $tag->getUpdatedAt());
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
