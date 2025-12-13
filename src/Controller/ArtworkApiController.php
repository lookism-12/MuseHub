<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Entity\Category;
use App\Repository\ArtworkRepository;
use App\Repository\CategoryRepository;
use App\Service\ImageResizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/artworks')]
class ArtworkApiController extends AbstractController
{
    public function __construct(
        private ArtworkRepository $artworkRepository,
        private CategoryRepository $categoryRepository,
        private \App\Repository\ArtworkLikeRepository $artworkLikeRepository,
        private EntityManagerInterface $em,
        private ImageResizer $imageResizer
    ) {
    }

    #[Route('/{id}/like', name: 'api_artworks_like', methods: ['POST'])]
    public function like(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Login required'], Response::HTTP_UNAUTHORIZED);
        }

        $artwork = $this->artworkRepository->find($id);
        if (!$artwork) {
            return new JsonResponse(['error' => 'Artwork not found'], Response::HTTP_NOT_FOUND);
        }

        $existingLike = $this->artworkLikeRepository->findOneByUserAndArtwork($user->getUuid(), $id);

        if ($existingLike) {
            // Unlike
            $this->em->remove($existingLike);
            $artwork->decrementLikesCount();
            $message = 'Unliked';
            $liked = false;
        } else {
            // Like
            $like = new \App\Entity\ArtworkLike();
            $like->setUser($user);
            $like->setArtwork($artwork);
            $this->em->persist($like);
            $artwork->incrementLikesCount();
            $message = 'Liked';
            $liked = true;
        }

        $this->em->flush();

        return new JsonResponse([
            'message' => $message,
            'likes_count' => $artwork->getLikesCount(),
            'liked' => $liked
        ]);
    }

    #[Route('', name: 'api_artworks_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $categoryId = $request->query->get('category');
        $artistUuid = $request->query->get('artist_uuid');
        $maxPrice = $request->query->get('max_price');
        $status = $request->query->get('status', 'visible');

        $qb = $this->artworkRepository->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', $status);

        if ($categoryId) {
            $qb->andWhere('a.category = :category')
                ->setParameter('category', $categoryId);
        }

        if ($artistUuid) {
            $qb->andWhere('a.artistUuid = :artistUuid')
                ->setParameter('artistUuid', $artistUuid);
        }

        if ($maxPrice) {
            $qb->andWhere('a.price <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        $artworks = $qb->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(function (Artwork $artwork) {
            return [
                'id' => $artwork->getId(),
                'title' => $artwork->getTitle(),
                'description' => $artwork->getDescription(),
                'image_url' => $artwork->getImageUrl(),
                'price' => $artwork->getPrice(),
                'artist_uuid' => $artwork->getArtistUuid(),
                'status' => $artwork->getStatus(),
                'category' => $artwork->getCategory() ? [
                    'id' => $artwork->getCategory()->getId(),
                    'name' => $artwork->getCategory()->getName(),
                ] : null,
            ];
        }, $artworks);

        return new JsonResponse($data);
    }

    #[Route('', name: 'api_artworks_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Check if user has ROLE_ARTIST or ROLE_ADMIN
        if (!$user || (!in_array('ROLE_ARTIST', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))) {
            return new JsonResponse(['error' => 'You must be an artist to create artworks'], Response::HTTP_FORBIDDEN);
        }
        
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title'])) {
            return new JsonResponse(['error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
        }

        $artwork = new Artwork();
        $artwork->setTitle($data['title']);
        $artwork->setDescription($data['description'] ?? null);
        $artwork->setImageUrl($data['image_url'] ?? null);
        $artwork->setPrice(isset($data['price']) ? (float)$data['price'] : null);
        $artwork->setArtistUuid($user->getUuid());
        $artwork->setStatus($data['status'] ?? 'visible');

        if (isset($data['category_id']) && $data['category_id'] !== '' && $data['category_id'] !== null) {
            $category = $this->categoryRepository->find((int)$data['category_id']);
            if ($category) {
                $artwork->setCategory($category);
            } else {
                $artwork->setCategory(null);
            }
        } else {
            $artwork->setCategory(null);
        }

        // Generate thumbnail if image exists
        if ($artwork->getImageUrl()) {
            $thumbnailPath = $this->imageResizer->generateThumbnailPath($artwork->getImageUrl());
            // In production, actually resize and save the image
        }

        $this->em->persist($artwork);
        $this->em->flush();

        return new JsonResponse([
            'id' => $artwork->getId(),
            'title' => $artwork->getTitle(),
            'message' => 'Artwork created successfully',
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_artworks_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $artwork = $this->artworkRepository->find($id);
        if (!$artwork) {
            return new JsonResponse(['error' => 'Artwork not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        
        // Check ownership or admin role
        if ($artwork->getArtistUuid() !== $user->getUuid() && !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Not authorized'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $artwork->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $artwork->setDescription($data['description']);
        }
        if (isset($data['image_url'])) {
            $artwork->setImageUrl($data['image_url']);
        }
        if (isset($data['price'])) {
            $artwork->setPrice((float)$data['price']);
        }
        if (isset($data['status'])) {
            $artwork->setStatus($data['status']);
        }
        if (isset($data['category_id'])) {
            if ($data['category_id'] !== '' && $data['category_id'] !== null) {
                $category = $this->categoryRepository->find((int)$data['category_id']);
                $artwork->setCategory($category);
            } else {
                $artwork->setCategory(null);
            }
        }

        $this->em->flush();

        return new JsonResponse(['message' => 'Artwork updated successfully']);
    }

    #[Route('/{id}', name: 'api_artworks_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $artwork = $this->artworkRepository->find($id);
        if (!$artwork) {
            return new JsonResponse(['error' => 'Artwork not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        
        if ($artwork->getArtistUuid() !== $user->getUuid() && !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'Not authorized'], Response::HTTP_FORBIDDEN);
        }

        $this->em->remove($artwork);
        $this->em->flush();

        return new JsonResponse(['message' => 'Artwork deleted successfully']);
    }
}
