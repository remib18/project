<?php

namespace App\DTO;

/**
 * Data Transfer Object for Course information
 */
readonly class CourseDTO
{
    /**
     * @param string $name The course name
     * @param string $description The course description
     * @param string|null $image URL to the course image
     * @param string $target URL target for the course
     * @param bool $isFavorite Whether the course is marked as favorite
     */
    public function __construct(
        public string  $name,
        public string  $description,
        public ?string $image,
        public string  $target,
        public bool    $isFavorite
    ) {
    }
}