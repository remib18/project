<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CourseController extends AbstractController
{
    #[Route('/course', name: 'app_course_courses')]
    public function index(): Response
    {
        return $this->render('course/index.html.twig', [
            'courses' => [
                [
                    'name' => 'WE4A',
                    'description' => 'Technologies et programmation WEB',
                    'image' => '/image/we4a.png',
                    'target' => '/course/we4a',
                    'isFavorite' => true
                ],
                [
                    'name' => 'SY43',
                    'description' => 'Android Development.',
                    'image' => '/image/sy43.png',
                    'target' => '/course/sy43',
                    'isFavorite' => false
                ],
                [
                    'name' => 'RS40',
                    'description' => 'Réseaux et Cybersécurité niveau 1.',
                    'image' => '/image/rs40.jpg',
                    'target' => '/course/rs40',
                    'isFavorite' => false
                ],
                [
                    'name' => 'IT41',
                    'description' => 'Classical and Quantum Algorithms.',
                    'image' => '/image/it41.jpg',
                    'target' => '/course/it41',
                    'isFavorite' => false
                ],
                [
                    'name' => 'LE03',
                    'description' => 'Anglais pratique et examen international.',
                    'image' => '/image/le03.jpg',
                    'target' => '/course/le03',
                    'isFavorite' => true
                ],
            ],
        ]);
    }

    #[Route('/course/{slug}', name: 'app_course_course')]
    public function course(string $slug): Response
    {
        return $this->render('course/course.html.twig', [
            'courseName' => 'Cours de '.$slug,
            'courseBase' => '/course/'.$slug,
            'activities' => [
                [
                    'title' => 'Devoir xxx',
                    'desc' => 'À rendre dans 3 jours',
                    'target' => '/course/'.$slug.'/activity/xxx',
                    'action' => 'Déposer'
                ],
                [
                    'title' => 'Cours xxx',
                    'desc' => 'Créé il y a 2 heures',
                    'target' => '/course/'.$slug.'/activity/xxx',
                    'action' => 'Voir'
                ],
                [
                    'title' => 'Cours xxx',
                    'desc' => 'Mis à jour hier',
                    'target' => '/course/'.$slug.'/activity/xxx',
                    'action' => 'Voir'
                ],
            ],
            'pinnedRessources' => [
                [
                    'title' => 'Cours xxx',
                    'userDesc' => 'Lire avant la prochaine séance',
                    'target' => '/course/'.$slug.'/resource/xxx',
                    'action' => 'Voir'
                ],
                [
                    'title' => 'Devoir xxx',
                    'userDesc' => 'À rendre pour la prochaine séance',
                    'target' => '/course/'.$slug.'/resource/xxx',
                    'action' => 'Déposer'
                ],
            ],
            'categories' => [
                [
                    'title' => 'Général',
                    'desc' => 'Informations générales sur l\'UE',
                    'resources' => [
                        [
                            'title' => 'Mode de fonctionnement',
                            'content' => 'Le cours est divisé en 3 parties : CM, TD et TP',
                            'type' => 'message',
                            'icon' => 'file-text',
                            'severity' => 'info',
                            'isPinned' => false,
                        ],
                        [
                            'title' => 'S\'il vous plaît',
                            'content' => 'Merci de ne pas envoyer de message à l\'enseignant en dehors des heures de cours',
                            'type' => 'message',
                            'icon' => 'file-text',
                            'severity' => 'warning',
                            'isPinned' => false,
                        ],
                    ],
                ],
                [
                    'title' => 'Cours',
                    'desc' => 'Ensemble des fichiers de cours',
                    'resources' => [
                        [
                            'title' => 'CM 1 — xxx',
                            'type' => 'document',
                            'icon' => 'file-text',
                            'isPinned' => false,
                            'target' => '/course/'.$slug.'/resource/xxx',
                        ],
                        [
                            'title' => 'CM 2 — xxx',
                            'userDesc' => 'Se référer au site caniuse.com pour les navigateurs supportés',
                            'type' => 'document',
                            'icon' => 'file-archive',
                            'isPinned' => false,
                            'target' => '/course/'.$slug.'/resource/xxx',
                        ],
                        [
                            'title' => 'CM 3 — xxx',
                            'type' => 'document',
                            'icon' => 'file-unknown',
                            'isPinned' => true,
                            'target' => '/course/'.$slug.'/resource/xxx',
                        ],
                    ],
                ],
                [
                    'title' => 'Devoirs',
                    'desc' => 'Ensemble des devoirs à rendre',
                    'resources' => [
                        [
                            'title' => 'Devoir 1 — xxx',
                            'userDesc' => 'Ne traiter que les exercices 1 et 2, nous en discuterons en TD',
                            'type' => 'document-submission',
                            'icon' => 'upload',
                            'isPinned' => true,
                            'target' => '/course/'.$slug.'/resource/xxx',
                        ],
                        [
                            'title' => 'Devoir 2 — xxx',
                            'type' => 'document-submission',
                            'icon' => 'upload',
                            'isPinned' => false,
                            'target' => '/course/'.$slug.'/resource/xxx',
                        ],
                    ],
                ],
            ],
        ]);
    }

    #[Route('/course/{slug}/members', name: 'app_course_members')]
    public function courseMembers(string $slug): Response
    {
        return $this->render('course/members.html.twig', [
            'courseName' => 'Cours de '.$slug,
            'courseBase' => '/course/'.$slug,
            'categories' => [
                [
                    'title' => 'Professeurs',
                    'desc' => 'Ensemble des professeurs du cours',
                    'members' => [
                        [
                            'firstname' => 'Jean',
                            'lastname' => 'Dupont',
                            'email' => 'jean@dupont.fr',
                        ],
                        [
                            'firstname' => 'John',
                            'lastname' => 'Doe',
                            'email' => 'john@doe.fr',
                        ]
                    ],
                ],
                [
                    'title' => 'Étudiants',
                    'desc' => 'Ensemble des étudiants du cours',
                    'members' => [
                        [
                            'firstname' => 'Jeannette',
                            'lastname' => 'Dupont',
                            'email' => 'jean@dupont.fr',
                        ],
                        [
                            'firstname' => 'Jane',
                            'lastname' => 'Doe',
                            'email' => 'john@doe.fr',
                        ]
                    ],
                ],
            ]
        ]);
    }
}
