<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'label.email',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'validation.email.not_blank',
                    ]),
                    new Assert\Email([
                        'message' => 'validation.email.invalid',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options'  => ['label' => 'label.password'],
                'second_options' => ['label' => 'label.password_repeat'],
                'invalid_message' => 'validation.password.mismatch',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'validation.password.not_blank',
                    ]),
                    new Assert\Length([
                        'min' => 6,
                        'minMessage' => 'validation.password.min_length',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}