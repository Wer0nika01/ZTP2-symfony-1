<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\Contact;
use App\Form\Type\ContactType; // The form type under test
use App\Form\DataTransformer\TagsDataTransformer; // The transformer dependency
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType; // Corrected path
use Symfony\Component\Form\Extension\Core\Type\TelType; // Corrected path
use Symfony\Component\Form\Extension\Core\Type\TextareaType; // Corrected path
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface; // For mocking the 'tags' field (itself, not for addModelTransformer)
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactTypeTest extends TestCase
{
    private MockObject|TagsDataTransformer $tagsDataTransformer;
    private ContactType $formType;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock the TagsDataTransformer dependency
        $this->tagsDataTransformer = $this->createMock(TagsDataTransformer::class);
        // Instantiate the form type with its mocked dependency
        $this->formType = new ContactType($this->tagsDataTransformer);
    }

    /**
     * Test the buildForm method to ensure all fields and transformer are correctly added.
     */
    public function testBuildForm(): void
    {
        // Mock the FormBuilderInterface
        $builder = $this->createMock(FormBuilderInterface::class);

        // FIX: Mock the FormBuilderInterface for the 'tags' field so we can test addModelTransformer.
        // The builder->get('tags') method returns a FormBuilderInterface for that specific field.
        $tagsFieldBuilder = $this->createMock(FormBuilderInterface::class); // Changed to FormBuilderInterface
        $tagsFieldBuilder->expects($this->once())
            ->method('addModelTransformer')
            ->with($this->tagsDataTransformer); // Assert the transformer is applied

        // FIX: Configure the builder's 'add' method to return itself for chaining.
        // This solves the TypeError by ensuring 'add' always returns the expected type.
        // Use withConsecutive to assert arguments for each of the 9 'add' calls in order.
        $builder->expects($this->exactly(9)) // Expect 'add' to be called 9 times
        ->method('add')
            ->withConsecutive(
                ['firstName', TextType::class, [
                    'label' => 'label.firstName',
                    'required' => true,
                    'attr' => ['maxlength' => 255],
                ]],
                ['lastName', TextType::class, [
                    'label' => 'label.lastName',
                    'required' => true,
                    'attr' => ['maxlength' => 255],
                ]],
                ['email', EmailType::class, [
                    'label' => 'label.email',
                    'required' => false,
                    'attr' => ['maxlength' => 255],
                ]],
                ['phone', TelType::class, [
                    'label' => 'label.phone',
                    'required' => false,
                    'attr' => ['maxlength' => 50],
                ]],
                ['address', TextareaType::class, [
                    'label' => 'label.address',
                    'required' => false,
                    'attr' => ['maxlength' => 1000],
                ]],
                ['company', TextType::class, [
                    'label' => 'label.company',
                    'required' => false,
                    'attr' => ['maxlength' => 255],
                ]],
                ['jobTitle', TextType::class, [
                    'label' => 'label.jobTitle',
                    'required' => false,
                    'attr' => ['maxlength' => 255],
                ]],
                ['notes', TextareaType::class, [
                    'label' => 'label.notes',
                    'required' => false,
                    'attr' => ['maxlength' => 2000],
                ]],
                ['tags', TextType::class, [
                    'label' => 'label.tags',
                    'required' => false,
                ]]
            )
            ->willReturnSelf(); // Always return the builder itself for chaining.

        // FIX: Configure the builder's 'get' method to return the specific tagsFieldBuilder mock
        // when 'tags' is requested.
        $builder->expects($this->once())
            ->method('get')
            ->with('tags')
            ->willReturn($tagsFieldBuilder);

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
                $this->assertEquals(Contact::class, $defaults['data_class']);
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
        $this->assertEquals('contact_form', $this->formType->getBlockPrefix());
    }
}
