<?php

namespace App\Repository;

use App\Entity\Employee;
use App\Entity\WorkTime;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkTime>
 */
class WorkTimeRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, WorkTime::class);
    }

    public function findByMonth(Employee $employee, string $monthYear): array {
        $monthDate = DateTimeImmutable::createFromFormat('m.Y', $monthYear);
        if (!$monthDate) {
            throw new InvalidArgumentException('Nieprawidłowy format daty. Oczekiwany format: MM.YYYY, np. "01.1970".');
        }

        $startOfMonth = $monthDate->modify('first day of this month')->setTime(0, 0, 0);
        $endOfMonth = $monthDate->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('workTime')
            ->andWhere('workTime.employee = :employee')
            ->setParameter('employee', $employee)
            ->andWhere('workTime.startDate BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->orderBy('workTime.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
