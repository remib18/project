<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Validates request DTOs and CSRF tokens
 */
readonly class RequestValidator
{
    public function __construct(
        private ValidatorInterface        $validator,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * Validate a DTO from JSON request data
     *
     * @param Request $request The HTTP request
     * @param string $dtoClass The DTO class name
     * @param string $csrfTokenId The CSRF token ID
     * @return array{isValid: bool, dto: ?object, errors: array}
     */
    public function validateRequestDto(Request $request, string $dtoClass, string $csrfTokenId): array
    {
        // Decode JSON data
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return [
                'isValid' => false,
                'dto' => null,
                'errors' => ['Invalid JSON request']
            ];
        }

        // Create DTO from request data
        $dto = $dtoClass::fromArray($data);

        // Validate CSRF token
        if (!isset($data['_token']) || !$this->csrfTokenManager->isTokenValid(
                new CsrfToken($csrfTokenId, $data['_token'])
            )) {
            return [
                'isValid' => false,
                'dto' => null,
                'errors' => ['Invalid CSRF token']
            ];
        }

        // Validate DTO constraints
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $propertyPath = $violation->getPropertyPath();
                $errors[$propertyPath] = $violation->getMessage();
            }

            return [
                'isValid' => false,
                'dto' => null,
                'errors' => $errors
            ];
        }

        return [
            'isValid' => true,
            'dto' => $dto,
            'errors' => []
        ];
    }
}