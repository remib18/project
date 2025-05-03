<?php

namespace App\Service;

use App\Entity\CourseUnit;
use App\Entity\User;
use App\Repository\CourseUnitRepository;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Service to handle course security and access control
 */
class CourseSecurityService
{
    private CourseUnitRepository $courseUnitRepository;

    public function __construct(CourseUnitRepository $courseUnitRepository)
    {
        $this->courseUnitRepository = $courseUnitRepository;
    }

    /**
     * Check if a user has access to a specific course unit
     *
     * @param User $user The user to check
     * @param CourseUnit $courseUnit The course unit to check access for
     * @return bool True if the user has access, false otherwise
     */
    public function hasAccessToCourse(User $user, CourseUnit $courseUnit): bool
    {
        // Check if user is an admin (they can access all courses)
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Check each group in the course unit
        foreach ($courseUnit->getGroups() as $group) {
            if ($group->getMembers()->contains($user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a course unit by slug and ensure the user has access to it
     *
     * @param string $slug The course unit slug
     * @param User $user The user to check access for
     * @return CourseUnit The course unit if accessible
     * @throws AccessDeniedException If the user doesn't have access
     * @throws \Exception If the course unit doesn't exist
     */
    public function getAccessibleCourseUnitOrFail(string $slug, User $user): CourseUnit
    {
        $courseUnit = $this->courseUnitRepository->findBySlugOrFail($slug);

        if (!$this->hasAccessToCourse($user, $courseUnit)) {
            throw new AccessDeniedException('You do not have access to this course');
        }

        return $courseUnit;
    }
}