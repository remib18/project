<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for course creation and update
 */
final class CourseUnitRequest
{
    #[Assert\NotBlank(message: 'Course name is required')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Course name must be at least {{ limit }} characters',
        maxMessage: 'Course name cannot be longer than {{ limit }} characters'
    )]
    public string $name;

    #[Assert\NotBlank(message: 'Course description is required')]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: 'Course description must be at least {{ limit }} characters',
        maxMessage: 'Course description cannot be longer than {{ limit }} characters'
    )]
    public string $description;

    #[Assert\Url(message: 'Image URL must be a valid URL')]
    #[Assert\Length(max: 255, maxMessage: 'Image URL cannot be longer than {{ limit }} characters')]
    public ?string $image = null;

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
        $dto->name = $data['name'] ?? '';
        $dto->description = $data['description'] ?? '';
        $dto->image = $data['image'] ?? null;
        $dto->_token = $data['_token'] ?? '';

        return $dto;
    }
}