<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CourseGroupRepository;
use App\Repository\RecentActivityRepository;
use App\Service\HomePageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for the home page
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class HomeController extends AbstractController
{
    public function __construct(
        private readonly HomePageService $homePageService
    ) {
    }

    /**
     * Main home page of the application
     */
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        /** @var User $user - We know the user is always logged on this page */
        $user = $this->getUser();
        $homeData = $this->homePageService->getHomePageData($user);

        return $this->render('home/index.html.twig', [
            'nowCourses' => $homeData->nowCourses,
            'upcomingCourses' => $homeData->upcomingCourses,
            'recentActivities' => $homeData->recentActivities,
        ]);
    }
}