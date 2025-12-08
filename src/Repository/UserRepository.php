<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function save(User $user, bool $flush = false): void
    {
        $this->getEntityManager()->persist($user);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Soft Delete Methods
    public function findActive(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.deletedAt IS NULL')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findDeleted(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.deletedAt IS NOT NULL')
            ->orderBy('u.deletedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function restore(User $user): void
    {
        $user->restore();
        $this->getEntityManager()->flush();
    }

    public function hardDelete(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }
}
