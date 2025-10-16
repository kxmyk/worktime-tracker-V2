<?php
declare(strict_types=1);

namespace App\Service;

use App\DTO\EmployeeDTO;
use App\Entity\Employee;
use Doctrine\ORM\EntityManagerInterface;

final readonly class EmployeeService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function createEmployee(EmployeeDTO $dto): Employee
    {
        $employee = new Employee($dto->firstName, $dto->lastName);
        $this->entityManager->persist($employee);
        $this->entityManager->flush();

        return $employee;
    }
}
