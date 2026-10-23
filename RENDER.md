# Guide de déploiement sur Render.com — WhatsApp Ads Manager

Ce guide vous explique pas à pas comment héberger gratuitement ou à moindre coût votre application **WhatsApp Ads Manager** sur **[Render.com](https://render.com)**.

---

## 1. Fonctionnement sur Render

Render fait tourner l'application grâce au [Dockerfile](file:///c:/Users/pc/Music/whatsapp-ads-manager%20(1)/whatsapp-ads-manager/Dockerfile) configuré dans le projet :
- **Serveur web** : Apache + PHP 8.2 avec toutes les extensions nécessaires (`pdo_mysql`, `pdo_pgsql`, `mbstring`, `zip`, `opcache`, etc.).
- **Frontend** : Les assets Vite (CSS & JS) sont automatiquement compilés durant la construction de l'image.
- **Port dynamique** : Le script [docker-entrypoint.sh](file:///c:/Users/pc/Music/whatsapp-ads-manager%20(1)/whatsapp-ads-manager/docker-entrypoint.sh) configure automatiquement le port attribué par Render (`$PORT` = 10000).
- **HTTPS automatique** : Render fournit gratuitement un certificat SSL et une URL publique en `https://votre-app.onrender.com`.

---

## 2. Étape 1 : Mettre votre code sur GitHub

1. Créez un dépôt privé ou public sur [github.com](https://github.com).
2. Dans le terminal du projet :
   ```bash
   git init
   git add .
   git commit -m "Préparation pour déploiement Render"
   git branch -M main
   git remote add origin https://github.com/votre-compte/whatsapp-ads-manager.git
   git push -u origin main
   ```

---

## 3. Étape 2 : Créer la base de données sur Render

Render propose deux options simples pour la base de données :

### Option A : Base PostgreSQL managée intégrée sur Render (100% gratuit & simple)
Laravel prend en charge nativement PostgreSQL :
1. Sur Render, cliquez sur **New +** ➔ **PostgreSQL**.
2. Nom : `whatsapp-ads-db`.
3. Plan : **Free**.
4. Cliquez sur **Create Database**.
5. Copiez les informations de connexion fournies par Render :
   - **Internal Database URL** (ou `Host`, `Database`, `User`, `Password`, `Port` = 5432).

### Option B : Base MySQL externe gratuite
Si vous préférez rester sur MySQL :
- Vous pouvez créer une base MySQL gratuite en 1 clic sur **[Aiven.io](https://aiven.io)**, **[Clever Cloud](https://clever-cloud.com)** ou **[Railway.app](https://railway.app)**, puis récupérer l'hôte, le port, le nom de base, l'utilisateur et le mot de passe.

---

## 4. Étape 3 : Créer le Web Service sur Render

1. Sur votre tableau de bord Render, cliquez sur **New +** ➔ **Web Service**.
2. Connectez votre dépôt GitHub `whatsapp-ads-manager`.
3. Configurez les champs suivants :
   - **Name** : `whatsapp-ads-manager` (ou le nom de votre choix).
   - **Region** : `Frankfurt (EU Central)` ou la région la plus proche.
   - **Runtime** : **Docker** *(Render détectera automatiquement le fichier Dockerfile)*.
   - **Plan** : **Free** (ou Starter pour éviter la mise en veille).

---

## 5. Étape 4 : Configurer les Variables d'Environnement

Dans la section **Environment Variables** (ou onglet **Environment** de votre service), ajoutez :

| Variable | Valeur recommandée | Description |
| :--- | :--- | :--- |
| `APP_NAME` | `WhatsApp Ads Manager` | Nom affiché de l'application |
| `APP_ENV` | `production` | Mode production |
| `APP_DEBUG` | `false` | Désactive l'affichage des erreurs détaillées |
| `APP_KEY` | *(Votre clé actuelle du `.env`)* | Clé de chiffrement AES-256 |
| `APP_URL` | `https://votre-app.onrender.com` | L'URL attribuée par Render |
| `APP_TIMEZONE` | `Africa/Douala` | Votre fuseau horaire |
| `LOG_CHANNEL` | `stderr` | Envoie les logs dans la console Render |
| `SESSION_DRIVER` | `database` | Gestion des sessions en base |
| `QUEUE_CONNECTION` | `database` | File d'attente des envois de messages |
| `DB_CONNECTION` | `pgsql` *(ou `mysql`)* | Type de base de données choisi |
| `DB_HOST` | *(Hôte de votre base de données)* | Adresse du serveur DB |
| `DB_PORT` | `5432` *(si pgsql)* ou `3306` *(si mysql)* | Port DB |
| `DB_DATABASE` | *(Nom de votre base)* | Base de données |
| `DB_USERNAME` | *(Utilisateur DB)* | Utilisateur |
| `DB_PASSWORD` | *(Mot de passe DB)* | Mot de passe |

---

## 6. Étape 5 : Déploiement et premier compte Super Admin

1. Cliquez sur **Deploy Web Service** (ou **Save Changes**).
2. Render va construire l'image Docker, compiler le CSS/JS et démarrer l'application.
3. Dès que le statut passe à **Live** :
   - Rendez-vous sur votre URL `https://votre-app.onrender.com/register`.
   - Inscrivez votre compte administrateur.
4. Pour promouvoir ce compte en **Super Administrateur** en production :
   - Dans l'onglet **Shell** de votre service sur Render, tapez simplement :
     ```bash
     php artisan make:superadmin votre-email@exemple.com
     ```
   - Votre compte aura instantanément accès au panneau de Super Administration !
