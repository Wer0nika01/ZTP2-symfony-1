<?php

namespace App\Tests\Unit\Form\Type;

use App\Dto\EventListFiltersDto;
use App\Entity\Category;
use App\Entity\Enum\EventStatus;
use App\Entity\Tag;
use App\Form\Type\EventListFilterType; // The form type under test
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\QueryBuilder; // Needed for mocking QueryBuilder

class EventListFilterTypeTest extends TestCase
{
    private EventListFilterType $formType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formType = new EventListFilterType();
    }

    /**
     * Test the buildForm method to ensure all fields are correctly added with their options.
     */
    public function testBuildForm(): void
    {
        // Mock the FormBuilderInterface that buildForm will interact with.
        $builder = $this->createMock(FormBuilderInterface::class);

        // FIX: Refactored to use withConsecutive() instead of deprecated at() matcher.
        // This explicitly defines the arguments for each sequential 'add' call.
        $builder->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
            // Arguments for the first 'add' call ('category' field)
                [
                    'category',
                    EntityType::class,
                    $this->callback(function (array $options) {
                        $this->assertArrayHasKey('class', $options);
                        $this->assertEquals(Category::class, $options['class']);
                        $this->assertArrayHasKey('choice_label', $options);
                        $this->assertEquals('title', $options['choice_label']);
                        $this->assertArrayHasKey('required', $options);
                        $this->assertFalse($options['required']);
                        $this->assertArrayHasKey('placeholder', $options);
                        $this->assertEquals('label.filter_by_category', $options['placeholder']);
                        $this->assertArrayHasKey('label', $options);
                        $this->assertEquals('label.category', $options['label']);
                        $this->assertArrayHasKey('query_builder', $options);
                        $this->assertIsCallable($options['query_builder']);

                        // Test the query_builder callback for category
                        $mockCategoryRepository = $this->createMock(CategoryRepository::class);
                        $mockQueryBuilder = $this->createMock(QueryBuilder::class);
                        $mockCategoryRepository->expects($this->once())
                            ->method('createQueryBuilder')
                            ->with('c')
                            ->willReturn($mockQueryBuilder);
                        $mockQueryBuilder->expects($this->once())
                            ->method('orderBy')
                            ->with('c.title', 'ASC')
                            ->willReturnSelf();

                        call_user_func($options['query_builder'], $mockCategoryRepository);
                        return true;
                    })
                ],
                // Arguments for the second 'add' call ('status' field)
                [
                    'status',
                    EnumType::class,
                    $this->callback(function (array $options) {
                        $this->assertArrayHasKey('class', $options);
                        $this->assertEquals(EventStatus::class, $options['class']);
                        $this->assertArrayHasKey('choice_label', $options);
                        $this->assertIsCallable($options['choice_label']);
                        $this->assertArrayHasKey('required', $options);
                        $this->assertFalse($options['required']);
                        $this->assertArrayHasKey('placeholder', $options);
                        $this->assertEquals('label.filter_by_status', $options['placeholder']);
                        $this->assertArrayHasKey('label', $options);
                        $this->assertEquals('label.status', $options['label']);

                        // Test the choice_label callback for status enum
                        $this->assertEquals('label.personal', call_user_func($options['choice_label'], EventStatus::PERSONAL));
                        $this->assertEquals('label.important', call_user_func($options['choice_label'], EventStatus::IMPORTANT));
                        $this->assertEquals('label.work', call_user_func($options['choice_label'], EventStatus::WORK));
                        return true;
                    })
                ],
                // Arguments for the third 'add' call ('tags' field)
                [
                    'tags',
                    EntityType::class,
                    $this->callback(function (array $options) {
                        $this->assertArrayHasKey('class', $options);
                        $this->assertEquals(Tag::class, $options['class']);
                        $this->assertArrayHasKey('choice_label', $options);
                        $this->assertEquals('name', $options['choice_label']);
                        $this->assertArrayHasKey('multiple', $options);
                        $this->assertTrue($options['multiple']);
                        $this->assertArrayHasKey('expanded', $options);
                        $this->assertTrue($options['expanded']);
                        $this->assertArrayHasKey('required', $options);
                        $this->assertFalse($options['required']);
                        $this->assertArrayHasKey('label', $options);
                        $this->assertEquals('label.tags', $options['label']);
                        $this->assertArrayHasKey('placeholder', $options);
                        $this->assertEquals('label.filter_by_tags', $options['placeholder']);
                        $this->assertArrayHasKey('query_builder', $options);
                        $this->assertIsCallable($options['query_builder']);

                        // Test the query_builder callback for tags
                        $mockTagRepository = $this->createMock(TagRepository::class);
                        $mockQueryBuilder = $this->createMock(QueryBuilder::class);
                        $mockTagRepository->expects($this->once())
                            ->method('createQueryBuilder')
                            ->with('t')
                            ->willReturn($mockQueryBuilder);
                        $mockQueryBuilder->expects($this->once())
                            ->method('orderBy')
                            ->with('t.name', 'ASC')
                            ->willReturnSelf();

                        call_user_func($options['query_builder'], $mockTagRepository);
                        return true;
                    })
                ]
            )
            ->willReturnSelf(); // Allow method chaining for 'add'

        // Call the method under test
        $this->formType->buildForm($builder, []);
    }

    /**
     * Test the configureOptions method to ensure default options are correctly set.
     */
    public function testConfigureOptions(): void
    {
        // Mock the OptionsResolver that configureOptions will interact with.
        $resolver = $this->createMock(OptionsResolver::class);

        // Expect 'setDefaults' to be called once with an array containing specific default options.
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function (array $defaults) {
                // Assert that 'data_class' is set to the correct DTO class.
                $this->assertArrayHasKey('data_class', $defaults);
                $this->assertEquals(EventListFiltersDto::class, $defaults['data_class']);

                // Assert that 'method' is set to 'GET'.
                $this->assertArrayHasKey('method', $defaults);
                $this->assertEquals('GET', $defaults['method']);

                // Assert that 'csrf_protection' is set to false.
                $this->assertArrayHasKey('csrf_protection', $defaults);
                $this->assertFalse($defaults['csrf_protection']);
                return true;
            }));

        // Call the configureOptions method on the form type instance.
        $this->formType->configureOptions($resolver);
    }

    /**
     * Test the getBlockPrefix method to ensure it returns the correct block prefix.
     */
    public function testGetBlockPrefix(): void
    {
        // Assert that the method returns the expected string 'event_filter'.
        $this->assertEquals('event_filter', $this->formType->getBlockPrefix());
    }
}
