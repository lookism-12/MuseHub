<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Entity\Category;
use App\Repository\ArtworkRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/artworks')]
class ArtworkAdminController extends AbstractController
{
    #[Route('', name: 'admin_artwork_index', methods: ['GET'])]
    public function index(ArtworkRepository $artworkRepository, CategoryRepository $categoryRepository): Response
    {
        $artworks = $artworkRepository->findBy([], ['id' => 'DESC']);
        $categories = $categoryRepository->findAll();
        
        // Calculer les statistiques
        $stats = [
            'total' => $artworkRepository->count([]),
            'visible' => $artworkRepository->count(['status' => 'visible']),
            'hidden' => $artworkRepository->count(['status' => 'hidden']),
        ];

        return $this->render('artwork/admin_index.html.twig', [
            'artworks' => $artworks,
            'categories' => $categories,
            'stats' => $stats,
            'categoryId' => null,
            'status' => null,
        ]);
    }

    #[Route('/new', name: 'admin_artwork_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepository): Response
    {
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $artistUuid = $request->request->get('artist_uuid');

            if ($title && $artistUuid) {
                $artwork = new Artwork();
                $artwork->setTitle($title);
                $artwork->setArtistUuid($artistUuid);
                $artwork->setDescription($request->request->get('description') ?: null);
                $artwork->setImageUrl($request->request->get('image_url') ?: null);
                $artwork->setPrice($request->request->get('price') ?: null);
                $artwork->setStatus($request->request->get('status') ?: 'visible');

                $categoryId = $request->request->getInt('category_id') ?: null;
                if ($categoryId) {
                    $category = $categoryRepository->find($categoryId);
                    if ($category) {
                        $artwork->setCategory($category);
                    }
                }

                $em->persist($artwork);
                $em->flush();

                return $this->redirectToRoute('admin_artwork_index');
            }
        }

        $categories = $categoryRepository->findAll();

        return $this->render('artwork/admin_form.html.twig', [
            'categories' => $categories,
            'artwork' => null,
            'action' => 'new',
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_artwork_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, ArtworkRepository $artworkRepository, EntityManagerInterface $em, CategoryRepository $categoryRepository): Response
    {
        $artwork = $artworkRepository->find($id);
        if (!$artwork) {
            throw $this->createNotFoundException('Artwork not found');
        }

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $artistUuid = $request->request->get('artist_uuid');

            if ($title && $artistUuid) {
                $artwork->setTitle($title);
                $artwork->setArtistUuid($artistUuid);
                $artwork->setDescription($request->request->get('description') ?: null);
                $artwork->setImageUrl($request->request->get('image_url') ?: null);
                $artwork->setPrice($request->request->get('price') ?: null);
                $artwork->setStatus($request->request->get('status') ?: 'visible');

                $categoryId = $request->request->getInt('category_id') ?: null;
                if ($categoryId) {
                    $category = $categoryRepository->find($categoryId);
                    if ($category) {
                        $artwork->setCategory($category);
                    }
                } else {
                    $artwork->setCategory(null);
                }

                $em->flush();

                return $this->redirectToRoute('admin_artwork_index');
            }
        }

        $categories = $categoryRepository->findAll();

        return $this->render('artwork/admin_form.html.twig', [
            'categories' => $categories,
            'artwork' => $artwork,
            'action' => 'edit',
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_artwork_delete', methods: ['POST'])]
    public function delete(int $id, ArtworkRepository $artworkRepository, EntityManagerInterface $em, Request $request): Response
    {
        $artwork = $artworkRepository->find($id);
        if (!$artwork) {
            throw $this->createNotFoundException('Artwork not found');
        }

        if ($this->isCsrfTokenValid('delete_artwork_' . $id, $request->request->get('_token'))) {
            $em->remove($artwork);
            $em->flush();
        }

        return $this->redirectToRoute('admin_artwork_index');
    }

    #[Route('/categories', name: 'admin_category_index', methods: ['GET'])]
    public function categories(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('artwork/admin_categories.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/categories/new', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function categoryNew(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            if ($name) {
                $category = new Category();
                $category->setName($name);
                $category->setDescription($request->request->get('description') ?: null);

                $em->persist($category);
                $em->flush();

                return $this->redirectToRoute('admin_category_index');
            }
        }

        return $this->render('artwork/admin_category_form.html.twig', [
            'category' => null,
            'action' => 'new',
        ]);
    }

    #[Route('/categories/{id}/edit', name: 'admin_category_edit', methods: ['GET', 'POST'])]
    public function categoryEdit(int $id, Request $request, CategoryRepository $categoryRepository, EntityManagerInterface $em): Response
    {
        $category = $categoryRepository->find($id);
        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            if ($name) {
                $category->setName($name);
                $category->setDescription($request->request->get('description') ?: null);

                $em->flush();

                return $this->redirectToRoute('admin_category_index');
            }
        }

        return $this->render('artwork/admin_category_form.html.twig', [
            'category' => $category,
            'action' => 'edit',
        ]);
    }

    #[Route('/categories/{id}/delete', name: 'admin_category_delete', methods: ['POST'])]
    public function categoryDelete(int $id, CategoryRepository $categoryRepository, EntityManagerInterface $em, Request $request): Response
    {
        $category = $categoryRepository->find($id);
        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        if ($this->isCsrfTokenValid('delete_category_' . $id, $request->request->get('_token'))) {
            // Check if category is used by any artworks
            $artworkRepository = $em->getRepository(\App\Entity\Artwork::class);
            $artworksWithCategory = $artworkRepository->findBy(['category' => $category]);
            
            if (count($artworksWithCategory) > 0) {
                $this->addFlash('error', 'Cannot delete category: it is used by ' . count($artworksWithCategory) . ' artwork(s)');
            } else {
                $em->remove($category);
                $em->flush();
                $this->addFlash('success', 'Category deleted successfully');
            }
        }

        return $this->redirectToRoute('admin_category_index');
    }
}
