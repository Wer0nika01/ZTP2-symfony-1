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
        $matcher = $this->exactly(9);

        $builder->expects($matcher)
            ->method('add')->willReturnCallback(function (...$parameters) use ($matcher, $builder) {
                if (1 === $matcher->getInvocationCount()) {
                    $this->assertSame('title', $parameters[0]);
                    $this->assertSame(TextType::class, $parameters[1]);
                    $this->assertSame([
                        'label' => 'label.title',
                        'required' => true,
                        'attr' => ['max_length' => 255],
                    ], $parameters[2]);
                }
                if (2 === $matcher->getInvocationCount()) {
                    $this->assertSame('description', $parameters[0]);
                    $this->assertSame(TextareaType::class, $parameters[1]);
                    $this->assertSame([
                        'required' => false,
                        'label' => 'label.description',
                        'attr' => ['rows' => 7],
                    ], $parameters[2]);
                }
                if (3 === $matcher->getInvocationCount()) {
                    $this->assertSame('startTime', $parameters[0]);
                    $this->assertSame(DateTimeType::class, $parameters[1]);
                    $this->assertSame([
                        'label' => 'label.startTime',
                        'widget' => 'single_text',
                        'input' => 'datetime_immutable',
                        'required' => true,
                    ], $parameters[2]);
                }
                if (4 === $matcher->getInvocationCount()) {
                    $this->assertSame('endTime', $parameters[0]);
                    $this->assertSame(DateTimeType::class, $parameters[1]);
                    $this->assertSame([
                        'label' => 'label.endTime',
                        'widget' => 'single_text',
                        'input' => 'datetime_immutable',
                        'required' => false,
                    ], $parameters[2]);
                }
                if (5 === $matcher->getInvocationCount()) {
                    $this->assertSame('location', $parameters[0]);
                    $this->assertSame(TextType::class, $parameters[1]);
                    $this->assertSame([
                        'label' => 'label.location',
                        'required' => false,
                        'attr' => ['max_length' => 255],
                    ], $parameters[2]);
                }
                if (6 === $matcher->getInvocationCount()) {
                    $this->assertSame('isAllDay', $parameters[0]);
                    $this->assertSame(CheckboxType::class, $parameters[1]);
                    $this->assertSame([
                        'label' => 'label.isAllDay',
                        'required' => false,
                    ], $parameters[2]);
                }
                if (7 === $matcher->getInvocationCount()) {
                    $this->assertSame('category', $parameters[0]);
                    $this->assertSame(EntityType::class, $parameters[1]);
                    $callback = function (array $options) {
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
                        $mockCategory->method('getTitle');
                        $this->assertEquals('Test Category Title', call_user_func($options['choice_label'], $mockCategory));

                        return true;
                    };
                    $this->assertTrue($callback($parameters[2]));
                }
                if (8 === $matcher->getInvocationCount()) {
                    $this->assertSame('tags', $parameters[0]);
                    $this->assertSame(EntityType::class, $parameters[1]);
                    $callback = function (array $options) {
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
                    };
                    $this->assertTrue($callback($parameters[2]));
                }
                if (9 === $matcher->getInvocationCount()) {
                    $this->assertSame('status', $parameters[0]);
                    $this->assertSame(EnumType::class, $parameters[1]);
                    $callback = function (array $options) {
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
                    };
                    $this->assertTrue($callback($parameters[2]));
                }

                return $builder;
            });

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
