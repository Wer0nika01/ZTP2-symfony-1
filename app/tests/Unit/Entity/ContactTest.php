<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Contact;
use App\Entity\User; // Assuming User entity exists and is used in Contact
use App\Entity\Tag; // Assuming Tag entity exists and is used in Contact
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

class ContactTest extends TestCase
{
    private Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contact = new Contact();
    }

    /**
     * Test if the ID is initially null for a new entity.
     */
    public function testGetId(): void
    {
        $this->assertNull($this->contact->getId());
    }

    /**
     * Test setting and getting the first name.
     */
    public function testFirstNameGetAndSet(): void
    {
        $firstName = 'John';
        $result = $this->contact->setFirstName($firstName);

        $this->assertSame($this->contact, $result); // Test fluent interface
        $this->assertEquals($firstName, $this->contact->getFirstName());
        $this->assertIsString($this->contact->getFirstName());

        $this->contact->setFirstName(null);
        $this->assertNull($this->contact->getFirstName());
    }

    /**
     * Test setting and getting the last name.
     */
    public function testLastNameGetAndSet(): void
    {
        $lastName = 'Doe';
        $result = $this->contact->setLastName($lastName);

        $this->assertSame($this->contact, $result); // Test fluent interface
        $this->assertEquals($lastName, $this->contact->getLastName());
        $this->assertIsString($this->contact->getLastName());

        $this->contact->setLastName(null);
        $this->assertNull($this->contact->getLastName());
    }

    /**
     * Test setting and getting the email.
     */
    public function testEmailGetAndSet(): void
    {
        $email = 'test@example.com';
        $result = $this->contact->setEmail($email);

        $this->assertSame($this->contact, $result); // Test fluent interface
        $this->assertEquals($email, $this->contact->getEmail());
        $this->assertIsString($this->contact->getEmail());

        $this->contact->setEmail(null);
        $this->assertNull($this->contact->getEmail());
    }

    /**
     * Test setting and getting the phone.
     */
    public function testPhoneGetAndSet(): void
    {
        $phone = '123-456-7890';
        $result = $this->contact->setPhone($phone);

        $this->assertSame($this->contact, $result); // Test fluent interface
        $this->assertEquals($phone, $this->contact->getPhone());
        $this->assertIsString($this->contact->getPhone());

        $this->contact->setPhone(null);
        $this->assertNull($this->contact->getPhone());
    }

    /**
     * Test setting and getting the address.
     */
    public function testAddressGetAndSet(): void
    {
        $address = '123 Main St, Anytown';
        $result = $this->contact->setAddress($address);

        $this->assertSame($this->contact, $result); // Test fluent interface
        $this->assertEquals($address, $this->contact->getAddress());
        $this->assertIsString($this->contact->getAddress());

        $this->contact->setAddress(null);
        $this->assertNull($this->contact->getAddress());
    }

    /**
     * Test setting and getting the company.
     */
    public function testCompanyGetAndSet(): void
    {
        $company = 'ABC Corp';
        $result = $this->contact->setCompany($company);

        $this->assertSame($this->contact, $result); // Test fluent interface
        $this->assertEquals($company, $this->contact->getCompany());
        $this->assertIsString($this->contact->getCompany());

        $this->contact->setCompany(null);
        $this->assertNull($this->contact->getCompany());
    }

    /**
     * Test setting and getting the job title.
     */
    public function testJobTitleGetAndSet(): void
    {
        $jobTitle = 'Software Engineer';
        $result = $this->contact->setJobTitle($jobTitle);

        $this->assertSame($this->contact, $result); // Test fluent interface
        $this->assertEquals($jobTitle, $this->contact->getJobTitle());
        $this->assertIsString($this->contact->getJobTitle());

        $this->contact->setJobTitle(null);
        $this->assertNull($this->contact->getJobTitle());
    }

    /**
     * Test setting and getting notes.
     */
    public function testNotesGetAndSet(): void
    {
        $notes = 'Important client contact.';
        $result = $this->contact->setNotes($notes);

        $this->assertSame($this->contact, $result); // Test fluent interface
        $this->assertEquals($notes, $this->contact->getNotes());
        $this->assertIsString($this->contact->getNotes());

        $this->contact->setNotes(null);
        $this->assertNull($this->contact->getNotes());
    }

    /**
     * Test setting and getting createdAt.
     */
    public function testCreatedAtGetAndSet(): void
    {
        $dateTime = new \DateTimeImmutable();
        $this->contact->setCreatedAt($dateTime);

        $this->assertEquals($dateTime, $this->contact->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->contact->getCreatedAt());

        $this->contact->setCreatedAt(null);
        $this->assertNull($this->contact->getCreatedAt());
    }

    /**
     * Test setting and getting updatedAt.
     */
    public function testUpdatedAtGetAndSet(): void
    {
        $dateTime = new \DateTimeImmutable();
        $this->contact->setUpdatedAt($dateTime);

        $this->assertEquals($dateTime, $this->contact->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->contact->getUpdatedAt());

        $this->contact->setUpdatedAt(null);
        $this->assertNull($this->contact->getUpdatedAt());
    }

    /**
     * Test setting and getting the author.
     */
    public function testAuthorGetAndSet(): void
    {
        $author = $this->createMock(User::class);
        $result = $this->contact->setAuthor($author);

        $this->assertSame($this->contact, $result); // Test fluent interface
        $this->assertSame($author, $this->contact->getAuthor());

        $this->contact->setAuthor(null);
        $this->assertNull($this->contact->getAuthor());
    }

    /**
     * Test tags collection initialization.
     */
    public function testTagsInitialization(): void
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->contact->getTags());
        $this->assertTrue($this->contact->getTags()->isEmpty());
    }

    /**
     * Test addTag method.
     */
    public function testAddTag(): void
    {
        $tag1 = $this->createMock(Tag::class);
        $tag2 = $this->createMock(Tag::class);

        $result1 = $this->contact->addTag($tag1);
        $this->assertSame($this->contact, $result1); // Test fluent interface
        $this->assertTrue($this->contact->getTags()->contains($tag1));
        $this->assertCount(1, $this->contact->getTags());

        // Try adding the same tag again, should not increase count
        $result2 = $this->contact->addTag($tag1);
        $this->assertSame($this->contact, $result2); // Test fluent interface
        $this->assertCount(1, $this->contact->getTags());

        // Add a second, different tag
        $this->contact->addTag($tag2);
        $this->assertTrue($this->contact->getTags()->contains($tag2));
        $this->assertCount(2, $this->contact->getTags());
    }

    /**
     * Test removeTag method.
     */
    public function testRemoveTag(): void
    {
        $tag1 = $this->createMock(Tag::class);
        $tag2 = $this->createMock(Tag::class);

        // Add tags first
        $this->contact->addTag($tag1);
        $this->contact->addTag($tag2);
        $this->assertCount(2, $this->contact->getTags());

        // Remove one tag
        $result1 = $this->contact->removeTag($tag1);
        $this->assertSame($this->contact, $result1); // Test fluent interface
        $this->assertFalse($this->contact->getTags()->contains($tag1));
        $this->assertCount(1, $this->contact->getTags());

        // Try removing a non-existent tag, should not change count
        $nonExistentTag = $this->createMock(Tag::class);
        $result2 = $this->contact->removeTag($nonExistentTag);
        $this->assertSame($this->contact, $result2); // Test fluent interface
        $this->assertCount(1, $this->contact->getTags());

        // Remove the last tag
        $this->contact->removeTag($tag2);
        $this->assertFalse($this->contact->getTags()->contains($tag2));
        $this->assertCount(0, $this->contact->getTags());
    }
}
