<?php

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\CategoryFixtures; // Dependency
use App\DataFixtures\EventFixtures; // The fixture under test
use App\DataFixtures\TagFixtures; // Dependency
use App\DataFixtures\UserFixtures; // Dependency
use App\Entity\Category;
use App\Entity\Enum\EventStatus;
use App\Entity\Event;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection; // For the Event entity assert

class EventFixturesTest extends TestCase
{
    private MockObject|ObjectManager $manager;
    private MockObject|Generator $faker;
    private MockObject|EventFixtures $eventFixtures;


    protected function setUp(): void
    {
        parent::setUp();

        // Reset call counts for each test
        self::$sentenceCallCount = 0;
        self::$realTextCallCount = 0;
        self::$dateTimeBetweenCallCount = 0;
        self::$booleanCallCount = 0;
        self::$addressCallCount = 0;
        self::$numberBetweenCallCount = 0;

        $this->manager = $this->createMock(ObjectManager::class);

        // Mock Faker Generator
        // FIX: Added 'unique' to onlyMethods() and configured it to return the mock itself.
        // This prevents Faker's internal UniqueGenerator from being instantiated, which caused the error.
        $this->faker = $this->getMockBuilder(Generator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call', '__get', 'unique']) // FIX: Added 'unique' here
            ->getMock();

        // FIX: Configure unique() to return $this->faker itself.
        // This allows chaining calls like $faker->unique()->sentence() if it were used,
        // and crucially prevents the real unique() method from causing issues.
        $this->faker->method('unique')->willReturn($this->faker);

        // Partially mock EventFixtures to mock inherited methods
        $this->eventFixtures = $this->getMockBuilder(EventFixtures::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createMany', 'getRandomReference', 'getRandomReferenceList'])
            ->getMock();

        // Use Reflection to set the protected 'manager' and 'faker' properties from AbstractBaseFixtures
        $reflection = new \ReflectionClass(EventFixtures::class);
        $parentReflection = $reflection->getParentClass(); // Get parent class Reflection for protected properties

        $managerProperty = $parentReflection->getProperty('manager');
        $managerProperty->setValue($this->eventFixtures, $this->manager);

        $fakerProperty = $parentReflection->getProperty('faker');
        $fakerProperty->setValue($this->eventFixtures, $this->faker);
    }

    /**
     * Test the loadData method to ensure events are created and flushed.
     */
    public function testLoadData(): void
    {
        $expectedEventCount = 100;

        // Configure main Faker Generator mock for ALL methods (including formatters) via __call
        // This is where we define what methods like 'sentence', 'realText', etc. return.
        $this->faker->expects($this->atLeast($expectedEventCount * 3)) // At least because boolean, dateTimeBetween, numberBetween are called conditionally or multiple times.
        ->method('__call')
            ->willReturnCallback(function (string $method, array $args) {
                switch ($method) {
                    case 'sentence':
                        self::$sentenceCallCount++;
                        return 'Event Title ' . self::$sentenceCallCount;
                    case 'realText':
                        self::$realTextCallCount++;
                        return 'Event Description ' . self::$realTextCallCount;
                    case 'dateTimeBetween':
                        self::$dateTimeBetweenCallCount++;
                        // Argument list might vary, so be flexible or specific if needed.
                        // For simplicity, just return a DateTime object.
                        return new \DateTime('+' . self::$dateTimeBetweenCallCount . ' days');
                    case 'boolean':
                        self::$booleanCallCount++;
                        // Simulate true/false for boolean, considering its a conditional call based on percentage.
                        // The fixture has multiple boolean calls with different percentages.
                        // To avoid over-complex mock setup, a simple alternating return is fine for unit test.
                        return (self::$booleanCallCount % 2 === 0);
                    case 'numberBetween':
                        self::$numberBetweenCallCount++;
                        // Need to respect min/max args if relevant for the fixture's logic
                        if (isset($args[1]) && $args[1] === 5) { // For tags (mt_rand(0, 5))
                            return self::$numberBetweenCallCount % 3; // 0, 1, 2
                        } elseif (isset($args[1]) && $args[1] === 3) { // For status (mt_rand(1, 3))
                            return (self::$numberBetweenCallCount % 3) + 1; // 1, 2, 3 (for enum values)
                        }
                        return 1; // Default
                    default:
                        throw new \BadMethodCallException(sprintf('Unexpected magic method call on Faker\Generator: %s::%s', Generator::class, $method));
                }
            });

        // Configure main Faker Generator mock for properties via __get
        // This is where we define what properties like 'address' return.
        $this->faker->expects($this->atLeast(0)) // It's conditional in fixture
        ->method('__get')
            ->willReturnCallback(function (string $property) {
                if ($property === 'address') {
                    self::$addressCallCount++;
                    return 'Location ' . self::$addressCallCount;
                }
                throw new \BadMethodCallException(sprintf('Unexpected magic property access on Faker\Generator: %s::$%s', Generator::class, $property));
            });


        // Configure getRandomReference mocks
        $mockCategory = $this->createMock(Category::class);
        $mockUser = $this->createMock(User::class);

        $this->eventFixtures->expects($this->exactly($expectedEventCount * 2))
            ->method('getRandomReference')
            ->withConsecutive(
                ...array_map(function() { return ['category', Category::class]; }, range(1, $expectedEventCount)),
                ...array_map(function() { return ['user', User::class]; }, range(1, $expectedEventCount))
            )
            ->willReturnOnConsecutiveCalls(
                ...array_merge(
                    array_fill(0, $expectedEventCount, $mockCategory),
                    array_fill(0, $expectedEventCount, $mockUser)
                )
            );

        // Configure getRandomReferenceList mock
        $mockTag1 = $this->createMock(Tag::class);
        $mockTag2 = $this->createMock(Tag::class);
        $this->eventFixtures->expects($this->exactly($expectedEventCount))
            ->method('getRandomReferenceList')
            ->with('tag', Tag::class, $this->anything())
            ->willReturnOnConsecutiveCalls(
                [],
                [$mockTag1],
                [$mockTag1, $mockTag2],
                ...array_fill(0, $expectedEventCount - 3, [])
            );

        // Configure createMany expectation
        $this->eventFixtures->expects($this->once())
            ->method('createMany')
            ->with(
                $expectedEventCount,
                'event',
                $this->callback(function (callable $callback) {
                    for ($i = 0; $i < 1; $i++) {
                        $event = $callback($i);

                        $this->assertInstanceOf(Event::class, $event);
                        $this->assertIsString($event->getTitle());
                        $this->assertNotNull($event->getTitle());
                        $this->assertIsString($event->getDescription());
                        $this->assertNotNull($event->getDescription());
                        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getStartTime());
                        $this->assertInstanceOf(Category::class, $event->getCategory());
                        $this->assertInstanceOf(Collection::class, $event->getTags());
                        $this->assertInstanceOf(User::class, $event->getAuthor());
                        $this->assertInstanceOf(EventStatus::class, $event->getStatus());
                    }
                    return true;
                })
            );

        // Expect flush to be called once on the ObjectManager
        $this->manager->expects($this->once())
            ->method('flush');

        // Call the loadData method
        $this->eventFixtures->loadData();
    }

    /**
     * Test getDependencies method.
     */
    public function testGetDependencies(): void
    {
        $expectedDependencies = [CategoryFixtures::class, TagFixtures::class, UserFixtures::class];
        $actualDependencies = $this->eventFixtures->getDependencies();

        // Use assertEqualsCanonicalizing to ignore order differences in arrays
        $this->assertEqualsCanonicalizing($expectedDependencies, $actualDependencies);
    }
}
