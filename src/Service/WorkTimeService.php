<?php
declare(strict_types=1);

namespace App\Service;

use App\Dto\WorkTimeDto;
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
        private EntityManagerInterface $entityManager,
        private int $hourlyRate,
        private float $overtimeMultiplier,
        private int $monthlyNormHours
    ) {
    }

    public function registerWorkTime(WorkTimeDto $dto): string {
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

    private function roundSecondsToHalfHour(int $seconds): float {
        $minutesTotal = (int)floor($seconds / 60);
        $remainderMinutes = $minutesTotal % 30;
        $baseMinutes = $minutesTotal - $remainderMinutes;

        if ($remainderMinutes < 15) {
            $roundedMinutes = $baseMinutes;
        } elseif ($remainderMinutes < 45) {
            $roundedMinutes = $baseMinutes + 30;
        } else {
            $roundedMinutes = $baseMinutes + 60;
        }

        return $roundedMinutes / 60.0;
    }

    public function getDailySummary(string $employeeId, string $date): array {
        $employeeRecord = $this->employeeRepository->find($employeeId);
        if (!$employeeRecord) {
            throw new BadRequestHttpException('Nie znaleziono pracownika.');
        }

        $requestedDate = DateTimeImmutable::createFromFormat('d.m.Y', $date);
        if (!$requestedDate) {
            throw new BadRequestHttpException('Nieprawidłowy format daty. Oczekiwano DD.MM.YYYY');
        }

        $dailyWorkTimes = $this->workTimeRepository->findBy([
            'employee' => $employeeRecord,
            'startDate' => DateTimeImmutable::createFromFormat('Y-m-d', $requestedDate->format('Y-m-d'))
        ]);

        if (!$dailyWorkTimes) {
            throw new BadRequestHttpException('Brak zarejestrowanego czasu pracy w tym dniu.');
        }

        $totalSecondsWorked = array_reduce($dailyWorkTimes, function ($carry, WorkTime $workTime) {
            return $carry + ($workTime->getEndTime()->getTimestamp() - $workTime->getStartTime()->getTimestamp());
        }, 0);

        $roundedHoursWorked = $this->roundSecondsToHalfHour($totalSecondsWorked);
        $totalPayPln = (int)round($roundedHoursWorked * $this->hourlyRate);

        return [
            'ilość godzin z danego dnia' => $roundedHoursWorked,
            'stawka' => $this->hourlyRate . ' PLN',
            'suma po przeliczeniu' => $totalPayPln . ' PLN',
        ];
    }

    public function getMonthlySummary(string $employeeId, string $monthYear): array {
        $employeeRecord = $this->employeeRepository->find($employeeId);
        if (!$employeeRecord) {
            throw new BadRequestHttpException('Nie znaleziono pracownika.');
        }

        $monthDate = DateTimeImmutable::createFromFormat('m.Y', $monthYear);
        if (!$monthDate) {
            throw new BadRequestHttpException('Nieprawidłowy format miesiąca. Oczekiwano MM.YYYY (np. 01.1970)');
        }

        $monthlyWorkTimes = $this->workTimeRepository->findByMonth($employeeRecord, $monthYear);
        if (!$monthlyWorkTimes) {
            throw new BadRequestHttpException('Brak zarejestrowanego czasu pracy w tym miesiącu.');
        }

        $totalSecondsWorked = array_reduce($monthlyWorkTimes, function ($carry, WorkTime $workTime) {
            return $carry + ($workTime->getEndTime()->getTimestamp() - $workTime->getStartTime()->getTimestamp());
        }, 0);

        $roundedHoursWorked = $this->roundSecondsToHalfHour($totalSecondsWorked);
        $normalHours = min($roundedHoursWorked, $this->monthlyNormHours);
        $overtimeHours = max(0.0, $roundedHoursWorked - $this->monthlyNormHours);

        $overtimeHourlyRate = (int)round($this->hourlyRate * $this->overtimeMultiplier);
        $totalPayPln = (int)round(($normalHours * $this->hourlyRate) + ($overtimeHours * $overtimeHourlyRate));

        return [
            'ilość normalnych godzin z danego miesiąca' => $normalHours,
            'stawka' => $this->hourlyRate . ' PLN',
            'ilość nadgodzin z danego miesiąca' => $overtimeHours,
            'stawka nadgodzinowa' => $overtimeHourlyRate . ' PLN',
            'suma po przeliczeniu' => $totalPayPln . ' PLN',
        ];
    }
}
