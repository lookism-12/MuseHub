<?php

namespace App\Service;

use App\Entity\Listing;
use App\Entity\Transaction;

class PaymentService
{
    private const PLATFORM_COMMISSION = 0.10; // 10% commission

    public function processPayment(Listing $listing, string $buyerUuid): Transaction
    {
        // Mock Stripe payment processing
        // In production, this would integrate with Stripe API
        
        $transaction = new Transaction();
        $transaction->setBuyerUuid($buyerUuid);
        $transaction->setListingUuid($listing->getUuid());
        $transaction->setAmount($listing->getPrice());
        $transaction->setStatus('paid');
        $transaction->setDate(new \DateTimeImmutable());

        // Simulate payment processing delay
        usleep(100000); // 0.1 second

        return $transaction;
    }

    public function calculateCommission(string $amount): string
    {
        $commission = bcmul($amount, (string)self::PLATFORM_COMMISSION, 2);
        return $commission;
    }

    public function calculateSellerAmount(string $amount): string
    {
        $commission = $this->calculateCommission($amount);
        return bcsub($amount, $commission, 2);
    }

    public function refundTransaction(Transaction $transaction): void
    {
        // Mock refund processing
        $transaction->setStatus('refunded');
    }
}

