<?php

// src/Service/UserService.php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

class UserService implements UserServiceInterface
{
    public function __construct(private readonly UserRepository $userRepository, private readonly EntityManagerInterface $em, private readonly PaginatorInterface $paginator)
    {
    }
    public function getPaginatedList(int $page): PaginationInterface
    {
        $queryBuilder = $this->userRepository->createQueryBuilder('u')
            ->orderBy('u.email', 'ASC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            UserRepository::PAGINATOR_ITEMS_PER_PAGE
        );
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function updateUser(User $user): void
    {
        $this->em->flush();
    }

    public function delete(User $user): void
    {
        $this->userRepository->delete($user);
    }

    public function save(User $user): void
    {
        $this->userRepository->save($user);
    }

    public function isEmailUnique(string $email, ?int $excludeUserId = null): bool
    {
        $qb = $this->userRepository->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email);

        if ($excludeUserId !== null) {
            $qb->andWhere('u.id != :id')->setParameter('id', $excludeUserId);
        }

        return count($qb->getQuery()->getResult()) === 0;
    }
}
