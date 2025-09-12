<?php

/**
 * Profile edit type.
 */

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class Profile edit type.
 */
class ProfileEditType extends AbstractType
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

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
