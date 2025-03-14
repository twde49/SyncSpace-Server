<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/settings')]
class UserSettingsController extends AbstractController
{
    
    #[Route('/enable-disable-notifications', name: 'enable_disable_notifications', methods: ['POST'])]
    public function enableDisableNotifications(Request $request, EntityManagerInterface $entityManager): Response
    {
        $requestData = $request->toArray();
        
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $currentUser->getUserSettings()->setNotificationsEnabled($requestData['notifications_enabled']);
        
        
        $entityManager->persist($currentUser);
        $entityManager->flush();
        
        return $this->json(['message' => 'Notifications setting updated successfully']);
    }
    
    #[Route('/update-theme-preference', name: 'update_theme_preference', methods: ['POST'])]
    public function updateThemePreference(Request $request, EntityManagerInterface $entityManager): Response
    {
        $requestData = $request->toArray();
        
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $currentUser->getUserSettings()->setTheme($requestData['theme']);
        
        $entityManager->persist($currentUser);
        $entityManager->flush();
        
        return $this->json(['message' => 'Theme preference updated successfully']);
    }
}
