<?php

namespace App\Controller;

use App\Repository\ListingRepository;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/marketplace')]
#[IsGranted('ROLE_ADMIN')]
class MarketplaceDashboardController extends AbstractController
{
    public function __construct(
        private ListingRepository $listingRepository,
        private TransactionRepository $transactionRepository
    ) {
    }

    #[Route('', name: 'admin_marketplace_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $period = $request->query->get('period', 'all');
        
        $start = null;
        $end = null;
        
        if ($period === 'week') {
            $start = new \DateTimeImmutable('-7 days');
            $end = new \DateTimeImmutable();
        } elseif ($period === 'month') {
            $start = new \DateTimeImmutable('-30 days');
            $end = new \DateTimeImmutable();
        }

        $transactions = $start && $end 
            ? $this->transactionRepository->findByDateRange($start, $end)
            : $this->transactionRepository->findAll();

        $revenue = $this->transactionRepository->getTotalRevenue($start, $end);
        $listings = $this->listingRepository->findAll();

        return $this->render('marketplace/admin_list.html.twig', [
            'transactions' => $transactions,
            'listings' => $listings,
            'revenue' => $revenue,
            'period' => $period,
        ]);
    }
}

