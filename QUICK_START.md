# Guide de Démarrage Rapide - MuseHub

## Problème: "Ce site est inaccessible"

### Solution 1: Vérifier que XAMPP est démarré

1. Ouvrez le **panneau de contrôle XAMPP**
2. Démarrez **Apache** (bouton Start)
3. Démarrez **MySQL** (bouton Start) si vous avez besoin de la base de données

### Solution 2: Accéder au site

**Option A: Via XAMPP (recommandé)**
- URL: `http://localhost/MuseHub/public/`
- Ou: `http://localhost/MuseHub/public/index.php`

**Option B: Via Symfony CLI**
```bash
symfony server:start
```
Puis accédez à: `http://localhost:8000`

**Option C: Via PHP built-in server**
```bash
cd public
php -S localhost:8000
```
Puis accédez à: `http://localhost:8000`

### Solution 3: Créer le fichier .env.local

Créez un fichier `.env.local` à la racine du projet avec:

```env
###> symfony/framework-bundle ###
APP_ENV=dev
APP_SECRET=change-this-secret-key-in-production-123456789
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
DATABASE_URL="mysql://root:@127.0.0.1:3306/musehub?serverVersion=8.0&charset=utf8mb4"
###< doctrine/doctrine-bundle ###

###> symfony/mailer ###
MAILER_DSN=null://null
###< symfony/mailer ###
```

### Solution 4: Vider le cache

```bash
php bin/console cache:clear
```

### Solution 5: Vérifier les permissions

Assurez-vous que les dossiers suivants sont accessibles en écriture:
- `var/cache/`
- `var/log/`

## Routes disponibles

Une fois le site accessible:

- **Page d'accueil**: `http://localhost/MuseHub/public/`
- **Admin**: `http://localhost/MuseHub/public/admin/`
- **API**: `http://localhost/MuseHub/public/api/...`

## Note sur JWT

Le bundle JWT a été temporairement désactivé à cause d'un problème de compatibilité avec Symfony 6.4. 
L'authentification API fonctionne toujours via le contrôleur `JWTAuthenticationController`.

Pour réactiver JWT plus tard, vous devrez:
1. Mettre à jour le bundle JWT vers une version compatible
2. Ou utiliser une alternative comme `gesdinet/jwt-refresh-token-bundle`

## Test rapide

Pour tester si le site fonctionne:

```bash
php bin/console router:match /
```

Cela devrait afficher la route "home".


