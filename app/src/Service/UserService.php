<?php

// src/Service/UserService.php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserService implements UserServiceInterface
{
    public function __construct(private readonly UserRepository $userRepository, private readonly TranslatorInterface $translator, private readonly PaginatorInterface $paginator)
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

    public function delete(User $user): void
    {
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $adminCount = $this->userRepository->countAdmins();

            if ($adminCount <= 1) {
                throw new \RuntimeException($this->translator->trans('message.cannot_delete_last_admin')
                );}
        }

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

    /**
     * Find a user by their ID.
     *
     * @param int $id User ID
     *
     * @return User|null User entity or null if not found
     */
    public function findOneById(int $id): ?User
    {
        return $this->userRepository->find($id);
    }


}
