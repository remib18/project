<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Mapper\UserMapper;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/user')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserMapper $userMapper
    )
    {}

    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        // Récupération des paramètres de pagination et de recherche
        $limit = $request->query->getInt('limit', 20);
        $offset = $request->query->getInt('offset', 0);
        $searchTerm = $request->query->get('search', '');

        // Limiter pour éviter les abus
        $limit = min($limit, 100);

        // Recherche des utilisateurs
        $users = $userRepository->findBySearchTerm($searchTerm, $limit, $offset);

        $userDTOs = $this->userMapper->mapUsersToDTO($users);

        return $this->json($userDTOs, Response::HTTP_OK);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator
    ): Response {
        $user = new User();
        $data = json_decode($request->getContent(), true);

        $form = $this->createForm(UserType::class, $user);
        $form->submit($data);

        // Get password from data
        $plainPassword = $data['password'] ?? null;

        // Validate password separately
        $passwordErrors = [];
        if (!empty($plainPassword)) {
            $passwordConstraints = new Assert\Collection([
                'password' => [
                    new Assert\NotBlank([
                        'message' => 'Le mot de passe ne peut pas être vide',
                    ]),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/[A-Z]/',
                        'message' => 'Le mot de passe doit contenir au moins une lettre majuscule',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/[a-z]/',
                        'message' => 'Le mot de passe doit contenir au moins une lettre minuscule',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/[0-9]/',
                        'message' => 'Le mot de passe doit contenir au moins un chiffre',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/[^A-Za-z0-9]/',
                        'message' => 'Le mot de passe doit contenir au moins un caractère spécial',
                    ]),
                ],
            ]);

            $passwordViolations = $validator->validate(['password' => $plainPassword], $passwordConstraints);
            if (count($passwordViolations) > 0) {
                foreach ($passwordViolations as $violation) {
                    $propertyPath = $violation->getPropertyPath();
                    $field = substr($propertyPath, 1, -1); // Remove brackets
                    $passwordErrors[$field] = $violation->getMessage();
                }
            }
        } else {
            $passwordErrors['password'] = 'Le mot de passe est requis';
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Encoder le mot de passe si nécessaire
            if (!empty($data['password'])) {
                $user->setPassword($hasher->hashPassword($user, $data['password']));
            } else {
                throw new BadRequestHttpException("Password is required");
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json(['status' => 'success'], Response::HTTP_OK);
        }

        // Récupérer les erreurs du formulaire
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $fieldName = $error->getOrigin()->getName();
            $errors[$fieldName] = $error->getMessage();
        }

        // Add password errors
        if (!empty($passwordErrors)) {
            $errors = array_merge($errors, $passwordErrors);
        }

        return $this->json([
            'status' => 'error',
            'form' => [
                'errors' => $errors
            ]
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->json([
            'user' => $user,
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator
    ): Response {
        $data = json_decode($request->getContent(), true);
        $form = $this->createForm(UserType::class, $user);
        $logger->info(json_encode($data));

        // Extract the password before form submission and remove it from the data array
        $plainPassword = $data['password'] ?? null;
        unset($data['password']);

        $form->submit($data);

        // Validate password if provided
        $passwordErrors = [];
        if (!empty($plainPassword)) {
            $passwordConstraints = new Assert\Collection([
                'password' => [
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/[A-Z]/',
                        'message' => 'Le mot de passe doit contenir au moins une lettre majuscule',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/[a-z]/',
                        'message' => 'Le mot de passe doit contenir au moins une lettre minuscule',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/[0-9]/',
                        'message' => 'Le mot de passe doit contenir au moins un chiffre',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/[^A-Za-z0-9]/',
                        'message' => 'Le mot de passe doit contenir au moins un caractère spécial',
                    ]),
                ],
            ]);

            $passwordViolations = $validator->validate(['password' => $plainPassword], $passwordConstraints);
            if (count($passwordViolations) > 0) {
                foreach ($passwordViolations as $violation) {
                    $propertyPath = $violation->getPropertyPath();
                    $field = substr($propertyPath, 1, -1); // Remove brackets
                    $passwordErrors[$field] = $violation->getMessage();
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            if (!empty($plainPassword)) {
                $hashedPassword = $hasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            return $this->json(['status' => 'success'], Response::HTTP_OK);
        }

        // Récupérer les erreurs du formulaire
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $fieldName = $error->getOrigin()->getName();
            $errors[$fieldName] = $error->getMessage();
        }

        // Add password errors
        if (!empty($passwordErrors)) {
            $errors = array_merge($errors, $passwordErrors);
        }

        return $this->json([
            'status' => 'error',
            'form' => [
                'errors' => $errors
            ]
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_user', $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            return $this->json(['status' => 'success'], Response::HTTP_OK);
        }

        return $this->json([
            'status' => 'error',
            'message' => 'Invalid CSRF token',
        ], Response::HTTP_BAD_REQUEST);
    }
}
