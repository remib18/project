<?php

namespace App\DTO\Request;

use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    #[Assert\Image(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png'],
        maxSizeMessage: 'The file is too large ({{ size }} {{ suffix }}). Maximum allowed size is {{ limit }} {{ suffix }}.',
        mimeTypesMessage: 'Please upload a valid image (JPEG or PNG)'
    )]
    public ?UploadedFile $imageFile = null;

    #[Assert\NotBlank(message: 'CSRF token is required')]
    public string $_token;

    /**
     * Create DTO from request data
     * @param array $data Request data
     * @param array|null $files File data
     * @return self
     */
    public static function fromArray(array $data, ?array $files = null): self
    {
        $dto = new self();
        $dto->name = $data['name'] ?? '';
        $dto->description = $data['description'] ?? '';
        $dto->_token = $data['_token'] ?? '';

        if ($files && isset($files['imageFile'])) {
            $dto->imageFile = $files['imageFile'];
        }

        return $dto;
    }
}