<?php

/**
 * Avatar repository Test.
 */

namespace App\Tests\Unit\Repository;

use App\Entity\Avatar;
use App\Repository\AvatarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class Avatar repository Test.
 */
class AvatarRepositoryTest extends TestCase
{
    private MockObject|EntityManagerInterface $entityManager;
    private AvatarRepository $avatarRepository;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $managerRegistry->method('getManagerForClass')
            ->with(Avatar::class)
            ->willReturn($this->entityManager);
        $managerRegistry->method('getManager')
            ->willReturn($this->entityManager);

        $mockClassMetadata = $this->createMock(ClassMetadata::class);
        $mockClassMetadata->name = Avatar::class;

        $this->entityManager->method('getClassMetadata')
            ->with(Avatar::class)
            ->willReturn($mockClassMetadata);

        $this->avatarRepository = new AvatarRepository($managerRegistry);
    }

    /**
     * Test the save method of the AvatarRepository.
     */
    public function testSave(): void
    {
        $avatar = $this->createMock(Avatar::class);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($avatar);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->avatarRepository->save($avatar);
    }

    /**
     * Test the delete method of the AvatarRepository.
     */
    public function testDelete(): void
    {
        $avatar = $this->createMock(Avatar::class);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($avatar);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->avatarRepository->delete($avatar);
    }
}
