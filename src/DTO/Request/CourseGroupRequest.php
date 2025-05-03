<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for course group creation and update
 */
final class CourseGroupRequest
{
    #[Assert\NotBlank(message: 'Group name is required')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'Group name must be at least {{ limit }} characters',
        maxMessage: 'Group name cannot be longer than {{ limit }} characters'
    )]
    public string $name;

    #[Assert\NotBlank(message: 'Room is required')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Room cannot be longer than {{ limit }} characters'
    )]
    public string $room;

    #[Assert\NotBlank(message: 'Day of week is required')]
    #[Assert\Range(
        min: 0,
        max: 6,
        notInRangeMessage: 'Day of week must be between {{ min }} and {{ max }}'
    )]
    public int $dayOfWeek;

    #[Assert\NotBlank(message: 'Start time is required')]
    #[Assert\Regex(
        pattern: '/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
        message: 'Start time must be in format HH:MM'
    )]
    public string $startTime;

    #[Assert\NotBlank(message: 'End time is required')]
    #[Assert\Regex(
        pattern: '/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
        message: 'End time must be in format HH:MM'
    )]
    public string $endTime;

    #[Assert\NotBlank(message: 'Course unit ID is required')]
    #[Assert\Positive(message: 'Course unit ID must be positive')]
    public int $courseUnitId;

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
        $dto->room = $data['room'] ?? '';
        $dto->dayOfWeek = (int)($data['dayOfWeek'] ?? 0);
        $dto->startTime = $data['startTime'] ?? '';
        $dto->endTime = $data['endTime'] ?? '';
        $dto->courseUnitId = (int)($data['courseUnitId'] ?? 0);
        $dto->_token = $data['_token'] ?? '';

        return $dto;
    }
}