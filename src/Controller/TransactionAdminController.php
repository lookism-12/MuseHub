<?php

namespace App\Controller;

use App\Repository\TransactionRepository;
use App\Service\InvoiceGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/transactions')]
#[IsGranted('ROLE_ADMIN')]
class TransactionAdminController extends AbstractController
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private InvoiceGenerator $invoiceGenerator
    ) {
    }

    #[Route('', name: 'admin_transactions_list', methods: ['GET'])]
    public function index(): Response
    {
        $transactions = $this->transactionRepository->createQueryBuilder('t')
            ->orderBy('t.date', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        return $this->render('admin/transactions.html.twig', [
            'transactions' => $transactions,
        ]);
    }

    #[Route('/invoice/{uuid}', name: 'admin_transactions_invoice', methods: ['GET'])]
    public function invoice(string $uuid): Response
    {
        $transaction = $this->transactionRepository->findOneBy(['uuid' => $uuid]);
        if (!$transaction) {
            throw $this->createNotFoundException('Transaction not found');
        }

        return $this->invoiceGenerator->downloadInvoice($transaction);
    }
}
