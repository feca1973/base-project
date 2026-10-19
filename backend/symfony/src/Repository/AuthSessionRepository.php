<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AuthSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthSession>
 */
class AuthSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthSession::class);
    }

    public function findValidByTokenHash(string $tokenHash, int $now): ?AuthSession
    {
        return $this->createQueryBuilder('authSession')
            ->join('authSession.user', 'user')
            ->addSelect('user')
            ->andWhere('authSession.tokenHash = :tokenHash')
            ->andWhere('authSession.expiresAt > :now')
            ->andWhere('user.isActive = true')
            ->setParameter('tokenHash', $tokenHash)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
