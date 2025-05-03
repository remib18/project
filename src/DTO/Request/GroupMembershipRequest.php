<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for group membership actions
 */
final class GroupMembershipRequest
{
    #[Assert\NotBlank(message: 'User ID is required')]
    #[Assert\Positive(message: 'User ID must be positive')]
    public int $userId;

    #[Assert\NotBlank(message: 'CSRF token is required')]
    public string $_token;

    /**
     * Create DTO from request data
     * @param array $data Request data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->userId = (int)($data['userId'] ?? 0);
        $dto->_token = $data['_token'] ?? '';

        return $dto;
    }
}