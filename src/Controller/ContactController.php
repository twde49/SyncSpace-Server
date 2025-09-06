<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contact')]
class ContactController extends AbstractController
{
    #[Route('/', name: 'app_contact', methods: ['POST'])]
    public function getContact(Request $request): Response
    {
        $data = $request->query->all();

        return new Response(json_encode($data), Response::HTTP_OK);
    }
}
