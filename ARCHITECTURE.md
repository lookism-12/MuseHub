# Architecture MuseHub

## Structure des Modules

### Module 1: User & Authentification
**Fichiers:**
- `src/Entity/User.php` - Entité utilisateur avec UUID, rôles, bio, avatar
- `src/Controller/AuthApiController.php` - API: register, me
- `src/Controller/JWTAuthenticationController.php` - API: login (JWT)
- `src/Controller/PasswordResetController.php` - API: forgot-password, reset-password
- `src/Controller/UserDashboardController.php` - Dashboard admin: liste, activation/suspension
- `src/Service/RoleAssigner.php` - Attribution automatique des rôles
- `src/Repository/UserRepository.php` - Repository avec méthodes personnalisées

**Endpoints API:**
- `POST /api/auth/register` - Inscription
- `POST /api/auth/login` - Connexion (retourne JWT)
- `GET /api/auth/me` - Profil utilisateur
- `POST /api/auth/forgot-password` - Demande réinitialisation
- `POST /api/auth/reset-password` - Réinitialisation

**Dashboard:**
- `GET /admin/users` - Liste utilisateurs (filtre par rôle)
- `POST /admin/users/{id}/toggle` - Activer/suspendre compte

---

### Module 2: Œuvres (Artworks)
**Fichiers:**
- `src/Entity/Artwork.php` - Entité œuvre avec artist_uuid, price, status
- `src/Entity/Category.php` - Entité catégorie
- `src/Controller/ArtworkApiController.php` - API CRUD complète
- `src/Controller/ArtworkDashboardController.php` - Dashboard admin avec statistiques
- `src/Service/ImageResizer.php` - Génération de miniatures
- `src/Repository/ArtworkRepository.php` - Repository avec filtres
- `src/Repository/CategoryRepository.php` - Repository catégories

**Endpoints API:**
- `GET /api/artworks` - Liste (filtres: category, artist_uuid, max_price, status)
- `POST /api/artworks` - Créer (ROLE_ARTIST)
- `PUT /api/artworks/{id}` - Modifier (propriétaire ou admin)
- `DELETE /api/artworks/{id}` - Supprimer (propriétaire ou admin)

**Dashboard:**
- `GET /admin/artworks` - CRUD + statistiques par catégorie/artiste
- `GET /admin/artworks/statistics` - Statistiques détaillées

---

### Module 3: Événements artistiques
**Fichiers:**
- `src/Entity/Event.php` - Entité événement avec organiser_uuid, date_time, location
- `src/Entity/Participant.php` - Entité participant avec event_uuid, participant_uuid, status
- `src/Controller/EventApiController.php` - API: liste, création, inscription
- `src/Controller/EventDashboardController.php` - Dashboard admin: calendrier, participants
- `src/Service/EventNotifier.php` - Notifications email aux participants
- `src/Repository/EventRepository.php` - Repository avec méthodes findByDateRange, findUpcoming
- `src/Repository/ParticipantRepository.php` - Repository avec vérification doublons
- `src/Command/CleanupPastEventsCommand.php` - Commande cron pour nettoyer événements passés

**Endpoints API:**
- `GET /api/events` - Liste (filtres: date_from, date_to)
- `POST /api/events` - Créer (ROLE_ARTIST ou ROLE_ADMIN)
- `POST /api/events/{id}/join` - S'inscrire (vérification doublon)

**Dashboard:**
- `GET /admin/events` - Calendrier événements
- `GET /admin/events/{id}/participants` - Gestion participants

**Commande:**
- `php bin/console app:cleanup-past-events` - Désactive événements passés

---

### Module 4: Marketplace
**Fichiers:**
- `src/Entity/Listing.php` - Entité annonce avec artwork_uuid, price, stock, status
- `src/Entity/Transaction.php` - Entité transaction avec buyer_uuid, listing_uuid, amount, status
- `src/Controller/MarketplaceApiController.php` - API: liste, création annonce, achat, facture
- `src/Controller/MarketplaceDashboardController.php` - Dashboard admin: ventes, revenus
- `src/Service/PaymentService.php` - Simulation paiement (mock Stripe), calcul commission
- `src/Service/InvoiceGenerator.php` - Génération factures PDF (HTML pour l'instant)
- `src/Repository/ListingRepository.php` - Repository avec findAvailable
- `src/Repository/TransactionRepository.php` - Repository avec getTotalRevenue, findByDateRange

**Endpoints API:**
- `GET /api/marketplace` - Liste annonces disponibles
- `POST /api/marketplace/listing` - Créer annonce (ROLE_ARTIST)
- `POST /api/marketplace/buy/{id}` - Acheter œuvre
- `GET /api/marketplace/invoice/{uuid}` - Télécharger facture

**Dashboard:**
- `GET /admin/marketplace` - Liste ventes/revenus (filtres par période)

---

### Module 5: Communauté (Interactions sociales)
**Fichiers:**
- `src/Entity/Post.php` - Entité post avec author_uuid, content, image_url, likes_count
- `src/Entity/Comment.php` - Entité commentaire avec post, commenter_uuid, content
- `src/Controller/CommunityApiController.php` - API: liste posts, création, commentaires
- `src/Controller/CommunityDashboardController.php` - Dashboard admin: modération, statistiques
- `src/Service/ContentFilter.php` - Modération automatique (mots interdits, patterns suspects)
- `src/Repository/PostRepository.php` - Repository avec pagination
- `src/Repository/CommentRepository.php` - Repository commentaires

**Endpoints API:**
- `GET /api/posts` - Liste posts (pagination: page, limit)
- `POST /api/posts` - Publier post (filtrage contenu)
- `GET /api/posts/{id}/comments` - Liste commentaires
- `POST /api/posts/{id}/comments` - Commenter (filtrage contenu)

**Dashboard:**
- `GET /admin/community` - Liste posts, graphique posts/jour
- `POST /admin/community/posts/{id}/delete` - Supprimer post signalé

---

## Configuration

### Security (config/packages/security.yaml)
- Provider: `app_user_provider` (Entity User, property email)
- Firewall API: JWT stateless
- Firewall Main: Form login pour dashboard
- Access Control: Routes publiques et protégées

### JWT (config/packages/lexik_jwt_authentication.yaml)
- Secret/Public keys via variables d'environnement
- Token TTL: 3600 secondes
- User identity field: email

### Migrations
- `migrations/Version20250101000000.php` - Migration complète pour tous les modules

---

## Principes d'Architecture

1. **Modules indépendants**: Pas de jointures entre modules, seulement UUIDs
2. **UUIDs pour références**: Toutes les références inter-modules utilisent UUIDs
3. **Services métier**: Logique métier dans des services dédiés
4. **Séparation API/Dashboard**: Contrôleurs séparés pour API REST et dashboards admin
5. **Sécurité par rôles**: ROLE_USER, ROLE_ARTIST, ROLE_ADMIN
6. **Validation**: Filtrage contenu, vérification droits, validation données

---

## Variables d'Environnement Requises

```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/musehub"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here
MAILER_DSN=null://null
```

---

## Notes d'Implémentation

- **Paiements**: Simulés via PaymentService (mock Stripe)
- **Emails**: Configuration via MAILER_DSN (Symfony Mailer)
- **Images**: ImageResizer génère chemins miniatures (implémentation complète à faire)
- **Factures**: InvoiceGenerator génère HTML (conversion PDF à implémenter)
- **Modération**: ContentFilter avec liste mots interdits (à étendre)
- **Cron**: Commande cleanup-past-events à planifier

