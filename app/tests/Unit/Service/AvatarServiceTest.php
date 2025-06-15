<?php

namespace App\Tests\Unit\Service;

use App\Entity\Avatar;
use App\Entity\User;
use App\Repository\AvatarRepository;
use App\Service\AvatarService; // The service under test
use App\Service\FileUploadServiceInterface; // Dependency
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem; // Dependency
use Symfony\Component\HttpFoundation\File\UploadedFile; // Dependency

class AvatarServiceTest extends TestCase
{
    private string $targetDirectory;
    private MockObject|AvatarRepository $avatarRepository;
    private MockObject|FileUploadServiceInterface $fileUploadService;
    private MockObject|Filesystem $filesystem;
    private AvatarService $avatarService;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize dummy value for targetDirectory
        $this->targetDirectory = '/path/to/uploads';

        // Mock all service dependencies
        $this->avatarRepository = $this->createMock(AvatarRepository::class);
        $this->fileUploadService = $this->createMock(FileUploadServiceInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);

        // Instantiate the AvatarService with its mocked dependencies
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
        // Mock entities and uploaded file
        $uploadedFile = $this->createMock(UploadedFile::class);
        $avatar = $this->createMock(Avatar::class);
        $user = $this->createMock(User::class);
        $filename = 'new_avatar.jpg';

        // Configure expectations for FileUploadService::upload()
        $this->fileUploadService->expects($this->once())
            ->method('upload')
            ->with($uploadedFile)
            ->willReturn($filename);

        // Configure expectations for Avatar entity setters
        $avatar->expects($this->once())
            ->method('setUser')
            ->with($user);
        $avatar->expects($this->once())
            ->method('setFilename')
            ->with($filename);

        // Configure expectations for AvatarRepository::save()
        $this->avatarRepository->expects($this->once())
            ->method('save')
            ->with($avatar);

        // Call the method under test
        $this->avatarService->create($uploadedFile, $avatar, $user);
    }

    /**
     * Test the update method when an old avatar exists (filename is not null).
     */
    public function testUpdateWhenOldAvatarExists(): void
    {
        // Mock entities and uploaded file
        $uploadedFile = $this->createMock(UploadedFile::class);
        $avatar = $this->createMock(Avatar::class);
        $user = $this->createMock(User::class);
        $oldFilename = 'old_avatar.jpg';
        $newFilename = 'new_avatar.jpg';

        // Configure avatar to return an old filename
        $avatar->expects($this->atLeastOnce()) // Called by getFilename and then implicitly by create->setFilename
        ->method('getFilename')
            ->willReturn($oldFilename);

        // Expect Filesystem::remove() to be called for the old file
        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with($this->targetDirectory . '/' . $oldFilename);

        // Expect FileUploadService::upload() to be called for the new file (as create is called internally)
        $this->fileUploadService->expects($this->once())
            ->method('upload')
            ->with($uploadedFile)
            ->willReturn($newFilename);

        // Expect Avatar setters (from the internal create call)
        $avatar->expects($this->once())
            ->method('setUser')
            ->with($user);
        $avatar->expects($this->once())
            ->method('setFilename')
            ->with($newFilename);

        // Expect AvatarRepository::save() (from the internal create call)
        $this->avatarRepository->expects($this->once())
            ->method('save')
            ->with($avatar);

        // Call the method under test
        $this->avatarService->update($uploadedFile, $avatar, $user);
    }

    /**
     * Test the update method when no old avatar exists (filename is null).
     */
    public function testUpdateWhenOldAvatarDoesNotExist(): void
    {
        // Mock entities and uploaded file
        $uploadedFile = $this->createMock(UploadedFile::class);
        $avatar = $this->createMock(Avatar::class);
        $user = $this->createMock(User::class);
        // We do NOT expect a new filename to be uploaded or saved in this specific test path

        // Configure avatar to return null for filename (no existing avatar)
        $avatar->expects($this->once())
            ->method('getFilename')
            ->willReturn(null);

        // Since getFilename() returns null, the if-block in update() should NOT be entered.
        // Therefore, none of the calls that happen inside that block (including create()) should occur.

        // Expect FileUploadService::upload() NOT to be called
        $this->fileUploadService->expects($this->never())
            ->method('upload');

        // Expect Avatar setters (from the internal create call) NOT to be called
        $avatar->expects($this->never())
            ->method('setUser');
        $avatar->expects($this->never())
            ->method('setFilename');

        // Expect AvatarRepository::save() NOT to be called
        $this->avatarRepository->expects($this->never()) // FIX: Changed to never()
        ->method('save');

        // Expect Filesystem::remove() NOT to be called (already correctly set)
        $this->filesystem->expects($this->never())
            ->method('remove');


        // Call the method under test
        $this->avatarService->update($uploadedFile, $avatar, $user);
    }

    /**
     * Test the delete method when the file exists on the filesystem.
     */
    public function testDeleteWhenFileExists(): void
    {
        // Mock Avatar entity
        $avatar = $this->createMock(Avatar::class);
        $filename = 'to_be_deleted.jpg';

        // Configure avatar to return a filename
        $avatar->method('getFilename')->willReturn($filename);

        // Expect Filesystem::exists() to be called and return true
        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($this->targetDirectory . '/' . $filename)
            ->willReturn(true);

        // Expect Filesystem::remove() to be called
        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with($this->targetDirectory . '/' . $filename);

        // Expect AvatarRepository::delete() to be called
        $this->avatarRepository->expects($this->once())
            ->method('delete')
            ->with($avatar);

        // Call the method under test
        $this->avatarService->delete($avatar);
    }

    /**
     * Test the delete method when the file does NOT exist on the filesystem.
     */
    public function testDeleteWhenFileDoesNotExist(): void
    {
        // Mock Avatar entity
        $avatar = $this->createMock(Avatar::class);
        $filename = 'non_existent.jpg';

        // Configure avatar to return a filename
        $avatar->method('getFilename')->willReturn($filename);

        // Expect Filesystem::exists() to be called and return false
        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($this->targetDirectory . '/' . $filename)
            ->willReturn(false);

        // Expect Filesystem::remove() NOT to be called
        $this->filesystem->expects($this->never())
            ->method('remove');

        // Expect AvatarRepository::delete() to be called
        $this->avatarRepository->expects($this->once())
            ->method('delete')
            ->with($avatar);

        // Call the method under test
        $this->avatarService->delete($avatar);
    }

    /**
     * Test the delete method when the avatar has no filename (null).
     */
    public function testDeleteWhenNoFilename(): void
    {
        // Mock Avatar entity
        $avatar = $this->createMock(Avatar::class);

        // Configure avatar to return null for filename
        $avatar->method('getFilename')->willReturn(null);

        // Expect Filesystem::exists() NOT to be called
        $this->filesystem->expects($this->never())
            ->method('exists');

        // Expect Filesystem::remove() NOT to be called
        $this->filesystem->expects($this->never())
            ->method('remove');

        // Expect AvatarRepository::delete() to be called
        $this->avatarRepository->expects($this->once())
            ->method('delete')
            ->with($avatar);

        // Call the method under test
        $this->avatarService->delete($avatar);
    }
}
