# Guide des Routes - MuseHub

## 🌐 Base URL
- **Local:** `http://localhost:8000`
- **XAMPP:** `http://localhost/MuseHub/public`

---

## 📱 Routes Web (Front Office)

### Pages Publiques
```
GET  /                    → Page d'accueil
GET  /artworks           → Liste des œuvres
GET  /events             → Liste des événements
GET  /marketplace        → Marketplace
GET  /community          → Communauté
GET  /login              → Page de connexion
POST /logout             → Déconnexion
```

### Exemples d'accès:
- `http://localhost:8000/`
- `http://localhost:8000/artworks`
- `http://localhost:8000/events`
- `http://localhost:8000/marketplace`
- `http://localhost:8000/community`
- `http://localhost:8000/login`

---

## 🔐 Authentification API

### Inscription
```bash
POST /api/auth/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "username": "username",
  "role": "ROLE_ARTIST"  // optionnel: ROLE_USER ou ROLE_ARTIST
}
```

### Connexion
```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@musehub.com",
  "password": "admin123"
}
```

### Profil utilisateur (nécessite JWT)
```bash
GET /api/auth/me
Authorization: Bearer YOUR_JWT_TOKEN
```

### Réinitialisation mot de passe
```bash
POST /api/auth/forgot-password
Content-Type: application/json

{
  "email": "user@example.com"
}

POST /api/auth/reset-password
Content-Type: application/json

{
  "token": "reset_token",
  "email": "user@example.com",
  "password": "new_password"
}
```

---

## 🎨 Module Artworks (Œuvres)

### Liste des œuvres (PUBLIC)
```bash
GET /api/artworks
GET /api/artworks?category=1
GET /api/artworks?artist_uuid=USER_UUID
GET /api/artworks?max_price=1000
GET /api/artworks?status=visible
```

### Créer une œuvre (ROLE_ARTIST requis)
```bash
POST /api/artworks
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
  "title": "Mon œuvre",
  "description": "Description de l'œuvre",
  "image_url": "https://example.com/image.jpg",
  "price": "500.00",
  "category_id": 1,
  "status": "visible"
}
```

### Modifier une œuvre (Propriétaire ou Admin)
```bash
PUT /api/artworks/{id}
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
  "title": "Titre modifié",
  "price": "600.00"
}
```

### Supprimer une œuvre (Propriétaire ou Admin)
```bash
DELETE /api/artworks/{id}
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## 🎭 Module Events (Événements)

### Liste des événements (PUBLIC)
```bash
GET /api/events
GET /api/events?date_from=2025-01-01&date_to=2025-12-31
```

### Créer un événement (ROLE_ARTIST ou ROLE_ADMIN)
```bash
POST /api/events
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
  "title": "Exposition d'art",
  "description": "Description de l'événement",
  "date_time": "2025-12-01 18:00:00",
  "location": "online"  // ou "offline"
}
```

### S'inscrire à un événement (ROLE_USER)
```bash
POST /api/events/{id}/join
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## 💰 Module Marketplace

### Liste des annonces (PUBLIC)
```bash
GET /api/marketplace
```

### Créer une annonce (ROLE_ARTIST)
```bash
POST /api/marketplace/listing
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
  "artwork_uuid": "UUID_DE_L_OEUVRE",
  "price": "500.00",
  "stock": 1
}
```

### Acheter une œuvre (ROLE_USER)
```bash
POST /api/marketplace/buy/{id}
Authorization: Bearer YOUR_JWT_TOKEN
```

### Télécharger une facture (Propriétaire ou Admin)
```bash
GET /api/marketplace/invoice/{uuid}
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## 💬 Module Community (Communauté)

### Liste des posts (PUBLIC)
```bash
GET /api/posts
GET /api/posts?page=1&limit=20
```

### Créer un post (ROLE_USER)
```bash
POST /api/posts
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
  "content": "Mon post sur la communauté",
  "image_url": "https://example.com/image.jpg"  // optionnel
}
```

### Liste des commentaires d'un post (PUBLIC)
```bash
GET /api/posts/{id}/comments
```

### Commenter un post (ROLE_USER)
```bash
POST /api/posts/{id}/comments
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
  "content": "Mon commentaire"
}
```

---

## 👨‍💼 Dashboards Admin (ROLE_ADMIN requis)

### Dashboard principal
```
GET /admin/              → Dashboard admin
```

### Gestion utilisateurs
```
GET  /admin/users                    → Liste des utilisateurs
GET  /admin/users?role=ROLE_ARTIST   → Filtrer par rôle
POST /admin/users/{id}/toggle        → Activer/suspendre un compte
```

### Gestion œuvres
```
GET /admin/artworks                  → Liste des œuvres
GET /admin/artworks?category=1       → Filtrer par catégorie
GET /admin/artworks?status=visible   → Filtrer par statut
GET /admin/artworks/statistics       → Statistiques
```

### Gestion événements
```
GET /admin/events                    → Liste des événements
GET /admin/events/{id}/participants  → Participants d'un événement
```

### Gestion marketplace
```
GET /admin/marketplace               → Liste des ventes
GET /admin/marketplace?period=week   → Filtrer par période (week/month)
GET /admin/marketplace?period=month  → Filtrer par période
```

### Gestion communauté
```
GET  /admin/community                → Liste des posts
POST /admin/community/posts/{id}/delete  → Supprimer un post
```

---

## 🧪 Exemples avec cURL

### Test connexion
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@musehub.com","password":"admin123"}'
```

### Liste des œuvres
```bash
curl http://localhost:8000/api/artworks
```

### Créer un post (avec token)
```bash
curl -X POST http://localhost:8000/api/posts \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"content":"Mon premier post"}'
```

### Liste des événements
```bash
curl http://localhost:8000/api/events
```

---

## 📊 Résumé des Endpoints

| Module | Endpoint | Méthode | Auth | Description |
|--------|----------|---------|------|-------------|
| **Auth** | `/api/auth/register` | POST | Public | Inscription |
| **Auth** | `/api/auth/login` | POST | Public | Connexion |
| **Auth** | `/api/auth/me` | GET | User | Profil |
| **Artworks** | `/api/artworks` | GET | Public | Liste |
| **Artworks** | `/api/artworks` | POST | Artist | Créer |
| **Artworks** | `/api/artworks/{id}` | PUT | Owner/Admin | Modifier |
| **Artworks** | `/api/artworks/{id}` | DELETE | Owner/Admin | Supprimer |
| **Events** | `/api/events` | GET | Public | Liste |
| **Events** | `/api/events` | POST | Artist/Admin | Créer |
| **Events** | `/api/events/{id}/join` | POST | User | S'inscrire |
| **Marketplace** | `/api/marketplace` | GET | Public | Liste |
| **Marketplace** | `/api/marketplace/listing` | POST | Artist | Créer annonce |
| **Marketplace** | `/api/marketplace/buy/{id}` | POST | User | Acheter |
| **Community** | `/api/posts` | GET | Public | Liste posts |
| **Community** | `/api/posts` | POST | User | Créer post |
| **Community** | `/api/posts/{id}/comments` | GET | Public | Liste commentaires |
| **Community** | `/api/posts/{id}/comments` | POST | User | Commenter |

---

## 🔑 Rôles Requis

- **PUBLIC** : Aucune authentification
- **ROLE_USER** : Utilisateur connecté
- **ROLE_ARTIST** : Artiste (peut créer œuvres/événements)
- **ROLE_ADMIN** : Administrateur (accès complet)

---

## 💡 Astuces

1. **Pour obtenir un token JWT** : Utilisez `/api/auth/login`
2. **Pour tester sans auth** : Utilisez les endpoints GET publics
3. **Pour accéder aux dashboards** : Connectez-vous d'abord via `/login`
4. **Filtres disponibles** : Voir les exemples ci-dessus pour chaque module


