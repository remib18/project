<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        // Récupération des paramètres de pagination et de recherche
        $limit = $request->query->getInt('limit', 20);
        $offset = $request->query->getInt('offset', 0);
        $searchTerm = $request->query->get('search', '');

        // Limiter pour éviter les abus
        $limit = min($limit, 100);

        // Recherche des utilisateurs avec pagination
        if (!empty($searchTerm)) {
            $users = $userRepository->findBySearchTerm($searchTerm, $limit, $offset);
        } else {
            $users = $userRepository->findBy([], ['lastname' => 'DESC', 'firstname' => 'DESC'], $limit, $offset);
        }

        return $this->json($users, Response::HTTP_OK);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $data = json_decode($request->getContent(), true);

        $form = $this->createForm(UserType::class, $user);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encoder le mot de passe si nécessaire
            if (isset($data['password'])) {
                // Dans un cas réel, vous utiliseriez le PasswordHasher
                $user->setPassword(password_hash($data['password'], PASSWORD_DEFAULT));
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
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $data = json_decode($request->getContent(), true);
        $form = $this->createForm(UserType::class, $user);
        $logger->info(json_encode($data, JSON_PRETTY_PRINT));
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->json(['status' => 'success'], Response::HTTP_OK);
        }

        // Récupérer les erreurs du formulaire
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $fieldName = $error->getOrigin()->getName();
            $errors[$fieldName] = $error->getMessage();
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
