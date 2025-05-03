<?php

namespace App\DTO;

/**
 * Data Transfer Object for Category information
 */
class CourseCategoryDTO
{
    /**
     * @param string $title The category title
     * @param string $desc The category description
     * @param array<CourseActivityDTO> $resources Activities in this category
     */
    public function __construct(
        readonly public string $title,
        readonly public string $desc,
        public array  $resources = []
    ) {
    }
}