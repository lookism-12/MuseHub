<?php

namespace App\Controller;

use App\Entity\Listing;
use App\Entity\Transaction;
use App\Repository\ListingRepository;
use App\Repository\TransactionRepository;
use App\Service\PaymentService;
use App\Service\InvoiceGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/marketplace')]
class MarketplaceApiController extends AbstractController
{
    public function __construct(
        private ListingRepository $listingRepository,
        private TransactionRepository $transactionRepository,
        private EntityManagerInterface $em,
        private PaymentService $paymentService,
        private InvoiceGenerator $invoiceGenerator
    ) {
    }

    #[Route('', name: 'api_marketplace_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $listings = $this->listingRepository->findAvailable();

        $data = array_map(function (Listing $listing) {
            return [
                'id' => $listing->getId(),
                'uuid' => $listing->getUuid(),
                'artwork_uuid' => $listing->getArtworkUuid(),
                'price' => $listing->getPrice(),
                'stock' => $listing->getStock(),
                'status' => $listing->getStatus(),
            ];
        }, $listings);

        return new JsonResponse($data);
    }

    #[Route('/listing', name: 'api_marketplace_create_listing', methods: ['POST'])]
    #[IsGranted('ROLE_ARTIST')]
    public function createListing(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['artwork_uuid']) || !isset($data['price'])) {
            return new JsonResponse(['error' => 'artwork_uuid and price are required'], Response::HTTP_BAD_REQUEST);
        }

        $listing = new Listing();
        $listing->setArtworkUuid($data['artwork_uuid']);
        $listing->setPrice($data['price']);
        $listing->setStock($data['stock'] ?? 1);
        $listing->setStatus('available');

        $this->em->persist($listing);
        $this->em->flush();

        return new JsonResponse([
            'id' => $listing->getId(),
            'uuid' => $listing->getUuid(),
            'message' => 'Listing created successfully',
        ], Response::HTTP_CREATED);
    }

    #[Route('/buy/{id}', name: 'api_marketplace_buy', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function buy(int $id): JsonResponse
    {
        $listing = $this->listingRepository->find($id);
        if (!$listing) {
            return new JsonResponse(['error' => 'Listing not found'], Response::HTTP_NOT_FOUND);
        }

        if ($listing->getStatus() !== 'available' || $listing->getStock() <= 0) {
            return new JsonResponse(['error' => 'Listing not available'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        // Process payment
        $transaction = $this->paymentService->processPayment($listing, $user->getUuid());

        // Update stock
        $listing->setStock($listing->getStock() - 1);
        if ($listing->getStock() <= 0) {
            $listing->setStatus('sold_out');
        }

        $this->em->persist($transaction);
        $this->em->flush();

        return new JsonResponse([
            'transaction_uuid' => $transaction->getUuid(),
            'amount' => $transaction->getAmount(),
            'message' => 'Purchase successful',
        ], Response::HTTP_CREATED);
    }

    #[Route('/listing/{id}', name: 'api_marketplace_update_listing', methods: ['PUT'])]
    #[IsGranted('ROLE_ARTIST')]
    public function updateListing(int $id, Request $request): JsonResponse
    {
        $listing = $this->listingRepository->find($id);
        if (!$listing) {
            return new JsonResponse(['error' => 'Listing not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user owns the artwork (you might need to add this check based on artwork ownership)
        // For now, only allow if listing is available
        if ($listing->getStatus() === 'sold_out') {
            return new JsonResponse(['error' => 'Cannot update sold out listing'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['price'])) {
            $listing->setPrice($data['price']);
        }
        if (isset($data['stock'])) {
            $listing->setStock($data['stock']);
        }
        if (isset($data['status']) && in_array($data['status'], ['available', 'sold_out', 'paused'])) {
            $listing->setStatus($data['status']);
        }

        $this->em->flush();

        return new JsonResponse([
            'id' => $listing->getId(),
            'uuid' => $listing->getUuid(),
            'message' => 'Listing updated successfully',
        ]);
    }

    #[Route('/listing/{id}', name: 'api_marketplace_delete_listing', methods: ['DELETE'])]
    #[IsGranted('ROLE_ARTIST')]
    public function deleteListing(int $id): JsonResponse
    {
        $listing = $this->listingRepository->find($id);
        if (!$listing) {
            return new JsonResponse(['error' => 'Listing not found'], Response::HTTP_NOT_FOUND);
        }

        // Prevent deletion if there are transactions
        $transactions = $this->transactionRepository->findBy(['listingUuid' => $listing->getUuid()]);
        if (count($transactions) > 0) {
            // Instead of deleting, mark as paused
            $listing->setStatus('paused');
            $this->em->flush();
            return new JsonResponse([
                'message' => 'Listing paused (cannot delete listing with transactions)',
                'status' => 'paused',
            ]);
        }

        $this->em->remove($listing);
        $this->em->flush();

        return new JsonResponse(['message' => 'Listing deleted successfully']);
    }

    #[Route('/invoice/{uuid}', name: 'api_marketplace_invoice', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getInvoice(string $uuid): Response
    {
        $transaction = $this->transactionRepository->findOneBy(['uuid' => $uuid]);
        if (!$transaction) {
            return new JsonResponse(['error' => 'Transaction not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if ($transaction->getBuyerUuid() !== $user->getUuid() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Not authorized'], Response::HTTP_FORBIDDEN);
        }

        return $this->invoiceGenerator->downloadInvoice($transaction);
    }
}

