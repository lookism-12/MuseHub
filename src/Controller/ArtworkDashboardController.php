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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/artworks')]
#[IsGranted('ROLE_ADMIN')]
class ArtworkDashboardController extends AbstractController
{
    public function __construct(
        private ArtworkRepository $artworkRepository,
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('', name: 'admin_artworks_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $categoryId = $request->query->get('category');
        $status = $request->query->get('status');

        $qb = $this->artworkRepository->createQueryBuilder('a');

        if ($categoryId) {
            $qb->andWhere('a.category = :category')
                ->setParameter('category', $categoryId);
        }

        if ($status) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        $artworks = $qb->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();

        $categories = $this->categoryRepository->findAll();
        $stats = $this->getStatistics();

        return $this->render('artwork/admin_index.html.twig', [
            'artworks' => $artworks,
            'categories' => $categories,
            'stats' => $stats,
            'categoryId' => $categoryId,
            'status' => $status,
        ]);
    }

    #[Route('/statistics', name: 'admin_artworks_stats', methods: ['GET'])]
    public function statistics(): Response
    {
        $stats = $this->getStatistics();
        
        return $this->render('artwork/admin_stats.html.twig', [
            'stats' => $stats,
        ]);
    }

    private function getStatistics(): array
    {
        $byCategory = [];
        $categories = $this->categoryRepository->findAll();
        
        foreach ($categories as $category) {
            $count = $this->artworkRepository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.category = :category')
                ->setParameter('category', $category)
                ->getQuery()
                ->getSingleScalarResult();
            
            $byCategory[$category->getName()] = $count;
        }

        return [
            'total' => $this->artworkRepository->count([]),
            'by_category' => $byCategory,
            'visible' => $this->artworkRepository->count(['status' => 'visible']),
            'hidden' => $this->artworkRepository->count(['status' => 'hidden']),
        ];
    }
}

