<?php

namespace App\Entity;

use App\Repository\WorkTimeRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkTimeRepository::class)]
class WorkTime {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Employee $employee;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $startTime;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $endTime;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $startDate;

    public function __construct(Employee $employee, DateTimeImmutable $startTime, DateTimeImmutable $endTime) {
        $this->employee = $employee;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->startDate = DateTimeImmutable::createFromFormat('Y-m-d', $startTime->format('Y-m-d'));
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getEmployee(): ?Employee {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): static {
        $this->employee = $employee;

        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): static {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): static {
        $this->endTime = $endTime;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static {
        $this->startDate = $startDate;

        return $this;
    }
}
