<?php

/**
 * Contact Voter.
 */

namespace App\Security\Voter;

use App\Entity\Contact;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class Contact voter.
 */
class ContactVoter extends Voter
{
    public const VIEW = 'CONTACT_VIEW';
    public const EDIT = 'CONTACT_EDIT';
    public const DELETE = 'CONTACT_DELETE';

    /**
     * Supports.
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Contact;
    }

    /**
     * Vote one attribute.
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var Contact $contact */
        $contact = $subject;
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        return match ($attribute) {
            self::VIEW, self::EDIT, self::DELETE => $contact->getAuthor() === $user,
            default => false,
        };
    }
}
