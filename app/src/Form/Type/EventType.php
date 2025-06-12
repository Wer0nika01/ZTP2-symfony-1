<?php
/**
 * Event type.
 */

namespace App\Form\Type;

use App\Entity\Category;
use App\Entity\Enum\EventStatus;
use App\Entity\Event;
use App\Entity\Tag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\DataTransformer\TagsDataTransformer;

/**
 * Class EventType.
 */
class EventType extends AbstractType
{
    private TagsDataTransformer $tagsDataTransformer;

    public function __construct(TagsDataTransformer $tagsDataTransformer)
    {
        $this->tagsDataTransformer = $tagsDataTransformer;
    }
    /**
     * Builds the form.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array<string, mixed> $options Form options
     *
     * @see FormTypeExtensionInterface::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'title',
            TextType::class,
            [
                'label' => 'label.title',
                'required' => true,
                'attr' => ['max_length' => 255],
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'required' => false,
                'label' => 'label.description',
                'attr' => ['rows' => 7],
            ]
        );

        $builder->add(
            'startTime',
            DateTimeType::class,
            [
                'label' => 'label.startTime',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => true,
            ]
        );

        $builder->add(
            'endTime',
            DateTimeType::class,
            [
                'label' => 'label.endTime',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ]
        );

        $builder->add(
            'location',
            TextType::class,
            [
                'label' => 'label.location',
                'required' => false,
                'attr' => ['max_length' => 255],
            ]
        );

        $builder->add(
            'isAllDay',
            CheckboxType::class,
            [
                'label' => 'label.isAllDay',
                'required' => false,
            ]
        );


        $builder->add(
            'category',
            EntityType::class,
            [
                'class' => Category::class,
                'choice_label' => function ($category): string {
                    return $category->getTitle();
                },
                'label' => 'label.category',
                'placeholder' => 'label.none',
                'required' => true,
            ]
        );
        $builder->add(
            'tags',
            TextType::class,
            [
                'label' => 'label.tags',
                'required' => false,
            ]
        )
            ->add('status', EnumType::class, [
                'class' => EventStatus::class,
                'choice_label' => fn (EventStatus $choice) => $choice->getLabel(),
                'label' => 'label.status',
                'required' => true,
                'by_reference' => false,
            ]);
        $builder->get('tags')->addModelTransformer($this->tagsDataTransformer);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Event::class]);
    }

    /**
     * Returns the prefix of the template block name for this type.
     *
     * The block prefix defaults to the underscored short class name with
     * the "Type" suffix removed (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix(): string
    {
        return 'event';
    }
}