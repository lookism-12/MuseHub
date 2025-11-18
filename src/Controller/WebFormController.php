<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Entity\Listing;
use App\Entity\Post;
use App\Repository\ArtworkRepository;
use App\Repository\CategoryRepository;
use App\Repository\ListingRepository;
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

        // Parse artwork reference (format: artistUuid-id)
        $parts = explode('-', $artworkUuid);
        if (count($parts) !== 2) {
            $this->addFlash('error', 'Référence d\'œuvre invalide');
            return $this->redirectToRoute('marketplace');
        }

        $artworkId = (int)$parts[1];
        $artwork = $this->artworkRepository->find($artworkId);
        
        // Verify artwork exists and belongs to user
        if (!$artwork || $artwork->getArtistUuid() !== $this->getUser()->getUuid()) {
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

