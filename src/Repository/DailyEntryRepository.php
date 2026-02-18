<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DailyEntry;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DailyEntry>
 */
class DailyEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyEntry::class);
    }

    public function findByUserAndDate(User $user, \DateTimeInterface $date): ?DailyEntry
    {
        $dateOnly = $date instanceof \DateTimeImmutable
            ? $date->format('Y-m-d')
            : (clone $date)->format('Y-m-d');

        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->andWhere('d.date = :date')
            ->setParameter('user', $user)
            ->setParameter('date', $dateOnly)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<DailyEntry>
     */
    public function findByUserAndMonth(User $user, string $yearMonth): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            return [];
        }

        $start = $yearMonth . '-01';
        $end = (new \DateTimeImmutable($yearMonth . '-01'))->modify('last day of this month')->format('Y-m-d');

        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->andWhere('d.date >= :start')
            ->andWhere('d.date <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('d.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Últimas N entradas do usuário (para /api/history/recent).
     *
     * @return list<DailyEntry>
     */
    public function findRecentByUser(User $user, int $limit = 7): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
