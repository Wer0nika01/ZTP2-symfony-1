<?php

namespace App\Tests\Unit\Service;

use App\Service\FileUploadService; // The service under test
use App\Service\FileUploadServiceInterface; // Interface for type hinting
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface; // Corrected namespace for SluggerInterface
use Symfony\Component\String\UnicodeString; // FIX: Added import for UnicodeString

class FileUploadServiceTest extends TestCase
{
    private string $targetDirectory;
    private MockObject|SluggerInterface $slugger;
    private FileUploadService $fileUploadService;

    protected function setUp(): void
    {
        parent::setUp();

        // Define a dummy target directory for testing
        $this->targetDirectory = '/path/to/test/uploads';

        // Mock the SluggerInterface dependency
        $this->slugger = $this->createMock(SluggerInterface::class);

        // Instantiate the FileUploadService with its mocked dependencies
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
        // Define expected values for the file
        $originalFilename = 'my_document.pdf';
        $filenameWithoutExtension = 'my_document'; // Added for clarity
        $safeFilename = 'my-document';
        $extension = 'pdf';
        // $generatedUniqueId = '12345'; // Not directly used in assertions, removed for clarity

        // Mock the UploadedFile object
        $uploadedFile = $this->createMock(UploadedFile::class);

        // Configure the UploadedFile mock's behavior
        $uploadedFile->expects($this->once())
            ->method('guessExtension')
            ->willReturn($extension);
        $uploadedFile->expects($this->once())
            ->method('getClientOriginalName')
            ->willReturn($originalFilename);

        // Configure the Slugger mock's behavior
        $this->slugger->expects($this->once())
            ->method('slug')
            // FIX: Expect the slug method to be called with the filename *without* extension
            ->with($filenameWithoutExtension)
            // Return a UnicodeString instance as expected by the service
            ->willReturn(new UnicodeString($safeFilename));

        // Expect the 'move' method to be called with the target directory and a generated filename
        // Use logicalAnd to combine constraints correctly
        $uploadedFile->expects($this->once())
            ->method('move')
            ->with(
                $this->targetDirectory,
                $this->logicalAnd(
                    $this->stringContains($safeFilename . '-'),
                    $this->stringEndsWith('.' . $extension)
                )
            );

        // Call the method under test
        $resultFileName = $this->fileUploadService->upload($uploadedFile);

        // Assert that the returned filename matches the expected pattern
        // (safeFilename-uniqueId.extension)
        $this->assertIsString($resultFileName);
        $this->assertStringStartsWith($safeFilename . '-', $resultFileName);
        $this->assertStringEndsWith('.' . $extension, $resultFileName);
        // The unique part in the middle is hard to assert exactly, but start/end covers it.
    }

    /**
     * Test the upload method when the uploaded file has no extension.
     */
    public function testUploadNoExtension(): void
    {
        // Mock the UploadedFile object
        $uploadedFile = $this->createMock(UploadedFile::class);

        // Configure the UploadedFile mock to return null for extension
        $uploadedFile->expects($this->once())
            ->method('guessExtension')
            ->willReturn(null);
        // getClientOriginalName and slug should not be called if extension is null
        $uploadedFile->expects($this->never())->method('getClientOriginalName');
        $this->slugger->expects($this->never())->method('slug');

        // Expect 'move' to be called with an empty filename as a result of no extension,
        // as per the current service logic.
        $uploadedFile->expects($this->once())
            ->method('move')
            ->with($this->targetDirectory, '');

        // Call the method under test
        $resultFileName = $this->fileUploadService->upload($uploadedFile);

        // Assert that an empty string is returned, as no extension means no filename is constructed
        $this->assertEquals('', $resultFileName);
    }

    /**
     * Test the upload method when a FileException occurs during move.
     */
    public function testUploadFileException(): void
    {
        // Define expected values for the file
        $originalFilename = 'corrupt_file.png';
        $safeFilename = 'corrupt-file';
        $extension = 'png';

        // Mock the UploadedFile object
        $uploadedFile = $this->createMock(UploadedFile::class);

        // Configure mocks to allow filename generation
        $uploadedFile->method('guessExtension')->willReturn($extension);
        $uploadedFile->method('getClientOriginalName')->willReturn($originalFilename);
        // FIX: Return a UnicodeString instance instead of a plain string
        $this->slugger->method('slug')->willReturn(new UnicodeString($safeFilename));

        // Configure 'move' method to throw a FileException
        $uploadedFile->expects($this->once())
            ->method('move')
            ->willThrowException(new FileException('Failed to move file.'));

        // Call the method under test.
        // The service's try-catch block handles the exception,
        // so the test itself should not expect an exception.
        // The method should still return the generated filename string.
        $resultFileName = $this->fileUploadService->upload($uploadedFile);

        // Assert that a filename was still generated and returned,
        // even though the move operation failed internally.
        $this->assertIsString($resultFileName);
        $this->assertStringStartsWith($safeFilename . '-', $resultFileName);
        $this->assertStringEndsWith('.' . $extension, $resultFileName);
    }

    /**
     * Test the getTargetDirectory method.
     */
    public function testGetTargetDirectory(): void
    {
        $this->assertEquals($this->targetDirectory, $this->fileUploadService->getTargetDirectory());
    }
}
