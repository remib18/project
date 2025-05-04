<?php

namespace App\DTO;

/**
 * Data Transfer Object for Scheduled Course information
 */
readonly class ScheduledCourseDTO
{
    /**
     * @param string $slug The course slug
     * @param string $name The course name
     * @param string $description The course description
     * @param string|null $image URL to the course image
     * @param string $room Room location
     * @param string $day Day of the week
     * @param string $start_time Formatted start time
     * @param string $end_time Formatted end time
     * @param string|null $group_name Optional group name
     */
    public function __construct(
        public string  $slug,
        public string  $name,
        public string  $description,
        public ?string $image,
        public string  $room,
        public string  $day,
        public string  $start_time,
        public string  $end_time,
        public ?string $group_name = null
    ) {
    }
}