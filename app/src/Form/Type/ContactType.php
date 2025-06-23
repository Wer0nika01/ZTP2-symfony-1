<?php

/**
 * Contact type.
 */

namespace App\Form\Type;

use App\Entity\Contact;
use App\Form\DataTransformer\TagsDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Contact type.
 */
class ContactType extends AbstractType
{
    /**
     * Constructor.
     *
     * @param TagsDataTransformer $tagsDataTransformer
     */
    public function __construct(private readonly TagsDataTransformer $tagsDataTransformer)
    {
    }

    /**
     * Builds the form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'label.firstName',
                'required' => true,
                'attr' => ['maxlength' => 255],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'label.lastName',
                'required' => true,
                'attr' => ['maxlength' => 255],
            ])
            ->add('email', EmailType::class, [
                'label' => 'label.email',
                'required' => false,
                'attr' => ['maxlength' => 255],
            ])
            ->add('phone', TelType::class, [
                'label' => 'label.phone',
                'required' => false,
                'attr' => ['maxlength' => 50],
            ])
            ->add('address', TextareaType::class, [
                'label' => 'label.address',
                'required' => false,
                'attr' => ['maxlength' => 1000],
            ])
            ->add('company', TextType::class, [
                'label' => 'label.company',
                'required' => false,
                'attr' => ['maxlength' => 255],
            ])
            ->add('jobTitle', TextType::class, [
                'label' => 'label.jobTitle',
                'required' => false,
                'attr' => ['maxlength' => 255],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'label.notes',
                'required' => false,
                'attr' => ['maxlength' => 2000],
            ])
            ->add('tags', TextType::class, [
                'label' => 'label.tags',
                'required' => false,
            ]);
        $builder->get('tags')->addModelTransformer($this->tagsDataTransformer);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }

    /**
     * Gets the block prefix for this type.
     *
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'contact_form';
    }
}
