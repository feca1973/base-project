<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MagicLoginToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MagicLoginToken>
 */
class MagicLoginTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MagicLoginToken::class);
    }

    public function findConsumableByTokenHash(
        string $tokenHash,
        string $purpose,
        int $now,
    ): ?MagicLoginToken
    {
        return $this->createQueryBuilder('token')
            ->andWhere('token.tokenHash = :tokenHash')
            ->andWhere('token.purpose = :purpose')
            ->andWhere('token.consumedAt IS NULL')
            ->andWhere('token.expiresAt >= :now')
            ->setParameter('tokenHash', $tokenHash)
            ->setParameter('purpose', $purpose)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
