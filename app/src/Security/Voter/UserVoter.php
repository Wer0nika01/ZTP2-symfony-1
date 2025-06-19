<?php

/**
 * User Voter.
 */

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
 * Class UserVoter.
 */
class UserVoter extends Voter
{
    public const VIEW = 'USER_VIEW';

    public const EDIT = 'USER_EDIT';

    public const DELETE = 'USER_DELETE';

    public const CAN_CHANGE_ROLES = 'CAN_CHANGE_ROLES';

    public const BLOCK = 'USER_BLOCK';

    /**
     * Constructor.
     *
     * @param Security       $security       Symfony Security component
     * @param UserRepository $userRepository User repository
     */
    public function __construct(private readonly Security $security, private readonly UserRepository $userRepository)
    {
    }

    /**
     * Determines if the voter supports the given attribute and subject.
     *
     * @param string $attribute The attribute to check
     * @param mixed  $subject   The subject to check against
     *
     * @return bool True if the voter supports the attribute and subject, false otherwise
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CAN_CHANGE_ROLES, self::BLOCK])) { // Dodano BLOCK
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
     * @param mixed          $subject   The subject to check against (should be a User entity)
     * @param TokenInterface $token     The current security token
     *
     * @return bool True if access is granted, false otherwise
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $loggedInUser = $token->getUser();

        if (!$loggedInUser instanceof UserInterface) {
            return false;
        }

        /** @var User $userToOperateOn */
        $userToOperateOn = $subject;

        if ($this->security->isGranted('ROLE_ADMIN') && $attribute === self::CAN_CHANGE_ROLES) {
            try {
                return $this->canAdminChangeRoles($userToOperateOn, $loggedInUser);
            } catch (NoResultException|NonUniqueResultException) {
                return false;
            }
        }

        return match ($attribute) {
            self::VIEW => $this->canView($userToOperateOn, $loggedInUser),
            self::EDIT => $this->canEdit($userToOperateOn, $loggedInUser),
            self::DELETE => $this->canDelete(),
            self::BLOCK => $this->canBlock($userToOperateOn, $loggedInUser),
            default => false,
        };
    }

    /**
     * Checks if the logged-in user can view the given user.
     *
     * @param User          $userToOperateOn The user being viewed
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
     * @param User          $userToOperateOn The user being edited
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
     *
     * @return bool
     */
    private function canDelete(): bool
    {
        return false;
    }

    /**
     * Checks if an administrator can change the roles of a given user (including their own).
     *
     * @param User          $userToOperateOn The user whose roles are being changed
     * @param UserInterface $loggedInUser    The currently logged-in administrator
     *
     * @return bool
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function canAdminChangeRoles(User $userToOperateOn, UserInterface $loggedInUser): bool
    {
        if ($userToOperateOn->getId() !== $loggedInUser->getId()) {
            return true;
        }

        $currentAdminCount = $this->userRepository->countAdmins();

        if ($currentAdminCount === 1) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the logged-in user can block/unblock the given user.
     *
     * @param User          $userToOperateOn The user to block/unblock
     * @param UserInterface $loggedInUser    The currently logged-in user
     *
     * @return bool
     */
    private function canBlock(User $userToOperateOn, UserInterface $loggedInUser): bool
    {
        // Only an admin can block/unblock users.
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        // An admin cannot block/unblock themselves.
        if ($userToOperateOn->getId() === $loggedInUser->getId()) {
            return false;
        }

        return true;
    }
}
