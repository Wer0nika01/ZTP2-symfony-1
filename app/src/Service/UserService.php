<?php

// src/Service/UserService.php
namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService implements UserServiceInterface
{
    private UserRepository $userRepository;
    private EntityManagerInterface $em;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $em)
    {
        $this->userRepository = $userRepository;
        $this->em = $em;
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function updateUser(User $user): void
    {
        $this->em->flush(); // Zakładamy, że $user jest już zmodyfikowany
    }

    public function deleteUser(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }
}
