<?php

namespace App\Controller;

use App\Service\HarvardArtService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HarvardArtController extends AbstractController
{
    #[Route('/harvard-collection', name: 'harvard_collection')]
    public function index(Request $request, HarvardArtService $harvardArtService): Response
    {
        $page = $request->query->getInt('page', 1);
        $data = $harvardArtService->getArtworks($page);

        return $this->render('front/harvard_collection.html.twig', [
            'artworks' => $data['records'] ?? [],
            'info' => $data['info'] ?? [],
            'page' => $page,
        ]);
    }
}
