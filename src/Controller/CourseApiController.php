<?php

namespace App\Controller;

use App\DTO\Request\CourseActivityPinRequest;
use App\DTO\Request\CourseGroupRequest;
use App\DTO\Request\CourseUnitRequest;
use App\DTO\Request\GroupMembershipRequest;
use App\Entity\CourseGroup;
use App\Entity\CourseSchedule;
use App\Entity\CourseUnit;
use App\Entity\Role;
use App\Mapper\CourseMapper;
use App\Repository\CourseActivityRepository;
use App\Repository\CourseGroupRepository;
use App\Repository\CourseUnitRepository;
use App\Repository\UserRepository;
use App\Service\ImageUploadService;
use App\Service\RequestValidator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/api/course')]
final class CourseApiController extends AbstractApiController
{

    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly CourseMapper $courseMapper,
        private readonly ImageUploadService $imageUploadService
    ) {}

    /**
     * Return all the course units and their groups
     * Only accessible for administrators
     *
     * @param Request $request
     * @param CourseUnitRepository $courseUnitRepository
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/', name: 'app_course_api', methods: ['GET'])]
    public function index(Request $request, CourseUnitRepository $courseUnitRepository): JsonResponse
    {
        try {
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
            $formattedCourseUnits = $this->courseMapper->mapCourseUnitsToDTO($courseUnits);

            return $this->createSuccessResponse($formattedCourseUnits);
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a specific course unit
     * Only accessible for administrators
     *
     * @param string $slug
     * @param CourseUnitRepository $courseUnitRepository
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{slug}', name: 'app_course_api_get', methods: ['GET'])]
    public function get(
        string $slug,
        CourseUnitRepository $courseUnitRepository
    ): JsonResponse {
        try {
            // Find the course unit or throw an exception
            $courseUnit = $courseUnitRepository->findBySlugOrFail($slug);

            $courseDTO = $this->courseMapper->mapCourseUnitToDTO($courseUnit);

            return $this->createSuccessResponse($courseDTO);
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        }
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
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'app_course_api_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        SluggerInterface $slugger,
        RequestValidator $requestValidator
    ): JsonResponse {
        try {
            // Get form data
            $data = [
                'name' => $request->request->get('name'),
                'description' => $request->request->get('description'),
                '_token' => $request->request->get('_token'),
            ];

            // Get files
            $files = $request->files->all();

            // Create DTO
            $dto = CourseUnitRequest::fromArray($data, $files);

            // Validate CSRF token
            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('course_creation', $dto->_token))) {
                return $this->createErrorResponse('Invalid CSRF token');
            }

            // Validate DTO
            $errors = $validator->validate($dto);
            if (count($errors) > 0) {
                return $this->createValidationErrorResponse($errors);
            }

            // Generate slug from name
            $slug = $slugger->slug(strtolower($dto->name))->toString();

            // Create new course unit
            $courseUnit = new CourseUnit();
            $courseUnit->setName($dto->name);
            $courseUnit->setDescription($dto->description);
            $courseUnit->setSlug($slug);

            // Handle image upload
            if ($dto->imageFile) {
                try {
                    $imagePath = $this->imageUploadService->uploadCourseImage($dto->imageFile, $slug);
                    $courseUnit->setImage($imagePath);
                } catch (\Exception $e) {
                    return $this->createErrorResponse($e->getMessage());
                }
            } else {
                // Use placeholder image when no file is uploaded
                $courseUnit->setImage($this->imageUploadService->getPlaceholderPath());
            }

            // Validate entity
            $entityErrors = $validator->validate($courseUnit);
            if (count($entityErrors) > 0) {
                return $this->createValidationErrorResponse($entityErrors);
            }

            // Save to database
            $entityManager->persist($courseUnit);
            $entityManager->flush();

            // Return success response with created course
            $courseDTO = $this->courseMapper->mapCourseUnitToDTO($courseUnit);
            return $this->createSuccessResponse($courseDTO);
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{slug}', name: 'app_course_api_edit', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{slug}', name: 'app_course_api_edit', methods: ['POST'])]
    public function edit(
        string $slug,
        Request $request,
        CourseUnitRepository $courseUnitRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        RequestValidator $requestValidator
    ): JsonResponse {
        try {
            // Get form data
            $data = [
                'name' => $request->request->get('name'),
                'description' => $request->request->get('description'),
                '_token' => $request->request->get('_token'),
            ];

            // Get files
            $files = $request->files->all();

            // Create DTO
            $dto = CourseUnitRequest::fromArray($data, $files);

            // Validate CSRF token
            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('course_edition', $dto->_token))) {
                return $this->createErrorResponse('Invalid CSRF token');
            }

            // Validate request
            $errors = $validator->validate($dto);
            if (count($errors) > 0) {
                return $this->createValidationErrorResponse($errors);
            }

            // Find the course unit
            $courseUnit = $courseUnitRepository->findBySlugOrFail($slug);

            // Update course unit properties
            $courseUnit->setName($dto->name);
            $courseUnit->setDescription($dto->description);

            // Handle image upload if new file is provided
            if ($dto->imageFile) {
                try {
                    // Delete old image (only if it's not the placeholder)
                    $oldImage = $courseUnit->getImage();
                    if ($oldImage) {
                        $this->imageUploadService->deleteCourseImage($oldImage);
                    }

                    // Upload new image
                    $imagePath = $this->imageUploadService->uploadCourseImage($dto->imageFile, $slug);
                    $courseUnit->setImage($imagePath);
                } catch (\Exception $e) {
                    return $this->createErrorResponse($e->getMessage());
                }
            }

            // Validate the entity
            $violations = $validator->validate($courseUnit);
            if (count($violations) > 0) {
                return $this->createValidationErrorResponse($violations);
            }

            // Save changes
            $entityManager->flush();

            $courseDTO = $this->courseMapper->mapCourseUnitToDTO($courseUnit);
            return $this->createSuccessResponse($courseDTO);
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a course unit
     * Only accessible for administrators
     *
     * @param string $slug
     * @param Request $request
     * @param CourseUnitRepository $courseUnitRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{slug}/delete', name: 'app_course_api_delete', methods: ['POST'])]
    public function delete(
        string $slug,
        Request $request,
        CourseUnitRepository $courseUnitRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            // Decode JSON request
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->createErrorResponse('Invalid JSON request');
            }

            // Validate CSRF token
            if (!isset($data['_token']) || !$this->csrfTokenManager->isTokenValid(
                    new CsrfToken('course_deletion', $data['_token'])
                )) {
                return $this->createErrorResponse('Invalid CSRF token');
            }

            // Find the course unit or throw an exception
            $courseUnit = $courseUnitRepository->findBySlugOrFail($slug);

            // Delete course image
            $image = $courseUnit->getImage();
            if ($image) {
                $this->imageUploadService->deleteCourseImage($image);
            }

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

            return $this->createSuccessResponse();
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a specific course group
     * Only accessible for administrators
     *
     * @param int $id - The course group ID
     * @param CourseGroupRepository $courseGroupRepository
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/group/{id}', name: 'app_course_api_get_group', methods: ['GET'])]
    public function getGroup(
        int $id,
        CourseGroupRepository $courseGroupRepository
    ): JsonResponse {
        try {
            // Find course group
            $courseGroup = $courseGroupRepository->find($id);
            if (!$courseGroup) {
                return $this->createErrorResponse("Course group with ID $id not found", Response::HTTP_NOT_FOUND);
            }

            $groupDTO = $this->courseMapper->mapCourseGroupToDTO($courseGroup);

            return $this->createSuccessResponse($groupDTO);
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
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
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/group/new', name: 'app_course_api_create_group', methods: ['POST'])]
    public function createGroup(
        Request $request,
        EntityManagerInterface $entityManager,
        CourseUnitRepository $courseUnitRepository,
        ValidatorInterface $validator,
        RequestValidator $requestValidator
    ): JsonResponse {
        try {
            // Validate request using our service
            $validation = $requestValidator->validateRequestDto(
                $request,
                CourseGroupRequest::class,
                'course_group_create'
            );

            if (!$validation['isValid']) {
                return $this->createErrorResponse(
                    'Validation failed',
                    Response::HTTP_BAD_REQUEST,
                    $validation['errors']
                );
            }

            /** @var CourseGroupRequest $dto */
            $dto = $validation['dto'];

            // Find course unit
            $courseUnit = $courseUnitRepository->find($dto->courseUnitId);
            if (!$courseUnit) {
                return $this->createErrorResponse("Course unit with ID {$dto->courseUnitId} not found", Response::HTTP_NOT_FOUND);
            }

            try {
                // Create schedule
                $startTime = new DateTime($dto->startTime);
                $endTime = new DateTime($dto->endTime);
                $schedule = new CourseSchedule(
                    $dto->dayOfWeek,
                    $startTime,
                    $endTime
                );

                // Create new course group
                $courseGroup = new CourseGroup();
                $courseGroup->setName($dto->name);
                $courseGroup->setRoom($dto->room);
                $courseGroup->setSchedule($schedule);
                $courseGroup->setUnit($courseUnit);

                // Validate entity
                $errors = $validator->validate($courseGroup);
                if (count($errors) > 0) {
                    return $this->createValidationErrorResponse($errors);
                }

                // Save to database
                $entityManager->persist($courseGroup);
                $entityManager->flush();

                $groupDTO = $this->courseMapper->mapCourseGroupToDTO($courseGroup);

                // Return success response
                return $this->createSuccessResponse($groupDTO);
            } catch (InvalidArgumentException $e) {
                return $this->createErrorResponse($e->getMessage());
            }
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
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
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/group/{id}', name: 'app_course_api_edit_group', methods: ['POST'])]
    public function editGroup(
        int $id,
        Request $request,
        CourseGroupRepository $courseGroupRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        RequestValidator $requestValidator
    ): JsonResponse {
        try {
            // Validate request using our service
            $validation = $requestValidator->validateRequestDto(
                $request,
                CourseGroupRequest::class,
                'course_group_create'
            );

            if (!$validation['isValid']) {
                return $this->createErrorResponse(
                    'Validation failed',
                    Response::HTTP_BAD_REQUEST,
                    $validation['errors']
                );
            }

            /** @var CourseGroupRequest $dto */
            $dto = $validation['dto'];

            // Find course group
            $courseGroup = $courseGroupRepository->find($id);
            if (!$courseGroup) {
                return $this->createErrorResponse("Course group with ID {$id} not found", Response::HTTP_NOT_FOUND);
            }

            // Update fields if provided
            if (isset($dto->name)) {
                $courseGroup->setName($dto->name);
            }

            if (isset($dto->room)) {
                $courseGroup->setRoom($dto->room);
            }

            // Update schedule if any schedule field is provided
            $updateSchedule = isset($dto->dayOfWeek) || isset($dto->startTime) || isset($dto->endTime);
            if ($updateSchedule) {
                $currentSchedule = $courseGroup->getSchedule();

                // Use existing or new values
                $dayOfWeek = $dto->dayOfWeek ?? $currentSchedule->getDayOfWeek();
                $startTime = isset($dto['startTime']) ? new DateTime($dto['startTime']) : $currentSchedule->getStartTime();
                $endTime = isset($dto['endTime']) ? new DateTime($dto['endTime']) : $currentSchedule->getEndTime();

                try {
                    $newSchedule = new CourseSchedule($dayOfWeek, $startTime, $endTime);
                    $courseGroup->setSchedule($newSchedule);
                } catch (InvalidArgumentException $e) {
                    return $this->createErrorResponse($e->getMessage());
                }
            }

            // Validate entity
            $errors = $validator->validate($courseGroup);
            if (count($errors) > 0) {
                return $this->createValidationErrorResponse($errors);
            }

            // Save changes
            $entityManager->flush();

            $groupDTO = $this->courseMapper->mapCourseGroupToDTO($courseGroup);

            // Return success response
            return $this->createSuccessResponse($groupDTO);
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a course unit group
     * Only accessible for administrators
     *
     * @param int $id - The course group ID
     * @param Request $request
     * @param CourseGroupRepository $courseGroupRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/group/{id}/delete', name: 'app_course_api_delete_group', methods: ['POST'])]
    public function deleteGroup(
        int $id,
        Request $request,
        CourseGroupRepository $courseGroupRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            // Decode JSON request
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->createErrorResponse('Invalid JSON request');
            }

            // Validate CSRF token
            if (!isset($data['_token']) || !$this->csrfTokenManager->isTokenValid(
                    new CsrfToken('course_group_delete', $data['_token'])
                )) {
                return $this->createErrorResponse('Invalid CSRF token');
            }

            // Find course group
            $courseGroup = $courseGroupRepository->find($id);
            if (!$courseGroup) {
                return $this->createErrorResponse("Course group with ID {$id} not found", Response::HTTP_NOT_FOUND);
            }

            // Remove all members relationship first
            foreach ($courseGroup->getMembers() as $member) {
                $courseGroup->removeMember($member);
            }

            // Delete the group
            $entityManager->remove($courseGroup);
            $entityManager->flush();

            // Return success response
            return $this->createSuccessResponse();
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get users eligible to be added to a course group (not already members)
     * Only accessible for administrators
     *
     * @param int $id - The course group ID
     * @param CourseGroupRepository $courseGroupRepository
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/group/{id}/eligible-users', name: 'app_course_api_eligible_users', methods: ['GET'])]
    public function getEligibleUsersForGroup(
        int $id,
        CourseGroupRepository $courseGroupRepository,
        UserRepository $userRepository
    ): JsonResponse {
        try {
            // Find course group
            $courseGroup = $courseGroupRepository->find($id);
            if (!$courseGroup) {
                return $this->createErrorResponse("Course group with ID {$id} not found", Response::HTTP_NOT_FOUND);
            }

            // Get users who are not already in the group
            $eligibleUsers = $userRepository->findUsersNotInGroup($courseGroup);

            // Format response data
            $formattedUsers = [];
            foreach ($eligibleUsers as $user) {
                $formattedUsers[] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'roles' => $user->getRoles(),
                ];
            }

            // Return users
            return $this->createSuccessResponse($formattedUsers);
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/group/{id}/add', name: 'app_course_api_add_user', methods: ['POST'])]
    public function addUserToGroup(
        int $id,
        Request $request,
        CourseGroupRepository $courseGroupRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        RequestValidator $requestValidator
    ): JsonResponse {
        try {
            // Validate request
            $validation = $requestValidator->validateRequestDto(
                $request,
                GroupMembershipRequest::class,
                'group_members'
            );

            if (!$validation['isValid']) {
                return $this->createErrorResponse(
                    'Validation failed',
                    Response::HTTP_BAD_REQUEST,
                    $validation['errors']
                );
            }

            /** @var GroupMembershipRequest $dto */
            $dto = $validation['dto'];

            // Find course group
            $courseGroup = $courseGroupRepository->findWithRelations($id);
            if (!$courseGroup) {
                return $this->createErrorResponse("Course group with ID {$id} not found", Response::HTTP_NOT_FOUND);
            }

            // Find user to add
            $user = $userRepository->find($dto->userId);
            if (!$user) {
                return $this->createErrorResponse("User with ID {$dto->userId} not found", Response::HTTP_NOT_FOUND);
            }

            // Check if user is already in the group
            if ($courseGroup->getMembers()->contains($user)) {
                return $this->createErrorResponse("User with ID {$dto->userId} is already in the group", Response::HTTP_CONFLICT);
            }

            // Add user to group
            $courseGroup->addMember($user);
            $entityManager->flush();

            // Return success response with formatted data to avoid circular references
            return $this->createSuccessResponse([
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getFullName(),
                ],
                'group' => [
                    'id' => $courseGroup->getId(),
                    'name' => $courseGroup->getName(),
                ]
            ]);
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/group/{id}/remove', name: 'app_course_api_remove_user', methods: ['POST'])]
    public function removeUserFromGroup(
        int $id,
        Request $request,
        CourseGroupRepository $courseGroupRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            // Decode JSON request
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->createErrorResponse('Invalid JSON request');
            }

            // Validate CSRF token
            if (!isset($data['_token']) || !$this->csrfTokenManager->isTokenValid(
                    new CsrfToken('group_members', $data['_token'])
                )) {
                return $this->createErrorResponse('Invalid CSRF token');
            }

            // Find course group
            $courseGroup = $courseGroupRepository->findWithRelations($id);
            if (!$courseGroup) {
                return $this->createErrorResponse("Course group with ID {$id} not found", Response::HTTP_NOT_FOUND);
            }

            // Decode JSON request
            if (!isset($data['userId'])) {
                return $this->createErrorResponse('Invalid JSON request, missing userId');
            }

            // Find user to remove
            $user = $userRepository->find($data['userId']);
            if (!$user) {
                return $this->createErrorResponse("User with ID {$data['userId']} not found", Response::HTTP_NOT_FOUND);
            }

            // Check if user is in the group
            if (!$courseGroup->getMembers()->contains($user)) {
                return $this->createErrorResponse("User with ID {$data['userId']} is not in the group", Response::HTTP_CONFLICT);
            }

            // Remove user from group
            $courseGroup->removeMember($user);
            $entityManager->flush();

            // Return success response with formatted data to avoid circular references
            return $this->createSuccessResponse([
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getFullName(),
                ],
                'group' => [
                    'id' => $courseGroup->getId(),
                    'name' => $courseGroup->getName(),
                ]
            ]);
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get users of a specific role in a course group with pagination
     * Only accessible for administrators
     *
     * @param int $id - The course group ID
     * @param string $role - The role to filter by (teacher or student)
     * @param Request $request
     * @param CourseGroupRepository $courseGroupRepository
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/group/{id}/members/{role}', name: 'app_course_api_group_members', methods: ['GET'])]
    public function getGroupMembersByRole(
        int $id,
        string $role,
        Request $request,
        CourseGroupRepository $courseGroupRepository,
        UserRepository $userRepository
    ): JsonResponse {
        try {
            // Find course group
            $courseGroup = $courseGroupRepository->find($id);
            if (!$courseGroup) {
                return $this->createErrorResponse("Course group with ID {$id} not found", Response::HTTP_NOT_FOUND);
            }

            // Validate role parameter
            if (!in_array($role, ['teacher', 'student'])) {
                return $this->createErrorResponse('Invalid role parameter', Response::HTTP_BAD_REQUEST);
            }

            // Map role parameter to Role enum
            $roleEnum = $role === 'teacher' ? Role::ROLE_TEACHER : Role::ROLE_STUDENT;

            // Get pagination parameters
            $limit = $request->query->getInt('limit', 20);
            $offset = $request->query->getInt('offset', 0);
            $searchTerm = $request->query->get('search', '');

            // Limit the number of results per page
            $limit = min($limit, 100);

            // Get group members by role
            $members = $userRepository->findGroupMembersByRole($courseGroup, $roleEnum, $limit, $offset, $searchTerm);
            $total = $userRepository->countGroupMembersByRole($courseGroup, $roleEnum, $searchTerm);

            // Format members for response
            $formattedMembers = [];
            foreach ($members as $member) {
                $formattedMembers[] = [
                    'id' => $member->getId(),
                    'email' => $member->getEmail(),
                    'firstname' => $member->getFirstname(),
                    'lastname' => $member->getLastname(),
                    'fullName' => $member->getFullName(),
                    'roles' => $member->getRoles()
                ];
            }

            // Return group members
            return $this->createPaginatedSuccessResponse(
                $formattedMembers,
                $total,
                ($offset + count($formattedMembers) < $total)
            );
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get users eligible to be added to a course group filtered by role
     * Only accessible for administrators
     *
     * @param int $id - The course group ID
     * @param string $role - The role to filter by (teacher or student)
     * @param CourseGroupRepository $courseGroupRepository
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/group/{id}/eligible-users/{role}', name: 'app_course_api_eligible_users_by_role', methods: ['GET'])]
    public function getEligibleUsersForGroupByRole(
        int $id,
        string $role,
        Request $request,
        CourseGroupRepository $courseGroupRepository,
        UserRepository $userRepository
    ): JsonResponse {
        try {
            // Find course group
            $courseGroup = $courseGroupRepository->find($id);
            if (!$courseGroup) {
                return $this->createErrorResponse("Course group with ID {$id} not found", Response::HTTP_NOT_FOUND);
            }

            // Validate role parameter
            if (!in_array($role, ['teacher', 'student'])) {
                return $this->createErrorResponse('Invalid role parameter', Response::HTTP_BAD_REQUEST);
            }

            // Map role parameter to Role enum
            $roleValue = $role === 'teacher' ? Role::ROLE_TEACHER : Role::ROLE_STUDENT;

            // Get pagination parameters
            $limit = $request->query->getInt('limit', 20);
            $offset = $request->query->getInt('offset', 0);
            $searchTerm = $request->query->get('search', '');

            // Limit the number of results per page
            $limit = min($limit, 100);

            // Get eligible users by role - FIXED: Using the correct method name
            $eligibleUsers = $userRepository->findUsersNotInGroupByRole($courseGroup, $roleValue, $limit, $offset, $searchTerm);
            $total = $userRepository->countUsersNotInGroupByRole($courseGroup, $roleValue, $searchTerm);

            // Format users for response directly to avoid circular reference
            $formattedUsers = [];
            foreach ($eligibleUsers as $user) {
                $formattedUsers[] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'fullName' => $user->getFullName(),
                    'roles' => $user->getRoles()
                ];
            }

            // Return eligible users
            return $this->createPaginatedSuccessResponse(
                $formattedUsers,
                $total,
                ($offset + count($eligibleUsers) < $total)
            );
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all members of a course group with teachers first, then students
     * Only accessible for administrators
     *
     * @param int $id - The course group ID
     * @param Request $request
     * @param CourseGroupRepository $courseGroupRepository
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/group/{id}/members', name: 'app_course_api_all_group_members', methods: ['GET'])]
    public function getAllGroupMembers(
        int $id,
        Request $request,
        CourseGroupRepository $courseGroupRepository
    ): JsonResponse {
        try {
            // Find course group
            $courseGroup = $courseGroupRepository->findWithRelations($id);
            if (!$courseGroup) {
                return $this->createErrorResponse("Course group with ID {$id} not found", Response::HTTP_NOT_FOUND);
            }

            error_log("Group ID: {$id}, Name: " . $courseGroup->getName());
            error_log("Members count: " . $courseGroup->getMembers()->count());
            error_log("Members IDs: " . implode(',', array_map(function($member) {
                    return $member->getId();
                }, $courseGroup->getMembers()->toArray())));

            // Get pagination parameters
            $limit = $request->query->getInt('limit', 20);
            $offset = $request->query->getInt('offset', 0);

            // Limit the number of results per page
            $limit = min($limit, 100);

            // Get all members as array to prevent circular reference issues
            $allMembers = [];
            foreach ($courseGroup->getMembers() as $member) {
                $allMembers[] = [
                    'id' => $member->getId(),
                    'email' => $member->getEmail(),
                    'firstname' => $member->getFirstname(),
                    'lastname' => $member->getLastname(),
                    'fullName' => $member->getFullName(),
                    'roles' => $member->getRoles()
                ];
            }

            // Sort members: teachers first, then students, alphabetically within each group
            usort($allMembers, function($a, $b) {
                $aIsTeacher = in_array('ROLE_TEACHER', $a['roles']);
                $bIsTeacher = in_array('ROLE_TEACHER', $b['roles']);

                // If one is a teacher and the other is not, teacher comes first
                if ($aIsTeacher && !$bIsTeacher) return -1;
                if (!$aIsTeacher && $bIsTeacher) return 1;

                // If both are teachers or both are students, sort alphabetically by last name, then first name
                $lastNameComparison = strcmp($a['lastname'], $b['lastname']);
                if ($lastNameComparison !== 0) return $lastNameComparison;

                return strcmp($a['firstname'], $b['firstname']);
            });

            // Apply pagination
            $totalMembers = count($allMembers);
            $members = array_slice($allMembers, $offset, $limit);

            // Return all members
            return $this->createPaginatedSuccessResponse(
                $members,
                $totalMembers,
                ($offset + count($members) < $totalMembers)
            );
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Pin or unpin a group activity with optional message
     * Only accessible for administrators and teachers who are members of the course
     *
     * @param int $id - The course activity ID
     * @param Request $request
     * @param CourseActivityRepository $courseActivityRepository
     * @param CourseGroupRepository $courseGroupRepository
     * @param EntityManagerInterface $entityManager
     * @param RequestValidator $requestValidator
     * @return JsonResponse
     */
    #[Route('/activity/{id}/pin', name: 'app_course_api_pin_activity', methods: ['POST'])]
    public function pinActivity(
        int $id,
        Request $request,
        CourseActivityRepository $courseActivityRepository,
        CourseGroupRepository $courseGroupRepository,
        EntityManagerInterface $entityManager,
        RequestValidator $requestValidator
    ): JsonResponse {
        try {
            // Find course activity
            $courseActivity = $courseActivityRepository->find($id);
            if (!$courseActivity) {
                return $this->createErrorResponse("Course activity with ID {$id} not found", Response::HTTP_NOT_FOUND);
            }

            // Get current pinned status
            $currentlyPinned = $courseActivity->isPinned();

            // Validate request using RequestDTO
            $validation = $requestValidator->validateRequestDto(
                $request,
                CourseActivityPinRequest::class,
                'course_activity_pin'
            );

            if (!$validation['isValid']) {
                return $this->createErrorResponse(
                    'Validation failed',
                    Response::HTTP_BAD_REQUEST,
                    $validation['errors']
                );
            }

            /** @var CourseActivityPinRequest $dto */
            $dto = $validation['dto'];

            // Additional validation based on operation type
            $operationErrors = $dto->validateOperation($currentlyPinned);
            if (!empty($operationErrors)) {
                return $this->createErrorResponse(
                    'Validation failed',
                    Response::HTTP_BAD_REQUEST,
                    $operationErrors
                );
            }

            $user = $this->getUser();
            $courseUnit = $courseActivity->getCourseUnit();

            // Check if user has permission
            if (!in_array('ROLE_ADMIN', $user->getRoles())) {
                // For teachers, verify they are members of at least one group in this course
                if (!in_array('ROLE_TEACHER', $user->getRoles()) ||
                    !$courseGroupRepository->isUserInCourseUnit($user, $courseUnit)) {
                    return $this->createErrorResponse('Permission denied', Response::HTTP_FORBIDDEN);
                }
            }

            // Perform the pin/unpin operation
            if (!$currentlyPinned) {
                // Pin the activity
                $courseActivity->setIsPinned(true);
                $courseActivity->setPinnedMessage($dto->pinnedMessage);
            } else {
                // Unpin the activity
                $courseActivity->setIsPinned(false);
                $courseActivity->setPinnedMessage(null);
            }

            // Save changes
            $entityManager->flush();

            // Return success response
            return $this->createSuccessResponse([
                'isPinned' => $courseActivity->isPinned(),
                'pinnedMessage' => $courseActivity->getPinnedMessage()
            ]);
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a course activity
     * Only accessible for administrators and teachers
     *
     * @param int $id - The course activity ID
     * @param Request $request
     * @param CourseActivityRepository $courseActivityRepository
     * @param CourseGroupRepository $courseGroupRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[Route('/activity/{id}', name: 'app_course_api_delete_activity', methods: ['DELETE'])]
    public function deleteActivity(
        int $id,
        Request $request,
        CourseActivityRepository $courseActivityRepository,
        CourseGroupRepository $courseGroupRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            // Decode JSON request body
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->createErrorResponse('Invalid JSON request');
            }

            // Validate CSRF token
            if (!isset($data['_token']) || !$this->csrfTokenManager->isTokenValid(
                    new CsrfToken('course_activity_delete', $data['_token'])
                )) {
                return $this->createErrorResponse('Invalid CSRF token');
            }

            // Find course activity
            $courseActivity = $courseActivityRepository->find($id);
            if (!$courseActivity) {
                return $this->createErrorResponse("Course activity with ID {$id} not found", Response::HTTP_NOT_FOUND);
            }

            $user = $this->getUser();
            $courseUnit = $courseActivity->getCourseUnit();

            // Check if user has permission
            if (!in_array('ROLE_ADMIN', $user->getRoles())) {
                // For teachers, verify they are members of at least one group in this course
                if (!in_array('ROLE_TEACHER', $user->getRoles()) ||
                    !$courseGroupRepository->isUserInCourseUnit($user, $courseUnit)) {
                    return $this->createErrorResponse('Permission denied', Response::HTTP_FORBIDDEN);
                }
            }

            // Delete course activity
            $entityManager->remove($courseActivity);
            $entityManager->flush();

            // Return success response
            return $this->createSuccessResponse();
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}