<?php

/**
 * User type.
 */

namespace App\Form\Type;

use App\Entity\User;
use App\Security\Voter\UserVoter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

/**
 * Class User type.
 */
class UserType extends AbstractType
{
    /**
     * Constructor.
     *
     * @param Security $security Security
     */
    public function __construct(private readonly Security $security)
    {
    }

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
        /** @var User|null $user */
        $user = $options['data'];

        $disableRolesField = false;
        if ($this->security->isGranted('ROLE_ADMIN')
            && $user
            && null !== $user->getId()
            && $this->security->getUser()
            && $user->getId() === $this->security->getUser()->getId()
        ) {
            $disableRolesField = !$this->security->isGranted(UserVoter::CAN_CHANGE_ROLES, $user);
        }

        $builder
            ->add('email', EmailType::class, [
                'label' => 'label.email',
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'label.roles',
                'choices' => [
                    'role.user' => 'ROLE_USER',
                    'role.admin' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
                'disabled' => $disableRolesField,
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
