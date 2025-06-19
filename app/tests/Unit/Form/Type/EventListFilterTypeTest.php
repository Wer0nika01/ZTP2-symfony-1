<?php

/**
 * Event list filter type Test.
 */

namespace App\Tests\Unit\Form\Type;

use App\Dto\EventListFiltersDto;
use App\Entity\Category;
use App\Entity\Enum\EventStatus;
use App\Entity\Tag;
use App\Form\Type\EventListFilterType;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\QueryBuilder;

/**
 * Class Event list filter type Test.
 */
class EventListFilterTypeTest extends TestCase
{
    private EventListFilterType $formType;

    /**
     * Set up.
     */
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
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
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
                    }),
                ],
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
                    }),
                ],
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
                    }),
                ]
            )
            ->willReturnSelf();

        $this->formType->buildForm($builder, []);
    }

    /**
     * Test the configureOptions method to ensure default options are correctly set.
     */
    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function (array $defaults) {
                $this->assertArrayHasKey('data_class', $defaults);
                $this->assertEquals(EventListFiltersDto::class, $defaults['data_class']);

                $this->assertArrayHasKey('method', $defaults);
                $this->assertEquals('GET', $defaults['method']);

                $this->assertArrayHasKey('csrf_protection', $defaults);
                $this->assertFalse($defaults['csrf_protection']);

                return true;
            }));

        $this->formType->configureOptions($resolver);
    }

    /**
     * Test the getBlockPrefix method to ensure it returns the correct block prefix.
     */
    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('event_filter', $this->formType->getBlockPrefix());
    }
}
