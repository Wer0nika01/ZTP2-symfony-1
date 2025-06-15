<?php

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\CategoryFixtures;
use App\Entity\Category;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryFixturesTest extends TestCase
{
    private MockObject|ObjectManager $manager;
    private MockObject|Generator $faker;
    private MockObject|CategoryFixtures $categoryFixtures;

    // A counter to track calls to dateTimeBetween within the mocked __call
    private static int $dateTimeBetweenCallCount = 0;
    // A counter for word calls within the mocked 'word' method
    private static int $wordCallCount = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the call count for dateTimeBetween and word for each test
        self::$dateTimeBetweenCallCount = 0;
        self::$wordCallCount = 0;

        // Create mocks for ObjectManager and Faker\Generator
        // FIX: Add 'word' to onlyMethods, as we'll mock it directly on $this->faker
        $this->faker = $this->getMockBuilder(Generator::class)
            ->disableOriginalConstructor() // Faker's constructor can be complex
            ->onlyMethods(['unique', '__call', 'word']) // Mock 'unique', '__call', and 'word' directly
            ->getMock();
        $this->manager = $this->createMock(ObjectManager::class);


        // Create a partial mock for CategoryFixtures.
        // We assume AbstractBaseFixtures (parent of CategoryFixtures) has a 'createMany' method
        // and that 'manager' and 'faker' are protected properties.
        // We disable the original constructor to avoid dependency issues during mocking,
        // and then manually set the protected properties using reflection.
        $this->categoryFixtures = $this->getMockBuilder(CategoryFixtures::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createMany'])
            ->getMock();

        // Use Reflection to set the protected 'manager' and 'faker' properties
        $reflection = new \ReflectionClass(CategoryFixtures::class);
        // Assuming 'manager' and 'faker' are defined in the parent AbstractBaseFixtures
        $parentReflection = $reflection->getParentClass();

        // Set the manager property
        $managerProperty = $parentReflection->getProperty('manager');
        $managerProperty->setValue($this->categoryFixtures, $this->manager);

        // Set the faker property
        $fakerProperty = $parentReflection->getProperty('faker');
        $fakerProperty->setValue($this->categoryFixtures, $this->faker);
    }

    /**
     * Test the loadData method to ensure categories are created correctly.
     */
    public function testLoadData(): void
    {
        $expectedCount = 20;
        $expectedAlias = 'category';

        // FIX: Configure the main faker mock's 'unique' method to return itself.
        // This makes $this->faker->unique()->word effectively $this->faker->word.
        $this->faker->method('unique')->willReturn($this->faker);

        // FIX: Configure the 'word' method directly on the main faker mock with consecutive calls.
        $this->faker->method('word')->willReturnOnConsecutiveCalls(
            'Word1', 'Word2', 'Word3', 'Word4', 'Word5',
            'Word6', 'Word7', 'Word8', 'Word9', 'Word10',
            'Word11', 'Word12', 'Word13', 'Word14', 'Word15',
            'Word16', 'Word17', 'Word18', 'Word19', 'Word20'
        );

        // Mock the magic __call method for dateTimeBetween calls.
        // This will be triggered when $this->faker->dateTimeBetween() is called in the fixture.
        $this->faker->method('__call')
            ->willReturnCallback(function (string $method, array $args) {
                if ($method === 'dateTimeBetween') {
                    // Sequence of DateTime objects to return for dateTimeBetween calls
                    $dates = [
                        new \DateTime('-100 days'), new \DateTime('-99 days'),
                        new \DateTime('-98 days'), new \DateTime('-97 days'),
                        new \DateTime('-96 days'), new \DateTime('-95 days'),
                        new \DateTime('-94 days'), new \DateTime('-93 days'),
                        new \DateTime('-92 days'), new \DateTime('-91 days'),
                        new \DateTime('-90 days'), new \DateTime('-89 days'),
                        new \DateTime('-88 days'), new \DateTime('-87 days'),
                        new \DateTime('-86 days'), new \DateTime('-85 days'),
                        new \DateTime('-84 days'), new \DateTime('-83 days'),
                        new \DateTime('-82 days'), new \DateTime('-81 days'),
                        new \DateTime('-80 days'), new \DateTime('-79 days'),
                        new \DateTime('-78 days'), new \DateTime('-77 days'),
                        new \DateTime('-76 days'), new \DateTime('-75 days'),
                        new \DateTime('-74 days'), new \DateTime('-73 days'),
                        new \DateTime('-72 days'), new \DateTime('-71 days'),
                        new \DateTime('-70 days'), new \DateTime('-69 days'),
                        new \DateTime('-68 days'), new \DateTime('-67 days'),
                        new \DateTime('-66 days'), new \DateTime('-65 days'),
                        new \DateTime('-64 days'), new \DateTime('-63 days'),
                        new \DateTime('-62 days'), new \DateTime('-61 days')
                    ];
                    $date = $dates[self::$dateTimeBetweenCallCount];
                    self::$dateTimeBetweenCallCount++;
                    return $date;
                }
                // If any other unexpected magic method is called, throw an exception
                throw new \BadMethodCallException(sprintf('Method %s::%s does not exist or is not mocked via __call.', Generator::class, $method));
            });

        // Expect the 'createMany' method (from AbstractBaseFixtures, mocked on CategoryFixtures)
        // to be called once with the specified count, alias, and a callback function.
        $this->categoryFixtures->expects($this->once())
            ->method('createMany')
            ->with(
                $expectedCount,
                $expectedAlias,
                $this->callback(function (callable $callback) use ($expectedCount) {
                    // This callback allows us to test the logic of the closure passed to createMany.
                    // We simulate createMany calling the callback 'expectedCount' times.
                    for ($i = 0; $i < $expectedCount; $i++) {
                        $category = $callback($i); // Execute the closure to get the Category entity

                        $this->assertInstanceOf(Category::class, $category);
                        $this->assertIsString($category->getTitle());
                        $this->assertNotNull($category->getTitle());
                        $this->assertNotNull($category->getCreatedAt());
                        $this->assertInstanceOf(\DateTimeImmutable::class, $category->getCreatedAt());
                        $this->assertNotNull($category->getUpdatedAt());
                        $this->assertInstanceOf(\DateTimeImmutable::class, $category->getUpdatedAt());
                        // Slug is generated by Gedmo listeners, which are not active in unit tests of fixtures.
                        // Therefore, we cannot reliably assert its value or non-null state here without
                        // mocking Doctrine's event system, which is beyond the scope of a fixture unit test.
                    }
                    return true; // Indicate that the callback assertion passed
                })
            );

        // Call the loadData method, which is the public method under test
        $this->categoryFixtures->loadData();
    }

    /**
     * Test loadData when ObjectManager or Generator are null (early return condition).
     */
    public function testLoadDataReturnsEarlyIfDependenciesAreNull(): void
    {
        // Create a mock for CategoryFixtures without setting manager and faker.
        // This simulates the condition where they might be null or not properly injected.
        $fixture = $this->getMockBuilder(CategoryFixtures::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createMany'])
            ->getMock();

        // Ensure createMany is NEVER called because loadData should return early
        $fixture->expects($this->never())->method('createMany');

        // Manually set manager and faker to null on this specific mock instance,
        // mimicking the scenario where the check in loadData() would pass.
        $reflection = new \ReflectionClass(CategoryFixtures::class);
        $parentReflection = $reflection->getParentClass(); // Assuming properties are in parent

        $managerProperty = $parentReflection->getProperty('manager');
        $managerProperty->setValue($fixture, null); // Set manager to null

        $fakerProperty = $parentReflection->getProperty('faker');
        $fakerProperty->setValue($fixture, null); // Set faker to null

        // Call loadData; it should exit early due to the null checks
        $fixture->loadData();
    }
}
