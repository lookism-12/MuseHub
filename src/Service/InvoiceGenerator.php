<?php

namespace App\Service;

use App\Entity\Transaction;
use Symfony\Component\HttpFoundation\Response;

class InvoiceGenerator
{
    public function generateInvoice(Transaction $transaction): string
    {
        // Generate a simple PDF invoice as HTML (in production, use a PDF library like TCPDF or Dompdf)
        $html = $this->generateInvoiceHtml($transaction);
        
        // In production, convert HTML to PDF using a library
        // For now, return HTML that can be converted
        return $html;
    }

    public function generateInvoiceHtml(Transaction $transaction): string
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Facture #{$transaction->getUuid()}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .invoice-details { margin: 20px 0; }
        .total { font-size: 18px; font-weight: bold; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MuseHub - Facture</h1>
        <p>Numéro de facture: {$transaction->getUuid()}</p>
        <p>Date: {$transaction->getDate()->format('d/m/Y H:i')}</p>
    </div>
    
    <div class="invoice-details">
        <h2>Détails de la transaction</h2>
        <table>
            <tr>
                <th>Description</th>
                <th>Montant</th>
            </tr>
            <tr>
                <td>Achat d'œuvre d'art</td>
                <td>{$transaction->getAmount()} €</td>
            </tr>
        </table>
        <div class="total">
            <p>Total: {$transaction->getAmount()} €</p>
            <p>Statut: {$transaction->getStatus()}</p>
        </div>
    </div>
    
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
        <p>Merci pour votre achat sur MuseHub!</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    public function downloadInvoice(Transaction $transaction): Response
    {
        $html = $this->generateInvoice($transaction);
        
        // In production, convert to PDF and return as download
        // For now, return HTML response
        $response = new Response($html);
        $response->headers->set('Content-Type', 'text/html');
        $response->headers->set('Content-Disposition', 'inline; filename="invoice_' . $transaction->getUuid() . '.html"');
        
        return $response;
    }
}

