<?php
declare(strict_types=1);

namespace App\Dto;

final readonly class EmployeeDto {
    public function __construct(
        public string $firstName,
        public string $lastName
    ) {
    }
}
