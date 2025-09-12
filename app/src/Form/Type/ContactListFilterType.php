<?php

/**
 * Contact list filter type.
 */

namespace App\Form\Type;

use App\Dto\ContactListFiltersDto;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Contact list filter type.
 */
class ContactListFilterType extends AbstractType
{
    /**
     * Builds the form.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array<string, mixed> $options Form options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('tags', EntityType::class, [
            'class' => Tag::class,
            'choice_label' => 'name',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'label' => 'label.tags',
            'placeholder' => 'label.filter_by_tags',
            'query_builder' => fn (TagRepository $tagRepository) => $tagRepository->createQueryBuilder('t')
                ->orderBy('t.name', 'ASC'),
        ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactListFiltersDto::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    /**
     * This method specifies the block prefix for the form.
     *
     * @return string String contact filter
     */
    public function getBlockPrefix(): string
    {
        return 'contact_filter';
    }
}
