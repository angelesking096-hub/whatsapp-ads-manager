# WhatsApp Ads Manager

Application SaaS multi-clients en Laravel, interface responsive en français, page d'accueil publique, authentification, espaces entreprise, campagnes, contacts avec consentement, bibliothèque média et envoi de modèles via WhatsApp Cloud API après configuration manuelle. La connexion OAuth Meta/Embedded Signup, les paiements SaaS et la validation en environnement de production restent à finaliser avant une ouverture publique. Un endpoint de webhook de statuts est inclus et valide la signature HMAC Meta, mais doit encore être testé de bout en bout avec l’application Meta réelle.

## Stack

- PHP 8.3+
- Laravel 12 ou 13 (selon compatibilité Composer disponible)
- Livewire (dépendance incluse; les écrans utilisent Blade dans cette version)
- Alpine.js pour les interactions frontend légères
- MySQL
- Vite + Tailwind CSS
- Queue database et Scheduler Laravel

## Installation locale

1. Installe PHP 8.3+, Composer, Node.js/npm et MySQL.
2. Crée une base `whatsapp_ads_manager` dans MySQL.
3. Depuis ce dossier, lance :

   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

4. Modifie `.env` avec les paramètres MySQL réels. Pour un test local seulement, `APP_DEBUG=true` est acceptable.
5. Installe et compile le frontend :

   ```bash
   npm install
   npm run build
   ```

6. Crée les tables et lance le serveur :

   ```bash
   php artisan migrate
   php artisan serve
   ```

7. Ouvre `http://127.0.0.1:8000/register`, crée un compte client et son espace entreprise.
8. Pour désigner le compte superadministrateur, après l’inscription et uniquement pour ton compte de confiance, exécute en base : `UPDATE users SET is_platform_admin = 1 WHERE email = 'ton-email@exemple.com';`. Ne rends jamais ce champ modifiable depuis le formulaire public.

> Si ton hébergement utilise une version de Laravel ou des dépendances incompatibles avec les contraintes Composer, adapte les versions avant le déploiement. Le dossier `vendor/` n'est pas inclus : Composer doit installer les dépendances dans l'environnement cible.

## Hébergement web classique

- Le document root du domaine doit pointer vers le dossier `public/`, jamais vers la racine du dépôt.
- Si l'hébergeur ne permet pas de pointer le domaine vers `public/`, demande une configuration correcte au support plutôt que de déplacer tous les fichiers sensibles dans un dossier public.
- Active HTTPS, configure `APP_ENV=production`, `APP_DEBUG=false`, une `APP_KEY` unique et des identifiants DB dédiés.
- Le traitement des campagnes programmées nécessite un cron régulier pour `php artisan schedule:run` et un worker de queue. Si l'hébergeur ne permet pas de worker persistant, configure un cron pour `php artisan queue:work --stop-when-empty` ou choisis un VPS.
- Exécute `php artisan storage:link` si tu actives le stockage public des médias. Les médias privés de clients doivent idéalement utiliser un stockage privé et des URL temporaires.

## Modules présents

- Inscription/connexion/déconnexion.
- Création automatique d'un tenant lors de l'inscription.
- Relations utilisateur ↔ entreprise et rôles de base.
- Tableau de bord avec statistiques des campagnes.
- Création, consultation, modification et suppression de campagnes.
- Envoi de modèles WhatsApp approuvés vers les contacts consentants via une file de jobs, avec journaux d’envoi et tentatives de reprise.
- Programmation des campagnes privées via Scheduler Laravel et commande `campaigns:dispatch-scheduled`.
- Configuration manuelle `WhatsAppAccount` avec chiffrement du jeton d’accès au niveau Eloquent.
- Les statuts et groupes restent en préparation avec action manuelle, faute de présumer une API officielle de publication compatible.

## À faire avant production

1. Implémenter Meta Embedded Signup/OAuth et le cycle de vie/renouvellement sécurisé des jetons.
2. Tester de bout en bout les webhooks signés de statut de livraison avec des événements Meta réels.
3. Renforcer les tests d’isolation tenant sur chaque endpoint et chaque fichier média et réaliser une revue de sécurité.
4. Ajouter validation d’e-mail, récupération de compte et surveillance de l’état des workers.
6. Configurer les tarifs, plans, paiements et facturation seulement après validation du modèle commercial.
7. Confirmer les capacités Meta actuelles pour la publication de statuts et de messages dans les groupes. La Cloud API standard ne doit pas être considérée comme une API universelle de publication de statuts ou de messages dans tous les groupes.
8. Faire une revue sécurité, tests de charge et sauvegarde/restauration avant d'ouvrir les inscriptions publiques.

## Variables Meta prévues

Voir `.env.example` pour `WHATSAPP_APP_ID`, `WHATSAPP_APP_SECRET`, `WHATSAPP_CONFIG_ID`, `WHATSAPP_WEBHOOK_VERIFY_TOKEN` et `WHATSAPP_GRAPH_VERSION`. Ne jamais partager ou committer de vrais secrets.

## Vérifications

Après installation des dépendances :

```bash
php artisan route:list
php artisan migrate:fresh --env=testing
vendor/bin/phpunit
```

Le code fourni n'a pas pu être exécuté dans un environnement Laravel complet ici, car `vendor/` et les dépendances Composer ne sont pas installés dans l'environnement de génération. Exécute les commandes ci-dessus dans ton environnement local et corrige les éventuelles différences de version.

Le guide de déploiement détaillé est disponible dans `DEPLOIEMENT.md`.

## Fonctions ajoutées dans la version de livraison

- Carnet de contacts par entreprise, avec statut de consentement, source déclarée, désinscription et suppression.
- Configuration manuelle d'un compte WhatsApp Cloud API dans Paramètres ; le jeton d'accès est chiffré par le cast Laravel. La connexion OAuth/Embedded Signup et la validation réelle du compte ne sont pas incluses.
- Lancement manuel et programmation des campagnes de messages privés vers les seuls contacts consentants et non désinscrits, avec un nom de modèle WhatsApp approuvé. Les envois passent par la file Laravel et chaque destinataire possède un journal d’état.
- Endpoint webhook WhatsApp avec vérification HMAC de la signature Meta et mise à jour des statuts de livraison quand les événements correspondent à un identifiant de message connu.
- Protection anti-bruteforce par limitation des tentatives de connexion et d'inscription, en plus des middlewares de session/CSRF Laravel.

### Configuration pour tester un envoi

1. Configure la connexion WhatsApp dans **Paramètres** avec un `Phone Number ID` et un jeton Cloud API valide. Ne mets pas de vrais secrets dans le code ou dans un dépôt public.
2. Crée un modèle WhatsApp dans Meta et attends son approbation ; utilise son nom exact dans la campagne. Cette version envoie le modèle sans variables : le texte de campagne est un brief interne et ne remplace pas le contenu du modèle Meta.
3. Ajoute des contacts ayant réellement accepté de recevoir des messages et conserve leurs preuves de consentement. Les cases du site ne remplacent pas les preuves d'origine.
4. Démarre un worker (`php artisan queue:work`) avant de lancer la campagne depuis son écran de détail. Un succès indique que l'API a accepté le message, pas nécessairement qu'il a été livré/lu.
5. Les campagnes privées avec date future et nom de modèle peuvent être mises en file automatiquement par la commande `campaigns:dispatch-scheduled`, planifiée chaque minute. Le cron de l’hébergeur doit exécuter `php artisan schedule:run` chaque minute ; un worker de queue doit également fonctionner. Les statuts/groupes ne sont pas automatiquement publiés.

## État exact de cette livraison

Cette archive est une version avancée du code source, pas une instance hébergée ni une certification de mise en production. L'environnement de génération n'a pas Composer/vendor et ne peut donc pas exécuter Laravel, les migrations ou les tests intégrés. Les fichiers PHP peuvent être vérifiés par lint, mais la validation finale doit être faite après `composer install`, `php artisan migrate:fresh --env=testing` et `vendor/bin/phpunit` dans un environnement compatible. Avant l'ouverture publique : ajouter les webhooks de statuts Meta avec validation de signature, tests de bout en bout, politiques de rétention/suppression, validation email/récupération de compte, protections de quotas, supervision, sauvegardes et revue sécurité.
