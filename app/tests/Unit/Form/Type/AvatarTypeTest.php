<?php

/**
 * Avatar type Test.
 */

namespace App\Tests\Unit\Form\Type;

use App\Entity\Avatar;
use App\Form\Type\AvatarType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

/**
 * Class Avatar type Test.
 */
class AvatarTypeTest extends TestCase
{
    private AvatarType $formType;

    /**
     * Set up.
     */
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
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('add')
            ->with('file', FileType::class, $this->callback(function (array $options) {
                $this->assertArrayHasKey('mapped', $options);
                $this->assertFalse($options['mapped']);

                $this->assertArrayHasKey('label', $options);
                $this->assertEquals('label.avatar', $options['label']);

                $this->assertArrayHasKey('required', $options);
                $this->assertTrue($options['required']);

                $this->assertArrayHasKey('constraints', $options);

                $constraint = $options['constraints'];
                $this->assertInstanceOf(Image::class, $constraint);
                $this->assertEquals(1024 * 1000, $constraint->maxSize); // 1024k = 1024000 bytes
                $this->assertEquals(
                    ['image/png', 'image/jpeg', 'image/pjpeg', 'image/jpeg', 'image/pjpeg'],
                    $constraint->mimeTypes
                );

                return true;
            }));

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
                $this->assertEquals(Avatar::class, $defaults['data_class']);

                return true;
            }));

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
