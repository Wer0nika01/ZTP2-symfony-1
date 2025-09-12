<?php

/**
 * User Service.
 */

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class User service.
 */
class UserService implements UserServiceInterface
{
    /**
     * Constructor.
     *
     * @param UserRepository      $userRepository User repository
     * @param TranslatorInterface $translator     Translator
     * @param PaginatorInterface  $paginator      Paginator
     */
    public function __construct(private readonly UserRepository $userRepository, private readonly TranslatorInterface $translator, private readonly PaginatorInterface $paginator)
    {
    }

    /**
     * Get paginated list.
     *
     * @param int $page Page number
     *
     * @return PaginationInterface Pagination
     */
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

    /**
     * Get all users.
     *
     * @return array|User[]
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * Delete user.
     *
     * @param User $user User entity
     */
    public function delete(User $user): void
    {
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $adminCount = $this->userRepository->countAdmins();

            if ($adminCount <= 1) {
                throw new \RuntimeException($this->translator->trans('message.cannot_delete_last_admin'));
            }
        }

        $this->userRepository->delete($user);
    }

    /**
     * Save user.
     *
     * @param User $user User entity
     */
    public function save(User $user): void
    {
        $this->userRepository->save($user);
    }

    /**
     * Is email unique?
     *
     * @param string   $email         String email
     * @param int|null $excludeUserId Excluded ids
     *
     * @return bool False or true
     */
    public function isEmailUnique(string $email, ?int $excludeUserId = null): bool
    {
        $qb = $this->userRepository->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email);

        if (null !== $excludeUserId) {
            $qb->andWhere('u.id != :id')->setParameter('id', $excludeUserId);
        }

        return 0 === count($qb->getQuery()->getResult());
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

    /**
     * Toggle to block users.
     *
     * @param User $user User entity
     */
    public function toggleBlock(User $user): void
    {
        $this->userRepository->toggleBlock($user);
    }
}
