<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\Category;
use App\Form\Type\CategoryType; // The form type under test
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryTypeTest extends TestCase
{
    private CategoryType $formType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formType = new CategoryType();
    }

    /**
     * Test the buildForm method to ensure the 'title' field is correctly added.
     */
    public function testBuildForm(): void
    {
        // Mock the FormBuilderInterface
        $builder = $this->createMock(FormBuilderInterface::class);

        // Expect the 'add' method to be called once with specific arguments
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'title', // Field name
                TextType::class, // Field type
                $this->callback(function (array $options) {
                    // Assertions for the options passed to the 'title' field
                    $this->assertArrayHasKey('label', $options);
                    $this->assertEquals('label.title', $options['label']);

                    $this->assertArrayHasKey('required', $options);
                    $this->assertTrue($options['required']);

                    $this->assertArrayHasKey('attr', $options);
                    $this->assertIsArray($options['attr']);
                    $this->assertArrayHasKey('max_length', $options['attr']);
                    $this->assertEquals(64, $options['attr']['max_length']);

                    return true; // Indicate that the callback assertion passed
                })
            );

        // Call the method under test
        $this->formType->buildForm($builder, []);
    }

    /**
     * Test the configureOptions method to ensure the data_class is correctly set.
     */
    public function testConfigureOptions(): void
    {
        // Mock the OptionsResolver
        $resolver = $this->createMock(OptionsResolver::class);

        // Expect setDefaults to be called once with an array containing 'data_class'
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function (array $defaults) {
                // Assert that 'data_class' key exists and has the correct value
                $this->assertArrayHasKey('data_class', $defaults);
                $this->assertEquals(Category::class, $defaults['data_class']);
                return true;
            }));

        // Call the method under test
        $this->formType->configureOptions($resolver);
    }

    /**
     * Test the getBlockPrefix method to ensure it returns the correct prefix.
     */
    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('category', $this->formType->getBlockPrefix());
    }
}
