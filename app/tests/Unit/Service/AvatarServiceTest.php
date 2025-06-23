<?php

/**
 * Avatar service Test.
 */

namespace App\Tests\Unit\Service;

use App\Entity\Avatar;
use App\Entity\User;
use App\Repository\AvatarRepository;
use App\Service\AvatarService;
use App\Service\FileUploadServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class Avatar service Test.
 */
class AvatarServiceTest extends TestCase
{
    private string $targetDirectory;
    private MockObject|AvatarRepository $avatarRepository;
    private MockObject|FileUploadServiceInterface $fileUploadService;
    private MockObject|Filesystem $filesystem;
    private AvatarService $avatarService;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->targetDirectory = '/path/to/uploads';

        $this->avatarRepository = $this->createMock(AvatarRepository::class);
        $this->fileUploadService = $this->createMock(FileUploadServiceInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->avatarService = new AvatarService(
            $this->targetDirectory,
            $this->avatarRepository,
            $this->fileUploadService,
            $this->filesystem
        );
    }

    /**
     * Test the create method for a new avatar.
     */
    public function testCreate(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $avatar = $this->createMock(Avatar::class);
        $user = $this->createMock(User::class);
        $filename = 'new_avatar.jpg';

        $this->fileUploadService->expects($this->once())
            ->method('upload')
            ->with($uploadedFile)
            ->willReturn($filename);

        $avatar->expects($this->once())
            ->method('setUser')
            ->with($user);
        $avatar->expects($this->once())
            ->method('setFilename')
            ->with($filename);

        $this->avatarRepository->expects($this->once())
            ->method('save')
            ->with($avatar);

        $this->avatarService->create($uploadedFile, $avatar, $user);
    }

    /**
     * Test the update method when an old avatar exists (filename is not null).
     */
    public function testUpdateWhenOldAvatarExists(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $avatar = $this->createMock(Avatar::class);
        $user = $this->createMock(User::class);
        $oldFilename = 'old_avatar.jpg';
        $newFilename = 'new_avatar.jpg';

        $avatar->expects($this->atLeastOnce())
        ->method('getFilename')
            ->willReturn($oldFilename);

        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with($this->targetDirectory.'/'.$oldFilename);

        $this->fileUploadService->expects($this->once())
            ->method('upload')
            ->with($uploadedFile)
            ->willReturn($newFilename);

        $avatar->expects($this->once())
            ->method('setUser')
            ->with($user);
        $avatar->expects($this->once())
            ->method('setFilename')
            ->with($newFilename);

        $this->avatarRepository->expects($this->once())
            ->method('save')
            ->with($avatar);

        $this->avatarService->update($uploadedFile, $avatar, $user);
    }

    /**
     * Test the update method when no old avatar exists (filename is null).
     */
    public function testUpdateWhenOldAvatarDoesNotExist(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $avatar = $this->createMock(Avatar::class);
        $user = $this->createMock(User::class);

        $avatar->expects($this->once())
            ->method('getFilename')
            ->willReturn(null);

        $this->fileUploadService->expects($this->never())
            ->method('upload');

        $avatar->expects($this->never())
            ->method('setUser');
        $avatar->expects($this->never())
            ->method('setFilename');

        $this->avatarRepository->expects($this->never())
        ->method('save');

        $this->filesystem->expects($this->never())
            ->method('remove');


        $this->avatarService->update($uploadedFile, $avatar, $user);
    }

    /**
     * Test the delete method when the file exists on the filesystem.
     */
    public function testDeleteWhenFileExists(): void
    {
        $avatar = $this->createMock(Avatar::class);
        $filename = 'to_be_deleted.jpg';

        $avatar->method('getFilename')->willReturn($filename);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($this->targetDirectory.'/'.$filename)
            ->willReturn(true);

        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with($this->targetDirectory.'/'.$filename);

        $this->avatarRepository->expects($this->once())
            ->method('delete')
            ->with($avatar);

        $this->avatarService->delete($avatar);
    }

    /**
     * Test the delete method when the file does NOT exist on the filesystem.
     */
    public function testDeleteWhenFileDoesNotExist(): void
    {
        $avatar = $this->createMock(Avatar::class);
        $filename = 'non_existent.jpg';

        $avatar->method('getFilename')->willReturn($filename);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($this->targetDirectory.'/'.$filename)
            ->willReturn(false);

        $this->filesystem->expects($this->never())
            ->method('remove');

        $this->avatarRepository->expects($this->once())
            ->method('delete')
            ->with($avatar);

        $this->avatarService->delete($avatar);
    }

    /**
     * Test the delete method when the avatar has no filename (null).
     */
    public function testDeleteWhenNoFilename(): void
    {
        $avatar = $this->createMock(Avatar::class);

        $avatar->method('getFilename')->willReturn(null);

        $this->filesystem->expects($this->never())
            ->method('exists');

        $this->filesystem->expects($this->never())
            ->method('remove');

        $this->avatarRepository->expects($this->once())
            ->method('delete')
            ->with($avatar);

        $this->avatarService->delete($avatar);
    }
}
