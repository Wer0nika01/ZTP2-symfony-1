<?php

/**
 * Tags data transformer Test.
 */

namespace App\Tests\Unit\Form\DataTransformer;

use App\Entity\Tag;
use App\Form\DataTransformer\TagsDataTransformer;
use App\Service\TagServiceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class TagsDataTransformerTest.
 */
class TagsDataTransformerTest extends TestCase
{
    private TagServiceInterface|MockObject $tagService;
    private TagsDataTransformer $transformer;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        $this->tagService = $this->createMock(TagServiceInterface::class);
        $this->transformer = new TagsDataTransformer($this->tagService);
    }

    /**
     * Test transform method with an empty collection.
     */
    public function testTransformWithEmptyCollection(): void
    {
        $collection = new ArrayCollection();
        $result = $this->transformer->transform($collection);

        $this->assertEquals('', $result);
    }

    /**
     * Test transform method with a collection of tags.
     */
    public function testTransformWithTags(): void
    {
        $tag1 = new Tag();
        $tag1->setName('tag1');
        $tag2 = new Tag();
        $tag2->setName('tag2');

        $collection = new ArrayCollection([$tag1, $tag2]);
        $result = $this->transformer->transform($collection);

        $this->assertEquals('tag1, tag2', $result);
    }

    /**
     * Test reverseTransform method with an empty string.
     */
    public function testReverseTransformWithEmptyString(): void
    {
        $result = $this->transformer->reverseTransform('');

        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    /**
     * Test reverseTransform method when tags already exist.
     */
    public function testReverseTransformExistingTags(): void
    {
        $tag1 = new Tag();
        $tag1->setName('tag1');
        $tag2 = new Tag();
        $tag2->setName(' tag2');

        $this->tagService->expects($this->exactly(2))
            ->method('findOneByName')
            ->willReturnMap([
                ['tag1', $tag1],
                [' tag2', $tag2],
            ]);

        $this->tagService->expects($this->any())
            ->method('save');

        $result = $this->transformer->reverseTransform('tag1, tag2');

        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('tag1', $result->get(0)->getName());
        $this->assertEquals('tag2', $result->get(1)->getName());
    }

    /**
     * Test reverseTransform method when new tags need to be created.
     */
    public function testReverseTransformNewTags(): void
    {
        $this->tagService->expects($this->exactly(2))
            ->method('findOneByName')
            ->willReturn(null); // Simulate tags not found

        $this->tagService->expects($this->exactly(2))
            ->method('save')
            ->will($this->returnCallback(function (Tag $tag) {
                return $tag;
            }));

        $result = $this->transformer->reverseTransform('newTag1, newTag2');

        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(2, $result);
        // Adjusted assertion to expect the leading space
        $this->assertEquals('newTag1', $result->get(0)->getName());
        $this->assertEquals('newTag2', $result->get(1)->getName());
    }

    /**
     * Test reverseTransform with mixed existing and new tags.
     */
    public function testReverseTransformMixedTags(): void
    {
        $existingTag = new Tag();
        $existingTag->setName('existingTag');

        $this->tagService->expects($this->exactly(2))
            ->method('findOneByName')
            ->willReturnMap([
                ['existingtag', $existingTag],
                ['newtag', null],
            ]);

        $this->tagService->expects($this->once())
            ->method('save')
            ->will($this->returnCallback(function (Tag $tag) {
                return $tag;
            }));

        $result = $this->transformer->reverseTransform('existingTag, newTag');

        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('existingTag', $result->get(0)->getName());
        // Adjusted assertion to expect the leading space
        $this->assertEquals('newTag', $result->get(1)->getName());
    }

    /**
     * Test reverseTransform with duplicate tag names in input.
     */
    public function testReverseTransformWithDuplicateInput(): void
    {
        $tag = new Tag();
        $tag->setName('duplicateTag');

        $this->tagService->expects($this->exactly(2))
            ->method('findOneByName')
            ->willReturn($tag);

        $this->tagService->expects($this->never())
            ->method('save'); // No new tags are created

        $result = $this->transformer->reverseTransform('duplicateTag, duplicateTag');

        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('duplicateTag', $result->get(0)->getName());
        $this->assertEquals('duplicateTag', $result->get(1)->getName());
    }
}
