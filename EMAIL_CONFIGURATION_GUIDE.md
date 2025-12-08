# Guide pour Configurer l'Envoi d'Email Gmail

## 🔧 Configuration Actuelle

Votre `.env` contient :
```env
MAILER_DSN=smtp://amenimakdouli@gmail.com:urxrtxnqnqdcskfr@smtp.gmail.com:587?encryption=tls
```

## ❌ Problèmes Possibles

1. **Mot de passe d'application incorrect**
2. **Gmail bloque les connexions SMTP**
3. **Authentification à 2 facteurs non activée**
4. **Port ou encryption incorrects**

## ✅ Solution Étape par Étape

### Étape 1 : Vérifier le Mot de Passe d'Application

Vous avez mentionné : `urxr_txnx_nqdc_skfr` (avec underscores)
Dans le .env il y a : `urxrtxnqnqdcskfr` (sans underscores)

**Le mot de passe d'application Google est de 16 caractères SANS espaces ni underscores.**

### Étape 2 : Créer un Nouveau Mot de Passe d'Application

1. Allez sur : https://myaccount.google.com/apppasswords
2. Connectez-vous avec `amenimakdouli@gmail.com`
3. Créez un nouveau mot de passe d'application :
   - Nom : "MuseHub Symfony"
   - Copiez le mot de passe (16 caractères)

### Étape 3 : Mettre à Jour la Configuration

Créez ou modifiez le fichier `.env.local` avec le NOUVEAU mot de passe :

```env
MAILER_DSN=smtp://amenimakdouli@gmail.com:VOTRE_NOUVEAU_MOT_DE_PASSE@smtp.gmail.com:587?encryption=tls
```

### Étape 4 : Tester avec un Script Simple

Créez `test_email.php` :

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

$dsn = 'smtp://amenimakdouli@gmail.com:VOTRE_MOT_DE_PASSE@smtp.gmail.com:587?encryption=tls';

try {
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);
    
    $email = (new Email())
        ->from('amenimakdouli@gmail.com')
        ->to('amenimakdouli@gmail.com')
        ->subject('Test Email MuseHub')
        ->text('Ceci est un email de test.')
        ->html('<p>Ceci est un <b>email de test</b>.</p>');
    
    $mailer->send($email);
    echo "✅ Email envoyé avec succès!\n";
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
```

Exécutez :
```bash
php test_email.php
```

## 🔍 Alternatives si Gmail ne Fonctionne Pas

### Option 1 : Utiliser Mailtrap (Gratuit pour Dev)

1. Créez un compte sur https://mailtrap.io
2. Copiez les credentials SMTP
3. Mettez à jour `.env.local` :

```env
MAILER_DSN=smtp://USERNAME:PASSWORD@smtp.mailtrap.io:2525?encryption=tls
```

### Option 2 : Utiliser SendGrid (Gratuit 100 emails/jour)

1. Créez un compte sur https://sendgrid.com
2. Créez une API Key
3. Mettez à jour `.env.local` :

```env
MAILER_DSN=smtp://apikey:VOTRE_API_KEY@smtp.sendgrid.net:587?encryption=tls
```

### Option 3 : Utiliser Mailgun (Gratuit 5000 emails/mois)

```env
MAILER_DSN=smtp://USERNAME:PASSWORD@smtp.mailgun.org:587?encryption=tls
```

## 🎯 Configuration Recommandée pour Gmail

Si vous voulez absolument utiliser Gmail :

```env
MAILER_DSN=smtp://amenimakdouli@gmail.com:VOTRE_MOT_APP_16_CHARS@smtp.gmail.com:587?encryption=tls
```

**Important :**
- ✅ Authentification à 2 facteurs ACTIVÉE
- ✅ Mot de passe d'application (16 caractères)
- ✅ Port 587 avec TLS
- ❌ PAS d'espaces ni underscores dans le mot de passe

## 🚀 Après Configuration

1. Videz le cache :
```bash
php bin/console cache:clear
```

2. Testez sur la page :
```
http://localhost/MuseHub-my-work/public/forgot-password
```

3. Entrez votre email : `amenimakdouli@gmail.com`

4. Vérifiez votre boîte Gmail (et le dossier SPAM !)
