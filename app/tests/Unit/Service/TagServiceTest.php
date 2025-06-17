<?php

namespace App\Tests\Unit\Service;

use App\Entity\Tag;
use App\Repository\TagRepository;
use App\Service\TagService;
use Doctrine\ORM\NonUniqueResultException;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\QueryBuilder; // Needed for queryAll mock

class TagServiceTest extends TestCase
{
    private $tagRepository;
    private $paginator;
    private $tagService;

    protected function setUp(): void
    {
        // Utwórz mocki dla zależności
        // Jawnie dodaj 'findOneByName' i 'findOneById' do addMethods(),
        // ponieważ są one prawdopodobnie dostarczane dynamicznie (np. przez __call)
        // i wymagają jawnego mockowania.
        // Inne metody, takie jak 'queryAll', 'save', 'delete', są zakładane jako jawnie
        // zadeklarowane i zostaną automatycznie zamockowane przez getMock().
        $this->tagRepository = $this->getMockBuilder(TagRepository::class)
            ->disableOriginalConstructor() // Wyłącz oryginalny konstruktor, ponieważ może wymagać EntityManager
            ->addMethods(['findOneByName', 'findOneById']) // Dodaj tylko te metody, na które PHPUnit narzeka
            ->getMock();

        $this->paginator = $this->createMock(PaginatorInterface::class);

        // Utwórz instancję serwisu z mockami
        $this->tagService = new TagService($this->tagRepository, $this->paginator);
    }

    public function testFindOneByName(): void
    {
        $tagName = 'Test Tag';
        $tag = $this->createMock(Tag::class);

        // Oczekuj, że findOneByName na repozytorium zostanie wywołane i zwróci tag
        $this->tagRepository->expects($this->once())
            ->method('findOneByName')
            ->with($tagName)
            ->willReturn($tag);

        $result = $this->tagService->findOneByName($tagName);

        $this->assertInstanceOf(Tag::class, $result);
        $this->assertEquals($tag, $result);
    }

    public function testFindOneByNameNotFound(): void
    {
        $tagName = 'Non Existent Tag';

        // Oczekuj, że findOneByName na repozytorium zostanie wywołane i zwróci null
        $this->tagRepository->expects($this->once())
            ->method('findOneByName')
            ->with($tagName)
            ->willReturn(null);

        $result = $this->tagService->findOneByName($tagName);

        $this->assertNull($result);
    }

    public function testFindOneById(): void
    {
        $tagId = 1;
        $tag = $this->createMock(Tag::class);

        // Oczekuj, że findOneById na repozytorium zostanie wywołane i zwróci tag
        $this->tagRepository->expects($this->once())
            ->method('findOneById')
            ->with($tagId)
            ->willReturn($tag);

        $result = $this->tagService->findOneById($tagId);

        $this->assertInstanceOf(Tag::class, $result);
        $this->assertEquals($tag, $result);
    }

    public function testFindOneByIdNotFound(): void
    {
        $tagId = 999;

        // Oczekuj, że findOneById na repozytorium zostanie wywołane i zwróci null
        $this->tagRepository->expects($this->once())
            ->method('findOneById')
            ->with($tagId)
            ->willReturn(null);

        $result = $this->tagService->findOneById($tagId);

        $this->assertNull($result);
    }

    public function testFindOneByIdThrowsNonUniqueResultException(): void
    {
        $tagId = 1;
        // Oczekuj, że findOneById rzuci NonUniqueResultException
        $this->tagRepository->expects($this->once())
            ->method('findOneById')
            ->with($tagId)
            ->willThrowException(new NonUniqueResultException());

        // Oczekuj, że zostanie rzucony ten konkretny wyjątek
        $this->expectException(NonUniqueResultException::class);

        $this->tagService->findOneById($tagId);
    }
}
