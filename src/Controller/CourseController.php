<?php

namespace App\Controller;

use App\DTO\CourseGroupDTO;
use App\DTO\CourseUnitDTO;
use App\DTO\ScheduledCourseDTO;
use App\Entity\CourseGroup;
use App\Entity\CourseSchedule;
use App\Entity\CourseUnit;
use App\Entity\Role;
use App\Entity\User;
use App\Formatter\CourseFormatter;
use App\Repository\CourseActivityRepository;
use App\Repository\CourseGroupRepository;
use App\Repository\CourseUnitRepository;
use App\Repository\UserRepository;
use App\Service\CourseSecurityService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Controller for handling course-related pages and actions
 */
final class CourseController extends AbstractController
{
    private CourseFormatter $courseFormatter;
    private CourseSecurityService $courseSecurityService;
    private LoggerInterface $logger;

    public function __construct(
        CourseFormatter $courseFormatter,
        CourseSecurityService $courseSecurityService,
        LoggerInterface $logger
    ) {
        $this->courseFormatter = $courseFormatter;
        $this->courseSecurityService = $courseSecurityService;
        $this->logger = $logger;
    }

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

    /**
     * Return all the course units and their groups
     * Only accessible for administrators
     *
     * @param Request $request
     * @param CourseUnitRepository $courseUnitRepository
     * @return Response
     */
    #[Route('/api/course', name: 'app_course_api', methods: ['GET'])]
    public function courseApiIndex(Request $request, CourseUnitRepository $courseUnitRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->isGranted(Role::ROLE_ADMIN)) {
            //throw $this->createAccessDeniedException('Only administrators can access this resource');
        }

        // Get pagination parameters
        $limit = $request->query->getInt('limit', 20);
        $offset = $request->query->getInt('offset', 0);
        $searchTerm = $request->query->get('search', '');

        // Limit to prevent abuse
        $limit = min($limit, 100);

        // Get course units with pagination
        if (!empty($searchTerm)) {
            $courseUnits = $courseUnitRepository->findBySearchTerm($searchTerm, $limit, $offset);
        } else {
            $courseUnits = $courseUnitRepository->findBy([], ['name' => 'ASC'], $limit, $offset);
        }

        // Format the course units for the API response
        $formattedCourseUnits = [];
        foreach ($courseUnits as $courseUnit) {
            $groups = [];
            foreach ($courseUnit->getGroups() as $group) {
                $schedule = $group->getSchedule();
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

                $members = $group->getMembers()->toArray();

                $groups[] = new CourseGroupDTO(
                    $group->getId(),
                    $group->getName(),
                    $members,
                    $scheduledCourse,
                    $group->getRoom()
                );
            }

            $formattedCourseUnits[] = new CourseUnitDTO(
                $courseUnit->getId(),
                $courseUnit->getName(),
                $courseUnit->getDescription(),
                $courseUnit->getImage(),
                '/course/' . $courseUnit->getSlug(),
                $groups
            );
        }

        sleep(2);

        return $this->json($formattedCourseUnits, Response::HTTP_OK);
    }

    /**
     * Create a new course unit
     * Only accessible for administrators
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @param SluggerInterface $slugger
     * @return JsonResponse
     */
    #[Route('/api/course', name: 'app_course_api_create', methods: ['POST'])]
    public function courseApiCreate(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        SluggerInterface $slugger
    ): JsonResponse {
        // Check if user has admin role
        if (!$this->isGranted(Role::ROLE_ADMIN->value)) {
            // throw new AccessDeniedException('Only administrators can create courses');
        }

        // Decode JSON request
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            throw new BadRequestHttpException('Invalid JSON data');
        }

        // Validate required fields
        if (empty($data['name']) || empty($data['description'])) {
            return new JsonResponse(['error' => 'Name and description are required'], Response::HTTP_BAD_REQUEST);
        }

        // Create new course unit
        $courseUnit = new CourseUnit();
        $courseUnit->setName($data['name']);
        $courseUnit->setDescription($data['description']);
        $courseUnit->setImage($data['image'] ?? null);

        // Generate slug from name
        $slug = $slugger->slug(strtolower($data['name']))->toString();
        $courseUnit->setSlug($slug);

        // Validate entity
        $errors = $validator->validate($courseUnit);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Save to database
        $entityManager->persist($courseUnit);
        $entityManager->flush();

        // Return success response with created course
        return new JsonResponse([
            'id' => $courseUnit->getId(),
            'name' => $courseUnit->getName(),
            'description' => $courseUnit->getDescription(),
            'image' => $courseUnit->getImage(),
            'slug' => $courseUnit->getSlug(),
        ], Response::HTTP_CREATED);
    }

    /**
     * Edit a course unit
     * Only accessible for administrators
     *
     * @param string $slug
     * @param Request $request
     * @param CourseUnitRepository $courseUnitRepository
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @return Response
     */
    #[Route('/api/course/{slug}', name: 'app_course_api_edit', methods: ['POST'])]
    public function courseApiEdit(
        string $slug,
        Request $request,
        CourseUnitRepository $courseUnitRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->isGranted(Role::ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only administrators can edit courses');
        }

        try {
            // Find the course unit or throw an exception
            $courseUnit = $courseUnitRepository->findBySlugOrFail($slug);

            // Get request data
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                throw new BadRequestHttpException('Invalid JSON data');
            }

            // Update course unit properties
            if (isset($data['name'])) {
                $courseUnit->setName($data['name']);
            }

            if (isset($data['description'])) {
                $courseUnit->setDescription($data['description']);
            }

            if (isset($data['image'])) {
                $courseUnit->setImage($data['image']);
            }

            if (isset($data['slug'])) {
                $courseUnit->setSlug($data['slug']);
            }

            // Validate the entity
            $violations = $validator->validate($courseUnit);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $propertyPath = $violation->getPropertyPath();
                    $errors[$propertyPath] = $violation->getMessage();
                }

                return $this->json([
                    'status' => 'error',
                    'errors' => $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            // Save changes
            $entityManager->flush();

            return $this->json([
                'status' => 'success',
                'course' => [
                    'id' => $courseUnit->getId(),
                    'name' => $courseUnit->getName(),
                    'description' => $courseUnit->getDescription(),
                    'image' => $courseUnit->getImage(),
                    'slug' => $courseUnit->getSlug()
                ]
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Delete a course unit
     * Only accessible for administrators
     *
     * @param string $slug
     * @param CourseUnitRepository $courseUnitRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/api/course/{slug}/delete', name: 'app_course_api_delete', methods: ['POST'])]
    public function courseApiDelete(
        string $slug,
        CourseUnitRepository $courseUnitRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->isGranted(Role::ROLE_ADMIN)) {
            throw $this->createAccessDeniedException('Only administrators can delete courses');
        }

        try {
            // Find the course unit or throw an exception
            $courseUnit = $courseUnitRepository->findBySlugOrFail($slug);

            // Check if there are any groups that should be deleted first
            $groups = $courseUnit->getGroups();
            if (!$groups->isEmpty()) {
                // Remove all groups associated with this course unit
                foreach ($groups as $group) {
                    $courseUnit->removeGroup($group);
                    $entityManager->remove($group);
                }
            }

            // Remove any activities associated with this course unit
            $activities = $courseUnit->getActivities();
            if (!$activities->isEmpty()) {
                foreach ($activities as $activity) {
                    $courseUnit->removeActivity($activity);
                    $entityManager->remove($activity);
                }
            }

            // Remove the course unit
            $entityManager->remove($courseUnit);
            $entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => sprintf('Course "%s" has been successfully deleted', $courseUnit->getName())
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Create a new course unit group
     * Only accessible for administrators
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param CourseUnitRepository $courseUnitRepository
     * @param ValidatorInterface $validator
     * @return Response
     * @throws Exception
     */
    #[Route('/api/course/group', name: 'app_course_api_create_group', methods: ['POST'])]
    public function courseApiCreateGroup(
        Request $request,
        EntityManagerInterface $entityManager,
        CourseUnitRepository $courseUnitRepository,
        ValidatorInterface $validator
    ): Response {
        // Check if user has admin role
        if (!$this->isGranted(Role::ROLE_ADMIN->value)) {
            throw new AccessDeniedException('Only administrators can create course groups');
        }

        // Decode JSON request
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            throw new BadRequestHttpException('Invalid JSON data');
        }

        // Validate required fields
        if (empty($data['name']) || empty($data['courseUnitId']) || empty($data['room']) ||
            empty($data['dayOfWeek']) || empty($data['startTime']) || empty($data['endTime'])) {
            return new JsonResponse([
                'error' => 'Name, courseUnitId, room, dayOfWeek, startTime and endTime are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Find course unit
        $courseUnit = $courseUnitRepository->find($data['courseUnitId']);
        if (!$courseUnit) {
            throw new NotFoundHttpException('Course unit not found');
        }

        try {
            // Create schedule
            $startTime = new DateTime($data['startTime']);
            $endTime = new DateTime($data['endTime']);
            $schedule = new CourseSchedule(
                (int) $data['dayOfWeek'],
                $startTime,
                $endTime
            );

            // Create new course group
            $courseGroup = new CourseGroup();
            $courseGroup->setName($data['name']);
            $courseGroup->setRoom($data['room']);
            $courseGroup->setSchedule($schedule);
            $courseGroup->setUnit($courseUnit);

            // Validate entity
            $errors = $validator->validate($courseGroup);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            // Save to database
            $entityManager->persist($courseGroup);
            $entityManager->flush();

            // Return success response
            return new JsonResponse([
                'id' => $courseGroup->getId(),
                'name' => $courseGroup->getName(),
                'room' => $courseGroup->getRoom(),
                'schedule' => [
                    'dayOfWeek' => $schedule->getDayOfWeek(),
                    'dayName' => $schedule->getDayName(),
                    'startTime' => $schedule->getFormattedStartTime(),
                    'endTime' => $schedule->getFormattedEndTime(),
                ],
                'courseUnit' => [
                    'id' => $courseUnit->getId(),
                    'name' => $courseUnit->getName(),
                    'slug' => $courseUnit->getSlug(),
                ]
            ], Response::HTTP_CREATED);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Edit a course unit group
     * Only accessible for administrators
     *
     * @param int $id - The course group ID
     * @param Request $request
     * @param CourseGroupRepository $courseGroupRepository
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @return Response
     * @throws Exception
     */
    #[Route('/api/course/group/{id}', name: 'app_course_api_edit_group', methods: ['POST'])]
    public function courseApiEditGroup(
        int $id,
        Request $request,
        CourseGroupRepository $courseGroupRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        // Check if user has admin role
        if (!$this->isGranted(Role::ROLE_ADMIN->value)) {
            throw new AccessDeniedException('Only administrators can edit course groups');
        }

        // Find course group
        $courseGroup = $courseGroupRepository->find($id);
        if (!$courseGroup) {
            throw new NotFoundHttpException('Course group not found');
        }

        // Decode JSON request
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            throw new BadRequestHttpException('Invalid JSON data');
        }

        // Update fields if provided
        if (isset($data['name'])) {
            $courseGroup->setName($data['name']);
        }

        if (isset($data['room'])) {
            $courseGroup->setRoom($data['room']);
        }

        // Update schedule if any schedule field is provided
        $updateSchedule = isset($data['dayOfWeek']) || isset($data['startTime']) || isset($data['endTime']);
        if ($updateSchedule) {
            $currentSchedule = $courseGroup->getSchedule();

            // Use existing or new values
            $dayOfWeek = $data['dayOfWeek'] ?? $currentSchedule->getDayOfWeek();
            $startTime = isset($data['startTime']) ? new DateTime($data['startTime']) : $currentSchedule->getStartTime();
            $endTime = isset($data['endTime']) ? new DateTime($data['endTime']) : $currentSchedule->getEndTime();

            try {
                $newSchedule = new CourseSchedule((int) $dayOfWeek, $startTime, $endTime);
                $courseGroup->setSchedule($newSchedule);
            } catch (InvalidArgumentException $e) {
                return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validate entity
        $errors = $validator->validate($courseGroup);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Save changes
        $entityManager->flush();

        // Get the course unit
        $courseUnit = $courseGroup->getUnit();
        $schedule = $courseGroup->getSchedule();

        // Return success response
        return new JsonResponse([
            'id' => $courseGroup->getId(),
            'name' => $courseGroup->getName(),
            'room' => $courseGroup->getRoom(),
            'schedule' => [
                'dayOfWeek' => $schedule->getDayOfWeek(),
                'dayName' => $schedule->getDayName(),
                'startTime' => $schedule->getFormattedStartTime(),
                'endTime' => $schedule->getFormattedEndTime(),
            ],
            'courseUnit' => [
                'id' => $courseUnit->getId(),
                'name' => $courseUnit->getName(),
                'slug' => $courseUnit->getSlug(),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Delete a course unit group
     * Only accessible for administrators
     *
     * @param int $id - The course group ID
     * @param CourseGroupRepository $courseGroupRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/api/course/group/{id}/delete', name: 'app_course_api_delete_group', methods: ['POST'])]
    public function courseApiDeleteGroup(
        int $id,
        CourseGroupRepository $courseGroupRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Check if user has admin role
        if (!$this->isGranted(Role::ROLE_ADMIN->value)) {
            throw new AccessDeniedException('Only administrators can delete course groups');
        }

        // Find course group
        $courseGroup = $courseGroupRepository->find($id);
        if (!$courseGroup) {
            throw new NotFoundHttpException('Course group not found');
        }

        // Remove all members relationship first
        foreach ($courseGroup->getMembers() as $member) {
            $courseGroup->removeMember($member);
        }

        // Delete the group
        $entityManager->remove($courseGroup);
        $entityManager->flush();

        // Return success response
        return new JsonResponse(['message' => 'Course group deleted successfully'], Response::HTTP_OK);
    }

    /**
     * Add a user to a course unit group
     * Only accessible for administrators
     *
     * @param int $id - The course group ID
     * @param Request $request
     * @param CourseGroupRepository $courseGroupRepository
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/api/course/group/{id}/add', name: 'app_course_api_add_user', methods: ['POST'])]
    public function courseApiAddUser(
        int $id,
        Request $request,
        CourseGroupRepository $courseGroupRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Check if user has admin role
        if (!$this->isGranted(Role::ROLE_ADMIN->value)) {
            throw new AccessDeniedException('Only administrators can add users to course groups');
        }

        // Find course group
        $courseGroup = $courseGroupRepository->find($id);
        if (!$courseGroup) {
            throw new NotFoundHttpException('Course group not found');
        }

        // Decode JSON request
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['userId'])) {
            throw new BadRequestHttpException('Invalid JSON data or missing userId');
        }

        // Find user to add
        $user = $userRepository->find($data['userId']);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        // Check if user is already in the group
        if ($courseGroup->getMembers()->contains($user)) {
            return new JsonResponse(
                ['message' => 'User is already a member of this group'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Add user to group
        $courseGroup->addMember($user);
        $entityManager->flush();

        // Return success response
        return new JsonResponse([
            'message' => 'User added to course group successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
            ],
            'group' => [
                'id' => $courseGroup->getId(),
                'name' => $courseGroup->getName(),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Remove a user from a course unit group
     * Only accessible for administrators
     *
     * @param int $id - The course group ID
     * @param Request $request
     * @param CourseGroupRepository $courseGroupRepository
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/api/course/group/{id}/remove', name: 'app_course_api_remove_user', methods: ['POST'])]
    public function courseApiRemoveUser(
        int $id,
        Request $request,
        CourseGroupRepository $courseGroupRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Check if user has admin role
        if (!$this->isGranted(Role::ROLE_ADMIN->value)) {
            throw new AccessDeniedException('Only administrators can remove users from course groups');
        }

        // Find course group
        $courseGroup = $courseGroupRepository->find($id);
        if (!$courseGroup) {
            throw new NotFoundHttpException('Course group not found');
        }

        // Decode JSON request
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['userId'])) {
            throw new BadRequestHttpException('Invalid JSON data or missing userId');
        }

        // Find user to remove
        $user = $userRepository->find($data['userId']);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        // Check if user is in the group
        if (!$courseGroup->getMembers()->contains($user)) {
            return new JsonResponse(
                ['message' => 'User is not a member of this group'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Remove user from group
        $courseGroup->removeMember($user);
        $entityManager->flush();

        // Return success response
        return new JsonResponse([
            'message' => 'User removed from course group successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
            ],
            'group' => [
                'id' => $courseGroup->getId(),
                'name' => $courseGroup->getName(),
            ]
        ], Response::HTTP_OK);
    }
}