<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Entity\Listing;
use App\Entity\Post;
use App\Entity\Participant;
use App\Repository\ArtworkRepository;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Repository\ListingRepository;
use App\Repository\ParticipantRepository;
use App\Service\ContentFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/web')]
class WebFormController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ArtworkRepository $artworkRepository,
        private CategoryRepository $categoryRepository,
        private ListingRepository $listingRepository,
        private EventRepository $eventRepository,
        private ParticipantRepository $participantRepository,
        private ContentFilter $contentFilter
    ) {
    }

    #[Route('/artworks/create', name: 'web_artworks_create', methods: ['POST'])]
    #[IsGranted('ROLE_ARTIST')]
    public function createArtwork(Request $request): Response
    {
        $user = $this->getUser();
        
        $title = $request->request->get('title');
        if (!$title) {
            $this->addFlash('error', 'Le titre est requis');
            return $this->redirectToRoute('artworks');
        }

        $artwork = new Artwork();
        $artwork->setTitle($title);
        $artwork->setDescription($request->request->get('description') ?: null);
        $artwork->setImageUrl($request->request->get('image_url') ?: null);
        $artwork->setPrice($request->request->get('price') ?: null);
        $artwork->setArtistUuid($user->getUuid());
        $artwork->setStatus($request->request->get('status') ?: 'visible');

        $categoryId = $request->request->get('category_id');
        if ($categoryId) {
            $category = $this->categoryRepository->find($categoryId);
            if ($category) {
                $artwork->setCategory($category);
            }
        }

        $this->em->persist($artwork);
        $this->em->flush();

        $this->addFlash('success', 'Œuvre créée avec succès !');
        return $this->redirectToRoute('artworks');
    }

    #[Route('/events/{id}/subscribe', name: 'web_events_subscribe', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function subscribeToEvent(int $id, Request $request): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            $this->addFlash('error', 'Événement introuvable.');
            return $this->redirectToRoute('events');
        }

        if (!$this->isCsrfTokenValid('subscribe_event_' . $id, (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('events');
        }

        $user = $this->getUser();
        $existing = $this->participantRepository->findExisting($event->getUuid(), $user->getUuid());
        if ($existing) {
            $this->addFlash('info', 'Vous êtes déjà inscrit à cet événement.');
            return $this->redirectToRoute('events');
        }

        $participant = new Participant();
        $participant->setEventUuid($event->getUuid());
        $participant->setParticipantUuid($user->getUuid());
        $participant->setStatus('confirmed');

        $this->em->persist($participant);
        $this->em->flush();

        $this->addFlash('success', 'Inscription confirmée !');
        return $this->redirectToRoute('events');
    }

    #[Route('/events/{id}/unsubscribe', name: 'web_events_unsubscribe', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unsubscribeFromEvent(int $id, Request $request): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            $this->addFlash('error', 'Événement introuvable.');
            return $this->redirectToRoute('events');
        }

        if (!$this->isCsrfTokenValid('unsubscribe_event_' . $id, (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('events');
        }

        $user = $this->getUser();
        $participant = $this->participantRepository->findExisting($event->getUuid(), $user->getUuid());

        if (!$participant) {
            $this->addFlash('info', 'Vous n’êtes pas inscrit à cet événement.');
            return $this->redirectToRoute('events');
        }

        $this->em->remove($participant);
        $this->em->flush();

        $this->addFlash('success', 'Votre inscription a été annulée.');
        return $this->redirectToRoute('events');
    }

    #[Route('/marketplace/listing/create', name: 'web_marketplace_listing_create', methods: ['POST'])]
    #[IsGranted('ROLE_ARTIST')]
    public function createListing(Request $request): Response
    {
        $artworkUuid = $request->request->get('artwork_uuid');
        $price = $request->request->get('price');

        if (!$artworkUuid || !$price) {
            $this->addFlash('error', 'L\'œuvre et le prix sont requis');
            return $this->redirectToRoute('marketplace');
        }

        // Parse artwork reference (format: artistUuid-id) – artistUuid already contains hyphens
        $separatorPos = strrpos($artworkUuid, '-');
        if ($separatorPos === false) {
            $this->addFlash('error', 'Référence d\'œuvre invalide');
            return $this->redirectToRoute('marketplace');
        }

        $artistUuid = substr($artworkUuid, 0, $separatorPos);
        $artworkId = (int)substr($artworkUuid, $separatorPos + 1);

        if (!$artistUuid || !$artworkId) {
            $this->addFlash('error', 'Référence d\'œuvre invalide');
            return $this->redirectToRoute('marketplace');
        }

        $artwork = $this->artworkRepository->find($artworkId);
        
        // Verify artwork exists and belongs to user
        if (!$artwork || $artwork->getArtistUuid() !== $artistUuid || $artwork->getArtistUuid() !== $this->getUser()->getUuid()) {
            $this->addFlash('error', 'Œuvre non trouvée ou non autorisée');
            return $this->redirectToRoute('marketplace');
        }

        // Create a UUID-like identifier for the listing
        $listingArtworkUuid = $artwork->getArtistUuid() . '-' . $artwork->getId();

        $listing = new Listing();
        $listing->setArtworkUuid($listingArtworkUuid);
        $listing->setPrice((float)$price);
        $listing->setStock((int)($request->request->get('stock') ?: 1));
        $listing->setStatus('available');

        $this->em->persist($listing);
        $this->em->flush();

        $this->addFlash('success', 'Annonce créée avec succès !');
        return $this->redirectToRoute('marketplace');
    }

    #[Route('/posts/create', name: 'web_posts_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createPost(Request $request): Response
    {
        $user = $this->getUser();
        $content = $request->request->get('content');

        if (!$content) {
            $this->addFlash('error', 'Le contenu est requis');
            return $this->redirectToRoute('community');
        }

        // Filter content
        $filterResult = $this->contentFilter->filterContent($content);
        if (!$filterResult['isValid']) {
            $this->addFlash('error', 'Le contenu n\'est pas valide: ' . implode(', ', $filterResult['issues']));
            return $this->redirectToRoute('community');
        }

        $post = new Post();
        $post->setAuthorUuid($user->getUuid());
        $post->setContent($filterResult['filteredContent']);
        $post->setImageUrl($request->request->get('image_url') ?: null);

        $this->em->persist($post);
        $this->em->flush();

        $this->addFlash('success', 'Post publié avec succès !');
        return $this->redirectToRoute('community');
    }
}

