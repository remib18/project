<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Base controller for API endpoints with common response handling
 */
abstract class AbstractApiController extends AbstractController
{
    /**
     * Create a success response
     *
     * @param mixed $data
     * @param int $status
     * @return JsonResponse
     */
    protected function createSuccessResponse($data = null, int $status = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'status' => 'success',
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $this->json($response, $status);
    }

    /**
     * Create a paginated success response
     *
     * @param mixed $data
     * @param int $total
     * @param boolean $hasMore
     */
    protected function createPaginatedSuccessResponse($data, int $total, bool $hasMore): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'data' => $data,
            'total' => $total,
            'hasMore' => $hasMore
        ], Response::HTTP_OK);
    }

    /**
     * Create an error response
     *
     * @param string $message
     * @param int $status
     * @param array $errors
     * @return JsonResponse
     */
    protected function createErrorResponse(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        array $errors = []
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return $this->json($response, $status);
    }

    /**
     * Create a validation error response from constraint violations
     *
     * @param ConstraintViolationListInterface $violations
     * @return JsonResponse
     */
    protected function createValidationErrorResponse(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $errors[$propertyPath] = $violation->getMessage();
        }

        return $this->createErrorResponse('Validation failed', Response::HTTP_BAD_REQUEST, $errors);
    }
}