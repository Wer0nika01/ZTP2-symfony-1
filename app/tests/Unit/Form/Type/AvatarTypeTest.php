<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\Avatar;
use App\Form\Type\AvatarType; // The form type under test
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class AvatarTypeTest extends TestCase
{
    private AvatarType $formType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formType = new AvatarType();
    }

    /**
     * Test the buildForm method to ensure the 'file' field is correctly added.
     */
    public function testBuildForm(): void
    {
        // Mock the FormBuilderInterface
        $builder = $this->createMock(FormBuilderInterface::class);

        // Expect the 'add' method to be called once
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'file', // Field name
                FileType::class, // Field type
                $this->callback(function (array $options) {
                    // Assertions for the options passed to the 'file' field
                    $this->assertArrayHasKey('mapped', $options);
                    $this->assertFalse($options['mapped']);

                    $this->assertArrayHasKey('label', $options);
                    $this->assertEquals('label.avatar', $options['label']);

                    $this->assertArrayHasKey('required', $options);
                    $this->assertTrue($options['required']);

                    $this->assertArrayHasKey('constraints', $options);

                    // Assertions for the Image constraint
                    $constraint = $options['constraints'];
                    $this->assertInstanceOf(Image::class, $constraint);
                    // FIX: Expect 1024 * 1000 (kilobytes in decimal system) for maxSize
                    $this->assertEquals(1024 * 1000, $constraint->maxSize); // 1024k = 1024000 bytes
                    $this->assertEquals(
                        ['image/png', 'image/jpeg', 'image/pjpeg', 'image/jpeg', 'image/pjpeg'],
                        $constraint->mimeTypes
                    );

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
                $this->assertEquals(Avatar::class, $defaults['data_class']);
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
        $this->assertEquals('avatar', $this->formType->getBlockPrefix());
    }
}
