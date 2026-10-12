<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
final class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findActiveByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('user')
            ->andWhere('user.email = :email')
            ->andWhere('user.isActive = true')
            ->setParameter('email', strtolower(trim($email)))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<User>
     */
    public function findAdminUsers(): array
    {
        return $this->createQueryBuilder('user')
            ->andWhere('user.roles LIKE :adminRole OR user.roles LIKE :superAdminRole')
            ->setParameter('adminRole', '%"ROLE_ADMIN"%')
            ->setParameter('superAdminRole', '%"ROLE_SUPER_ADMIN"%')
            ->orderBy('user.createdAt', 'DESC')
            ->addOrderBy('user.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
