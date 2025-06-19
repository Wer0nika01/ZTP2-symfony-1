<?php

/**
 * Avatar voter.
 */

namespace App\Security\Voter;

use App\Entity\Avatar;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class Avatar voter.
 */
class AvatarVoter extends Voter
{
    public const DELETE = 'DELETE';

    /**
     * Supports.
     *
     * @param string $attribute
     * @param mixed  $subject
     *
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::DELETE === $attribute && $subject instanceof Avatar;
    }

    /**
     * Vote one attribute.
     *
     * @param string         $attribute
     * @param mixed          $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Avatar $avatar */
        $avatar = $subject;

        return $avatar->getUser() === $user;
    }
}
