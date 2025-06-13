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
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ContactListFilterType extends AbstractType
{
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
     * It will be used in the URL query string (e.g., ?contact_filter[tags]=...).
     */
    public function getBlockPrefix(): string
    {
        return 'contact_filter';
    }
}
