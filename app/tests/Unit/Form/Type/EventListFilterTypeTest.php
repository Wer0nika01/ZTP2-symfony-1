<?php

/**
 * Event list filter type Test.
 */

namespace App\Tests\Unit\Form\Type;

use App\Dto\EventListFiltersDto;
use App\Entity\Category;
use App\Form\Type\EventListFilterType;
use App\Repository\CategoryRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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

        $builder->expects($this->once())
            ->method('add')
            ->with('category', EntityType::class, $this->callback(function (array $options) {
                $this->assertEquals(Category::class, $options['class']);
                $this->assertEquals('title', $options['choice_label']);
                $this->assertFalse($options['required']);
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
                    ->willReturn($mockQueryBuilder);

                call_user_func($options['query_builder'], $mockCategoryRepository);

                return true;
            }));

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
