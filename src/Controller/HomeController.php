<?php

// src/Controller/HomeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', parameters: [
            'nowCourses' => [
                [
                    'slug' => 'we4a',
                    'name' => 'WE4A',
                    'description' => 'Technologies et programmation WEB.',
                    "image" => '/image/we4a.png',
                    'room' => 'A101',
                    'start_time' => '8h00',
                    'end_time' => '10h00'
                ],
            ],
            'upcomingCourses' => [
                [
                    'slug' => 'it41',
                    'name' => 'IT41',
                    'description' => 'Classical and Quantum Algorithms.',
                    'image' => '/image/it41.png',
                    'room' => 'A213', 'start_time' => '10h15',
                    'end_time' => '12h15'
                ],
                [
                    'slug' => 'sy43',
                    'name' => 'SY43',
                    'description' => 'Android development.','room' => 'A102',
                    'image' => '/image/sy43.png',
                    'start_time' => '14h00',
                    'end_time' => '16h00'
                ],
                [
                    'slug' => 'rs40',
                    'name' => 'RS40',
                    'description' => 'Réseaux et Cybersécurité niveau 1.',
                    'room' => 'A206',
                    'image' => '/image/rs40.png',
                    'start_time' => '16h15',
                    'end_time' => '18h15'
                ],

            ],
            'recentActivities' => [
                [
                    'type' => 'user',
                    'user' => 'John Doe',
                    'action' => 'a modifié une ressource de l’UE IT41',
                    'target' => '/course/it41',
                ],
                [
                    'type' => 'user',
                    'user' => 'John Doe',
                    'action' => 'a ajouté une nouvelle ressource dans l’UE SY43',
                    'target' => '/course/sy43',
                ],
                [
                    'type' => 'user',
                    'user' => 'John Doe',
                    'action' => 'a modifié un devoir de l’UE WE4A',
                    'target' => '/course/we4a',
                ],
                [
                    'type' => 'user',
                    'user' => 'John Doe',
                    'action' => 'a publié une note à ton devoir WE4A - Rendu CSS/HTML/JS : 20/20',
                    'target' => '/course/we4a',
                ],
                [
                    'type' => 'alert',
                    'message' => 'Devoir à rendre demain à 23h59 pour le cours WE4A',
                    'target' => '/course/we4a',
                ],
            ],
        ]);
    }
}
