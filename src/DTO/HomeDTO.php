<?php

namespace App\DTO;

/**
 * Data Transfer Object for Home page information
 */
readonly class HomeDTO
{
    /**
     * @param array<ScheduledCourseDTO> $nowCourses Currently ongoing courses
     * @param array<ScheduledCourseDTO> $upcomingCourses Upcoming courses
     * @param array<RecentActivityDTO> $recentActivities Recent user activities
     */
    public function __construct(
        public array $nowCourses,
        public array $upcomingCourses,
        public array $recentActivities
    ) {
    }
}