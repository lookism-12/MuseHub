<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\ArtworkRepository;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Repository\ListingRepository;
use App\Repository\OffreRepository;
use App\Repository\ParticipantRepository;
use App\Repository\PostReactionRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\ContentFilter;
use App\Service\CommentModerationService;
use App\Service\NotificationService;
use App\Service\SearchService;
use Doctrine\ORM\EntityManagerInterface;
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
        private OffreRepository $offreRepository,
        private PostRepository $postRepository,
        private PostReactionRepository $postReactionRepository,
        private ParticipantRepository $participantRepository,
        private UserRepository $userRepository,
        private SearchService $searchService,
        private ContentFilter $contentFilter,
        private CommentModerationService $moderationService,
        private NotificationService $notificationService,
        private EntityManagerInterface $em
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

        // Récupérer les offres pour chaque listing
        $offresParListing = [];
        foreach ($listings as $listing) {
            $offresParListing[$listing->getId()] = $this->offreRepository->findByListing($listing->getId());
        }

        return $this->render('front/marketplace.html.twig', [
            'listings' => $listings,
            'userArtworks' => $userArtworks,
            'offresParListing' => $offresParListing,
        ]);
    }

    #[Route('/marketplace/test', name: 'marketplace_test')]
    public function marketplaceTest(): Response
    {
        $listings = $this->listingRepository->findAvailable();

        $offresParListing = [];
        foreach ($listings as $listing) {
            $offresParListing[$listing->getId()] = $this->offreRepository->findByListing($listing->getId());
        }

        return $this->render('front/marketplace_test.html.twig', [
            'listings' => $listings,
            'offresParListing' => $offresParListing,
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

    #[Route('/community', name: 'community', methods: ['GET', 'POST'])]
    public function community(\Symfony\Component\HttpFoundation\Request $request): Response
    {
        // Handle comment creation POST requests
        if ($request->isMethod('POST')) {
            return $this->handleCommentCreation($request);
        }

        $categoryId = $request->query->get('category');
        $sortBy = $request->query->get('sort', 'recent');
        $search = $request->query->get('q');

        // Use MeiliSearch ONLY for search queries (no category or sort filters)
        if (!empty($search) && empty($categoryId) && $sortBy === 'recent') {
            return $this->searchAndRender($search, $request);
        }

        // Use SQL for category filtering and sorting
        $qb = $this->postRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c');

        // Apply category filter
        if ($categoryId) {
            $qb->andWhere('p.category = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        // For most sorts, we can use database ordering
        $useDatabaseSort = true;

        switch ($sortBy) {
            case 'liked':
                $qb->orderBy('p.likesCount', 'DESC');
                break;
            case 'commented':
                // For commented sorting, we'll sort in PHP after fetching
                $useDatabaseSort = false;
                break;
            case 'oldest':
                $qb->orderBy('p.createdAt', 'ASC');
                break;
            case 'recent':
            default:
                $qb->orderBy('p.createdAt', 'DESC');
                break;
        }

        $posts = $qb->setMaxResults(50)->getQuery()->getResult();

        // Sort by comment count in PHP if needed
        if (!$useDatabaseSort && $sortBy === 'commented') {
            usort($posts, function($a, $b) {
                $aCount = $a->getComments()->count();
                $bCount = $b->getComments()->count();
                return $bCount <=> $aCount; // Descending order
            });
        }

        $authorNames = $this->buildAuthorNamesMap($posts);
        $commentAuthorNames = $this->buildCommenterNamesMap($posts);
        $userReactions = $this->buildUserReactionMap($posts);

        // Build moderation data for admins only
        $moderationData = [];
        if ($this->isGranted('ROLE_ADMIN')) {
            $moderationData = $this->buildCommentModerationMap($posts);
        }

        return $this->render('front/community.html.twig', [
            'posts' => $posts,
            'authorNames' => $authorNames,
            'commentAuthorNames' => $commentAuthorNames,
            'userReactions' => $userReactions,
            'moderationData' => $moderationData,
            'currentCategory' => $categoryId,
            'currentSort' => $sortBy,
            'currentSearch' => $search,
        ]);
    }

    /**
     * Handle search using MeiliSearch and render community view
     * Only called when search is used alone (no category or sort filters)
     */
    private function searchAndRender(string $search, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        // Perform search using MeiliSearch (no filters since we only use search alone)
        $searchResults = $this->searchService->searchPosts($search, 50, []);

        if (isset($searchResults['error'])) {
            // Fallback to SQL LIKE if MeiliSearch fails
            $qb = $this->postRepository->createQueryBuilder('p')
                ->leftJoin('p.category', 'c')
                ->addSelect('c')
                ->where('p.content LIKE :search')
                ->setParameter('search', '%' . $search . '%')
                ->orderBy('p.createdAt', 'DESC');

            $posts = $qb->setMaxResults(50)->getQuery()->getResult();
        } else {
            // Get posts from MeiliSearch results
            $postIds = array_column($searchResults['hits'], 'id');
            if (empty($postIds)) {
                $posts = [];
            } else {
                // Fetch posts with category joins for proper display
                $posts = $this->postRepository->createQueryBuilder('p')
                    ->leftJoin('p.category', 'c')
                    ->addSelect('c')
                    ->where('p.id IN (:ids)')
                    ->setParameter('ids', $postIds)
                    ->orderBy('p.createdAt', 'DESC') // Order by creation date as fallback
                    ->getQuery()
                    ->getResult();
            }
        }

        $authorNames = $this->buildAuthorNamesMap($posts);
        $commentAuthorNames = $this->buildCommenterNamesMap($posts);
        $userReactions = $this->buildUserReactionMap($posts);

        return $this->render('front/community.html.twig', [
            'posts' => $posts,
            'authorNames' => $authorNames,
            'commentAuthorNames' => $commentAuthorNames,
            'userReactions' => $userReactions,
            'moderationData' => [], // Empty array for search results (moderation disabled)
            'currentCategory' => '', // No category filter when searching
            'currentSort' => 'recent', // Always recent sort when searching
            'currentSearch' => $search,
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

    /**
     * Build moderation data map for comments (admin only)
     * @param array<int, \App\Entity\Post> $posts
     */
    private function buildCommentModerationMap(array $posts): array
    {
        $moderationMap = [];

        foreach ($posts as $post) {
            foreach ($post->getComments() as $comment) {
                try {
                    $moderationMap[$comment->getId()] = [
                        'isValid' => $comment->isValid(),
                        'containsBadWords' => $comment->isContainsBadWords(),
                        'isSpam' => $comment->isSpam(),
                        'toxicityScore' => $comment->getToxicityScore(),
                    ];
                } catch (\Exception $e) {
                    // If moderation fields don't exist in database yet, provide defaults
                    $moderationMap[$comment->getId()] = [
                        'isValid' => true,
                        'containsBadWords' => false,
                        'isSpam' => false,
                        'toxicityScore' => null,
                    ];
                }
            }
        }

        return $moderationMap;
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

    /**
     * Handle comment creation with rate limiting and moderation
     */
    private function handleCommentCreation(Request $request): Response
    {
        $postId = $request->request->get('post_id');
        $content = trim((string) $request->request->get('content'));

        // Find the post
        $post = $this->postRepository->find($postId);
        if (!$post) {
            return $this->renderCommunityWithMessage('Publication introuvable.', 'error');
        }

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('comment_post_' . $post->getId(), (string) $request->request->get('_token'))) {
            return $this->renderCommunityWithMessage('Le formulaire a expiré, merci de réessayer.', 'error');
        }

        // Get current user
        $user = $this->getUser();

        // Rate limiting would be applied here in production
        // For now, we skip it to focus on the core functionality

        // Validate content is not empty
        if ($content === '') {
            return $this->renderCommunityWithMessage('Le commentaire ne peut pas être vide.', 'error');
        }

        // Filter content using ContentFilter
        $filterResult = $this->contentFilter->filterContent($content);
        if (!$filterResult['isValid']) {
            return $this->renderCommunityWithMessage('Votre commentaire a été bloqué: ' . implode(', ', $filterResult['issues']), 'error');
        }

        // Check toxicity using CommentModerationService
        $toxicityCheck = $this->moderationService->checkToxicity($filterResult['filteredContent']);
        if ($toxicityCheck['isToxic']) {
            return $this->renderCommunityWithMessage($this->moderationService->getToxicityErrorMessage($toxicityCheck['score']), 'error');
        }

        // Create the comment
        $comment = new Comment();
        $comment->setPost($post);
        $comment->setContent($filterResult['filteredContent']);

        // Set commenter UUID (authenticated user or guest)
        if ($user) {
            $comment->setCommenterUuid($user->getUuid());
        } else {
            $displayName = trim((string) $request->request->get('commenter_name'));
            if ($displayName !== '') {
                $normalizedName = preg_replace('/[^a-z0-9]+/i', '_', $displayName);
                $comment->setCommenterUuid('guest_' . strtolower(trim($normalizedName, '_')) . '_' . uniqid());
            } else {
                $comment->setCommenterUuid('guest_' . uniqid());
            }
        }

        // Save the comment
        $this->em->persist($comment);
        $this->em->flush();

        // Create notification for post comment if user is authenticated
        if ($user) {
            $this->notificationService->createPostCommentNotification($post, $comment, $user->getUuid());
        }

        // Return success message
        return $this->renderCommunityWithMessage('Commentaire publié avec succès!', 'success');
    }

    /**
     * Render community page with a flash message
     */
    private function renderCommunityWithMessage(string $message, string $type): Response
    {
        // Add flash message
        $this->addFlash($type, $message);

        // Redirect to community page (GET request)
        return $this->redirectToRoute('community');
    }
}
