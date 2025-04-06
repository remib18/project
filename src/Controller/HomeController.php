<?php

// src/Controller/HomeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', parameters: [
            'nowCourses' => [
                ['name' => 'WE4A', 'description' => 'Technologies et programmation WEB.','room' => 'A101', 'start_time' => '8h00', 'end_time' => '10h00'],
            ],
            'upcomingCourses' => [
                ['name' => 'IT41', 'description' => 'Classical and Quantum Algorithms.','room' => 'A213', 'start_time' => '10h15', 'end_time' => '12h15'],
                ['name' => 'SY43', 'description' => 'Android development.','room' => 'A102', 'start_time' => '14h00', 'end_time' => '16h00'],
                ['name' => 'RS40', 'description' => 'Réseaux et Cybersécurité niveau 1.','room' => 'A206', 'start_time' => '16h15', 'end_time' => '18h15'],

            ],
            'recentActivities' => [
                ['type' => 'user', 'user' => 'John Doe', 'action' => 'a modifié une ressource de l’UE xxx'],
                ['type' => 'user', 'user' => 'John Doe', 'action' => 'a ajouté une nouvelle ressource dans l’UE xxx'],
                ['type' => 'user', 'user' => 'John Doe', 'action' => 'a modifié un devoir de l’UE xxx'],
                ['type' => 'user', 'user' => 'John Doe', 'action' => 'a publié une note à ton devoir xxx : 16/20'],
                ['type' => 'alert', 'message' => 'Devoir à rendre demain à xxhxx pour le cours xxx'],
            ],
        ]);
    }
}
