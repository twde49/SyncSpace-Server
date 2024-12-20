<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DocController extends AbstractController
{
    #[Route('/', name: 'app_doc',methods: ['GET'])]
    public function index(): Response
    {
        return $this->render("doc/index.html.twig");
    }
}
