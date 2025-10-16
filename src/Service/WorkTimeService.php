<?php
declare(strict_types=1);

namespace App\Service;

use App\DTO\WorkTimeDTO;
use App\Repository\WorkTimeRepository;
use App\Repository\EmployeeRepository;
use App\Entity\WorkTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

final readonly class WorkTimeService {
    public function __construct(
        private WorkTimeRepository $workTimeRepository,
        private EmployeeRepository $employeeRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function registerWorkTime(WorkTimeDTO $dto): string {
        $this->entityManager->beginTransaction();
        try {
            $employeeRecord = $this->employeeRepository->find($dto->employeeId);
            if (!$employeeRecord) {
                throw new BadRequestHttpException('Nie znaleziono pracownika.');
            }

            $workStartDate = DateTimeImmutable::createFromFormat('Y-m-d', $dto->startTime->format('Y-m-d'));

            $existingWorkTime = $this->workTimeRepository->findOneBy([
                'employee' => $employeeRecord,
                'startDate' => $workStartDate
            ]);
            if ($existingWorkTime) {
                throw new BadRequestHttpException('Czas pracy dla tego dnia już istnieje.');
            }

            $durationSeconds = $dto->endTime->getTimestamp() - $dto->startTime->getTimestamp();
            if ($durationSeconds <= 0) {
                throw new BadRequestHttpException('Czas końca musi być późniejszy niż czas rozpoczęcia.');
            }
            if ($durationSeconds > 12 * 3600) {
                throw new BadRequestHttpException('Długość zmiany nie może przekraczać 12 godzin.');
            }

            $workTimeEntity = new WorkTime($employeeRecord, $dto->startTime, $dto->endTime);
            $this->entityManager->persist($workTimeEntity);
            $this->entityManager->flush();
            $this->entityManager->commit();

            return 'Czas pracy został dodany!';
        } catch (Throwable $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
}