<?php

namespace App\Security\Voter;

use App\Entity\Avatar;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AvatarVoter extends Voter
{
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::DELETE && $subject instanceof Avatar;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
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
