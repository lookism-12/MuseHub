# MuseHub - Plateforme Artistique

Plateforme de mise en relation entre artistes, galeries et amateurs d'art pour partager, vendre et organiser des événements collaboratifs.

## Architecture

Le projet est organisé en 5 modules indépendants :

1. **User & Authentification** - Gestion des utilisateurs et authentification JWT
2. **Œuvres (Artworks)** - Gestion des créations artistiques
3. **Événements artistiques** - Organisation d'événements
4. **Marketplace** - Vente d'œuvres d'art
5. **Communauté** - Posts et commentaires

## Installation

### Prérequis

- PHP 8.1+
- Composer
- Symfony CLI (optionnel)
- Base de données (MySQL/PostgreSQL)

### Étapes d'installation

1. **Installer les dépendances**
```bash
composer install
```

2. **Configurer les variables d'environnement**

Créez un fichier `.env.local` avec :
```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/musehub?serverVersion=8.0"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here
MAILER_DSN=null://null
```

3. **Générer les clés JWT**

```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

Ou utilisez la commande Symfony (si disponible) :
```bash
php bin/console lexik:jwt:generate-keypair --skip-if-exists
```

4. **Créer la base de données**

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. **Créer un utilisateur admin (optionnel)**

```bash
php bin/console doctrine:query:sql "INSERT INTO user (uuid, email, password, username, roles, is_active, created_at) VALUES (UUID(), 'admin@musehub.com', '$2y$13$...', 'admin', '[\"ROLE_ADMIN\"]', 1, NOW())"
```

## API Endpoints

### Authentification

- `POST /api/auth/register` - Inscription
  ```json
  {
    "email": "user@example.com",
    "password": "password123",
    "username": "username",
    "role": "ROLE_ARTIST" // optional: ROLE_USER, ROLE_ARTIST, ROLE_ADMIN
  }
  ```

- `POST /api/auth/login` - Connexion (retourne un JWT token)
  ```json
  {
    "email": "user@example.com",
    "password": "password123"
  }
  ```

- `GET /api/auth/me` - Profil de l'utilisateur connecté (nécessite JWT)

- `POST /api/auth/forgot-password` - Demande de réinitialisation
- `POST /api/auth/reset-password` - Réinitialisation du mot de passe

### Œuvres (Artworks)

- `GET /api/artworks` - Liste des œuvres (filtres: category, artist_uuid, max_price, status)
- `POST /api/artworks` - Créer une œuvre (ROLE_ARTIST requis)
- `PUT /api/artworks/{id}` - Modifier une œuvre (propriétaire ou admin)
- `DELETE /api/artworks/{id}` - Supprimer une œuvre (propriétaire ou admin)

### Événements

- `GET /api/events` - Liste des événements (filtres: date_from, date_to)
- `POST /api/events` - Créer un événement (ROLE_ARTIST ou ROLE_ADMIN)
- `POST /api/events/{id}/join` - S'inscrire à un événement

### Marketplace

- `GET /api/marketplace` - Liste des annonces disponibles
- `POST /api/marketplace/listing` - Créer une annonce (ROLE_ARTIST)
- `POST /api/marketplace/buy/{id}` - Acheter une œuvre
- `GET /api/marketplace/invoice/{uuid}` - Télécharger une facture

### Communauté

- `GET /api/posts` - Liste des posts (pagination: page, limit)
- `POST /api/posts` - Créer un post
- `GET /api/posts/{id}/comments` - Liste des commentaires d'un post
- `POST /api/posts/{id}/comments` - Commenter un post

## Dashboards Admin

Tous les dashboards nécessitent le rôle `ROLE_ADMIN` :

- `/admin/users` - Gestion des utilisateurs
- `/admin/artworks` - Gestion des œuvres et statistiques
- `/admin/events` - Gestion des événements et participants
- `/admin/marketplace` - Gestion des ventes et revenus
- `/admin/community` - Modération des posts et commentaires

## Commandes

- `php bin/console app:cleanup-past-events` - Désactive les événements passés (à planifier en cron)

## Rôles

- `ROLE_USER` - Utilisateur simple (suit, aime, commente, achète)
- `ROLE_ARTIST` - Artiste (publie des créations, crée des événements)
- `ROLE_ADMIN` - Administrateur (gère la plateforme)

## Services Métier

- **RoleAssigner** - Attribution automatique des rôles
- **ImageResizer** - Génération de miniatures
- **EventNotifier** - Notifications par email pour les événements
- **PaymentService** - Simulation de paiement (mock Stripe)
- **InvoiceGenerator** - Génération de factures PDF
- **ContentFilter** - Modération automatique du contenu

## Notes

- Les modules sont indépendants et utilisent des UUIDs pour les références (pas de jointures entre modules)
- L'authentification JWT est utilisée pour l'API
- Les paiements sont simulés (mock)
- Les emails nécessitent la configuration MAILER_DSN

## Développement

```bash
# Lancer le serveur de développement
symfony server:start

# Ou avec PHP built-in server
php -S localhost:8000 -t public
```

## Tests

```bash
php bin/phpunit
```

