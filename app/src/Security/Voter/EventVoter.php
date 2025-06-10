<?php

/**
 * Event voter.
 */

namespace App\Security\Voter;

use App\Entity\Event;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class EventVoter.
 */
final class EventVoter extends Voter
{
    /**
     * Delete permission.
     *
     * @const string
     */
    public const DELETE = 'EVENT_DELETE';

    /**
     * Edit permission.
     *
     * @const string
     */
    public const EDIT = 'EVENT_EDIT';

    /**
     * View permission.
     *
     * @const string
     */
    public const VIEW = 'EVENT_VIEW';

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed  $subject   The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool Result
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::DELETE, self::EDIT, self::VIEW])
            && $subject instanceof Event;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string         $attribute Permission name
     * @param mixed          $subject   Object
     * @param TokenInterface $token     Security token
     *
     * @return bool Vote result
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }
        if (!$subject instanceof Event) {
            return false;
        }

        return match ($attribute) {
            self::EDIT => $this->canEdit($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::VIEW => $this->canView($subject, $user),
            default => false,
        };
    }

    /**
     * Checks if user can delete event.
     *
     * @param Event          $event Event entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canDelete(Event $event, UserInterface $user): bool
    {
        return $event->getAuthor()?->getId() === $user->getId();
    }

    /**
     * Checks if user can edit event.
     *
     * @param Event          $event Event entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canEdit(Event $event, UserInterface $user): bool
    {
        return $event->getAuthor()?->getId() === $user->getId();
    }

    /**
     * Checks if user can view event.
     *
     * @param Event          $event Event entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canView(Event $event, UserInterface $user): bool
    {
        return $event->getAuthor()?->getId() === $user->getId();
    }
}
