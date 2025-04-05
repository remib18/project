<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CourseController extends AbstractController
{
    #[Route('/course', name: 'app_course_courses')]
    public function index(string $slug): Response
    {
        return $this->render('course/index.html.twig');
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
                    'title' => 'Cours',
                    'desc' => 'Ensemble des fichiers de cours',
                    'resources' => [
                        [
                            'title' => 'CM 1 — xxx',
                            'type' => 'document',
                            'icon' => 'file-text',
                            'isPinned' => false,
                            'target' => '/course/'.$slug.'/resource/xxx',
                            'action' => 'Voir'
                        ],
                        [
                            'title' => 'CM 2 — xxx',
                            'userDesc' => 'Se référer au site caniuse.com pour les navigateurs supportés',
                            'type' => 'document',
                            'icon' => 'file-archive',
                            'isPinned' => false,
                            'target' => '/course/'.$slug.'/resource/xxx',
                            'action' => 'Voir'
                        ],
                        [
                            'title' => 'CM 3 — xxx',
                            'type' => 'document',
                            'icon' => 'file-unknown',
                            'isPinned' => true,
                            'target' => '/course/'.$slug.'/resource/xxx',
                            'action' => 'Voir'
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
                            'action' => 'Déposer'
                        ],
                        [
                            'title' => 'Devoir 2 — xxx',
                            'type' => 'document-submission',
                            'icon' => 'upload',
                            'isPinned' => false,
                            'target' => '/course/'.$slug.'/resource/xxx',
                            'action' => 'Déposer'
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
