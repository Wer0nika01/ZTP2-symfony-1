<?php

/**
 * Contact list filter type Test.
 */

namespace App\Tests\Unit\Form\Type;

use App\Dto\ContactListFiltersDto;
use App\Entity\Tag;
use App\Form\Type\ContactListFilterType;
use App\Repository\TagRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\QueryBuilder;

/**
 * Class Contact list filter type Test.
 */
class ContactListFilterTypeTest extends TestCase
{
    private ContactListFilterType $formType;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->formType = new ContactListFilterType();
    }

    /**
     * Test the buildForm method to ensure the 'tags' field is correctly added with all options.
     */
    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('add')
            ->with(
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

                    $resultQueryBuilder = call_user_func($options['query_builder'], $mockTagRepository);

                    $this->assertSame($mockQueryBuilder, $resultQueryBuilder);

                    return true;
                })
            );

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
                $this->assertEquals(ContactListFiltersDto::class, $defaults['data_class']);

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
        $this->assertEquals('contact_filter', $this->formType->getBlockPrefix());
    }
}
