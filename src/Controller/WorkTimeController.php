<?php
declare(strict_types=1);

namespace App\Controller;

use App\Dto\WorkTimeDto;
use App\Service\WorkTimeService;
use App\Repository\WorkTimeRepository;
use App\Repository\EmployeeRepository;
use JsonException;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class WorkTimeController extends AbstractController {
    public function __construct(
        private readonly WorkTimeService $workTimeService,
        private readonly WorkTimeRepository $workTimeRepository,
        private readonly EmployeeRepository $employeeRepository
    ) {
    }

    #[Route('/api/worktime/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse {
        try {
            $requestData = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $workTimeDto = WorkTimeDto::fromArray($requestData);
        } catch (JsonException) {
            return $this->json(['error' => 'Nieprawidłowy JSON'], JsonResponse::HTTP_BAD_REQUEST);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $message = $this->workTimeService->registerWorkTime($workTimeDto);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->json(['response' => [$message]], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/worktime/summary/daily', methods: ['POST'])]
    public function getDailySummary(Request $request): JsonResponse {
        try {
            $requestData = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->json(['error' => 'Nieprawidłowy JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!isset($requestData['employeeId'], $requestData['date'])) {
            return $this->json(['error' => 'Brakuje wymaganych pól: employeeId i date (DD.MM.YYYY)'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $summary = $this->workTimeService->getDailySummary($requestData['employeeId'], $requestData['date']);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->json(['response' => $summary]);
    }

    #[Route('/api/worktime/summary/monthly', methods: ['POST'])]
    public function getMonthlySummary(Request $request): JsonResponse {
        try {
            $requestData = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->json(['error' => 'Nieprawidłowy JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!isset($requestData['employeeId'], $requestData['date'])) {
            return $this->json(['error' => 'Brakuje wymaganych pól: employeeId i date (MM.YYYY)'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $summary = $this->workTimeService->getMonthlySummary($requestData['employeeId'], $requestData['date']);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->json(['response' => $summary]);
    }
}
