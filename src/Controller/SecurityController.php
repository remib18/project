<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class SecurityController extends AbstractController
{
    private $passwordHasher;
    private $entityManager;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager)
    {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/profile', name: 'app_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();

        return $this->render('security/profile.html.twig', [
            'user' => $user,
            'password_min_length' => 12,
        ]);
    }

    #[Route(path: '/profile/update', name: 'app_profile_update', methods: ['POST'])]
    public function updateProfile(Request $request): Response
    {
        $user = $this->getUser();

        // Vérifier si l'utilisateur est connecté
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour modifier votre profil.');
        }

        // Récupérer les valeurs envoyées
        $firstName = $request->request->get('first_name');
        $lastName = $request->request->get('last_name');
        $plainPassword = $request->request->get('password');
        $confirmPassword = $request->request->get('confirm_password');

        // Si les informations de prénom ou nom sont renseignées
        if ($firstName) {
            $user->setFirstName($firstName);
        }
        if ($lastName) {
            $user->setLastName($lastName);
        }

        // Vérification du mot de passe
        if (!empty($plainPassword)) {
            // Vérification de la correspondance des mots de passe
            if ($plainPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_profile');
            }

            // Validation du mot de passe (contraintes)
            $validator = Validation::createValidator();
            $constraints = new Assert\Sequentially([
                new Assert\Length([
                    'min' => 'password_min_length',
                    'minMessage' => 'Le mot de passe doit contenir au moins {{ password_min_length }} caractères.',
                ]),
                new Assert\Regex([
                    'pattern' => '/[A-Z]/',
                    'message' => 'Le mot de passe doit contenir au moins une lettre majuscule.',
                ]),
                new Assert\Regex([
                    'pattern' => '/[a-z]/',
                    'message' => 'Le mot de passe doit contenir au moins une lettre minuscule.',
                ]),
                new Assert\Regex([
                    'pattern' => '/[0-9]/',
                    'message' => 'Le mot de passe doit contenir au moins un chiffre.',
                ]),
                new Assert\Regex([
                    'pattern' => '/[^A-Za-z0-9]/',
                    'message' => 'Le mot de passe doit contenir au moins un caractère spécial.',
                ]),
            ]);

            /** @var ConstraintViolationListInterface $violations */
            $violations = $validator->validate($plainPassword, $constraints);

            // Si des violations sont détectées, ajouter des messages d'erreur
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }
                return $this->redirectToRoute('app_profile');
            }

            // Hashage du mot de passe si validé
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }

        // Sauvegarder les modifications dans la base de données
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
        return $this->redirectToRoute('app_profile');
    }
}