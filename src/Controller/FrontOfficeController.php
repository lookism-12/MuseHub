<?php

namespace App\Controller;

use App\Repository\ArtworkRepository;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Repository\ListingRepository;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontOfficeController extends AbstractController
{
    public function __construct(
        private ArtworkRepository $artworkRepository,
        private CategoryRepository $categoryRepository,
        private EventRepository $eventRepository,
        private ListingRepository $listingRepository,
        private PostRepository $postRepository
    ) {
    }

    #[Route('/', name: 'home')]
    public function index(): Response
    {
        // Récupérer les dernières œuvres, événements et posts
        $latestArtworks = $this->artworkRepository->findBy(
            ['status' => 'visible'],
            ['id' => 'DESC'],
            6
        );
        
        $upcomingEvents = $this->eventRepository->findUpcoming();
        $latestPosts = $this->postRepository->findBy([], ['createdAt' => 'DESC'], 3);

        return $this->render('front/home.html.twig', [
            'artworks' => $latestArtworks,
            'events' => array_slice($upcomingEvents, 0, 3),
            'posts' => $latestPosts,
        ]);
    }

    #[Route('/artworks', name: 'artworks')]
    public function artworks(): Response
    {
        $artworks = $this->artworkRepository->findBy(
            ['status' => 'visible'],
            ['id' => 'DESC']
        );
        $categories = $this->categoryRepository->findAll();

        return $this->render('front/artworks.html.twig', [
            'artworks' => $artworks,
            'categories' => $categories,
        ]);
    }

    #[Route('/artists', name: 'artists')]
    public function artists(): Response
    {
        return $this->render('front/artists.html.twig');
    }

    #[Route('/events', name: 'events')]
    public function events(): Response
    {
        $events = $this->eventRepository->findUpcoming();

        return $this->render('front/events.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/marketplace', name: 'marketplace')]
    public function marketplace(): Response
    {
        $listings = $this->listingRepository->findAvailable();
        // Get artworks that the user owns (if logged in as artist) for creating listings
        $userArtworks = [];
        $user = $this->getUser();
        if ($user && (in_array('ROLE_ARTIST', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles()))) {
            $userArtworks = $this->artworkRepository->findBy(
                ['artistUuid' => $user->getUuid()],
                ['id' => 'DESC']
            );
        }

        return $this->render('front/marketplace.html.twig', [
            'listings' => $listings,
            'userArtworks' => $userArtworks,
        ]);
    }

    #[Route('/community', name: 'community')]
    public function community(): Response
    {
        $posts = $this->postRepository->findBy([], ['createdAt' => 'DESC'], 20);

        return $this->render('front/community.html.twig', [
            'posts' => $posts,
        ]);
    }
}
