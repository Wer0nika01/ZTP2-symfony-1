<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserVoter
 */
class UserVoter extends Voter
{
    public const VIEW = 'USER_VIEW';
    public const EDIT = 'USER_EDIT';
    public const DELETE = 'USER_DELETE';

    /**
     * Constructor
     *
     * @param Security $security
     */
    public function __construct(private readonly Security $security)
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
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
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
     * Zwykły użytkownik może zobaczyć tylko swój własny profil.
     * Admin może zobaczyć wszystkie profile (już sprawdzone wyżej).
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
     * Zwykły użytkownik może edytować tylko swój własny profil.
     * Admin może edytować wszystkie profile (już sprawdzone wyżej).
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
     * Zwykły użytkownik NIE MOŻE usuwać innych użytkowników ani siebie.
     * Tylko Admin może usuwać użytkowników.
     * (Dostęp dla admina już sprawdzony wyżej, więc jeśli tu dotarł nie-admin, to zwracamy false).
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
}