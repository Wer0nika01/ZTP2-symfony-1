<?php

namespace App\Tests\Unit\Form\Type;

use App\Dto\ContactListFiltersDto;
use App\Entity\Tag;
use App\Form\Type\ContactListFilterType;
use App\Repository\TagRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType; // FIX: Corrected namespace for EntityType
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\QueryBuilder; // Needed for mocking QueryBuilder

class ContactListFilterTypeTest extends TestCase
{
    private ContactListFilterType $formType;

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
        // Mock the FormBuilderInterface that buildForm will interact with.
        $builder = $this->createMock(FormBuilderInterface::class);

        // Expect the 'add' method to be called once with specific arguments for the 'tags' field.
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'tags', // The name of the field.
                EntityType::class, // The type of the field (Symfony's EntityType).
                $this->callback(function (array $options) {
                    // Assertions for the 'class' option of EntityType.
                    $this->assertArrayHasKey('class', $options);
                    $this->assertEquals(Tag::class, $options['class']);

                    // Assertions for the 'choice_label' option.
                    $this->assertArrayHasKey('choice_label', $options);
                    $this->assertEquals('name', $options['choice_label']);

                    // Assertions for 'multiple' and 'expanded' options.
                    $this->assertArrayHasKey('multiple', $options);
                    $this->assertTrue($options['multiple']);
                    $this->assertArrayHasKey('expanded', $options);
                    $this->assertTrue($options['expanded']);

                    // Assertions for 'required', 'label', and 'placeholder' options.
                    $this->assertArrayHasKey('required', $options);
                    $this->assertFalse($options['required']);
                    $this->assertArrayHasKey('label', $options);
                    $this->assertEquals('label.tags', $options['label']);
                    $this->assertArrayHasKey('placeholder', $options);
                    $this->assertEquals('label.filter_by_tags', $options['placeholder']);

                    // Assert that the 'query_builder' option exists and is a callable.
                    $this->assertArrayHasKey('query_builder', $options);
                    $this->assertIsCallable($options['query_builder']);

                    // Test the behavior of the 'query_builder' callable itself.
                    // We need to mock the TagRepository that the callable expects as an argument.
                    $mockTagRepository = $this->createMock(TagRepository::class);
                    // We also need to mock the QueryBuilder that the TagRepository's createQueryBuilder will return.
                    $mockQueryBuilder = $this->createMock(QueryBuilder::class);

                    // Configure the mock TagRepository to return our mock QueryBuilder when createQueryBuilder is called.
                    $mockTagRepository->expects($this->once())
                        ->method('createQueryBuilder')
                        ->with('t') // Ensure it's called with the correct alias.
                        ->willReturn($mockQueryBuilder);

                    // Configure the mock QueryBuilder to expect orderBy to be called and return itself for chaining.
                    $mockQueryBuilder->expects($this->once())
                        ->method('orderBy')
                        ->with('t.name', 'ASC') // Ensure sorting is applied correctly.
                        ->willReturnSelf(); // Allow fluent interface.

                    // Execute the 'query_builder' callback, passing our mocked TagRepository.
                    // This will trigger the expectations set on $mockTagRepository and $mockQueryBuilder.
                    $resultQueryBuilder = call_user_func($options['query_builder'], $mockTagRepository);

                    // Assert that the callable returns the expected mock QueryBuilder.
                    $this->assertSame($mockQueryBuilder, $resultQueryBuilder);

                    return true; // Indicate that all assertions within this callback passed.
                })
            );

        // Call the buildForm method on the form type instance.
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
                $this->assertEquals(ContactListFiltersDto::class, $defaults['data_class']);

                // Assert that 'method' is set to 'GET'.
                $this->assertArrayHasKey('method', $defaults);
                $this->assertEquals('GET', $defaults['method']);

                // Assert that 'csrf_protection' is set to false.
                $this->assertArrayHasKey('csrf_protection', $defaults);
                $this->assertFalse($defaults['csrf_protection']);
                return true; // Indicate that all assertions within this callback passed.
            }));

        // Call the configureOptions method on the form type instance.
        $this->formType->configureOptions($resolver);
    }

    /**
     * Test the getBlockPrefix method to ensure it returns the correct block prefix.
     */
    public function testGetBlockPrefix(): void
    {
        // Assert that the method returns the expected string 'contact_filter'.
        $this->assertEquals('contact_filter', $this->formType->getBlockPrefix());
    }
}
