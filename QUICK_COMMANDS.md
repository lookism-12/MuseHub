# Commandes Rapides - MuseHub

## 🌐 Accès Direct dans le Navigateur

### Pages Web
```
http://localhost:8000/                    → Accueil
http://localhost:8000/login               → Connexion
http://localhost:8000/artworks           → Œuvres
http://localhost:8000/events             → Événements
http://localhost:8000/marketplace        → Marketplace
http://localhost:8000/community         → Communauté
```

### Dashboards Admin (nécessite connexion)
```
http://localhost:8000/admin/             → Dashboard principal
http://localhost:8000/admin/users       → Gestion utilisateurs
http://localhost:8000/admin/artworks    → Gestion œuvres
http://localhost:8000/admin/events      → Gestion événements
http://localhost:8000/admin/marketplace  → Gestion ventes
http://localhost:8000/admin/community   → Modération communauté
```

---

## 📡 API Endpoints - Commandes cURL

### 🔐 Authentification

#### S'inscrire
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"user@test.com","password":"test123","username":"testuser","role":"ROLE_USER"}'
```

#### Se connecter
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@musehub.com","password":"admin123"}'
```

#### Profil utilisateur (remplacez YOUR_TOKEN)
```bash
curl http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 🎨 Artworks (Œuvres)

#### Liste des œuvres
```bash
curl http://localhost:8000/api/artworks
```

#### Liste avec filtres
```bash
curl "http://localhost:8000/api/artworks?category=1&max_price=1000"
```

#### Créer une œuvre (nécessite token Artist)
```bash
curl -X POST http://localhost:8000/api/artworks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"title":"Mon œuvre","description":"Description","price":"500.00","category_id":1}'
```

---

### 🎭 Events (Événements)

#### Liste des événements
```bash
curl http://localhost:8000/api/events
```

#### Liste avec filtres de date
```bash
curl "http://localhost:8000/api/events?date_from=2025-01-01&date_to=2025-12-31"
```

#### Créer un événement (nécessite token Artist/Admin)
```bash
curl -X POST http://localhost:8000/api/events \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"title":"Exposition","description":"Description","date_time":"2025-12-01 18:00:00","location":"online"}'
```

#### S'inscrire à un événement
```bash
curl -X POST http://localhost:8000/api/events/1/join \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 💰 Marketplace

#### Liste des annonces
```bash
curl http://localhost:8000/api/marketplace
```

#### Créer une annonce (nécessite token Artist)
```bash
curl -X POST http://localhost:8000/api/marketplace/listing \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"artwork_uuid":"UUID_DE_L_OEUVRE","price":"500.00","stock":1}'
```

#### Acheter une œuvre
```bash
curl -X POST http://localhost:8000/api/marketplace/buy/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 💬 Community (Communauté)

#### Liste des posts
```bash
curl http://localhost:8000/api/posts
```

#### Liste avec pagination
```bash
curl "http://localhost:8000/api/posts?page=1&limit=20"
```

#### Créer un post (nécessite token)
```bash
curl -X POST http://localhost:8000/api/posts \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"content":"Mon premier post sur la communauté"}'
```

#### Liste des commentaires d'un post
```bash
curl http://localhost:8000/api/posts/1/comments
```

#### Commenter un post
```bash
curl -X POST http://localhost:8000/api/posts/1/comments \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"content":"Mon commentaire"}'
```

---

## 🧪 Test Rapide - Script PowerShell

Créez un fichier `test_api.ps1` :

```powershell
# Test connexion
$response = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login" `
  -Method POST `
  -ContentType "application/json" `
  -Body '{"email":"admin@musehub.com","password":"admin123"}'

$token = $response.token
Write-Host "Token: $token"

# Test liste œuvres
Invoke-RestMethod -Uri "http://localhost:8000/api/artworks"

# Test liste événements
Invoke-RestMethod -Uri "http://localhost:8000/api/events"

# Test liste posts
Invoke-RestMethod -Uri "http://localhost:8000/api/posts"

# Test profil (avec token)
$headers = @{ "Authorization" = "Bearer $token" }
Invoke-RestMethod -Uri "http://localhost:8000/api/auth/me" -Headers $headers
```

---

## 🧪 Test Rapide - Script Bash

Créez un fichier `test_api.sh` :

```bash
#!/bin/bash

# Test connexion
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@musehub.com","password":"admin123"}' | jq -r '.token')

echo "Token: $TOKEN"

# Test liste œuvres
echo "=== Artworks ==="
curl -s http://localhost:8000/api/artworks | jq

# Test liste événements
echo "=== Events ==="
curl -s http://localhost:8000/api/events | jq

# Test liste posts
echo "=== Posts ==="
curl -s http://localhost:8000/api/posts | jq

# Test profil
echo "=== Profile ==="
curl -s http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer $TOKEN" | jq
```

---

## 📋 Checklist des Endpoints

### ✅ Endpoints Publics (pas besoin de token)
- [ ] `GET /api/artworks` - Liste œuvres
- [ ] `GET /api/events` - Liste événements
- [ ] `GET /api/marketplace` - Liste annonces
- [ ] `GET /api/posts` - Liste posts
- [ ] `GET /api/posts/{id}/comments` - Commentaires
- [ ] `POST /api/auth/register` - Inscription
- [ ] `POST /api/auth/login` - Connexion

### 🔒 Endpoints Protégés (nécessite token)
- [ ] `GET /api/auth/me` - Profil
- [ ] `POST /api/artworks` - Créer œuvre (Artist)
- [ ] `PUT /api/artworks/{id}` - Modifier (Owner/Admin)
- [ ] `DELETE /api/artworks/{id}` - Supprimer (Owner/Admin)
- [ ] `POST /api/events` - Créer événement (Artist/Admin)
- [ ] `POST /api/events/{id}/join` - S'inscrire (User)
- [ ] `POST /api/marketplace/listing` - Créer annonce (Artist)
- [ ] `POST /api/marketplace/buy/{id}` - Acheter (User)
- [ ] `POST /api/posts` - Créer post (User)
- [ ] `POST /api/posts/{id}/comments` - Commenter (User)

---

## 💡 Astuces

1. **Obtenir un token rapidement** :
   ```bash
   curl -X POST http://localhost:8000/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"admin@musehub.com","password":"admin123"}' | jq -r '.token'
   ```

2. **Sauvegarder le token dans une variable** :
   ```bash
   TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"admin@musehub.com","password":"admin123"}' | jq -r '.token')
   ```

3. **Utiliser le token dans les requêtes** :
   ```bash
   curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/auth/me
   ```

4. **Tester avec Postman/Insomnia** :
   - Importez les endpoints depuis `ROUTES_GUIDE.md`
   - Créez une collection avec les variables `{{base_url}}` et `{{token}}`

---

## 🎯 Exemples d'Usage Complets

### Scénario 1: Créer un artiste et publier une œuvre
```bash
# 1. S'inscrire comme artiste
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"artist@test.com","password":"artist123","username":"artist","role":"ROLE_ARTIST"}'

# 2. Se connecter
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"artist@test.com","password":"artist123"}' | jq -r '.token')

# 3. Créer une œuvre
curl -X POST http://localhost:8000/api/artworks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"title":"Ma première œuvre","description":"Description","price":"300.00"}'
```

### Scénario 2: Créer un événement et s'y inscrire
```bash
# 1. Créer un événement (avec token Artist)
curl -X POST http://localhost:8000/api/events \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"title":"Vernissage","date_time":"2025-12-15 19:00:00","location":"online"}'

# 2. S'inscrire (avec token User)
curl -X POST http://localhost:8000/api/events/1/join \
  -H "Authorization: Bearer $USER_TOKEN"
```

---

**Note:** Remplacez `YOUR_TOKEN` par le token JWT obtenu via `/api/auth/login`


