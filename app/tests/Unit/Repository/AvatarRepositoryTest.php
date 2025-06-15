<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Avatar;
use App\Repository\AvatarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\Mapping\ClassMetadata; // Required for mocking internal Doctrine calls

class AvatarRepositoryTest extends TestCase
{
    private MockObject|ManagerRegistry $managerRegistry;
    private MockObject|EntityManagerInterface $entityManager;
    private AvatarRepository $avatarRepository; // Changed back to concrete type

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock for EntityManagerInterface
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // Create a mock for ManagerRegistry
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        // FIX: Configure the ManagerRegistry mock to correctly return the mocked EntityManager.
        // This is crucial for ServiceEntityRepository's constructor.
        $this->managerRegistry->method('getManagerForClass')
            ->with(Avatar::class)
            ->willReturn($this->entityManager);
        $this->managerRegistry->method('getManager')
            ->willReturn($this->entityManager);

        // When testing ServiceEntityRepository (or a class extending it),
        // we often need to mock the internal ClassMetadataFactory that Doctrine uses.
        // The ServiceEntityRepository internally tries to get ClassMetadata for the entity.
        $mockClassMetadata = $this->createMock(ClassMetadata::class);
        $mockClassMetadata->name = Avatar::class; // This property is accessed, so it needs to be set.

        $this->entityManager->method('getClassMetadata')
            ->with(Avatar::class)
            ->willReturn($mockClassMetadata);


        // FIX: Instantiate the AvatarRepository with the mocked registry.
        // We are NOT disabling the original constructor now.
        $this->avatarRepository = new AvatarRepository($this->managerRegistry);
    }

    /**
     * Test the save method of the AvatarRepository.
     */
    public function testSave(): void
    {
        // Create a mock Avatar entity
        $avatar = $this->createMock(Avatar::class);

        // Expect the EntityManager's persist method to be called once with the avatar
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($avatar);

        // Expect the EntityManager's flush method to be called once
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Call the save method on the repository
        $this->avatarRepository->save($avatar);
    }

    /**
     * Test the delete method of the AvatarRepository.
     */
    public function testDelete(): void
    {
        // Create a mock Avatar entity
        $avatar = $this->createMock(Avatar::class);

        // Expect the EntityManager's remove method to be called once with the avatar
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($avatar);

        // Expect the EntityManager's flush method to be called once
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Call the delete method on the repository
        $this->avatarRepository->delete($avatar);
    }
}
