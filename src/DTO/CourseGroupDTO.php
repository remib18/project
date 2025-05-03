<?php

namespace App\DTO;

use App\Entity\User;

/**
 * Data Transfer Object for a course group
 */
readonly class CourseGroupDTO
{
    /**
     * @param string $name The group name
     * @param array<User> $members List of members in the group
     * @param ScheduledCourseDTO $schedule The schedule of the course
     * @param string $room The room where the course is held
     */
    public function __construct(
        public int $id,
        public string  $name,
        public array  $members,
        public ScheduledCourseDTO $schedule,
        public string  $room,
    ) {
    }
}