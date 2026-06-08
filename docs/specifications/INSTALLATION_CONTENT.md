# INSTALLATION_CONTENT — Installer FlatCMS

> **Contenu éditorial prêt à intégrer**
>
> Projet : FlatCMS  
> Page : Installation `fr-FR`  
> URL cible : `https://flat-cms.fr/fr-FR/documentation/installation/`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Brief parent : `PAGE_BRIEFS.md` — `P0-INSTALL-FR`  
> Documents associés : `DOCUMENTATION_CONTENT.md`, `ARCHITECTURE_CONTENT.md`, `LAUNCH_CHECKLIST.md`  
> Statut : première version rédactionnelle à tester sur le package final avant publication

---

## 1. Objectif du guide

Ce guide explique comment installer FlatCMS à partir de l’archive officielle.

Il couvre le tronc commun à tous les environnements :

- vérifier PHP et le serveur web ;
- télécharger et contrôler l’archive ;
- extraire les fichiers ;
- configurer le document root ;
- appliquer les permissions ;
- lancer l’installateur ;
- créer le premier compte ;
- vérifier le frontend et l’administration ;
- sécuriser l’installation ;
- effectuer une première sauvegarde.

Les configurations propres à Apache, Nginx, MAMP, WAMP, Synology DSM et Raspberry Pi sont détaillées dans des guides séparés.

---

# 2. Métadonnées SEO

## URL canonique

```text
https://flat-cms.fr/fr-FR/documentation/installation/
```

## Balise `<title>`

```text
Installer FlatCMS sur Apache, Nginx, MAMP ou WAMP
```

## Meta description

```text
Installez FlatCMS, vérifiez PHP, configurez le document root public/,
appliquez les permissions et lancez l’installateur officiel.
```

## Directive robots

```text
index, follow, max-image-preview:large
```

## Open Graph

### `og:title`

```text
Installer FlatCMS
```

### `og:description`

```text
Guide complet pour télécharger, configurer et sécuriser FlatCMS sur un
serveur PHP compatible.
```

### `og:type`

```text
article
```

### `og:url`

```text
https://flat-cms.fr/fr-FR/documentation/installation/
```

### `og:image`

```text
https://flat-cms.fr/assets/images/og/installation-flatcms-fr-FR.webp
```

---

# 3. Résultat attendu

À la fin de ce guide :

- FlatCMS est installé dans un dossier dédié ;
- le serveur web expose le dossier `public/` ;
- le runtime utilise une version PHP compatible ;
- les dossiers nécessaires sont accessibles en écriture ;
- l’installateur est terminé ;
- le frontend répond sans erreur ;
- l’administration est accessible ;
- les fichiers sensibles ne sont pas exposés ;
- une première sauvegarde est disponible.

---

# 4. Avant de commencer

## H2

```text
Choisir l’environnement d’installation
```

FlatCMS peut être installé dans plusieurs contextes :

- environnement local macOS avec MAMP ;
- environnement local Windows avec WAMP ou MAMP ;
- serveur Apache ;
- serveur Nginx avec PHP-FPM ;
- hébergement mutualisé compatible ;
- serveur Linux ;
- Synology DSM avec Web Station ;
- Raspberry Pi ;
- VPS ou serveur privé.

## Guides spécialisés

```text
Apache
→ /fr-FR/documentation/installation/apache/

Nginx
→ /fr-FR/documentation/installation/nginx/

MAMP
→ /fr-FR/documentation/installation/mamp/

WAMP
→ /fr-FR/documentation/installation/wamp/

Synology DSM
→ /fr-FR/documentation/installation/synology/

Raspberry Pi
→ /fr-FR/documentation/installation/raspberry-pi/
```

---

# 5. Prérequis

## H2

```text
Vérifier les prérequis techniques
```

## PHP

Le dépôt FlatCMS LTS Core annonce :

```text
PHP 8.3 ou version ultérieure compatible
```

En production, utilisez une branche PHP encore prise en charge par le projet PHP.

## Serveur web

Utiliser l’un des environnements suivants :

```text
Apache 2.4+
Nginx
serveur compatible avec PHP-FPM ou une intégration PHP équivalente
```

## Extensions PHP

La liste exacte doit être validée par l’installateur du package final.

Les extensions généralement nécessaires à un CMS PHP de ce type peuvent inclure :

```text
json
mbstring
fileinfo
openssl
session
filter
ctype
iconv
zip
curl
gd ou imagick selon les fonctions médias
```

Cette liste ne doit pas être publiée comme contractuelle avant comparaison avec les contrôles réels de l’installateur.

## Accès au système de fichiers

Le processus PHP doit pouvoir :

- lire le code et les configurations ;
- écrire dans les dossiers de données requis ;
- écrire dans les dossiers de cache ou de stockage requis ;
- téléverser les médias dans les emplacements autorisés ;
- créer les fichiers générés par l’installateur lorsque nécessaire.

## HTTPS

HTTPS est fortement recommandé en production.

Il devient indispensable lorsque le site utilise :

- authentification ;
- formulaires ;
- cookies de session ;
- administration ;
- clés ou intégrations externes ;
- paiements ou composants commerciaux.

## Outils recommandés

- accès FTP, SFTP ou SSH ;
- éditeur de texte ;
- navigateur récent ;
- terminal ;
- accès aux logs du serveur ;
- sauvegarde du dossier de destination ;
- accès à la configuration Apache ou Nginx.

---

# 6. Vérifier PHP

## H2

```text
Contrôler la version de PHP utilisée par le serveur
```

## En ligne de commande

### macOS ou Linux

```bash
php -v
```

### Windows PowerShell

```powershell
php -v
```

## Résultat attendu

```text
PHP 8.3.x ou version ultérieure compatible
```

## Important

La version PHP affichée dans le terminal peut être différente de celle utilisée par Apache, Nginx, MAMP, WAMP ou Web Station.

Créez temporairement un fichier de diagnostic uniquement si nécessaire :

```php
<?php

declare(strict_types=1);

phpinfo();
```

Supprimez ce fichier immédiatement après le contrôle.

## Vérifier les extensions

### macOS, Linux et Windows avec PHP dans le PATH

```bash
php -m
```

## Rechercher une extension précise

### macOS ou Linux

```bash
php -m | grep -i mbstring
```

### Windows PowerShell

```powershell
php -m | Select-String -Pattern "mbstring"
```

---

# 7. Télécharger FlatCMS

## H2

```text
Télécharger l’archive officielle
```

Téléchargez FlatCMS depuis :

```text
https://flat-cms.fr/fr-FR/telechargement/
```

La page de téléchargement doit indiquer :

- numéro de version ;
- date de publication ;
- taille du fichier ;
- format de l’archive ;
- checksum ;
- licence ;
- prérequis ;
- notes de version.

## Ne pas utiliser

- une archive reçue par une source inconnue ;
- un package modifié sans documentation ;
- une ancienne copie conservée localement sans vérifier sa version ;
- une archive de développement pour un site de production.

---

# 8. Vérifier l’intégrité de l’archive

## H2

```text
Comparer le checksum
```

La page de téléchargement doit fournir un checksum officiel, de préférence SHA-256.

## macOS ou Linux

```bash
shasum -a 256 FlatCMS-1.0.0.zip
```

ou :

```bash
sha256sum FlatCMS-1.0.0.zip
```

## Windows PowerShell

```powershell
Get-FileHash .\FlatCMS-1.0.0.zip -Algorithm SHA256
```

## Résultat attendu

La valeur obtenue doit être strictement identique au checksum publié.

## En cas de différence

- ne pas installer l’archive ;
- supprimer le fichier ;
- télécharger à nouveau depuis la source officielle ;
- vérifier la version ;
- contacter FlatCMS si le problème persiste.

---

# 9. Extraire les fichiers

## H2

```text
Extraire FlatCMS dans un dossier dédié
```

Exemple de structure :

```text
/var/www/flatcms/
├── app/
├── config/
├── data/
├── public/
├── resources/
├── storage/
├── themes/
├── index.php
├── README.md
└── VERSION
```

## Recommandations

- utiliser un dossier vide ;
- conserver l’arborescence de l’archive ;
- ne pas déplacer uniquement `public/` sans comprendre les chemins ;
- ne pas fusionner immédiatement avec un ancien site ;
- éviter les espaces et caractères spéciaux dans le chemin serveur ;
- ne pas supprimer les fichiers masqués ;
- vérifier que tous les modules attendus sont présents.

## Contrôle rapide

Vérifiez au minimum la présence de :

```text
app/
config/
data/
public/
themes/
index.php
VERSION
README.md
```

---

# 10. Comprendre les deux phases de l’installation

## H2

```text
Installateur initial et document root de production
```

Le contrat actuel du dépôt annonce comme entrée canonique de l’installateur après extraction :

```text
index.php?step=1
```

Il conserve également un alias de compatibilité Apache :

```text
/install/
```

pour certains déploiements où la racine du projet est temporairement exposée.

## Phase 1 — Installation

Selon l’environnement et le package final, l’installateur peut être lancé depuis la racine du projet.

Exemple local conceptuel :

```text
http://localhost/flatcms/index.php?step=1
```

## Phase 2 — Production

Après l’installation, le serveur web doit exposer :

```text
project_root/public/
```

et non :

```text
project_root/
```

## Pourquoi cette séparation ?

Le dossier `public/` contient les ressources destinées au Web.

Les dossiers suivants doivent rester privés :

```text
app/
config/
data/
resources/
storage/
themes/ selon le contrat d’exposition
.env
.env.local
sauvegardes
logs
```

## Attention

Le comportement exact de transition entre l’installateur racine et le document root `public/` doit être validé avec le package final.

---

# 11. Configurer le document root

## H2

```text
Pointer le serveur vers public/
```

Le document root recommandé est :

```text
/path/to/flatcms/public
```

## Apache VirtualHost conceptuel

```apache
<VirtualHost *:80>
    ServerName flatcms.local
    DocumentRoot "/path/to/flatcms/public"

    <Directory "/path/to/flatcms/public">
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "${APACHE_LOG_DIR}/flatcms-error.log"
    CustomLog "${APACHE_LOG_DIR}/flatcms-access.log" combined
</VirtualHost>
```

## Nginx conceptuel

```nginx
server {
    listen 80;
    server_name flatcms.local;

    root /path/to/flatcms/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_pass 127.0.0.1:9000;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

## Important pour Nginx

Adaptez :

```text
fastcgi_pass
```

au socket ou à l’hôte réel de PHP-FPM.

Exemples :

```nginx
fastcgi_pass 127.0.0.1:9000;
```

ou :

```nginx
fastcgi_pass unix:/run/php/php8.3-fpm.sock;
```

## Test de la configuration Nginx

```bash
sudo nginx -t
```

Puis, après validation :

```bash
sudo systemctl reload nginx
```

## Test Apache

```bash
sudo apachectl configtest
```

ou selon la distribution :

```bash
sudo apache2ctl configtest
```

---

# 12. Configurer Apache

## H2

```text
Activer les réécritures d’URL
```

FlatCMS utilise des URLs propres qui doivent être transmises au front controller lorsque le fichier public demandé n’existe pas.

Apache peut appliquer les règles :

- dans la configuration du VirtualHost ;
- ou dans `.htaccess` lorsque `AllowOverride` l’autorise.

## Recommandation

Préférer la configuration du VirtualHost lorsque vous contrôlez le serveur.

Utiliser `.htaccess` surtout lorsque l’hébergement mutualisé ne permet pas de modifier la configuration principale.

## Vérifier `mod_rewrite`

### Debian ou Ubuntu

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## VirtualHost

```apache
<Directory "/path/to/flatcms/public">
    AllowOverride All
    Require all granted
</Directory>
```

## Point de vigilance

Si `AllowOverride None` est actif, Apache ignore les règles présentes dans `.htaccess`.

## Logs utiles

```text
error.log
access.log
```

Un niveau de trace élevé pour `mod_rewrite` ne doit être utilisé que temporairement pour le diagnostic.

---

# 13. Configurer Nginx

## H2

```text
Acheminer les URLs vers le front controller
```

Le template livré avec le projet utilise le principe suivant :

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Nginx :

1. cherche un fichier public réel ;
2. cherche un dossier public réel ;
3. transmet les autres requêtes à `public/index.php`.

## Exécution PHP

Le template limite l’exécution publique à :

```nginx
location = /index.php
```

et rejette les autres fichiers PHP :

```nginx
location ~ \.php$ {
    return 404;
}
```

## Protection complémentaire

Le template prévoit également des règles contre l’accès direct à :

```text
data/
storage/
config/
app/
resources/
vendor/
.env
nginx.conf
composer.json
fichiers de sauvegarde
```

## Prudence sur les en-têtes

Les en-têtes CSP, HSTS et autres politiques de sécurité du template doivent être adaptés au site réel.

N’activez pas `preload` pour HSTS sans comprendre ses conséquences.

---

# 14. Appliquer les permissions

## H2

```text
Donner uniquement les droits nécessaires
```

## Principe

Le serveur web doit pouvoir lire le projet.

Il doit pouvoir écrire uniquement dans les dossiers qui le nécessitent réellement.

## Ne pas utiliser par défaut

```bash
chmod -R 777 /path/to/flatcms
```

Cette commande accorde des droits excessifs et ne constitue pas une correction durable.

## Exemple Linux conceptuel

Supposons :

```text
utilisateur de déploiement : deploy
groupe du serveur web : www-data
```

### Propriétaire et groupe

```bash
sudo chown -R deploy:www-data /var/www/flatcms
```

### Dossiers

```bash
sudo find /var/www/flatcms -type d -exec chmod 750 {} \;
```

### Fichiers

```bash
sudo find /var/www/flatcms -type f -exec chmod 640 {} \;
```

### Dossiers nécessitant l’écriture

À adapter au contrat final :

```bash
sudo chmod -R 770 /var/www/flatcms/data
sudo chmod -R 770 /var/www/flatcms/storage
sudo chmod -R 770 /var/www/flatcms/public/uploads
```

## Important

Le chemin réel des médias et la liste exacte des dossiers inscriptibles doivent être validés avec le package final.

## macOS avec MAMP

Les fichiers appartiennent généralement à l’utilisateur local.

Vérifiez néanmoins l’utilisateur exécutant Apache et les droits d’écriture.

## Windows

Utilisez les propriétés de sécurité NTFS pour accorder au compte du serveur web les droits nécessaires sans ouvrir le dossier à tous les utilisateurs.

## Synology

Le compte du processus Web Station, souvent associé au groupe `http`, doit disposer des droits nécessaires sur les dossiers d’écriture.

Les ACL Synology peuvent primer sur les permissions Unix affichées.

---

# 15. Vérifier les dossiers d’écriture

## H2

```text
Tester l’écriture avant de lancer l’installateur
```

## Linux

Identifiez l’utilisateur du serveur web.

Exemples fréquents :

```text
www-data
apache
nginx
http
```

Test conceptuel :

```bash
sudo -u www-data touch /var/www/flatcms/data/.write-test
sudo -u www-data rm /var/www/flatcms/data/.write-test
```

## Attention

Remplacez `www-data` par le compte réel.

## Interprétation

### Le fichier est créé

Le processus dispose des droits nécessaires sur ce dossier.

### Permission denied

Vérifiez :

- propriétaire ;
- groupe ;
- ACL ;
- permissions ;
- montage en lecture seule ;
- `open_basedir` ;
- AppArmor ou SELinux ;
- chemin réel ;
- utilisateur PHP-FPM ;
- permissions des dossiers parents.

---

# 16. Configurer les secrets

## H2

```text
Conserver les secrets hors du contenu public
```

Utilisez le mécanisme privé défini par FlatCMS, par exemple :

```text
.env.local
variables d’environnement
stockage privé du serveur
```

## Secrets possibles

- clés API ;
- SMTP ;
- jetons ;
- clés de licence ;
- identifiants de service ;
- secrets de session ;
- paramètres d’intégration.

## Règles

- ne jamais publier un vrai secret dans la documentation ;
- ne pas committer `.env.local` ;
- limiter les droits de lecture ;
- utiliser des valeurs différentes en développement et production ;
- renouveler toute clé exposée ;
- ne pas afficher les secrets dans les logs ;
- ne pas envoyer une clé API au navigateur.

## Exemple fictif

```env
APP_ENV=production
APP_DEBUG=false
MAIL_HOST=smtp.example.test
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

Les noms exacts des variables doivent être tirés du package final et de sa documentation.

---

# 17. Lancer l’installateur

## H2

```text
Ouvrir l’entrée canonique de l’installation
```

Le contrat du dépôt annonce :

```text
index.php?step=1
```

## Exemple local

```text
http://flatcms.local/index.php?step=1
```

## Alias Apache de compatibilité

```text
http://flatcms.local/install/
```

si le déploiement utilise temporairement la racine du projet et si l’alias est pris en charge par le package.

## Ne pas deviner l’URL

Si l’installateur ne s’ouvre pas :

- vérifier le document root temporaire ;
- vérifier les règles Apache ou Nginx ;
- vérifier l’existence de `index.php` ;
- vérifier la base URL ;
- consulter les logs ;
- vérifier que le package n’est pas incomplet.

---

# 18. Parcours d’installation attendu

## H2

```text
Suivre les étapes de l’installateur
```

Le nombre et le contenu exacts des étapes doivent être validés avec la version finale.

Le parcours peut couvrir :

1. accueil ;
2. vérification de PHP ;
3. vérification des extensions ;
4. vérification des fichiers ;
5. vérification des permissions ;
6. configuration du site ;
7. configuration des URLs ;
8. création du compte administrateur ;
9. installation éventuelle d’un site d’exemple ;
10. finalisation.

## À chaque étape

- lire les messages ;
- corriger les erreurs avant de continuer ;
- ne pas forcer l’étape suivante ;
- conserver le message exact en cas d’échec ;
- consulter les logs ;
- éviter de modifier plusieurs paramètres simultanément.

---

# 19. Configuration du site

## H2

```text
Renseigner les informations initiales
```

Les champs peuvent inclure :

- nom du site ;
- slogan ;
- description ;
- URL publique ;
- langue par défaut ;
- fuseau horaire ;
- adresse e-mail ;
- paramètres de contact ;
- thème initial ;
- site d’exemple.

## Recommandations

### URL

Utiliser l’URL finale de l’environnement.

Exemples :

```text
http://flatcms.local
https://www.example.com
```

### Langue

Choisir la locale principale :

```text
fr-FR
```

### Fuseau horaire

Pour la France métropolitaine :

```text
Europe/Paris
```

### E-mail

Utiliser une adresse réelle que vous pouvez tester.

---

# 20. Créer le compte administrateur

## H2

```text
Protéger le premier compte
```

## Recommandations

- utiliser une adresse e-mail personnelle et sécurisée ;
- choisir un mot de passe unique ;
- utiliser un gestionnaire de mots de passe ;
- éviter `admin` comme identifiant lorsque le système permet un autre nom ;
- activer la double authentification si elle est disponible et validée ;
- conserver les codes de récupération hors du serveur ;
- ne pas partager le compte principal ;
- créer ensuite des comptes distincts selon les rôles.

## Mot de passe

Utiliser au minimum :

```text
une phrase de passe longue et unique
```

Ne pas réutiliser le mot de passe :

- de l’hébergement ;
- de l’e-mail ;
- du compte GitHub ;
- d’un autre CMS.

---

# 21. Installer ou non le site d’exemple

## H2

```text
Choisir le contenu de démonstration
```

L’installateur peut proposer un site vitrine d’exemple.

## Installer l’exemple si

- vous découvrez FlatCMS ;
- vous souhaitez comprendre les pages, menus et widgets ;
- l’environnement est local ou de démonstration ;
- vous prévoyez de remplacer intégralement le contenu.

## Ne pas l’installer si

- le site de production doit partir d’une base vide ;
- le package d’exemple n’est pas compatible avec le thème choisi ;
- les contenus fictifs risquent d’être indexés ;
- vous avez déjà préparé un contenu réel.

## Production

Si un contenu d’exemple est installé :

- remplacer les textes ;
- remplacer les images ;
- supprimer les comptes de démonstration ;
- vérifier les liens ;
- vérifier les métadonnées ;
- vérifier les locales ;
- supprimer les données fictives ;
- empêcher leur indexation avant publication.

---

# 22. Finaliser l’installation

## H2

```text
Terminer et verrouiller le parcours d’installation
```

Après la dernière étape :

- vérifier que la configuration est enregistrée ;
- vérifier que le compte administrateur existe ;
- vérifier la redirection vers l’administration ou le site ;
- basculer le document root vers `public/` si nécessaire ;
- supprimer ou protéger les routes d’installation ;
- vider les caches ;
- relancer le serveur si la configuration a changé ;
- tester le frontend ;
- tester l’administration.

## Nginx

Le template propose un durcissement optionnel après installation :

```nginx
location ^~ /install/ {
    return 403;
}
```

Cette règle doit être adaptée à la route réelle et appliquée seulement lorsque l’installation est terminée.

---

# 23. Vérifier le frontend

## H2

```text
Contrôler le site public
```

Testez :

- page d’accueil ;
- styles CSS ;
- JavaScript ;
- images ;
- navigation ;
- page 404 ;
- URLs propres ;
- langue ;
- liens ;
- responsive ;
- HTTPS ;
- absence de message d’erreur.

## Symptômes fréquents

### CSS absent

Vérifier :

- document root ;
- base URL ;
- chemins des assets ;
- droits de lecture ;
- cache ;
- erreurs 404 ;
- proxy ou sous-dossier.

### Images absentes

Vérifier :

- chemin public ;
- permissions ;
- casse des noms ;
- fichiers réellement présents ;
- URL générée ;
- MIME type.

### Toutes les pages renvoient 404

Vérifier :

- `mod_rewrite` ;
- `.htaccess` ;
- `AllowOverride` ;
- `try_files` ;
- front controller ;
- base URL.

---

# 24. Vérifier l’administration

## H2

```text
Contrôler le premier accès administrateur
```

Testez :

- page de connexion ;
- connexion ;
- tableau de bord ;
- déconnexion ;
- création d’une page ;
- téléversement d’une image ;
- création d’un article ;
- menu ;
- paramètres ;
- utilisateurs ;
- logs ;
- messages de succès et d’erreur.

## Important

Ne testez pas seulement l’affichage.

Vérifiez qu’une modification :

1. est enregistrée ;
2. reste présente après rechargement ;
3. apparaît sur le frontend si elle doit y apparaître ;
4. respecte les permissions ;
5. ne produit pas d’erreur dans les logs.

---

# 25. Vérifier les logs

## H2

```text
Consulter les journaux avant de déclarer l’installation réussie
```

Sources possibles :

- log Apache ;
- log Nginx ;
- log PHP-FPM ;
- log PHP ;
- log FlatCMS ;
- log système ;
- log Web Station ;
- journal MAMP ou WAMP.

## Rechercher

```text
Fatal error
Uncaught
Permission denied
No such file
Class not found
JSON
write
open_basedir
500
404
```

## Production

Les erreurs détaillées doivent être journalisées côté serveur, pas affichées publiquement.

---

# 26. Sécuriser l’installation

## H2

```text
Appliquer les contrôles essentiels
```

## Document root

```text
public/
```

## Secrets

```text
hors du Web et hors du dépôt
```

## HTTPS

- certificat valide ;
- redirection HTTP vers HTTPS ;
- cookies sécurisés ;
- liens internes HTTPS.

## Permissions

- pas de `777` global ;
- écriture limitée ;
- comptes séparés ;
- sauvegardes privées.

## Administration

- mot de passe unique ;
- comptes nominatifs ;
- rôles minimaux ;
- protection contre les tentatives ;
- double authentification si disponible.

## Fichiers

Empêcher l’accès à :

```text
.env
.env.local
config/
data/
storage/
app/
resources/
backups/
logs/
```

## Installation

- désactiver ou protéger le parcours ;
- supprimer les fichiers temporaires ;
- ne pas conserver un compte ou mot de passe de démonstration.

## En-têtes

Adapter :

- `X-Content-Type-Options` ;
- politique de framing ;
- `Referrer-Policy` ;
- CSP ;
- HSTS après validation de HTTPS.

---

# 27. Effectuer une première sauvegarde

## H2

```text
Sauvegarder immédiatement l’installation fonctionnelle
```

Inclure :

- code déployé ;
- données JSON ;
- médias ;
- configuration privée ;
- version ;
- règles Apache ou Nginx ;
- paramètres PHP utiles ;
- tâches planifiées ;
- documentation de restauration.

## Vérifier

- l’archive s’ouvre ;
- les fichiers principaux sont présents ;
- les permissions peuvent être restaurées ;
- le secret est sauvegardé dans un emplacement protégé ;
- la sauvegarde n’est pas accessible publiquement.

## Nom recommandé

```text
flatcms-1.0.0-initial-2026-06-08.tar.gz
```

---

# 28. Tests finaux

## H2

```text
Valider l’installation
```

## Technique

- [ ] PHP compatible ;
- [ ] extensions validées ;
- [ ] document root `public/` ;
- [ ] frontend en `200 OK` ;
- [ ] administration accessible ;
- [ ] URLs propres ;
- [ ] fichiers sensibles inaccessibles ;
- [ ] écriture des données ;
- [ ] téléversement média ;
- [ ] logs sans erreur critique ;
- [ ] HTTPS en production ;
- [ ] installateur protégé.

## Fonctionnel

- [ ] connexion ;
- [ ] déconnexion ;
- [ ] création de page ;
- [ ] modification ;
- [ ] publication ;
- [ ] article ;
- [ ] média ;
- [ ] menu ;
- [ ] thème ;
- [ ] utilisateur ;
- [ ] e-mail si configuré ;
- [ ] sauvegarde.

## SEO

- [ ] title ;
- [ ] meta description ;
- [ ] canonique ;
- [ ] `robots.txt` ;
- [ ] sitemap ;
- [ ] page 404 réelle ;
- [ ] pas de `noindex` involontaire.

---

# 29. Erreurs fréquentes

## H2

```text
Résoudre les problèmes d’installation
```

---

## Erreur — Unable to write public/.htaccess

### Signification

FlatCMS ne peut pas créer ou modifier le fichier attendu.

### Vérifier

- existence de `public/` ;
- propriétaire ;
- groupe ;
- permissions du dossier ;
- permissions du fichier ;
- utilisateur Apache ou PHP-FPM ;
- ACL ;
- `open_basedir` ;
- système de fichiers en lecture seule.

### Important

Le droit d’écriture du dossier parent peut être nécessaire même si le fichier existe.

---

## Erreur — Unable to write nginx.conf template

### Vérifier

- emplacement réel ;
- droits du dossier parent ;
- compte PHP ;
- ACL ;
- protection de l’hébergeur ;
- package incomplet ;
- environnement ne nécessitant pas ce fichier.

### Prudence

Le template Nginx ne doit pas être activé sans adapter :

```text
root
server_name
fastcgi_pass
TLS
CSP
```

---

## Erreur — Module introuvable

### Exemple

```text
Module App\Modules\AiAgent introuvable
```

### Vérifier

- dossier du module ;
- casse exacte ;
- namespace ;
- fichier de classe ;
- autoloading ;
- manifeste ;
- archive complète ;
- antivirus ou extraction partielle ;
- cache ;
- version du package.

### Windows

La casse peut sembler tolérée par le système de fichiers local, mais elle doit rester conforme au code et aux environnements Linux.

---

## Erreur — 500 Internal Server Error

### Vérifier

- log serveur ;
- log PHP ;
- syntaxe PHP ;
- extension manquante ;
- droits ;
- chemin ;
- configuration Apache ou Nginx ;
- fichier JSON invalide ;
- cache ;
- version PHP.

### Ne pas faire

```text
modifier au hasard plusieurs fichiers
masquer l’erreur
activer 777 partout
réinstaller sans sauvegarder les logs
```

---

## Erreur — URLs propres indisponibles

### Apache

- `mod_rewrite` ;
- `AllowOverride` ;
- `.htaccess` ;
- VirtualHost ;
- base URL.

### Nginx

- `try_files` ;
- `root` ;
- `location = /index.php` ;
- PHP-FPM ;
- query string.

---

## Erreur — Permission denied

### Vérifier

- compte Apache/Nginx ;
- compte PHP-FPM ;
- propriétaire ;
- groupe ;
- ACL ;
- dossiers parents ;
- montage ;
- SELinux ou AppArmor ;
- `open_basedir`.

---

## Erreur — PHP not found

### Terminal

PHP n’est pas dans le `PATH`.

### Windows avec MAMP

Le binaire peut se trouver dans un chemin du type :

```text
C:\MAMP\bin\php\php8.x.x\php.exe
```

### macOS avec MAMP

Le binaire peut se trouver dans :

```text
/Applications/MAMP/bin/php/php8.x.x/bin/php
```

Adaptez le numéro de version réellement installé.

---

## Erreur — CSS ou JavaScript en 404

### Vérifier

- document root ;
- chemin `/public` ajouté deux fois ;
- base URL ;
- règles de réécriture ;
- chemins absolus ;
- sous-dossier ;
- cache ;
- permissions.

---

# 30. Installer en sous-dossier

## H2

```text
Utiliser un chemin comme /flatcms/
```

Exemple :

```text
https://example.com/flatcms/
```

Ce déploiement peut nécessiter :

- base URL correcte ;
- `RewriteBase` sous Apache ;
- `location` spécifique sous Nginx ;
- chemins d’assets ;
- URL canonique ;
- cookies ;
- redirections ;
- installateur ;
- liens générés.

## Recommandation

Pour un site principal, préférer un hôte ou sous-domaine dédié avec `public/` comme document root.

---

# 31. Installer derrière un proxy ou Cloudflare

## H2

```text
Conserver le bon schéma et la bonne adresse
```

Vérifiez :

- `X-Forwarded-Proto` ;
- adresse IP du visiteur ;
- HTTPS côté origine ;
- redirections ;
- cache ;
- règles WAF ;
- téléversements ;
- administration ;
- cookies ;
- taille maximale des requêtes.

## Cloudflare

- ne pas mettre en cache l’administration ;
- ne pas mettre en cache les formulaires ;
- vérifier les challenges ;
- autoriser les assets ;
- tester les téléversements ;
- utiliser un certificat valide côté origine.

---

# 32. Installer sur un hébergement mutualisé

## H2

```text
Vérifier les contraintes de l’hébergeur
```

Questions à poser :

- puis-je définir le document root sur `public/` ?
- quelle version PHP est disponible ?
- quelles extensions sont actives ?
- `.htaccess` est-il autorisé ?
- puis-je modifier `open_basedir` ?
- quelles permissions sont possibles ?
- puis-je créer des tâches cron ?
- quelle taille maximale pour les fichiers ?
- quels logs sont accessibles ?
- quelle méthode d’envoi d’e-mail est disponible ?

## Si le document root ne peut pas être modifié

Utilisez uniquement la méthode de compatibilité officiellement documentée par FlatCMS.

Ne déplacez pas manuellement des fichiers sans comprendre les conséquences sur :

- chemins ;
- sécurité ;
- mises à jour ;
- installateur ;
- assets ;
- stockage.

---

# 33. Après l’installation

## H2

```text
Étapes recommandées
```

1. mettre à jour le titre et la description ;
2. vérifier la langue et le fuseau ;
3. configurer l’e-mail ;
4. créer les comptes nécessaires ;
5. supprimer les comptes de test ;
6. choisir le thème ;
7. créer les pages ;
8. configurer le menu ;
9. vérifier le footer ;
10. configurer les sauvegardes ;
11. activer uniquement les modules utiles ;
12. vérifier les licences ;
13. configurer le SEO ;
14. tester la page 404 ;
15. préparer la mise en production.

---

# 34. Questions fréquentes éditoriales

> Ces questions n’impliquent pas automatiquement un balisage `FAQPage`.

## H2

```text
Questions fréquentes sur l’installation
```

### H3 — FlatCMS nécessite-t-il MySQL ?

```text
Non. Le LTS Core stocke ses données principales dans des fichiers
structurés et ne dépend pas d’un serveur MySQL ou MariaDB pour son
fonctionnement normal.
```

### H3 — Quelle version de PHP faut-il utiliser ?

```text
Le dépôt annonce PHP 8.3 ou une version ultérieure compatible. Utilisez
de préférence une branche encore prise en charge et vérifiée par la
version de FlatCMS installée.
```

### H3 — Dois-je pointer le serveur vers la racine du projet ?

```text
Non en production. Le document root recommandé est le dossier public/.
La racine peut être utilisée temporairement par le parcours d’installation
selon le contrat du package.
```

### H3 — Puis-je installer FlatCMS dans un sous-dossier ?

```text
Oui si le serveur, la base URL et les règles de réécriture sont
configurés pour ce chemin. Un hôte dédié reste généralement plus simple.
```

### H3 — Puis-je utiliser les permissions 777 ?

```text
Ce n’est pas recommandé. Accordez uniquement les droits de lecture et
d’écriture nécessaires au compte du serveur web.
```

### H3 — Pourquoi l’installateur reste-t-il bloqué à l’étape 1 ?

```text
Vérifiez les prérequis, la présence des modules, les chemins, les
permissions, la version PHP, les logs et le document root utilisé pendant
l’installation.
```

### H3 — Dois-je conserver l’installateur après la configuration ?

```text
Les routes et fichiers d’installation doivent être protégés ou désactivés
selon la procédure officielle de la version utilisée.
```

---

# 35. CTA final

## H2

```text
Installez FlatCMS dans un environnement contrôlé
```

## Texte

```text
Commencez en local, vérifiez le parcours complet, puis reproduisez la
configuration sur le serveur de production avec une sauvegarde et un
plan de retour arrière.
```

## CTA principal

```text
Télécharger FlatCMS
```

Destination :

```text
/fr-FR/telechargement/
```

## CTA secondaire

```text
Choisir un guide serveur
```

Destination :

```text
/fr-FR/documentation/installation/#environnements
```

## Lien tertiaire

```text
Ouvrir le centre de dépannage
```

Destination :

```text
/fr-FR/documentation/depannage/
```

---

# 36. Maillage interne attendu

| Section | Destination |
|---|---|
| Avant de commencer | Guides par environnement |
| Prérequis | Page Prérequis |
| Télécharger | Téléchargement |
| Document root | Guide Document root public |
| Apache | Guide Apache |
| Nginx | Guide Nginx |
| Permissions | Guide Permissions |
| Secrets | Configuration |
| Installateur | Référence installateur |
| Sécurité | Guide sécurité |
| Sauvegarde | Guide sauvegarde |
| Erreurs | Centre de dépannage |
| Après installation | Parcours administrateur |
| CTA final | Téléchargement, environnements, dépannage |

---

# 37. Médias à produire

## Image Open Graph

Concept :

```text
Archive FlatCMS
→ serveur PHP
→ dossier public/
→ installateur
→ site fonctionnel
```

## Captures

- page de téléchargement ;
- checksum ;
- arborescence extraite ;
- configuration document root ;
- première étape de l’installateur ;
- vérification des prérequis ;
- création du compte ;
- écran de finalisation ;
- frontend ;
- administration.

## Diagrammes

### Document root

```text
Web
→ public/

Privé
→ app/
→ config/
→ data/
→ storage/
```

### Cycle d’installation

```text
Télécharger
→ Vérifier
→ Extraire
→ Configurer
→ Permissions
→ Installer
→ Sécuriser
→ Sauvegarder
```

---

# 38. Textes alternatifs suggérés

## Arborescence

```text
Arborescence FlatCMS avec le dossier public exposé au Web
```

## Installateur

```text
Première étape de l’installateur FlatCMS
```

## Prérequis

```text
Écran de vérification de PHP, des extensions et des permissions
```

## Document root

```text
Serveur web pointant vers le dossier public de FlatCMS
```

Les textes doivent être adaptés aux captures finales.

---

# 39. Données structurées attendues

```text
WebPage
TechArticle
HowTo si la procédure complète et ordonnée est visible
BreadcrumbList
ImageObject
```

## Identifiants

```text
https://flat-cms.fr/fr-FR/documentation/installation/#webpage
https://flat-cms.fr/fr-FR/documentation/installation/#article
https://flat-cms.fr/fr-FR/documentation/installation/#breadcrumb
https://flat-cms.fr/fr-FR/documentation/installation/#primaryimage
```

## `HowTo`

Ne l’utiliser que si :

- les étapes sont visibles ;
- le résultat est défini ;
- les prérequis sont indiqués ;
- les outils ou fournitures ne sont pas inventés ;
- la durée éventuelle est réaliste.

---

# 40. Composants du thème suggérés

```text
HeroDocumentation
PrerequisitesChecklist
EnvironmentCards
InstallationSteps
CodeBlock
ExpectedResult
WarningCallout
DirectoryDiagram
ServerConfigurationTabs
TroubleshootingCards
FaqAccordion
CallToActionBanner
```

---

# 41. Éléments à confirmer avant publication

- liste exacte des extensions PHP ;
- versions PHP réellement testées ;
- étapes finales de l’installateur ;
- URL exacte en installation racine ;
- comportement après bascule vers `public/` ;
- dossiers exacts nécessitant l’écriture ;
- chemin réel des médias ;
- génération de `.htaccess` ;
- génération du template Nginx ;
- statut de l’alias `/install/` ;
- protection finale de l’installateur ;
- fonctionnement du site d’exemple ;
- nom des fichiers de configuration privés ;
- comportement MAMP et WAMP ;
- procédure Synology ;
- procédure Raspberry Pi ;
- messages d’erreur réels ;
- captures de la version finale.

---

# 42. Checklist éditoriale

- [ ] Le guide correspond au package final.
- [ ] PHP 8.3+ est confirmé.
- [ ] Les extensions sont validées.
- [ ] Le document root `public/` est central.
- [ ] Les deux phases d’installation sont expliquées.
- [ ] Les commandes indiquent le système concerné.
- [ ] Les chemins sont présentés comme exemples lorsqu’ils varient.
- [ ] Les permissions 777 sont déconseillées.
- [ ] Les secrets ne sont pas exposés.
- [ ] Le parcours de l’installateur est exact.
- [ ] Les erreurs fréquentes reprennent les messages exacts.
- [ ] Apache et Nginx sont distingués.
- [ ] Le guide n’annonce pas de compatibilité non testée.
- [ ] La sauvegarde finale est incluse.
- [ ] Les liens vers les guides spécialisés sont prévus.

---

# 43. Checklist d’intégration

- [ ] URL correcte.
- [ ] Canonique auto-référencée.
- [ ] `<html lang="fr-FR">`.
- [ ] Groupe `hreflang`.
- [ ] Title.
- [ ] Meta description.
- [ ] Open Graph.
- [ ] H1 unique.
- [ ] Sommaire.
- [ ] Blocs de code copiables.
- [ ] Libellés des systèmes.
- [ ] Avertissements accessibles.
- [ ] Liens HTML explorables.
- [ ] Images responsive.
- [ ] Textes alternatifs.
- [ ] JSON-LD.
- [ ] Sitemap.
- [ ] Directive robots.
- [ ] Test mobile.
- [ ] Test clavier.
- [ ] Test des liens.
- [ ] Test HTTP `200`.
- [ ] Test complet sur une archive vierge.

---

# 44. Sources internes

- `README.md`
- `VERSION`
- `nginx.conf`
- `flatcms.json`
- package FlatCMS v1.0.0 ;
- installateur réel ;
- fichiers `.htaccess` ;
- arborescence `public/` ;
- contrôles PHP ;
- contrôles de permissions ;
- guides MAMP, WAMP, Apache, Nginx, Synology et Raspberry Pi.

---

# 45. Références externes

- PHP — Supported Versions  
  https://www.php.net/supported-versions.php

- PHP Manual — Installation and Configuration  
  https://www.php.net/manual/en/install.php

- Apache HTTP Server — mod_rewrite  
  https://httpd.apache.org/docs/2.4/mod/mod_rewrite.html

- Apache HTTP Server — .htaccess files  
  https://httpd.apache.org/docs/2.4/howto/htaccess.html

- Nginx — `try_files`  
  https://nginx.org/en/docs/http/ngx_http_core_module.html#try_files

- Nginx — `fastcgi_pass`  
  https://nginx.org/en/docs/http/ngx_http_fastcgi_module.html#fastcgi_pass

Les sources externes encadrent PHP, Apache et Nginx. Le contrat
d’installation propre à FlatCMS reste défini par le package et le README
de la version distribuée.

---

# 46. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction complète du guide d’installation | ChatGPT / Alain BROYE |

---

# 47. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer DOWNLOAD_CONTENT.md
```

Ce document contiendra la rédaction complète de la page :

```text
/fr-FR/telechargement/
```

Il présentera la version stable, l’archive, le checksum, les prérequis,
la licence, les notes de version et les liens d’installation.
