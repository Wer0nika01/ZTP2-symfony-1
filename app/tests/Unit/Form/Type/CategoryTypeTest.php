<?php

/**
 * Category type Test.
 */

namespace App\Tests\Unit\Form\Type;

use App\Entity\Category;
use App\Form\Type\CategoryType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Category type Test.
 */
class CategoryTypeTest extends TestCase
{
    private CategoryType $formType;

    /**
     * Set up.
     */
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
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('add')
            ->with(
                'title',
                TextType::class,
                $this->callback(function (array $options) {
                    $this->assertArrayHasKey('label', $options);
                    $this->assertEquals('label.title', $options['label']);

                    $this->assertArrayHasKey('required', $options);
                    $this->assertTrue($options['required']);

                    $this->assertArrayHasKey('attr', $options);
                    $this->assertIsArray($options['attr']);
                    $this->assertArrayHasKey('max_length', $options['attr']);
                    $this->assertEquals(64, $options['attr']['max_length']);

                    return true;
                })
            );

        $this->formType->buildForm($builder, []);
    }

    /**
     * Test the configureOptions method to ensure the data_class is correctly set.
     */
    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function (array $defaults) {
                $this->assertArrayHasKey('data_class', $defaults);
                $this->assertEquals(Category::class, $defaults['data_class']);

                return true;
            }));

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
