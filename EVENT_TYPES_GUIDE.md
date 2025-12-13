# 🎭 EventType Entity - Documentation

## 📋 Vue d'ensemble

L'entité **EventType** permet de catégoriser les événements avec des caractéristiques riches et créatives. Chaque type d'événement possède des propriétés uniques qui définissent son comportement et son apparence.

---

## 🎨 Types d'événements disponibles

| Type | Icône | Couleur | Payant | Certificat | Capacité | Localisation |
|------|-------|---------|--------|------------|----------|--------------|
| **Exhibition** | 🎨 | #FF6B6B | Non | Non | Illimitée | Both |
| **Workshop** | 🛠️ | #4ECDC4 | Oui | Oui | 20 max | Both |
| **Conference** | 🎤 | #95E1D3 | Oui | Oui | 100 max | Both |
| **Auction** | 🔨 | #F38181 | Non | Non | 50 max (Invité) | Both |
| **Gallery Opening** | 🖼️ | #AA96DA | Non | Non | Illimitée | Offline |
| **Artist Talk** | 💬 | #FCBAD3 | Non | Non | 30 max | Both |
| **Virtual Tour** | 🌐 | #A8D8EA | Non | Non | Illimitée | Online |
| **Masterclass** | 🎓 | #FFE66D | Oui | Oui | 15 max | Both (Membres) |

---

## 🏗️ Structure de l'entité

### Propriétés principales

```php
- id (int) - Identifiant unique
- name (string) - Nom du type d'événement
- description (string|null) - Description détaillée
- icon (string|null) - Emoji ou classe FontAwesome
- color (string|null) - Code couleur hexadécimal (#RRGGBB)
```

### Propriétés de capacité

```php
- capacity_type (string) - Type de capacité
  * 'unlimited' - Capacité illimitée
  * 'limited' - Capacité limitée
  * 'invite_only' - Sur invitation uniquement
  
- default_max_participants (int|null) - Nombre max de participants
- typical_duration_hours (int|null) - Durée typique en heures
```

### Propriétés fonctionnelles

```php
- requires_payment (bool) - Événement payant
- certificate_enabled (bool) - Délivrance de certificat
- recording_enabled (bool) - Enregistrement possible
- allowed_location (string) - Localisation autorisée
  * 'online' - En ligne uniquement
  * 'offline' - En présentiel uniquement
  * 'both' - Les deux modes
```

### Propriétés de gestion

```php
- visibility (string) - Visibilité de l'événement
  * 'public' - Accessible à tous
  * 'private' - Privé
  * 'members_only' - Réservé aux membres
  
- is_active (bool) - Type actif ou non
- sort_order (int) - Ordre d'affichage
```

---

## 🔗 Relation avec Event

```php
Event::eventType (ManyToOne)
  ↓
EventType::events (OneToMany)
```

Un **Event** appartient à un **EventType** (optionnel).  
Un **EventType** peut avoir plusieurs **Events**.

---

## 🚀 API Endpoints

### 📋 Lister tous les types d'événements

**GET** `/api/event-types`

**Filtres disponibles:**
- `?active=true` - Types actifs uniquement
- `?capacity_type=limited` - Filtrer par type de capacité
- `?paid=true` - Types payants uniquement

**Exemple:**
```bash
curl http://localhost/MuseHub-my-work/MuseHub-my-work/public/api/event-types
```

**Réponse:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Exhibition",
      "description": "Art exhibition and gallery showcase",
      "icon": "🎨",
      "color": "#FF6B6B",
      "capacity_type": "unlimited",
      "default_max_participants": null,
      "typical_duration_hours": 8,
      "requires_payment": false,
      "certificate_enabled": false,
      "recording_enabled": true,
      "allowed_location": "both",
      "visibility": "public",
      "is_active": true,
      "events_count": 0
    }
  ],
  "count": 8
}
```

---

### 🔍 Voir un type d'événement

**GET** `/api/event-types/{id}`

**Exemple:**
```bash
curl http://localhost/MuseHub-my-work/MuseHub-my-work/public/api/event-types/1
```

---

### ➕ Créer un type d'événement (ADMIN)

**POST** `/api/event-types`  
**Auth:** Bearer Token (ROLE_ADMIN requis)

**Body:**
```json
{
  "name": "Art Fair",
  "description": "Large-scale art fair and exhibition",
  "icon": "🎪",
  "color": "#FF9999",
  "capacity_type": "unlimited",
  "typical_duration_hours": 24,
  "requires_payment": true,
  "certificate_enabled": false,
  "recording_enabled": true,
  "allowed_location": "offline",
  "visibility": "public",
  "sort_order": 9
}
```

**Exemple:**
```bash
curl -X POST http://localhost/MuseHub-my-work/MuseHub-my-work/public/api/event-types \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"name":"Art Fair","icon":"🎪","color":"#FF9999"}'
```

---

### ✏️ Modifier un type d'événement (ADMIN)

**PUT/PATCH** `/api/event-types/{id}`  
**Auth:** Bearer Token (ROLE_ADMIN requis)

**Body:**
```json
{
  "color": "#00FF00",
  "default_max_participants": 50
}
```

---

### 🗑️ Désactiver un type d'événement (ADMIN)

**DELETE** `/api/event-types/{id}`  
**Auth:** Bearer Token (ROLE_ADMIN requis)

*Note: Utilise une suppression douce (soft delete) - désactive le type*

---

### 📊 Statistiques des types d'événements

**GET** `/api/event-types/stats/summary`

**Réponse:**
```json
{
  "success": true,
  "data": {
    "total": 8,
    "active": 8,
    "paid": 3,
    "free": 5,
    "with_certificate": 3,
    "by_capacity_type": {
      "unlimited": 3,
      "limited": 4,
      "invite_only": 1
    },
    "by_location": {
      "online": 1,
      "offline": 1,
      "both": 6
    }
  }
}
```

---

## 💡 Cas d'utilisation

### 1. **Afficher les types d'événements disponibles**
```javascript
fetch('/api/event-types?active=true')
  .then(res => res.json())
  .then(data => {
    data.data.forEach(type => {
      console.log(`${type.icon} ${type.name} - ${type.color}`);
    });
  });
```

### 2. **Filtrer les événements payants**
```bash
GET /api/event-types?paid=true
```

### 3. **Créer un événement avec un type**
```php
$event = new Event();
$event->setTitle("Summer Exhibition");
$event->setEventType($eventTypeRepository->find(1)); // Exhibition
```

### 4. **Afficher les événements par type**
```php
$eventType = $eventTypeRepository->find(2); // Workshop
$events = $eventType->getEvents();
```

---

## 🎨 Utilisation des couleurs et icônes

Les couleurs peuvent être utilisées pour:
- Badge de type d'événement
- Bordure de carte
- Fond de catégorie
- Légende de calendrier

Les icônes peuvent être:
- Emoji Unicode (🎨, 🖼️, 🎭)
- Classes FontAwesome (`fas fa-palette`)
- SVG personnalisés

---

## 📝 Installation rapide

```bash
# Créer la table et les données
php create_event_types.php
```

---

## 🔐 Permissions

| Action | Permission requise |
|--------|-------------------|
| Lister | Public |
| Voir détails | Public |
| Créer | ROLE_ADMIN |
| Modifier | ROLE_ADMIN |
| Supprimer | ROLE_ADMIN |

---

## 🌟 Fonctionnalités créatives

1. **Certificats de participation** - Pour workshops, conférences, masterclasses
2. **Enregistrements** - Possibilité d'enregistrer l'événement
3. **Invitation uniquement** - Pour événements exclusifs (auctions)
4. **Durée typique** - Aide à la planification
5. **Localisation flexible** - Online, offline ou hybride
6. **Visibilité contrôlée** - Public, privé ou membres uniquement

---

## 🧪 Tests

```bash
# Lister tous les types
curl http://localhost/MuseHub-my-work/MuseHub-my-work/public/api/event-types

# Voir les workshops (limités)
curl http://localhost/MuseHub-my-work/MuseHub-my-work/public/api/event-types?capacity_type=limited

# Statistiques
curl http://localhost/MuseHub-my-work/MuseHub-my-work/public/api/event-types/stats/summary
```

---

## 📚 Références

- Entité: `src/Entity/EventType.php`
- Repository: `src/Repository/EventTypeRepository.php`
- Contrôleur: `src/Controller/EventTypeApiController.php`
- Script d'installation: `create_event_types.php`
