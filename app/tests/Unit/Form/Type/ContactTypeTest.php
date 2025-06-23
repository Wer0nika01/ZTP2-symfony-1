<?php

/**
 * Contact type Test.
 */

namespace App\Tests\Unit\Form\Type;

use App\Entity\Contact;
use App\Form\Type\ContactType;
use App\Form\DataTransformer\TagsDataTransformer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Contact type Test.
 */
class ContactTypeTest extends TestCase
{
    private MockObject|TagsDataTransformer $tagsDataTransformer;
    private ContactType $formType;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tagsDataTransformer = $this->createMock(TagsDataTransformer::class);
        $this->formType = new ContactType($this->tagsDataTransformer);
    }

    /**
     * Test the buildForm method to ensure all fields and transformer are correctly added.
     */
    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $tagsFieldBuilder = $this->createMock(FormBuilderInterface::class);
        $tagsFieldBuilder->expects($this->once())
            ->method('addModelTransformer')
            ->with($this->tagsDataTransformer);

        $builder->expects($this->exactly(9))
        ->method('add')
            ->withConsecutive(
                ['firstName', TextType::class, [
                    'label' => 'label.firstName',
                    'required' => true,
                    'attr' => ['maxlength' => 255],
                ], ],
                ['lastName', TextType::class, [
                    'label' => 'label.lastName',
                    'required' => true,
                    'attr' => ['maxlength' => 255],
                ], ],
                ['email', EmailType::class, [
                    'label' => 'label.email',
                    'required' => false,
                    'attr' => ['maxlength' => 255],
                ], ],
                ['phone', TelType::class, [
                    'label' => 'label.phone',
                    'required' => false,
                    'attr' => ['maxlength' => 50],
                ], ],
                ['address', TextareaType::class, [
                    'label' => 'label.address',
                    'required' => false,
                    'attr' => ['maxlength' => 1000],
                ], ],
                ['company', TextType::class, [
                    'label' => 'label.company',
                    'required' => false,
                    'attr' => ['maxlength' => 255],
                ], ],
                ['jobTitle', TextType::class, [
                    'label' => 'label.jobTitle',
                    'required' => false,
                    'attr' => ['maxlength' => 255],
                ], ],
                ['notes', TextareaType::class, [
                    'label' => 'label.notes',
                    'required' => false,
                    'attr' => ['maxlength' => 2000],
                ], ],
                ['tags', TextType::class, [
                    'label' => 'label.tags',
                    'required' => false,
                ], ]
            )
            ->willReturnSelf();

        $builder->expects($this->once())
            ->method('get')
            ->with('tags')
            ->willReturn($tagsFieldBuilder);

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
                $this->assertEquals(Contact::class, $defaults['data_class']);

                return true;
            }));

        $this->formType->configureOptions($resolver);
    }

    /**
     * Test the getBlockPrefix method to ensure it returns the correct prefix.
     */
    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('contact_form', $this->formType->getBlockPrefix());
    }
}
