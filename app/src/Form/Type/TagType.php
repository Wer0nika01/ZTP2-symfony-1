<?php

/** * Tag type. */

namespace App\Form\Type;

use App\Entity\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** * Class TagType. */
class TagType extends AbstractType
{
    /** * Builds the form.
     *
     * @param FormBuilderInterface $builder
     * @param array $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'label.name',
            ]);
    }

    /** * Configures the options for this type.
     *
     * @param OptionsResolver $resolver */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tag::class,
        ]);
    }
}
