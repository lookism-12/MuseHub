<?php

namespace App\Controller;

use App\Repository\ArtworkRepository;
use App\Repository\EventRepository;
use App\Repository\ListingRepository;
use App\Repository\PostRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class BackOfficeController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private ArtworkRepository $artworkRepository,
        private EventRepository $eventRepository,
        private ListingRepository $listingRepository,
        private PostRepository $postRepository,
        private TransactionRepository $transactionRepository
    ) {
    }

    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        // Statistiques
        $stats = [
            'users' => $this->userRepository->count([]),
            'artists' => count($this->userRepository->createQueryBuilder('u')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ARTIST%')
                ->getQuery()
                ->getResult()),
            'artworks' => $this->artworkRepository->count([]),
            'events' => $this->eventRepository->count([]),
            'listings' => $this->listingRepository->count([]),
            'posts' => $this->postRepository->count([]),
            'revenue' => $this->transactionRepository->getTotalRevenue(),
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
        ]);
    }
}
