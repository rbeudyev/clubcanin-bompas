# Guide de déploiement sur o2switch

Ce guide vous explique comment déployer votre application Symfony sur o2switch.

## Prérequis

-   Accès FTP/SFTP à votre hébergement o2switch
-   Accès SSH (recommandé) ou FTP uniquement
-   Identifiants de base de données MySQL/MariaDB fournis par o2switch
-   PHP 8.2 ou supérieur (vérifiez avec o2switch)

## Étape 1 : Préparation des fichiers

### 1.1 Fichiers à transférer

Transférez tous les fichiers suivants sur le serveur o2switch (dans le répertoire `www` ou `public_html`) :

```
✓ bin/
✓ config/
✓ migrations/
✓ public/
✓ src/
✓ templates/
✓ translations/
✓ assets/
✓ composer.json
✓ composer.lock
✓ importmap.php
✓ symfony.lock
```

### 1.2 Fichiers à NE PAS transférer

Ces fichiers/dossiers ne doivent PAS être transférés (déjà dans .gitignore) :

```
✗ .env
✗ .env.local
✗ .env.*.local
✗ var/
✗ vendor/
✗ .git/
✗ .idea/
✗ docker/
✗ compose.yaml
✗ Dockerfile
✗ tests/
✗ .DS_Store
```

## Étape 2 : Configuration de la base de données

### 2.1 Créer la base de données

1. Connectez-vous à votre panel o2switch
2. Créez une base de données MySQL/MariaDB
3. Notez les informations de connexion :
    - Nom de la base de données
    - Nom d'utilisateur
    - Mot de passe
    - Serveur (généralement `localhost`)

### 2.2 Configuration des variables d'environnement

Créez un fichier `.env` dans la racine du projet sur le serveur avec le contenu suivant :

```env
APP_ENV=prod
APP_SECRET=your-secret-key-here-change-this
APP_DEBUG=0

# Base de données o2switch
DATABASE_URL="mysql://username:password@localhost:3306/database_name?serverVersion=mariadb-10.11&charset=utf8mb4"

# Mailer (ajustez selon votre configuration o2switch)
MAILER_DSN=smtp://username:password@smtp.o2switch.net:587

# URI par défaut (remplacez par votre domaine)
DEFAULT_URI=https://votre-domaine.fr
```

**Important :**

-   Remplacez `your-secret-key-here-change-this` par une clé secrète générée (voir étape 3.2)
-   Remplacez `username`, `password`, et `database_name` par vos identifiants o2switch
-   Remplacez `votre-domaine.fr` par votre nom de domaine

## Étape 3 : Installation via SSH (recommandé)

### 3.1 Connexion SSH

Connectez-vous en SSH à votre serveur o2switch :

```bash
ssh votre-utilisateur@votre-serveur.o2switch.net
cd www  # ou public_html selon votre configuration
```

### 3.2 Installation des dépendances

```bash
# Installer Composer si ce n'est pas déjà fait
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

### 3.3 Générer la clé secrète

```bash
php bin/console secrets:generate-keys
php bin/console secrets:set APP_SECRET --random
```

### 3.4 Configurer les permissions

```bash
# Permissions pour les dossiers
chmod -R 755 var/
chmod -R 755 public/uploads/

# Propriétaire (remplacez 'votre-utilisateur' par votre utilisateur o2switch)
chown -R votre-utilisateur:www-data var/
chown -R votre-utilisateur:www-data public/uploads/
```

### 3.5 Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

### 3.6 Compiler les assets

```bash
php bin/console asset-map:compile
```

### 3.7 Vider et réchauffer le cache

```bash
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod
```

## Étape 4 : Configuration Apache/.htaccess

### 4.1 Fichier .htaccess dans public/

Créez ou modifiez le fichier `public/.htaccess` avec le contenu suivant :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Redirection HTTPS (si vous avez un certificat SSL)
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Redirection vers index.php pour toutes les requêtes
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

# Protection des fichiers sensibles
<FilesMatch "^(composer\.(json|lock)|\.env)">
    Require all denied
</FilesMatch>
```

### 4.2 Configuration du DocumentRoot

Assurez-vous que le DocumentRoot d'Apache pointe vers le dossier `public/` :

Dans votre panel o2switch ou via un fichier `.htaccess` à la racine :

```apache
# Si o2switch vous permet de modifier le DocumentRoot
# Il doit pointer vers : /chemin/vers/votre/projet/public
```

**Note :** Si o2switch ne permet pas de changer le DocumentRoot, vous devrez peut-être :

-   Déplacer le contenu de `public/` à la racine
-   Ou configurer une redirection dans le `.htaccess` racine

## Étape 5 : Configuration alternative (sans SSH)

Si vous n'avez pas accès SSH, vous pouvez :

### 5.1 Installer Composer localement

Sur votre machine locale :

```bash
composer install --no-dev --optimize-autoloader
```

### 5.2 Transférer le dossier vendor/

Transférez le dossier `vendor/` complet via FTP (peut prendre du temps).

### 5.3 Exécuter les commandes via le panel o2switch

Certains hébergeurs proposent un terminal web. Sinon, contactez le support o2switch pour :

-   Exécuter les migrations
-   Vider le cache
-   Configurer les permissions

## Étape 6 : Vérifications finales

### 6.1 Vérifier les permissions

```bash
ls -la var/
ls -la public/uploads/
```

Les dossiers doivent être accessibles en écriture.

### 6.2 Tester l'application

1. Visitez votre site : `https://votre-domaine.fr`
2. Vérifiez que les pages se chargent correctement
3. Testez l'upload de fichiers (si applicable)
4. Vérifiez les logs en cas d'erreur : `var/log/prod.log`

### 6.3 Vérifier les logs

```bash
tail -f var/log/prod.log
```

## Étape 7 : Optimisations de production

### 7.1 OPcache

Vérifiez que OPcache est activé dans votre `php.ini` (généralement activé par défaut sur o2switch).

### 7.2 Compression Gzip

Ajoutez dans `public/.htaccess` :

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

### 7.3 Cache des fichiers statiques

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

## Dépannage

### Erreur 500

1. Vérifiez les logs : `var/log/prod.log`
2. Vérifiez les permissions : `chmod -R 755 var/`
3. Vérifiez le fichier `.env` et les variables d'environnement

### Erreur de base de données

1. Vérifiez la `DATABASE_URL` dans `.env`
2. Vérifiez que la base de données existe
3. Vérifiez les permissions de l'utilisateur MySQL

### Assets non chargés

1. Exécutez : `php bin/console asset-map:compile`
2. Vérifiez les permissions de `public/assets/`

### Problèmes de permissions

```bash
find var/ -type d -exec chmod 755 {} \;
find var/ -type f -exec chmod 644 {} \;
find public/uploads/ -type d -exec chmod 755 {} \;
find public/uploads/ -type f -exec chmod 644 {} \;
```

## Support o2switch

Si vous rencontrez des problèmes spécifiques à o2switch :

-   Contactez le support o2switch
-   Vérifiez leur documentation : https://www.o2switch.fr/support/
-   Demandez la version PHP installée et les extensions disponibles

## Commandes utiles pour la maintenance

```bash
# Mettre à jour les dépendances
composer update --no-dev --optimize-autoloader

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Vider le cache
php bin/console cache:clear --env=prod

# Compiler les assets
php bin/console asset-map:compile

# Vérifier la configuration
php bin/console debug:container --env=prod
```
