<?php

namespace App\Controller;

use App\Service\HarvardArtMuseumsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HarvardArtMuseumsController extends AbstractController
{
    #[Route('/harvard-artworks', name: 'app_harvard_artworks')]
    public function index(HarvardArtMuseumsService $harvardArtMuseumsService): Response
    {
        $artworks = $harvardArtMuseumsService->getArtworks();

        return $this->render('harvard_art_museums/index.html.twig', [
            'artworks' => $artworks,
        ]);
    }
}
