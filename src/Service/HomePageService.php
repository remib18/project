<?php

namespace App\Service;

use App\DTO\HomeDTO;
use App\DTO\ScheduledCourseDTO;
use App\Entity\User;
use App\Formatter\CourseFormatter;
use App\Repository\CourseGroupRepository;
use App\Repository\RecentActivityRepository;

/**
 * Service to handle home page data
 */
class HomePageService
{
    public function __construct(
        private readonly CourseGroupRepository $courseGroupRepository,
        private readonly RecentActivityRepository $recentActivityRepository,
        private readonly CourseFormatter $courseFormatter
    ) {
    }

    /**
     * Get all data needed for the home page
     *
     * @param User $user The current user
     * @return HomeDTO Data for the home page
     */
    public function getHomePageData(User $user): HomeDTO
    {
        $nowCourses = $this->getNowCourses($user);
        $upcomingCourses = $this->getUpcomingCourses($user);
        $recentActivities = $this->getRecentActivities($user);

        return new HomeDTO(
            $nowCourses,
            $upcomingCourses,
            $recentActivities
        );
    }

    /**
     * Get the courses that are currently happening
     *
     * @param User $user The current user
     * @return array<ScheduledCourseDTO> Currently ongoing courses
     */
    private function getNowCourses(User $user): array
    {
        $currentGroups = $this->courseGroupRepository->findCurrentGroupsForUser($user);
        return $this->courseFormatter->formatGroupsAsScheduledCourses($currentGroups);
    }

    /**
     * Get the upcoming courses for the user
     *
     * @param User $user The current user
     * @param int $limit Maximum number of upcoming courses to return
     * @return array<ScheduledCourseDTO> Upcoming courses
     */
    private function getUpcomingCourses(User $user, int $limit = 3): array
    {
        $upcomingGroups = $this->courseGroupRepository->findUpcomingGroupsForUser($user, null, $limit);
        return $this->courseFormatter->formatGroupsAsScheduledCourses($upcomingGroups);
    }

    /**
     * Get recent activities for the user
     *
     * @param User $user The current user
     * @param int $limit Maximum number of activities to return
     * @return array Recent activities
     */
    private function getRecentActivities(User $user, int $limit = 5): array
    {
        return $this->recentActivityRepository->getRecentActivitiesForUser($user, $limit);
    }
}