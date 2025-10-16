<?php
declare(strict_types=1);

namespace App\Dto;

use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final readonly class WorkTimeDto {
    public function __construct(
        public string $employeeId,
        public DateTimeImmutable $startTime,
        public DateTimeImmutable $endTime
    ) {
        if (!Uuid::isValid($employeeId)) {
            throw new InvalidArgumentException('Nieprawidłowe employeeId.');
        }

        if ($startTime >= $endTime) {
            throw new InvalidArgumentException('Czas rozpoczęcia musi być wcześniej niż czas zakończenia.');
        }
    }

    public static function fromArray(array $input): self {
        if (!isset($input['employeeId'], $input['startTime'], $input['endTime'])) {
            throw new InvalidArgumentException('Brakuje wymaganych pól: employeeId, startTime, endTime.');
        }

        $employeeUuid = (string)$input['employeeId'];

        $startDateTime = DateTimeImmutable::createFromFormat('d.m.Y H:i', (string)$input['startTime']);
        $endDateTime = DateTimeImmutable::createFromFormat('d.m.Y H:i', (string)$input['endTime']);

        if (!$startDateTime) {
            throw new InvalidArgumentException('Nieprawidłowy format startTime. Poprawny format: "DD.MM.YYYY HH:MM", np. "01.01.1970 08:00".');
        }

        if (!$endDateTime) {
            throw new InvalidArgumentException('Nieprawidłowy format endTime. Poprawny format "DD.MM.YYYY HH:MM", np. "01.01.1970 14:00".');
        }

        return new self($employeeUuid, $startDateTime, $endDateTime);
    }
}
