<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DocController extends AbstractController
{
    /**
     * Renders the documentation index page.
     *
     * @return Response The rendered documentation index page
     */
    #[Route('/', name: 'app_doc', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('doc/index.html.twig');
    }
}
