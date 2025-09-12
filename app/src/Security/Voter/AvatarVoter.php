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
     * @param string $attribute String attribute
     * @param mixed  $subject   Subject
     *
     * @return bool True or false
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::DELETE === $attribute && $subject instanceof Avatar;
    }

    /**
     * Vote one attribute.
     *
     * @param string         $attribute String attribute
     * @param mixed          $subject   Subject
     * @param TokenInterface $token     Token
     *
     * @return bool True or false
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
