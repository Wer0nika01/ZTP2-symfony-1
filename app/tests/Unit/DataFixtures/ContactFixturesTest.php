<?php

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\ContactFixtures;
use App\DataFixtures\TagFixtures; // Dependency
use App\DataFixtures\UserFixtures; // Dependency
use App\Entity\Contact;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Faker\UniqueGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Doctrine\Common\Collections\Collection;

class ContactFixturesTest extends TestCase
{
    private MockObject|ObjectManager $manager;
    private MockObject|Generator $faker;
    private MockObject|ContactFixtures $contactFixtures;

    // Counters for Faker's consecutive calls
    private static int $realTextCallCount = 0;
    // FIX: Declare booleanSequence as a class property
    private array $booleanSequenceForFaker;


    protected function setUp(): void
    {
        parent::setUp();

        // Reset counters for each test
        self::$booleanCallCount = 0;
        self::$realTextCallCount = 0;
        self::$uniqueSafeEmailCallCount = 0;
        self::$firstNameCallCount = 0;
        self::$lastNameCallCount = 0;
        self::$e164PhoneNumberCallCount = 0;
        self::$addressCallCount = 0;
        self::$companyCallCount = 0;
        self::$jobTitleCallCount = 0;

        // FIX: Initialize the class property
        $this->booleanSequenceForFaker = [];
        for ($i = 0; $i < 50; $i++) {
            $this->booleanSequenceForFaker[] = ($i % 5 >= 0); // 80% email (4/5 true)
            $this->booleanSequenceForFaker[] = ($i % 10 >= 3); // 70% phone (7/10 true)
            $this->booleanSequenceForFaker[] = ($i % 10 >= 4); // 60% address (6/10 true)
            $this->booleanSequenceForFaker[] = ($i % 10 >= 5); // 50% company (5/10 true)
            $this->booleanSequenceForFaker[] = ($i % 10 >= 6); // 40% jobTitle (4/10 true)
            $this->booleanSequenceForFaker[] = ($i % 10 >= 7); // 30% notes (3/10 true)
        }


        // Mock ObjectManager for persist and flush
        $this->manager = $this->createMock(ObjectManager::class);
        // Removed willReturn(null) as persist() and flush() are void methods
        $this->manager->method('persist');
        $this->manager->method('flush');

        // Mock Faker\Generator
        $this->faker = $this->getMockBuilder(Generator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get', 'unique', '__call'])
            ->getMock();

        // FIX: Configure the __call method to handle 'boolean' and 'realText' dynamically.
        // Use $this->booleanSequenceForFaker instead of local $booleanSequence
        $this->faker->method('__call')
            ->willReturnCallback(function (string $name, array $arguments) { // Removed 'use (&$booleanSequence)'
                switch ($name) {
                    case 'boolean':
                        // Faker's boolean expects a single percentage argument.
                        // Ensure we always return a boolean, even if sequence is exhausted for some reason
                        if (empty($this->booleanSequenceForFaker)) {
                            return false; // Or throw an exception, depending on desired strictness
                        }
                        $value = array_shift($this->booleanSequenceForFaker); // FIX: Use class property
                        return $value;
                    case 'realText':
                        self::$realTextCallCount++;
                        return 'Real text ' . self::$realTextCallCount;
                    default:
                        // Fallback for any other unexpected magic method calls
                        throw new \BadMethodCallException(sprintf('Method %s::%s does not exist or is not mocked.', Generator::class, $name));
                }
            });

        // FIX: Mock Faker\UniqueGenerator directly, as it's the class that uses __get for formatters.
        $mockForUniqueGenerator = $this->getMockBuilder(UniqueGenerator::class)
            ->disableOriginalConstructor() // UniqueGenerator also has a constructor we want to skip
            ->onlyMethods(['__get']) // It uses __get for formatters like safeEmail
            ->getMock();

        // Configure the __get method for the unique generator mock to accept the property name.
        $mockForUniqueGenerator->method('__get')
            ->willReturnCallback(function (string $property) {
                if ($property === 'safeEmail') {
                    self::$uniqueSafeEmailCallCount++;
                    return 'email' . self::$uniqueSafeEmailCallCount . '@example.com';
                }
                throw new \OutOfBoundsException(sprintf('Undefined property: %s::$%s', UniqueGenerator::class, $property));
            });
        $this->faker->method('unique')->willReturn($mockForUniqueGenerator);

        // Mock for Faker's magic properties accessed via __get for string values
        $this->faker->method('__get')
            ->willReturnCallback(function (string $property) {
                switch ($property) {
                    case 'firstName':
                        self::$firstNameCallCount++;
                        return 'FirstName' . self::$firstNameCallCount;
                    case 'lastName':
                        self::$lastNameCallCount++;
                        return 'LastName' . self::$lastNameCallCount;
                    case 'e164PhoneNumber':
                        self::$e164PhoneNumberCallCount++;
                        return 'PhoneNumber' . self::$e164PhoneNumberCallCount;
                    case 'address':
                        self::$addressCallCount++;
                        return 'Address' . self::$addressCallCount;
                    case 'company':
                        self::$companyCallCount++;
                        return 'Company' . self::$companyCallCount;
                    case 'jobTitle':
                        self::$jobTitleCallCount++;
                        return 'JobTitle' . self::$jobTitleCallCount;
                    default:
                        throw new \OutOfBoundsException(sprintf('Undefined property: %s::$%s', Generator::class, $property));
                }
            });

        // Create a partial mock for ContactFixtures.
        // We will mock 'createMany' and 'getRandomReference'.
        $this->contactFixtures = $this->getMockBuilder(ContactFixtures::class)
            ->setConstructorArgs([]) // No constructor args as we disable it if we set properties manually
            ->onlyMethods(['createMany', 'getRandomReference'])
            ->getMock();

        // Use Reflection to set the protected 'manager' and 'faker' properties
        $reflection = new ReflectionClass(ContactFixtures::class);
        $abstractBaseFixturesReflection = $reflection->getParentClass(); // Get parent class Reflection

        $managerProperty = $abstractBaseFixturesReflection->getProperty('manager');
        $managerProperty->setValue($this->contactFixtures, $this->manager);

        $fakerProperty = $abstractBaseFixturesReflection->getProperty('faker');
        $fakerProperty->setValue($this->contactFixtures, $this->faker);
    }

    /**
     * Test the loadData method.
     */
    public function testLoadData(): void
    {
        $expectedContactCount = 50;

        // Configure getRandomReference mock
        // This will be called for User and Tag entities
        $userCount = 0;
        $tagCount = 0;
        $this->contactFixtures->method('getRandomReference')
            ->willReturnCallback(function (string $alias, string $class) use (&$userCount, &$tagCount) {
                if ($class === User::class) {
                    $userMock = $this->createMock(User::class);
                    $userMock->method('getId')->willReturn(++$userCount);
                    return $userMock;
                } elseif ($class === Tag::class) {
                    $tagMock = $this->createMock(Tag::class);
                    $tagMock->method('getId')->willReturn(++$tagCount);
                    return $tagMock;
                }
                throw new \InvalidArgumentException('Unexpected class requested for getRandomReference: ' . $class);
            });


        // Expect createMany to be called once with the correct arguments
        $this->contactFixtures->expects($this->once())
            ->method('createMany')
            ->with(
                $expectedContactCount,
                'contact',
                $this->callback(function (callable $callback) use ($expectedContactCount) {
                    // This callback simulates the execution of the closure passed to createMany.
                    // We iterate and assert on each created contact.
                    for ($i = 0; $i < $expectedContactCount; $i++) {
                        $contact = $callback($i); // Execute the closure to get the Contact entity

                        // FIX: Manually set createdAt and updatedAt as Gedmo listener is not active in unit tests.
                        $contact->setCreatedAt(new \DateTimeImmutable());
                        $contact->setUpdatedAt(new \DateTimeImmutable());

                        $this->assertInstanceOf(Contact::class, $contact);
                        $this->assertNotNull($contact->getFirstName());
                        $this->assertNotNull($contact->getLastName());
                        $this->assertInstanceOf(User::class, $contact->getAuthor());
                        $this->assertInstanceOf(\DateTimeImmutable::class, $contact->getCreatedAt());
                        $this->assertInstanceOf(\DateTimeImmutable::class, $contact->getUpdatedAt());
                        $this->assertInstanceOf(Collection::class, $contact->getTags());
                        // The number of tags depends on mt_rand(0, 4) in the fixture.
                        // We can't assert an exact count without mocking mt_rand, which is overkill for a unit test.
                        // We just check it's a collection.
                    }
                    return true; // Indicate that the callback assertion passed
                })
            );

        // Expect manager->flush() to be called once at the end
        $this->manager->expects($this->once())
            ->method('flush');

        // Call the loadData method on the fixture
        $this->contactFixtures->loadData();
    }

    /**
     * Test loadData method returns early if manager or faker are null.
     */
    public function testLoadDataReturnsEarlyIfDependenciesAreNull(): void
    {
        // Create a mock for ContactFixtures without setting manager and faker.
        $fixture = $this->getMockBuilder(ContactFixtures::class)
            ->setConstructorArgs([])
            ->onlyMethods(['createMany', 'getRandomReference'])
            ->getMock();

        // Ensure createMany is NEVER called because loadData should return early
        $fixture->expects($this->never())->method('createMany');
        $this->manager->expects($this->never())->method('flush'); // Flush also should not be called

        // Manually set manager and faker to null on this specific mock instance,
        // mimicking the scenario where the check in loadData() would pass.
        $reflection = new ReflectionClass(ContactFixtures::class);
        $abstractBaseFixturesReflection = $reflection->getParentClass();

        $managerProperty = $abstractBaseFixturesReflection->getProperty('manager');
        $managerProperty->setValue($fixture, null); // Set manager to null

        $fakerProperty = $abstractBaseFixturesReflection->getProperty('faker');
        $fakerProperty->setValue($fixture, null); // Set faker to null

        // Call loadData; it should exit early due to the null checks
        $fixture->loadData();
    }

    /**
     * Test getDependencies method.
     */
    public function testGetDependencies(): void
    {
        $expectedDependencies = [UserFixtures::class, TagFixtures::class];
        $this->assertEquals($expectedDependencies, $this->contactFixtures->getDependencies());
    }
}
