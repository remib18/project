<?php

namespace App\Mapper;

use App\DTO\CourseGroupDTO;
use App\DTO\CourseUnitDTO;
use App\DTO\ScheduledCourseDTO;
use App\Entity\CourseGroup;
use App\Entity\CourseUnit;

/**
 * Maps entities to DTOs and vice versa
 */
class CourseMapper
{
    /**
     * Convert a CourseUnit entity to a CourseUnitDTO
     *
     * @param CourseUnit $courseUnit
     * @return CourseUnitDTO
     */
    public function mapCourseUnitToDTO(CourseUnit $courseUnit): CourseUnitDTO
    {
        $groups = [];
        foreach ($courseUnit->getGroups() as $group) {
            $groups[] = $this->mapCourseGroupToDTO($group);
        }

        return new CourseUnitDTO(
            $courseUnit->getId(),
            $courseUnit->getName(),
            $courseUnit->getDescription(),
            $courseUnit->getImage(),
            '/course/' . $courseUnit->getSlug(),
            $groups
        );
    }

    /**
     * Convert a CourseGroup entity to a CourseGroupDTO
     *
     * @param CourseGroup $group
     * @return CourseGroupDTO
     */
    public function mapCourseGroupToDTO(CourseGroup $group): CourseGroupDTO
    {
        $courseUnit = $group->getUnit();
        $schedule = $group->getSchedule();

        if ($courseUnit === null || $schedule === null) {
            throw new \InvalidArgumentException('CourseGroup must have a valid unit and schedule');
        }

        $scheduledCourse = new ScheduledCourseDTO(
            $courseUnit->getSlug(),
            $courseUnit->getName(),
            $courseUnit->getDescription(),
            $courseUnit->getImage(),
            $group->getRoom(),
            $schedule->getFormattedStartTime(),
            $schedule->getFormattedEndTime(),
            $group->getName()
        );

        // Convert collection to array and limit sensitive data
        $members = [];
        foreach ($group->getMembers() as $member) {
            $members[] = [
                'id' => $member->getId(),
                'email' => $member->getEmail(),
                'firstname' => $member->getFirstname(),
                'lastname' => $member->getLastname(),
                'fullName' => $member->getFullName(),
                'roles' => $member->getRoles()
            ];
        }

        return new CourseGroupDTO(
            $group->getId(),
            $group->getName(),
            $members,
            $scheduledCourse,
            $group->getRoom()
        );
    }

    /**
     * Convert a collection of CourseUnit entities to an array of DTOs
     *
     * @param iterable $courseUnits
     * @return array<CourseUnitDTO>
     */
    public function mapCourseUnitsToDTO(iterable $courseUnits): array
    {
        $dtos = [];
        foreach ($courseUnits as $courseUnit) {
            $dtos[] = $this->mapCourseUnitToDTO($courseUnit);
        }
        return $dtos;
    }
}