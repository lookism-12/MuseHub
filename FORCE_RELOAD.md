# Forcer le rechargement de la page communauté

Si la page communauté ne se met pas à jour, essayez :

1. **Vider le cache du navigateur** :
   - Chrome/Edge : `Ctrl + Shift + Delete` → Cocher "Images et fichiers en cache" → Effacer
   - Ou : `Ctrl + F5` pour forcer le rechargement

2. **Vider le cache Symfony** :
   ```bash
   php bin/console cache:clear
   ```

3. **Redémarrer le serveur PHP** :
   - Arrêtez le serveur (Ctrl+C)
   - Relancez : `php -S localhost:8000 -t public`

4. **Vérifier l'URL** :
   - Assurez-vous d'aller sur : `http://localhost:8000/community`
   - Pas sur : `http://localhost/MuseHub/public/community`

5. **Mode navigation privée** :
   - Ouvrez une fenêtre de navigation privée
   - Allez sur `http://localhost:8000/community`

Le fichier template est bien à : `templates/front/community.html.twig`


