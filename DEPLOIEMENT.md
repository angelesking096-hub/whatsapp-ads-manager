# Guide de livraison et de mise en ligne — WhatsApp Ads Manager

## 1. Prérequis

- PHP 8.3+ avec les extensions Laravel nécessaires (PDO MySQL, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON, Fileinfo, etc.).
- Composer 2, Node.js/npm pour compiler les assets, MySQL/MariaDB.
- Un hébergement avec HTTPS, accès cron et, idéalement, un worker de queue persistant. Pour une campagne d'envoi réelle, l'accès à WhatsApp Business Platform / Cloud API et un modèle approuvé sont nécessaires.

## 2. Mise en ligne

1. Téléverser et extraire l'archive dans un répertoire privé de l'hébergement.
2. Configurer le domaine pour pointer vers le sous-dossier `public/`. Ne jamais exposer `.env`, `vendor/`, `storage/` ou les fichiers source directement.
3. Installer les dépendances depuis le répertoire du projet :

   ```bash
   composer install --no-dev --optimize-autoloader
   npm install
   npm run build
   ```

4. Créer la base de données et l'utilisateur MySQL. Copier `.env.example` en `.env` et renseigner `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://votre-domaine`, `APP_TIMEZONE=Africa/Douala`, les identifiants de base de données et `QUEUE_CONNECTION=database`.
5. Générer la clé d'application une seule fois (`php artisan key:generate`) et ne plus la modifier après avoir enregistré les jetons chiffrés.
6. Lancer `php artisan migrate --force`, puis `php artisan config:cache`, `php artisan route:cache` et `php artisan view:cache`.
7. Créer un compte depuis `/register`, puis promouvoir uniquement le compte propriétaire de confiance comme superadministrateur avec une requête SQL exécutée dans un outil d'administration sécurisé :

   ```sql
   UPDATE users SET is_platform_admin = 1 WHERE email = 'votre-email@exemple.com';
   ```

8. Dans Paramètres, enregistrer le Phone Number ID, le numéro affiché et le jeton Cloud API valide. Ne pas transmettre le jeton à une autre personne.
9. Créer un modèle WhatsApp approuvé dans Meta, ajouter des contacts avec consentement vérifiable, puis faire un test contrôlé avec un petit nombre de destinataires.
10. Dans Meta Developers, configurer le callback webhook HTTPS `https://votre-domaine/webhooks/whatsapp`, renseigner la même valeur de vérification dans `WHATSAPP_WEBHOOK_VERIFY_TOKEN`, ajouter `WHATSAPP_APP_SECRET` dans `.env`, puis abonner l’application aux événements `messages` nécessaires. Vérifier la configuration avec un événement de test Meta avant le lancement.

## 3. Tâches planifiées et file d'attente

La commande `campaigns:dispatch-scheduled` place les campagnes de messages privés arrivées à échéance dans la file. Le cron Laravel doit tourner chaque minute :

```cron
* * * * * cd /chemin/vers/le-projet && php artisan schedule:run >> /dev/null 2>&1
```

Un worker doit également consommer la file. Sur VPS, privilégier un worker persistant géré par Supervisor ou un service équivalent :

```bash
php artisan queue:work --tries=3
```

Sur un hébergement mutualisé sans worker persistant, vérifier avec l'hébergeur s'il autorise une commande cron du type `php artisan queue:work --stop-when-empty`. Ne pas lancer un worker public sans limites ni supervision.

## 4. Vérifications avant ouverture

Dans un environnement de test, après installation des dépendances :

```bash
php artisan route:list
php artisan migrate:fresh --env=testing
vendor/bin/phpunit
```

La génération de cette archive a permis le lint syntaxique des fichiers PHP. Les tests Laravel, les migrations réelles et le build Vite n'ont pas pu être exécutés ici, car Composer, les dépendances `vendor/`, Node modules et un serveur MySQL/SQLite opérationnel ne sont pas disponibles dans l'environnement de création.

## 5. Limites importantes

- L'envoi Cloud API utilise des modèles WhatsApp approuvés, sans variables dynamiques dans cette version. Le brief de campagne n'est pas substitué au contenu du modèle Meta.
- Les statuts de livraison/lecture nécessitent que le webhook Meta signé soit configuré et testé de bout en bout.
- La connexion OAuth/Embedded Signup, les abonnements/paiements, l'e-mail de vérification/récupération et l'observabilité avancée ne sont pas intégrés.
- Les statuts et groupes WhatsApp ne sont pas publiés automatiquement par ce projet. Ne pas utiliser d'automatisation non officielle qui contourne les règles de la plateforme.
- Les pages Conditions et Confidentialité sont des modèles. Compléter les coordonnées de l'exploitant, la rétention et les bases légales puis faire valider les textes avant de collecter des données réelles.
