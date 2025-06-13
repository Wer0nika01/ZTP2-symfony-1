<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * Class UserVoter
 */
class UserVoter extends Voter
{
    public const VIEW = 'USER_VIEW';
    public const EDIT = 'USER_EDIT';
    public const DELETE = 'USER_DELETE';
    public const CAN_CHANGE_ROLES = 'CAN_CHANGE_ROLES';

    /**
     * Constructor
     *
     * @param Security $security
     */
    public function __construct(private readonly Security $security, private readonly userRepository $userRepository)
    {
    }

    /**
     * Determines if the voter supports the given attribute and subject.
     *
     * @param string $attribute The attribute to check (e.g., 'USER_EDIT')
     * @param mixed  $subject   The object to check (e.g., a User entity)
     *
     * @return bool True if the voter supports the attribute and subject, false otherwise
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CAN_CHANGE_ROLES])) {
            return false;
        }

        if (!$subject instanceof User) {
            return false;
        }

        return true;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     *
     * @param string         $attribute The attribute to check
     * @param mixed          $subject   The object to check
     * @param TokenInterface $token     The security token
     *
     * @return bool True if access is granted, false otherwise
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $loggedInUser = $token->getUser();

        if (!$loggedInUser instanceof UserInterface) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var User $userToOperateOn */
        $userToOperateOn = $subject;

        if ($this->security->isGranted('ROLE_ADMIN')) {
            if ($attribute === self::CAN_CHANGE_ROLES) {
                try {
                    return $this->canAdminChangeRoles($userToOperateOn, $loggedInUser);
                } catch (NoResultException|NonUniqueResultException $e) {
                    return false;
                }
            }
            return true;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($userToOperateOn, $loggedInUser),
            self::EDIT => $this->canEdit($userToOperateOn, $loggedInUser),
            self::DELETE => $this->canDelete($userToOperateOn, $loggedInUser),
            default => false,
        };
    }

    /**
     * Checks if the logged-in user can view the given user.
     *
     * @param User        $userToOperateOn The user being viewed
     * @param UserInterface $loggedInUser    The currently logged-in user
     *
     * @return bool
     */
    private function canView(User $userToOperateOn, UserInterface $loggedInUser): bool
    {
        return $loggedInUser->getId() === $userToOperateOn->getId();
    }

    /**
     * Checks if the logged-in user can edit the given user.
     *
     * @param User        $userToOperateOn The user being edited
     * @param UserInterface $loggedInUser    The currently logged-in user
     *
     * @return bool
     */
    private function canEdit(User $userToOperateOn, UserInterface $loggedInUser): bool
    {
        return $loggedInUser->getId() === $userToOperateOn->getId();
    }

    /**
     * Checks if the logged-in user can delete the given user.
     *
     * @param User        $userToOperateOn The user being deleted
     * @param UserInterface $loggedInUser    The currently logged-in user
     *
     * @return bool
     */
    private function canDelete(User $userToOperateOn, UserInterface $loggedInUser): bool
    {
        return false;
    }

    /**
     * Checks if an administrator can change the roles of a given user (including their own).
     *
     * @param User          $userToOperateOn The user whose roles are being modified
     * @param UserInterface $loggedInUser    The currently logged-in user (who is an administrator)
     *
     * @return bool
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function canAdminChangeRoles(User $userToOperateOn, UserInterface $loggedInUser): bool
    {
        if ($userToOperateOn->getId() !== $loggedInUser->getId()) {
            return true;
        }

        $currentAdminCount = $this -> userRepository->countAdmins();

        if ($currentAdminCount === 1) {
            return false;
        }

        return true;
    }
}