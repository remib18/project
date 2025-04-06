<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        // Generate CSRF tokens for the admin area
        $csrfTokens = [
            'delete_user' => $csrfTokenManager->getToken('delete_user')->getValue(),
            'update_user' => $csrfTokenManager->getToken('update_user')->getValue(),
            'create_user' => $csrfTokenManager->getToken('create_user')->getValue(),
        ];

        return $this->render('admin/index.html.twig', [
            'tokens' => $csrfTokens,
        ]);
    }
}
