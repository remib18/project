<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for course activity pin/unpin operations
 */
final class CourseActivityPinRequest
{
    #[Assert\NotBlank(message: 'CSRF token is required')]
    public string $_token;

    /**
     * Pinned message (required for pinning, must not be provided for unpinning)
     */
    #[Assert\Length(
        max: 32,
        maxMessage: 'Pinned message cannot be longer than {{ limit }} characters'
    )]
    public ?string $pinnedMessage = null;

    /**
     * Validate the request based on operation type
     *
     * @param bool $isPinned Current pinned status
     * @return array Validation errors, empty if valid
     */
    public function validateOperation(bool $isPinned): array
    {
        $errors = [];

        // If currently unpinned, we're pinning -> message is required
        if (!$isPinned) {
            if (empty($this->pinnedMessage)) {
                $errors['pinnedMessage'] = 'Pinned message is required when pinning an activity';
            } elseif (strlen($this->pinnedMessage) > 32) {
                $errors['pinnedMessage'] = 'Pinned message cannot be longer than 32 characters';
            }
        }
        // If currently pinned, we're unpinning -> message should not be provided
        else {
            if ($this->pinnedMessage !== null) {
                $errors['pinnedMessage'] = 'Pinned message should not be provided when unpinning';
            }
        }

        return $errors;
    }

    /**
     * Create DTO from request data
     * @param array $data Request data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->_token = $data['_token'] ?? '';

        // Only set pinnedMessage if it exists in the request, otherwise keep it null
        if (array_key_exists('pinnedMessage', $data)) {
            $dto->pinnedMessage = $data['pinnedMessage'];
        }

        return $dto;
    }
}