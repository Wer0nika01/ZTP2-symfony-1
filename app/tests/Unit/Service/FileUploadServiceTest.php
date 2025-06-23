<?php

/**
 * File upload service Test.
 */

namespace App\Tests\Unit\Service;

use App\Service\FileUploadService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

/**
 * Class File upload service Test.
 */
class FileUploadServiceTest extends TestCase
{
    private string $targetDirectory;
    private MockObject|SluggerInterface $slugger;
    private FileUploadService $fileUploadService;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->targetDirectory = '/path/to/test/uploads';

        $this->slugger = $this->createMock(SluggerInterface::class);

        $this->fileUploadService = new FileUploadService(
            $this->targetDirectory,
            $this->slugger
        );
    }

    /**
     * Test the upload method for a successful file upload.
     */
    public function testUploadSuccess(): void
    {
        $originalFilename = 'my_document.pdf';
        $filenameWithoutExtension = 'my_document';
        $safeFilename = 'my-document';
        $extension = 'pdf';

        $uploadedFile = $this->createMock(UploadedFile::class);

        $uploadedFile->expects($this->once())
            ->method('guessExtension')
            ->willReturn($extension);
        $uploadedFile->expects($this->once())
            ->method('getClientOriginalName')
            ->willReturn($originalFilename);

        $this->slugger->expects($this->once())
            ->method('slug')
            ->with($filenameWithoutExtension)
            ->willReturn(new UnicodeString($safeFilename));

        $uploadedFile->expects($this->once())
            ->method('move')
            ->with(
                $this->targetDirectory,
                $this->logicalAnd(
                    $this->stringContains($safeFilename.'-'),
                    $this->stringEndsWith('.'.$extension)
                )
            );

        $resultFileName = $this->fileUploadService->upload($uploadedFile);

        $this->assertIsString($resultFileName);
        $this->assertStringStartsWith($safeFilename.'-', $resultFileName);
        $this->assertStringEndsWith('.'.$extension, $resultFileName);
    }

    /**
     * Test the upload method when the uploaded file has no extension.
     */
    public function testUploadNoExtension(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);

        $uploadedFile->expects($this->once())
            ->method('guessExtension')
            ->willReturn(null);
        $uploadedFile->expects($this->never())->method('getClientOriginalName');
        $this->slugger->expects($this->never())->method('slug');

        $uploadedFile->expects($this->once())
            ->method('move')
            ->with($this->targetDirectory, '');

        $resultFileName = $this->fileUploadService->upload($uploadedFile);

        $this->assertEquals('', $resultFileName);
    }

    /**
     * Test the upload method when a FileException occurs during move.
     */
    public function testUploadFileException(): void
    {
        $originalFilename = 'corrupt_file.png';
        $safeFilename = 'corrupt-file';
        $extension = 'png';

        $uploadedFile = $this->createMock(UploadedFile::class);

        $uploadedFile->method('guessExtension')->willReturn($extension);
        $uploadedFile->method('getClientOriginalName')->willReturn($originalFilename);
        $this->slugger->method('slug')->willReturn(new UnicodeString($safeFilename));

        $uploadedFile->expects($this->once())
            ->method('move')
            ->willThrowException(new FileException('Failed to move file.'));

        $resultFileName = $this->fileUploadService->upload($uploadedFile);

        $this->assertIsString($resultFileName);
        $this->assertStringStartsWith($safeFilename.'-', $resultFileName);
        $this->assertStringEndsWith('.'.$extension, $resultFileName);
    }

    /**
     * Test the getTargetDirectory method.
     */
    public function testGetTargetDirectory(): void
    {
        $this->assertEquals($this->targetDirectory, $this->fileUploadService->getTargetDirectory());
    }
}
