<?php

namespace App\Controller;

use App\Entity\User;
use App\Formatter\CourseFormatter;
use App\Repository\CourseActivityRepository;
use App\Repository\CourseUnitRepository;
use App\Repository\UserRepository;
use App\Service\CourseSecurityService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for handling course-related pages and actions
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CourseController extends AbstractController
{
    public function __construct(
        private readonly CourseFormatter $courseFormatter,
        private readonly CourseSecurityService $courseSecurityService,
    ) {}

    /**
     * Display the course listing page - only shows courses the user is a member of
     *
     * @param CourseUnitRepository $courseUnitRepository
     * @return Response
     */
    #[Route('/course', name: 'app_course_courses')]
    public function index(CourseUnitRepository $courseUnitRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User must be logged in to view courses');
        }

        $courseUnits = $courseUnitRepository->findCourseUnitsForUser($user);
        $formattedCourses = [];

        foreach ($courseUnits as $courseUnit) {
            $formattedCourses[] = $this->courseFormatter->formatCourseUnitForDisplay($courseUnit);
        }

        return $this->render('course/index.html.twig', [
            'courses' => $formattedCourses,
        ]);
    }

    /**
     * Display a single course page with activities and resources
     * Only shows courses the user is a member of
     *
     * @param string $slug
     * @param CourseActivityRepository $activityRepository
     * @return Response
     * @throws NotFoundHttpException|AccessDeniedException
     */
    #[Route('/course/{slug}', name: 'app_course_course')]
    public function course(
        string $slug,
        CourseActivityRepository $activityRepository
    ): Response {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException('User must be logged in to view course');
            }

            // Find course unit and verify user has access to it
            $courseUnit = $this->courseSecurityService->getAccessibleCourseUnitOrFail($slug, $user);

            // Get recent activities
            $recentActivities = $activityRepository->getRecentActivities($courseUnit);
            $formattedActivities = [];
            foreach ($recentActivities as $activity) {
                $formattedActivities[] = $this->courseFormatter->formatActivityForDisplay($activity, $slug);
            }

            // Get pinned resources
            $pinnedResources = $activityRepository->getPinnedResources($courseUnit);
            $formattedPinnedResources = [];
            foreach ($pinnedResources as $resource) {
                $formattedPinnedResources[] = $this->courseFormatter->formatActivityForDisplay($resource, $slug);
            }

            // Get activities by category
            $activitiesByCategory = $activityRepository->getActivitiesByCategory($courseUnit);
            $formattedCategories = $this->courseFormatter->formatActivitiesByCategory($activitiesByCategory, $slug);

            return $this->render('course/course.html.twig', [
                'courseName' => $courseUnit->getName(),
                'courseBase' => '/course/' . $slug,
                'activities' => $formattedActivities,
                'pinnedRessources' => $formattedPinnedResources,
                'categories' => $formattedCategories,
            ]);

        } catch (Exception $e) {
            if ($e instanceof AccessDeniedException) {
                throw $e;
            }
            throw new NotFoundHttpException('Course not found', $e);
        }
    }

    /**
     * Display the members page for a course
     * Only accessible for courses the user is a member of
     *
     * @param string $slug
     * @param UserRepository $userRepository
     * @return Response
     * @throws NotFoundHttpException|AccessDeniedException
     */
    #[Route('/course/{slug}/members', name: 'app_course_members')]
    public function courseMembers(
        string $slug,
        UserRepository $userRepository
    ): Response {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException('User must be logged in to view course members');
            }

            // Find course unit and verify user has access to it
            $courseUnit = $this->courseSecurityService->getAccessibleCourseUnitOrFail($slug, $user);

            // Get members categorized by role
            $membersByRole = $userRepository->getMembersByRole($courseUnit);
            $formattedMembers = $this->courseFormatter->formatMembersByRole(
                $membersByRole['professors'],
                $membersByRole['students']
            );

            return $this->render('course/members.html.twig', [
                'courseName' => $courseUnit->getName(),
                'courseBase' => '/course/' . $slug,
                'categories' => $formattedMembers,
            ]);

        } catch (Exception $e) {
            if ($e instanceof AccessDeniedException) {
                throw $e;
            }
            throw new NotFoundHttpException('Course not found', $e);
        }
    }
}