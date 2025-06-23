<?php

/**
 * Event type Test.
 */

namespace App\Tests\Unit\Form\Type;

use App\Entity\Category;
use App\Entity\Enum\EventStatus;
use App\Entity\Event;
use App\Entity\Tag;
use App\Form\Type\EventType;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Event type Test.
 */
class EventTypeTest extends TestCase
{
    private EventType $formType;

    /**
     * Set up.
     */
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
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->exactly(9))
            ->method('add')
            ->withConsecutive(
                [
                    'title',
                    TextType::class,
                    [
                        'label' => 'label.title',
                        'required' => true,
                        'attr' => ['max_length' => 255],
                    ],
                ],
                [
                    'description',
                    TextareaType::class,
                    [
                        'required' => false,
                        'label' => 'label.description',
                        'attr' => ['rows' => 7],
                    ],
                ],
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
                [
                    'location',
                    TextType::class,
                    [
                        'label' => 'label.location',
                        'required' => false,
                        'attr' => ['max_length' => 255],
                    ],
                ],
                [
                    'isAllDay',
                    CheckboxType::class,
                    [
                        'label' => 'label.isAllDay',
                        'required' => false,
                    ],
                ],
                [
                    'category',
                    EntityType::class,
                    $this->callback(function (array $options) {
                        $this->assertArrayHasKey('class', $options);
                        $this->assertEquals(Category::class, $options['class']);
                        $this->assertArrayHasKey('choice_label', $options);
                        $this->assertIsCallable($options['choice_label']);
                        $this->assertArrayHasKey('label', $options);
                        $this->assertEquals('label.category', $options['label']);
                        $this->assertArrayHasKey('placeholder', $options);
                        $this->assertEquals('label.none', $options['placeholder']);
                        $this->assertArrayHasKey('required', $options);
                        $this->assertTrue($options['required']);

                        $mockCategory = $this->createMock(Category::class);
                        $mockCategory->method('getTitle')->willReturn('Test Category Title');
                        $this->assertEquals('Test Category Title', call_user_func($options['choice_label'], $mockCategory));

                        return true;
                    }),
                ],
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

                        $this->assertEquals('label.personal', call_user_func($options['choice_label'], EventStatus::PERSONAL));
                        $this->assertEquals('label.important', call_user_func($options['choice_label'], EventStatus::IMPORTANT));
                        $this->assertEquals('label.work', call_user_func($options['choice_label'], EventStatus::WORK));

                        return true;
                    }),
                ]
            )
            ->willReturnSelf();

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
                $this->assertEquals(Event::class, $defaults['data_class']);

                return true;
            }));

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
