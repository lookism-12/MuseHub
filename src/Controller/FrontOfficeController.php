<?php

namespace App\Controller;

use App\Repository\ArtworkRepository;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Repository\ListingRepository;
use App\Repository\ParticipantRepository;
use App\Repository\PostReactionRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontOfficeController extends AbstractController
{
    public function __construct(
        private ArtworkRepository $artworkRepository,
        private CategoryRepository $categoryRepository,
        private EventRepository $eventRepository,
        private ListingRepository $listingRepository,
        private PostRepository $postRepository,
        private PostReactionRepository $postReactionRepository,
        private ParticipantRepository $participantRepository,
        private UserRepository $userRepository
    ) {
    }

    #[Route('/', name: 'home')]
    public function index(): Response
    {
        // Sélection d'œuvres pour la page d'accueil
        $visibleArtworks = $this->artworkRepository->findBy(
            ['status' => 'visible'],
            ['id' => 'DESC'],
            12
        );

        $featuredArtworks = array_slice($visibleArtworks, 0, min(6, count($visibleArtworks)));
        $latestArtworks = count($visibleArtworks) > 6
            ? array_slice($visibleArtworks, 6)
            : $visibleArtworks;

        $artistNames = $this->buildArtistNamesMap($visibleArtworks);
        
        $upcomingEvents = $this->eventRepository->findUpcoming();
        $latestPosts = $this->postRepository->findBy([], ['createdAt' => 'DESC'], 3);

        // Calculate statistics
        $totalArtworks = $this->artworkRepository->count(['status' => 'visible']);
        $totalArtists = count($this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ARTIST%')
            ->getQuery()
            ->getResult());
        $totalEvents = $this->eventRepository->count([]);
        $totalUsers = $this->userRepository->count([]);

        return $this->render('front/home.html.twig', [
            'featuredArtworks' => $featuredArtworks,
            'latestArtworks' => $latestArtworks,
            'artistNames' => $artistNames,
            'events' => array_slice($upcomingEvents, 0, 3),
            'posts' => $latestPosts,
            'stats' => [
                'artworks' => $totalArtworks,
                'artists' => $totalArtists,
                'events' => $totalEvents,
                'users' => $totalUsers,
            ],
        ]);
    }

    /**
     * Construit une table de correspondance UUID artiste => nom public.
     *
     * @param array<int, \App\Entity\Artwork> $artworks
     */
    private function buildArtistNamesMap(array $artworks): array
    {
        $artistUuids = array_values(array_unique(array_filter(array_map(
            static fn($artwork) => $artwork->getArtistUuid(),
            $artworks
        ))));

        return $this->fetchUserNamesByUuid($artistUuids, 'Artiste MuseHub');
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

    #[Route('/artworks/{id}', name: 'artwork_show', requirements: ['id' => '\d+'])]
    public function artworkShow(int $id): Response
    {
        $artwork = $this->artworkRepository->find($id);
        if (!$artwork || $artwork->getStatus() !== 'visible') {
            throw $this->createNotFoundException('Œuvre introuvable');
        }

        return $this->render('front/artwork_show.html.twig', [
            'artwork' => $artwork,
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
        $participationMap = [];
        $user = $this->getUser();

        if ($user) {
            $participations = $this->participantRepository->findByParticipantUuid($user->getUuid());
            foreach ($participations as $participant) {
                $participationMap[$participant->getEventUuid()] = true;
            }
        }

        return $this->render('front/events.html.twig', [
            'events' => $events,
            'participationMap' => $participationMap,
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

    #[Route('/marketplace/offers/{id}', name: 'marketplace_offer_show', requirements: ['id' => '\d+'])]
    public function marketplaceOffer(int $id): Response
    {
        $listing = $this->listingRepository->find($id);
        if (!$listing) {
            throw $this->createNotFoundException('Annonce introuvable');
        }

        return $this->render('front/marketplace_offer.html.twig', [
            'listing' => $listing,
        ]);
    }

    #[Route('/community', name: 'community')]
    public function community(Request $request): Response
    {
        $categoryId = $request->query->get('category');
        $sortBy = $request->query->get('sort', 'recent');

        $qb = $this->postRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c');

        // Apply category filter
        if ($categoryId) {
            $qb->andWhere('p.category = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        // Apply sorting
        switch ($sortBy) {
            case 'liked':
                $qb->orderBy('p.likesCount', 'DESC');
                break;
            case 'commented':
                $qb->addSelect('COUNT(cm.id) as commentCount')
                   ->leftJoin('p.comments', 'cm')
                   ->groupBy('p.id')
                   ->orderBy('commentCount', 'DESC');
                break;
            case 'recent':
            default:
                $qb->orderBy('p.createdAt', 'DESC');
                break;
        }

        $posts = $qb->setMaxResults(20)->getQuery()->getResult();
        $authorNames = $this->buildAuthorNamesMap($posts);
        $commentAuthorNames = $this->buildCommenterNamesMap($posts);
        $userReactions = $this->buildUserReactionMap($posts);

        return $this->render('front/community.html.twig', [
            'posts' => $posts,
            'authorNames' => $authorNames,
            'commentAuthorNames' => $commentAuthorNames,
            'userReactions' => $userReactions,
            'currentCategory' => $categoryId,
            'currentSort' => $sortBy,
        ]);
    }

    /**
     * Retourne les noms publics des auteurs de posts.
     *
     * @param array<int, \App\Entity\Post> $posts
     */
    private function buildAuthorNamesMap(array $posts): array
    {
        $authorUuids = array_values(array_unique(array_filter(array_map(
            static fn($post) => $post->getAuthorUuid(),
            $posts
        ))));

        return $this->fetchUserNamesByUuid($authorUuids);
    }

    /**
     * @param array<int, \App\Entity\Post> $posts
     */
    private function buildCommenterNamesMap(array $posts): array
    {
        $commentUuids = [];
        foreach ($posts as $post) {
            foreach ($post->getComments() as $comment) {
                $uuid = $comment->getCommenterUuid();
                if ($uuid && !str_starts_with($uuid, 'guest_')) {
                    $commentUuids[] = $uuid;
                }
            }
        }

        return $this->fetchUserNamesByUuid(array_values(array_unique($commentUuids)));
    }

    /**
     * @param array<int, \App\Entity\Post> $posts
     */
    private function buildUserReactionMap(array $posts): array
    {
        $user = $this->getUser();
        if (!$user) {
            return [];
        }

        $postIds = array_values(array_filter(array_map(
            static fn($post) => $post->getId(),
            $posts
        )));

        if (empty($postIds)) {
            return [];
        }

        $reactions = $this->postReactionRepository->findByUserAndPostIds($user->getUuid(), $postIds);

        $map = [];
        foreach ($reactions as $reaction) {
            $map[$reaction->getPost()->getId()] = $reaction->getType();
        }

        return $map;
    }

    private function fetchUserNamesByUuid(array $uuids, string $fallback = 'Membre MuseHub'): array
    {
        if (empty($uuids)) {
            return [];
        }

        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.uuid IN (:uuids)')
            ->setParameter('uuids', $uuids)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($users as $user) {
            $displayName = $user->getUsername() ?: $user->getEmail() ?: $fallback;
            $map[$user->getUuid()] = $displayName;
        }

        return $map;
    }
}
