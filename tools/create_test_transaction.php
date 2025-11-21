<?php

use App\Entity\Transaction;
use App\Entity\Listing;
use App\Service\InvoiceGenerator;

require __DIR__ . '/../vendor/autoload.php';

// Boot the Symfony kernel
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

$doctrine = $container->get('doctrine');
$em = $doctrine->getManager();

/** @var \App\Repository\ListingRepository $listingRepo */
$listingRepo = $em->getRepository(Listing::class);
$listing = $listingRepo->findOneBy(['status' => 'available']);

if (! $listing) {
    echo "No available listing found.\n";
    exit(1);
}

$transaction = new Transaction();
$transaction->setBuyerUuid('test_buyer_' . uniqid());
$transaction->setListingUuid($listing->getUuid());
$transaction->setAmount($listing->getPrice());
$transaction->setStatus('paid');
$transaction->setDate(new DateTimeImmutable());

$em->persist($transaction);
$em->flush();

echo "Created transaction with UUID: " . $transaction->getUuid() . "\n";

// Generate an invoice HTML via the InvoiceGenerator service
/** @var InvoiceGenerator $invoiceGenerator */
$invoiceGenerator = $container->get(InvoiceGenerator::class);
$response = $invoiceGenerator->downloadInvoice($transaction);

$html = $response->getContent();

$outDir = __DIR__ . '/../var/tmp';
if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$file = $outDir . '/invoice_' . $transaction->getUuid() . '.html';
file_put_contents($file, $html);

echo "Invoice written to: " . $file . "\n";

// Clean up kernel
$kernel->shutdown();

return 0;
