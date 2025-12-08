<?php
// Test simple d'envoi d'email
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

echo "=== Test d'Envoi d'Email ===\n\n";

// Lire la configuration depuis .env
$envFile = __DIR__ . '/.env';
$envContent = file_get_contents($envFile);
preg_match('/MAILER_DSN=(.+)/', $envContent, $matches);
$dsn = trim($matches[1] ?? '');

echo "DSN trouvé : $dsn\n\n";

if (empty($dsn)) {
    die("❌ MAILER_DSN non trouvé dans .env\n");
}

try {
    echo "1️⃣ Création du transport...\n";
    $transport = Transport::fromDsn($dsn);
    
    echo "2️⃣ Création du mailer...\n";
    $mailer = new Mailer($transport);
    
    echo "3️⃣ Création de l'email...\n";
    $email = (new Email())
        ->from('amenimakdouli@gmail.com')
        ->to('amenimakdouli@gmail.com')
        ->subject('Test Email MuseHub - ' . date('H:i:s'))
        ->text('Ceci est un email de test envoyé à ' . date('H:i:s'))
        ->html('<p>Ceci est un <b>email de test</b> envoyé à <strong>' . date('H:i:s') . '</strong></p>');
    
    echo "4️⃣ Envoi de l'email...\n";
    $mailer->send($email);
    
    echo "\n✅ Email envoyé avec succès!\n";
    echo "📧 Vérifiez votre boîte Gmail : amenimakdouli@gmail.com\n";
    echo "⚠️  N'oubliez pas de vérifier le dossier SPAM !\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERREUR lors de l'envoi :\n";
    echo "Message : " . $e->getMessage() . "\n";
    echo "\n";
    echo "Causes possibles :\n";
    echo "1. Mot de passe d'application incorrect\n";
    echo "2. Authentification à 2 facteurs non activée sur Gmail\n";
    echo "3. Gmail bloque la connexion SMTP\n";
    echo "4. Problème de connexion internet\n";
    echo "\n";
    echo "Solution :\n";
    echo "1. Allez sur : https://myaccount.google.com/apppasswords\n";
    echo "2. Créez un nouveau mot de passe d'application\n";
    echo "3. Mettez à jour MAILER_DSN dans .env avec le nouveau mot de passe\n";
}
