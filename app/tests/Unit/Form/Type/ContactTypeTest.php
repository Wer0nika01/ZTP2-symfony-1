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
        $matcher = $this->exactly(9);

        $builder->expects($matcher)
        ->method('add')->willReturnCallback(function (...$parameters) use ($matcher, $builder) {
            if ($matcher->getInvocationCount() === 1) {
                $this->assertSame('firstName', $parameters[0]);
                $this->assertSame(TextType::class, $parameters[1]);
                $this->assertSame([
                    'label' => 'label.firstName',
                    'required' => true,
                    'attr' => ['maxlength' => 255],
                ], $parameters[2]);
            }
            if ($matcher->getInvocationCount() === 2) {
                $this->assertSame('lastName', $parameters[0]);
                $this->assertSame(TextType::class, $parameters[1]);
                $this->assertSame([
                    'label' => 'label.lastName',
                    'required' => true,
                    'attr' => ['maxlength' => 255],
                ], $parameters[2]);
            }
            if ($matcher->getInvocationCount() === 3) {
                $this->assertSame('email', $parameters[0]);
                $this->assertSame(EmailType::class, $parameters[1]);
                $this->assertSame([
                    'label' => 'label.email',
                    'required' => false,
                    'attr' => ['maxlength' => 255],
                ], $parameters[2]);
            }
            if ($matcher->getInvocationCount() === 4) {
                $this->assertSame('phone', $parameters[0]);
                $this->assertSame(TelType::class, $parameters[1]);
                $this->assertSame([
                    'label' => 'label.phone',
                    'required' => false,
                    'attr' => ['maxlength' => 50],
                ], $parameters[2]);
            }
            if ($matcher->getInvocationCount() === 5) {
                $this->assertSame('address', $parameters[0]);
                $this->assertSame(TextareaType::class, $parameters[1]);
                $this->assertSame([
                    'label' => 'label.address',
                    'required' => false,
                    'attr' => ['maxlength' => 1000],
                ], $parameters[2]);
            }
            if ($matcher->getInvocationCount() === 6) {
                $this->assertSame('company', $parameters[0]);
                $this->assertSame(TextType::class, $parameters[1]);
                $this->assertSame([
                    'label' => 'label.company',
                    'required' => false,
                    'attr' => ['maxlength' => 255],
                ], $parameters[2]);
            }
            if ($matcher->getInvocationCount() === 7) {
                $this->assertSame('jobTitle', $parameters[0]);
                $this->assertSame(TextType::class, $parameters[1]);
                $this->assertSame([
                    'label' => 'label.jobTitle',
                    'required' => false,
                    'attr' => ['maxlength' => 255],
                ], $parameters[2]);
            }
            if ($matcher->getInvocationCount() === 8) {
                $this->assertSame('notes', $parameters[0]);
                $this->assertSame(TextareaType::class, $parameters[1]);
                $this->assertSame([
                    'label' => 'label.notes',
                    'required' => false,
                    'attr' => ['maxlength' => 2000],
                ], $parameters[2]);
            }
            if ($matcher->getInvocationCount() === 9) {
                $this->assertSame('tags', $parameters[0]);
                $this->assertSame(TextType::class, $parameters[1]);
                $this->assertSame([
                    'label' => 'label.tags',
                    'required' => false,
                ], $parameters[2]);
            }
            return $builder;
        });

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
