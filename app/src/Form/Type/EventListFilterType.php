<?php

namespace App\Form\Type;

use App\Dto\EventListFiltersDto;
use App\Entity\Category;
use App\Entity\Enum\EventStatus;
use App\Entity\Tag;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventListFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'title',
                'required' => false,
                'placeholder' => 'label.filter_by_category',
                'label' => 'label.category',
                'query_builder' => function (CategoryRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.title', 'ASC');
                },
            ])
            ->add('status', EnumType::class, [
                'class' => EventStatus::class,
                'choice_label' => fn (EventStatus $status) => $status->getLabel(),
                'required' => false,
                'placeholder' => 'label.filter_by_status',
                'label' => 'label.status',
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => 'label.tags',
                'placeholder' => 'label.filter_by_tags',
                'query_builder' => function (TagRepository $tr) {
                    return $tr->createQueryBuilder('t')
                        ->orderBy('t.name', 'ASC');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventListFiltersDto::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    /**
     * This method specifies the block prefix for the form.
     * It will be used in the URL query string (e.g., ?event_filter[category]=...).
     */
    public function getBlockPrefix(): string
    {
        return 'event_filter';
    }
}