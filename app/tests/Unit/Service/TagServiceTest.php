<?php

/**
 * Tag service Test.
 */

namespace App\Tests\Unit\Service;

use App\Entity\Tag;
use App\Repository\TagRepository;
use App\Service\TagService;
use Doctrine\ORM\NonUniqueResultException;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class Tag service Test.
 */
class TagServiceTest extends TestCase
{
    private $tagRepository;
    private $tagService;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->tagRepository = $this->getMockBuilder(TagRepository::class)
            ->disableOriginalConstructor()
            ->addMethods(['findOneByName', 'findOneById'])
            ->getMock();

        $paginator = $this->createMock(PaginatorInterface::class);

        $this->tagService = new TagService($this->tagRepository, $paginator);
    }

    /**
     * Test find one by name.
     */
    public function testFindOneByName(): void
    {
        $tagName = 'Test Tag';
        $tag = $this->createMock(Tag::class);

        $this->tagRepository->expects($this->once())
            ->method('findOneByName')
            ->with($tagName)
            ->willReturn($tag);

        $result = $this->tagService->findOneByName($tagName);

        $this->assertInstanceOf(Tag::class, $result);
        $this->assertEquals($tag, $result);
    }

    /**
     * Test find one by name not found.
     */
    public function testFindOneByNameNotFound(): void
    {
        $tagName = 'Non Existent Tag';

        $this->tagRepository->expects($this->once())
            ->method('findOneByName')
            ->with($tagName)
            ->willReturn(null);

        $result = $this->tagService->findOneByName($tagName);

        $this->assertNull($result);
    }

    /**
     * Test find one by ID.
     */
    public function testFindOneById(): void
    {
        $tagId = 1;
        $tag = $this->createMock(Tag::class);

        $this->tagRepository->expects($this->once())
            ->method('findOneById')
            ->with($tagId)
            ->willReturn($tag);

        $result = $this->tagService->findOneById($tagId);

        $this->assertInstanceOf(Tag::class, $result);
        $this->assertEquals($tag, $result);
    }

    /**
     * Test find one by ID not found.
     */
    public function testFindOneByIdNotFound(): void
    {
        $tagId = 999;

        $this->tagRepository->expects($this->once())
            ->method('findOneById')
            ->with($tagId)
            ->willReturn(null);

        $result = $this->tagService->findOneById($tagId);

        $this->assertNull($result);
    }

    /**
     * Test find one by ID throws non-unique result exception.
     */
    public function testFindOneByIdThrowsNonUniqueResultException(): void
    {
        $tagId = 1;
        $this->tagRepository->expects($this->once())
            ->method('findOneById')
            ->with($tagId)
            ->willThrowException(new NonUniqueResultException());

        $this->expectException(NonUniqueResultException::class);

        $this->tagService->findOneById($tagId);
    }
}
