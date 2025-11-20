<?php

namespace App\Controller;

use App\Entity\Listing;
use App\Repository\ListingRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        private TransactionRepository $transactionRepository,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('', name: 'admin_marketplace_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $period = $request->query->get('period', 'all');
        $status = $request->query->get('status', 'all');
        
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
        $listingQb = $this->listingRepository->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC');

        if ($status !== 'all') {
            $listingQb->andWhere('l.status = :status')->setParameter('status', $status);
        }

        $listings = $listingQb->getQuery()->getResult();

        return $this->render('marketplace/admin_list.html.twig', [
            'transactions' => $transactions,
            'listings' => $listings,
            'revenue' => $revenue,
            'period' => $period,
            'status' => $status,
        ]);
    }

    #[Route('/listings/new', name: 'admin_marketplace_listing_new', methods: ['GET', 'POST'])]
    public function newListing(Request $request): Response
    {
        $listing = new Listing();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('create_listing_admin', (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('admin_marketplace_listing_new');
            }

            $artworkUuid = trim((string)$request->request->get('artwork_uuid'));
            $price = $request->request->get('price');

            if (!$artworkUuid || !$price) {
                $this->addFlash('error', 'UUID œuvre et prix sont requis.');
                return $this->redirectToRoute('admin_marketplace_listing_new');
            }

            $listing->setArtworkUuid($artworkUuid);
            $listing->setPrice(number_format((float)$price, 2, '.', ''));
            $listing->setStock((int)$request->request->get('stock', 1));
            $listing->setStatus($request->request->get('status', 'available'));

            $this->em->persist($listing);
            $this->em->flush();

            $this->addFlash('success', 'Annonce créée.');
            return $this->redirectToRoute('admin_marketplace_list');
        }

        return $this->render('marketplace/admin_listing_form.html.twig', [
            'listing' => $listing,
            'action' => 'new',
        ]);
    }

    #[Route('/listings/{id}/edit', name: 'admin_marketplace_listing_edit', methods: ['GET', 'POST'])]
    public function editListing(int $id, Request $request): Response
    {
        $listing = $this->listingRepository->find($id);
        if (!$listing) {
            $this->addFlash('error', 'Annonce introuvable.');
            return $this->redirectToRoute('admin_marketplace_list');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_listing_' . $id, (string)$request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('admin_marketplace_listing_edit', ['id' => $id]);
            }

            $price = $request->request->get('price');
            if (!$price) {
                $this->addFlash('error', 'Le prix est requis.');
                return $this->redirectToRoute('admin_marketplace_listing_edit', ['id' => $id]);
            }

            $artworkUuid = trim((string)$request->request->get('artwork_uuid'));
            if ($artworkUuid) {
                $listing->setArtworkUuid($artworkUuid);
            }
            $listing->setPrice(number_format((float)$price, 2, '.', ''));
            $listing->setStock((int)$request->request->get('stock', 1));
            $listing->setStatus($request->request->get('status', 'available'));

            $this->em->flush();
            $this->addFlash('success', 'Annonce mise à jour.');

            return $this->redirectToRoute('admin_marketplace_list');
        }

        return $this->render('marketplace/admin_listing_form.html.twig', [
            'listing' => $listing,
            'action' => 'edit',
        ]);
    }

    #[Route('/listings/{id}/delete', name: 'admin_marketplace_listing_delete', methods: ['POST'])]
    public function deleteListing(int $id, Request $request): Response
    {
        $listing = $this->listingRepository->find($id);
        if (!$listing) {
            $this->addFlash('error', 'Annonce introuvable.');
            return $this->redirectToRoute('admin_marketplace_list');
        }

        if (!$this->isCsrfTokenValid('delete_listing_' . $id, (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_marketplace_list');
        }

        $transactions = $this->transactionRepository->findBy(['listingUuid' => $listing->getUuid()]);
        if (count($transactions) > 0) {
            $listing->setStatus('paused');
            $this->em->flush();
            $this->addFlash('warning', 'Annonce mise en pause (au moins une transaction).');
            return $this->redirectToRoute('admin_marketplace_list');
        }

        $this->em->remove($listing);
        $this->em->flush();

        $this->addFlash('success', 'Annonce supprimée.');
        return $this->redirectToRoute('admin_marketplace_list');
    }
}

