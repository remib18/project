<?php

namespace App\Formatter;

use App\Entity\CourseActivity;
use App\Entity\CourseGroup;
use App\Entity\CourseUnit;
use App\Entity\User;
use App\DTO\CourseActivityDTO;
use App\DTO\CourseCategoryDTO;
use App\DTO\CourseDTO;
use App\DTO\MemberCategoryDTO;
use App\DTO\ScheduledCourseDTO;

/**
 * Formatter class for course-related entities
 */
class CourseFormatter
{
    /**
     * Format a CourseUnit for display in the course listing
     *
     * @param CourseUnit $courseUnit
     * @return CourseDTO
     */
    public function formatCourseUnitForDisplay(CourseUnit $courseUnit): CourseDTO
    {
        return new CourseDTO(
            $courseUnit->getName(),
            $courseUnit->getDescription(),
            $courseUnit->getImage(),
            '/course/' . $courseUnit->getSlug(),
            false // Default to false as specified
        );
    }

    /**
     * Format a CourseActivity for display
     *
     * @param CourseActivity $activity
     * @param string $courseSlug
     * @return CourseActivityDTO
     */
    public function formatActivityForDisplay(CourseActivity $activity, string $courseSlug): CourseActivityDTO
    {
        $now = new \DateTimeImmutable();
        $updatedAt = $activity->getUpdatedAt();
        $timeDiff = $now->diff($updatedAt);

        $formattedTime = '';
        if ($timeDiff->d === 0) {
            if ($timeDiff->h === 0) {
                $formattedTime = 'Créé il y a ' . $timeDiff->i . ' minutes';
            } else {
                $formattedTime = 'Créé il y a ' . $timeDiff->h . ' heures';
            }
        } else if ($timeDiff->d === 1) {
            $formattedTime = 'Mis à jour hier';
        } else {
            $formattedTime = 'Mis à jour il y a ' . $timeDiff->d . ' jours';
        }

        $type = $activity->getType();
        $data = $activity->getData();
        $action = $type === 'document-submission' ? 'Déposer' : 'Voir';

        return new CourseActivityDTO(
            $activity->getName(),
            $formattedTime,
            '/course/' . $courseSlug . '/activity/' . $activity->getId(),
            $action,
            $activity->isPinned() ? $activity->getPinnedMessage() : null,
            $type,
            $data['icon'] ?? 'file-text',
            $activity->isPinned(),
            $data['severity'] ?? null,
            $data['content'] ?? null
        );
    }

    /**
     * Format activities by category for display
     *
     * @param array<CourseActivity> $activities
     * @param string $courseSlug
     * @return array<CourseCategoryDTO>
     */
    public function formatActivitiesByCategory(array $activities, string $courseSlug): array
    {
        $categorized = [];

        foreach ($activities as $activity) {
            $category = $activity->getCategory();

            if (!isset($categorized[$category])) {
                $categoryInfo = CourseActivity::CATEGORIES[$category] ?? [
                    'name' => ucfirst($category),
                    'desc' => 'Activities for ' . ucfirst($category)
                ];

                $categorized[$category] = new CourseCategoryDTO(
                    $categoryInfo['name'],
                    $categoryInfo['desc'],
                    []
                );
            }

            $categorized[$category]->resources[] = $this->formatActivityForDisplay(
                $activity,
                $courseSlug
            );
        }

        return array_values($categorized);
    }

    /**
     * Format members by role for display
     *
     * @param array<User> $professors
     * @param array<User> $students
     * @return array<MemberCategoryDTO>
     */
    public function formatMembersByRole(array $professors, array $students): array
    {
        $membersByRole = [
            'professors' => new MemberCategoryDTO(
                'Professeurs',
                'Ensemble des professeurs du cours',
                $professors
            ),
            'students' => new MemberCategoryDTO(
                'Étudiants',
                'Ensemble des étudiants du cours',
                $students
            )
        ];

        return array_values($membersByRole);
    }

    /**
     * Format course groups as scheduled courses
     *
     * @param array<CourseGroup> $groups The course groups to format
     * @return array<ScheduledCourseDTO> Formatted scheduled courses
     */
    public function formatGroupsAsScheduledCourses(array $groups): array
    {
        return array_map(function (CourseGroup $group) {
            $courseUnit = $group->getUnit();

            return new ScheduledCourseDTO(
                $courseUnit->getSlug(),
                $courseUnit->getName(),
                $courseUnit->getDescription(),
                $courseUnit->getImage(),
                $group->getRoom(),
                $group->getSchedule()->getFormattedStartTime(),
                $group->getSchedule()->getFormattedEndTime(),
                $group->getName()
            );
        }, $groups);
    }
}