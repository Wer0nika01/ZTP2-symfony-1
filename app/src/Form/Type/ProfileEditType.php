<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class ProfileEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'label.email',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'message.email_not_blank']),
                    new Email(['message' => 'message.invalid_email']),
                ],
            ])
            // Uncomment these fields now
            ->add('firstName', TextType::class, [
                'label' => 'label.first_name',
                'required' => false,
                'constraints' => [
                    new Length(max: 255),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'label.last_name',
                'required' => false,
                'constraints' => [
                    new Length(max: 255),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}