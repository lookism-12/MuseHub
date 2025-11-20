# Fix Database - Tables Manquantes

## ✅ Problème Résolu

La table `listing` et toutes les autres tables ont été créées avec succès.

## 📋 Tables Disponibles

Toutes les tables suivantes existent maintenant dans la base de données `musehub`:

- ✅ `user` - Utilisateurs
- ✅ `artwork` - Œuvres d'art
- ✅ `category` - Catégories
- ✅ `event` - Événements
- ✅ `participant` - Participants aux événements
- ✅ `listing` - Annonces marketplace
- ✅ `transaction` - Transactions d'achat
- ✅ `post` - Posts de la communauté
- ✅ `comment` - Commentaires

## 🔧 Si vous avez encore des erreurs

### Option 1: Vérifier les tables
```bash
php bin/console doctrine:query:sql "SHOW TABLES"
```

### Option 2: Recréer les tables
```bash
php create_tables.php
```

### Option 3: Vider le cache
```bash
php bin/console cache:clear
```

## ✅ Test Rapide

Testez que tout fonctionne:

```bash
# Test liste marketplace (utilise la table listing)
curl http://localhost:8000/api/marketplace

# Test liste événements (utilise la table event)
curl http://localhost:8000/api/events

# Test liste posts (utilise la table post)
curl http://localhost:8000/api/posts
```

Toutes les tables sont maintenant créées et prêtes à être utilisées! 🎉


