<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\Category;
use App\Entity\Enum\EventStatus;
use App\Entity\Event; // The entity the form is for
use App\Entity\Tag;
use App\Form\Type\EventType; // The form type under test
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType; // FIX: Corrected use statement
use Symfony\Component\Form\Extension\Core\Type\EnumType; // FIX: Corrected use statement
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventTypeTest extends TestCase
{
    private EventType $formType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formType = new EventType();
    }

    /**
     * Test the buildForm method to ensure all fields are correctly added with their options.
     */
    public function testBuildForm(): void
    {
        // Mock the FormBuilderInterface that buildForm will interact with.
        $builder = $this->createMock(FormBuilderInterface::class);

        // Expect 'add' to be called 9 times in total.
        // Use withConsecutive to define expectations for each call sequentially.
        $builder->expects($this->exactly(9))
            ->method('add')
            ->withConsecutive(
            // 1. 'title' field
                [
                    'title',
                    TextType::class,
                    [
                        'label' => 'label.title',
                        'required' => true,
                        'attr' => ['max_length' => 255],
                    ],
                ],
                // 2. 'description' field
                [
                    'description',
                    TextareaType::class,
                    [
                        'required' => false,
                        'label' => 'label.description',
                        'attr' => ['rows' => 7],
                    ],
                ],
                // 3. 'startTime' field
                [
                    'startTime',
                    DateTimeType::class,
                    [
                        'label' => 'label.startTime',
                        'widget' => 'single_text',
                        'input' => 'datetime_immutable',
                        'required' => true,
                    ],
                ],
                // 4. 'endTime' field
                [
                    'endTime',
                    DateTimeType::class,
                    [
                        'label' => 'label.endTime',
                        'widget' => 'single_text',
                        'input' => 'datetime_immutable',
                        'required' => false,
                    ],
                ],
                // 5. 'location' field
                [
                    'location',
                    TextType::class,
                    [
                        'label' => 'label.location',
                        'required' => false,
                        'attr' => ['max_length' => 255],
                    ],
                ],
                // 6. 'isAllDay' field
                [
                    'isAllDay',
                    CheckboxType::class,
                    [
                        'label' => 'label.isAllDay',
                        'required' => false,
                    ],
                ],
                // 7. 'category' field
                [
                    'category',
                    EntityType::class,
                    $this->callback(function (array $options) {
                        $this->assertArrayHasKey('class', $options);
                        $this->assertEquals(Category::class, $options['class']);
                        $this->assertArrayHasKey('choice_label', $options);
                        $this->assertIsCallable($options['choice_label']); // It's a callable function
                        $this->assertArrayHasKey('label', $options);
                        $this->assertEquals('label.category', $options['label']);
                        $this->assertArrayHasKey('placeholder', $options);
                        $this->assertEquals('label.none', $options['placeholder']);
                        $this->assertArrayHasKey('required', $options);
                        $this->assertTrue($options['required']);

                        // Test the choice_label callback for category
                        $mockCategory = $this->createMock(Category::class);
                        $mockCategory->method('getTitle')->willReturn('Test Category Title');
                        $this->assertEquals('Test Category Title', call_user_func($options['choice_label'], $mockCategory));

                        return true;
                    }),
                ],
                // 8. 'tags' field
                [
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
                        $this->assertArrayHasKey('label', $options);
                        $this->assertEquals('label.tags', $options['label']);
                        $this->assertArrayHasKey('required', $options);
                        $this->assertFalse($options['required']);
                        $this->assertArrayHasKey('by_reference', $options);
                        $this->assertFalse($options['by_reference']);

                        return true;
                    }),
                ],
                // 9. 'status' field
                [
                    'status',
                    EnumType::class,
                    $this->callback(function (array $options) {
                        $this->assertArrayHasKey('class', $options);
                        $this->assertEquals(EventStatus::class, $options['class']);
                        $this->assertArrayHasKey('choice_label', $options);
                        $this->assertIsCallable($options['choice_label']); // It's a callable function
                        $this->assertArrayHasKey('label', $options);
                        $this->assertEquals('label.status', $options['label']);
                        $this->assertArrayHasKey('required', $options);
                        $this->assertTrue($options['required']);

                        // Test the choice_label callback for status enum
                        $this->assertEquals('label.personal', call_user_func($options['choice_label'], EventStatus::PERSONAL));
                        $this->assertEquals('label.important', call_user_func($options['choice_label'], EventStatus::IMPORTANT));
                        $this->assertEquals('label.work', call_user_func($options['choice_label'], EventStatus::WORK));

                        return true;
                    }),
                ]
            )
            ->willReturnSelf(); // Allow method chaining for 'add'

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
                $this->assertEquals(Event::class, $defaults['data_class']);
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
        $this->assertEquals('event', $this->formType->getBlockPrefix());
    }
}
