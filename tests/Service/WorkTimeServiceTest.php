<?php

namespace App\Tests\Service;

use App\DTO\WorkTimeDto;
use App\Entity\Employee;
use App\Entity\WorkTime;
use App\Repository\EmployeeRepository;
use App\Repository\WorkTimeRepository;
use App\Service\WorkTimeService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class WorkTimeServiceTest extends TestCase
{
    private WorkTimeService $service;
    private EmployeeRepository $employeeRepository;
    private WorkTimeRepository $workTimeRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->employeeRepository = $this->createMock(EmployeeRepository::class);
        $this->workTimeRepository = $this->createMock(WorkTimeRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->service = new WorkTimeService(
            $this->workTimeRepository,
            $this->employeeRepository,
            $this->entityManager,
            20,      // hourlyRate
            2.0,     // overtimeMultiplier
            40       // monthlyNormHours
        );
    }

    #[Test]
    public function registerWorkTimeSuccessfully(): void
    {
        $employeeUuid = Uuid::v4()->toRfc4122();

        $employee = new Employee($employeeUuid, 'Joe', 'Doe');
        $this->employeeRepository->method('find')->willReturn($employee);

        $dto = new WorkTimeDto(
            $employeeUuid,
            new DateTimeImmutable('2025-03-22 08:00:00'),
            new DateTimeImmutable('2025-03-22 16:00:00')
        );

        $this->entityManager->expects(self::once())->method('persist');
        $this->entityManager->expects(self::once())->method('flush');

        $result = $this->service->registerWorkTime($dto);
        self::assertSame('Czas pracy został dodany!', $result);
    }

    #[Test]
    public function registerWorkTimeFailsWhenEmployeeNotFound(): void
    {
        $employeeUuid = Uuid::v4()->toRfc4122();

        $this->employeeRepository->method('find')->willReturn(null); // brak pracownika

        $dto = new WorkTimeDto(
            $employeeUuid,
            new DateTimeImmutable('2025-03-22 08:00:00'),
            new DateTimeImmutable('2025-03-22 16:00:00')
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Nie znaleziono pracownika.');

        $this->service->registerWorkTime($dto);
    }

    #[Test]
    public function getDailySummaryReturnsCorrectData(): void
    {
        $employee = new Employee('1', 'Jan', 'Kowalski');
        $this->employeeRepository->method('find')->willReturn($employee);

        $workTime = new WorkTime($employee, new DateTimeImmutable('2025-03-22 08:00:00'), new DateTimeImmutable('2025-03-22 16:00:00'));
        $this->workTimeRepository->method('findBy')->willReturn([$workTime]);

        $result = $this->service->getDailySummary('1', '22.03.2025');

        $expected = [
            'ilość godzin z danego dnia' => 8.0,
            'stawka' => '20 PLN',
            'suma po przeliczeniu' => '160 PLN',
        ];

        self::assertSame($expected, $result);
    }

    #[Test]
    public function getMonthlySummaryCalculatesOvertimeCorrectly(): void
    {
        $employee = new Employee('1', 'Jan', 'Kowalski');
        $this->employeeRepository->method('find')->willReturn($employee);

        $workTimes = [
            new WorkTime($employee, new DateTimeImmutable('2025-03-01 08:00:00'), new DateTimeImmutable('2025-03-01 16:00:00')),
            new WorkTime($employee, new DateTimeImmutable('2025-03-02 08:00:00'), new DateTimeImmutable('2025-03-02 16:00:00')),
            new WorkTime($employee, new DateTimeImmutable('2025-03-03 08:00:00'), new DateTimeImmutable('2025-03-03 16:00:00')),
            new WorkTime($employee, new DateTimeImmutable('2025-03-04 08:00:00'), new DateTimeImmutable('2025-03-04 16:00:00')),
            new WorkTime($employee, new DateTimeImmutable('2025-03-05 08:00:00'), new DateTimeImmutable('2025-03-05 16:00:00')),
            new WorkTime($employee, new DateTimeImmutable('2025-03-06 08:00:00'), new DateTimeImmutable('2025-03-06 16:00:00')),
        ];

        $this->workTimeRepository->method('findByMonth')->willReturn($workTimes);

        $result = $this->service->getMonthlySummary('1', '03.2025');

        $expected = [
            'ilość normalnych godzin z danego miesiąca' => 40,
            'stawka' => '20 PLN',
            'ilość nadgodzin z danego miesiąca' => 8.0,
            'stawka nadgodzinowa' => '40 PLN',
            'suma po przeliczeniu' => '1120 PLN',
        ];

        self::assertSame($expected, $result);
    }
}
