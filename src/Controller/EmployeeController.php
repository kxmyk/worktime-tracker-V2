<?php
declare(strict_types=1);

namespace App\Controller;

use App\Dto\EmployeeDto;
use App\Service\EmployeeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use JsonException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class EmployeeController extends AbstractController {
    public function __construct(private readonly EmployeeService $employeeService) {
    }

    #[Route('/api/employees/create', methods: ['POST'])]
    public function create(Request $request): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestHttpException('Nieprawidłowy JSON.');
        }

        if (!isset($data['firstName'], $data['lastName'])) {
            return $this->json(['error' => 'Niepoprawne dane wejściowe - firstName, lastName'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dto = new EmployeeDto($data['firstName'], $data['lastName']);
        $employee = $this->employeeService->createEmployee($dto);

        return $this->json(['response' => ['id' => $employee->getId()]], JsonResponse::HTTP_CREATED);
    }
}
