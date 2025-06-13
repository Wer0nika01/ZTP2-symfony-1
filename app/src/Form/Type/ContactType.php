<?php

namespace App\Form\Type;

use App\Entity\Contact;
use App\Entity\Tag;
use App\Form\DataTransformer\TagsDataTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactType extends AbstractType
{
    public function __construct(private readonly TagsDataTransformer $tagsDataTransformer)
    {
    }

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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'contact_form';
    }
}
