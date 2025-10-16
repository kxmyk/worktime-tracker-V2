<?php
declare(strict_types=1);

namespace App\Controller;

use App\DTO\WorkTimeDTO;
use App\Service\WorkTimeService;
use JsonException;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class WorkTimeController extends AbstractController {
    public function __construct(
        private readonly WorkTimeService $workTimeService
    ) {
    }

    #[Route('/api/worktime/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse {
        try {
            $requestData = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $workTimeDto = WorkTimeDTO::fromArray($requestData);
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
}