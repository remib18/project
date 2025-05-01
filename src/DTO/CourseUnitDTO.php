<?php

namespace App\DTO;

/**
 * Data Transfer Object for a course unit with its groups
 */
readonly class CourseUnitDTO
{
    /**
     * @param string $name The course name
     * @param string $description The course description
     * @param string|null $image URL to the course image
     * @param string $target URL target for the course
     * @param array<CourseGroupDTO> $groups List of groups associated with the course
     */
    public function __construct(
        public int $id,
        public string  $name,
        public string  $description,
        public ?string $image,
        public string  $target,
        public array  $groups,
    ) {
    }
}