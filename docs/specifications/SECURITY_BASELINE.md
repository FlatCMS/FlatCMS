# SECURITY_BASELINE — Socle de sécurité FlatCMS

> **Spécification de sécurité obligatoire**
>
> Projet : FlatCMS  
> Portée : LTS Core, site officiel `flat-cms.fr`, thème `flatcms`, administration, Builders, services de licences, téléchargements, démo et intégrations IA  
> Date : 8 juin 2026  
> Documents parents : `PREPRODUCTION_TEST_PLAN.md`, `THEME_SPECIFICATION.md`, `PRIVACY_CONTENT.md`, `CONTACT_CONTENT.md`, `404_CONTENT.md`, `BUILDER_LICENSE_ENFORCEMENT.md`  
> Référentiel principal : OWASP ASVS 5.0.0  
> Statut : baseline de référence pour Codex, les releases et les déploiements

---

# 1. Objet

Ce document définit le niveau minimal de sécurité attendu pour FlatCMS et
pour le futur site officiel.

Il ne constitue pas une certification.

Il sert à :

- fixer des exigences ;
- auditer le code ;
- auditer la configuration serveur ;
- guider les tests ;
- empêcher les régressions ;
- préparer les releases ;
- cadrer les services premium ;
- organiser la réponse aux incidents ;
- documenter les décisions de sécurité.

Le socle doit être appliqué selon une approche de défense en profondeur.

Aucune mesure isolée ne suffit à garantir la sécurité.

---

# 2. Référentiel

## 2.1 OWASP ASVS

La baseline utilise :

```text
OWASP ASVS 5.0.0
```

comme grille générale de vérification des contrôles techniques.

## 2.2 Niveau cible

Objectif minimal pour le site officiel et l’administration :

```text
ASVS niveau 2 comme cible de conception
```

Les fonctions à risque élevé peuvent nécessiter des exigences renforcées :

- paiement ;
- licences ;
- authentification ;
- administration ;
- upload ;
- sauvegardes ;
- IA avec outils ;
- chaîne de release ;
- gestion des secrets.

## 2.3 Identifiants

Lorsqu’une exigence ASVS est référencée, utiliser :

```text
v5.0.0-<chapitre>.<section>.<exigence>
```

afin d’éviter les ambiguïtés entre versions.

---

# 3. Principes directeurs

## 3.1 Moindre privilège

Chaque utilisateur, processus, service et fichier dispose uniquement des
droits nécessaires.

## 3.2 Deny by default

En l’absence d’autorisation explicite :

```text
refuser
```

## 3.3 Séparation des responsabilités

Séparer :

- frontend ;
- administration ;
- stockage ;
- services externes ;
- releases ;
- licences ;
- IA ;
- sauvegardes ;
- logs.

## 3.4 Validation serveur

Tout contrôle important doit être appliqué côté serveur.

JavaScript améliore l’expérience, mais ne protège pas une action.

## 3.5 Défense en profondeur

Combiner :

- validation ;
- autorisation ;
- échappement ;
- headers ;
- permissions ;
- journalisation ;
- limitation ;
- monitoring ;
- sauvegarde.

## 3.6 Échec sûr

En cas d’erreur :

- ne pas accorder un droit ;
- ne pas exposer un secret ;
- ne pas supprimer de données ;
- ne pas casser le frontend public ;
- retourner un message générique ;
- journaliser de manière filtrée.

---

# 4. Classification des actifs

## Critiques

- clés privées ;
- secrets d’environnement ;
- mots de passe ;
- sessions ;
- compte Super Admin ;
- système de release ;
- clés de signature ;
- sauvegardes ;
- paiement ;
- service de licences ;
- données personnelles ;
- outils IA pouvant agir.

## Importants

- contenus ;
- configurations JSON ;
- médias ;
- comptes éditeurs ;
- logs de sécurité ;
- e-mails ;
- paramètres ;
- thèmes ;
- modules.

## Publics

- pages publiées ;
- articles ;
- documentation ;
- package officiel ;
- checksums ;
- licences ;
- médias publics.

La classification détermine :

- accès ;
- stockage ;
- chiffrement ;
- journalisation ;
- rétention ;
- sauvegarde ;
- réponse aux incidents.

---

# 5. Modèle de menace

Le modèle doit inclure au minimum :

- visiteur anonyme malveillant ;
- compte utilisateur compromis ;
- éditeur malveillant ;
- administrateur compromis ;
- module tiers compromis ;
- dépendance compromise ;
- archive de release altérée ;
- fournisseur externe indisponible ;
- fournisseur externe compromis ;
- upload malveillant ;
- vol de session ;
- injection ;
- traversée de répertoire ;
- fuite de secret ;
- abus du système de licence ;
- prompt injection ;
- action IA non autorisée ;
- perte ou corruption de fichiers JSON ;
- attaque par déni de service.

## Revue

Le modèle doit être revu :

- avant une release majeure ;
- avant un nouveau service ;
- avant un paiement ;
- avant une IA avec outils ;
- après un incident ;
- après une modification d’architecture.

---

# 6. Architecture d’exposition

## Principe absolu

Le document root public doit être :

```text
project_root/public
```

et non :

```text
project_root
```

## Hors du document root

Doivent rester hors accès public direct :

```text
app/
config/
data/
storage/
resources/
vendor/
backups/
logs/
.env
.env.local
composer.json
composer.lock
nginx.conf
```

selon l’arborescence réelle.

## Test

Toute tentative HTTP vers ces chemins doit retourner :

```text
403
```

ou :

```text
404
```

sans contenu du fichier.

---

# 7. Point d’entrée PHP

## Nginx

Seul le front controller doit être exécuté :

```text
public/index.php
```

Les autres fichiers `.php` accessibles par URL doivent être refusés.

Exemple de principe :

```nginx
location = /index.php {
    # traitement PHP-FPM
}

location ~ \.php$ {
    return 404;
}
```

## Apache

Les règles doivent également empêcher l’exécution arbitraire d’un fichier
PHP ajouté dans :

- uploads ;
- médias ;
- cache ;
- stockage ;
- thèmes non approuvés.

## Invariant

Aucun média téléversé ne doit pouvoir devenir un script exécutable.

---

# 8. Configuration Nginx

Le template actuel doit être audité avant publication.

## Exigences

- `server_tokens off`;
- docroot `public/`;
- un seul point d’entrée PHP ;
- refus des fichiers cachés hors `.well-known`;
- refus des fichiers de configuration ;
- refus de `app`, `config`, `data`, `storage`, `vendor`;
- limites de requête ;
- timeouts ;
- headers ;
- logs ;
- TLS ;
- pages d’erreur sûres.

## Point d’attention CSP

La CSP actuelle autorise plusieurs domaines tiers et contient :

```text
style-src 'unsafe-inline'
```

Cette politique doit être réduite pour le site officiel.

La baseline recommandée est :

- ressources locales par défaut ;
- nonce ou hash pour les scripts nécessaires ;
- suppression progressive de `unsafe-inline`;
- tiers ajoutés uniquement après justification ;
- aucun wildcard large sans nécessité ;
- phase `Report-Only` avant enforcement.

## Point d’attention HSTS

Ne pas activer :

```text
preload
```

avant d’avoir confirmé :

- HTTPS sur tous les sous-domaines ;
- redirections correctes ;
- absence de sous-domaine HTTP nécessaire ;
- capacité opérationnelle à maintenir HTTPS ;
- volonté d’inscription dans la liste preload.

Une directive HSTS mal préparée peut rendre des sous-domaines
inaccessibles.

---

# 9. Configuration Apache

## Exigences

- `AllowOverride` maîtrisé ;
- réécriture limitée ;
- blocage des fichiers sensibles ;
- désactivation du listing ;
- refus d’exécution dans les uploads ;
- headers équivalents ;
- erreurs non verbeuses ;
- accès à `public/` uniquement.

## `.htaccess`

Si utilisé :

- versionné ;
- généré de manière sûre ;
- testé ;
- permissions minimales ;
- aucun secret ;
- aucune directive dépendant d’un module absent sans diagnostic.

---

# 10. TLS et HTTPS

## Exigences

- HTTPS obligatoire en production ;
- certificat valide ;
- chaîne complète ;
- renouvellement automatisé et surveillé ;
- redirection HTTP vers HTTPS ;
- aucun mixed content ;
- protocoles et suites modernes ;
- cookies `Secure`;
- liens absolus HTTPS.

## HSTS

Activer progressivement :

1. courte durée ;
2. surveillance ;
3. augmentation ;
4. `includeSubDomains` après validation ;
5. `preload` uniquement après décision explicite.

## Monitoring

Alerte avant expiration du certificat.

---

# 11. Headers de sécurité

Baseline recommandée, adaptée au contexte :

```text
Content-Security-Policy
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy
Strict-Transport-Security
Cross-Origin-Opener-Policy
Cross-Origin-Resource-Policy
X-Frame-Options ou frame-ancestors
```

## CSP minimale conceptuelle

```text
default-src 'self';
base-uri 'self';
object-src 'none';
frame-ancestors 'self';
form-action 'self';
script-src 'self' 'nonce-...';
style-src 'self';
img-src 'self' data: blob:;
font-src 'self';
connect-src 'self';
media-src 'self';
manifest-src 'self';
worker-src 'self' blob:;
```

Les besoins tiers doivent être ajoutés individuellement et documentés.

## Reporting

Avant enforcement strict :

```text
Content-Security-Policy-Report-Only
```

avec un endpoint protégé et une rétention limitée.

---

# 12. PHP

## Version

FlatCMS annonce :

```text
PHP 8.3+
```

Utiliser uniquement une branche PHP encore prise en charge par le projet
PHP et validée par les tests FlatCMS.

## Production

```ini
display_errors = Off
log_errors = On
expose_php = Off
```

## Limites

Définir :

- `memory_limit`;
- `max_execution_time`;
- `max_input_time`;
- `post_max_size`;
- `upload_max_filesize`;
- `max_file_uploads`;
- limites de variables.

## Sessions

Configurer :

```ini
session.use_strict_mode = 1
session.use_only_cookies = 1
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Lax
```

Adapter `SameSite` uniquement à un besoin démontré.

## Fonctions dangereuses

Ne pas baser toute la sécurité sur `disable_functions`.

Réduire l’usage applicatif des appels système et les isoler.

---

# 13. Permissions système

## Principe

Le serveur PHP ne doit pas pouvoir modifier l’ensemble du projet.

## Lecture seule recommandée

```text
app/
vendor/
resources/
public/assets compilés
templates du Core
```

## Écriture limitée

Uniquement les dossiers nécessaires :

```text
data/
storage/cache/
storage/logs/
public/uploads/ ou stockage média désigné
backups/ si local
```

## Valeurs indicatives

Dossiers :

```text
750 ou 755 selon utilisateur et groupe
```

Fichiers :

```text
640 ou 644
```

Secrets :

```text
600 ou 640
```

Les valeurs exactes dépendent du modèle d’hébergement.

## Interdiction

```text
chmod -R 777
```

ne constitue jamais une correction acceptable.

---

# 14. Propriétaire et groupe

Définir explicitement :

- utilisateur de déploiement ;
- utilisateur PHP-FPM ;
- groupe partagé ;
- utilisateur sauvegarde ;
- utilisateur cron.

## Objectif

- le code est déployé par un compte ;
- PHP écrit uniquement dans les zones autorisées ;
- les sauvegardes ne sont pas publiques ;
- les logs ne sont pas modifiables par un visiteur ;
- les secrets ne sont pas lisibles par tous.

---

# 15. Secrets

## Stockage

Les secrets doivent rester dans :

```text
.env.local
```

ou un gestionnaire de secrets.

## Interdictions

- dépôt Git ;
- archive publique ;
- JavaScript ;
- HTML ;
- JSON public ;
- capture ;
- log ;
- message d’erreur ;
- ticket non sécurisé ;
- prompt IA.

## Secrets concernés

- SMTP ;
- OpenAI ;
- paiement ;
- licence ;
- signature ;
- OAuth ;
- sauvegarde ;
- API ;
- webhook.

## Rotation

Prévoir :

- propriétaire ;
- date de création ;
- date de rotation ;
- usage ;
- révocation ;
- procédure d’urgence.

## Détection

La CI doit rechercher les secrets avant fusion et release.

---

# 16. Configuration

## Règle

La configuration non secrète peut être versionnée.

La configuration secrète ne doit pas l’être.

## Validation

Au démarrage :

- vérifier les valeurs requises ;
- refuser une configuration dangereuse ;
- afficher une erreur générique ;
- loguer un identifiant d’erreur ;
- ne jamais afficher la valeur secrète.

---

# 17. Authentification

## Mots de passe

Utiliser :

```php
password_hash()
password_verify()
password_needs_rehash()
```

avec l’algorithme recommandé par la version PHP utilisée.

## Exigences

- longueur minimale raisonnable ;
- longueur maximale acceptant les gestionnaires de mots de passe ;
- aucun troncage silencieux ;
- aucune règle complexe arbitraire ;
- comparaison sûre ;
- réhachage progressif ;
- mot de passe jamais logué.

## Blocage

Éviter un verrouillage permanent permettant un déni de service.

Utiliser :

- limitation progressive ;
- délai ;
- détection ;
- notification ;
- déverrouillage sécurisé.

---

# 18. Multifactor authentication

Pour les comptes privilégiés, recommander ou imposer :

- TOTP ;
- WebAuthn ;
- codes de récupération.

L’e-mail peut constituer une étape complémentaire, mais sa robustesse
dépend de la sécurité de la boîte.

## Comptes prioritaires

- Super Admin ;
- administrateur ;
- compte de release ;
- compte de paiement ;
- compte de licence.

---

# 19. Sessions

## Identifiant

- généré par le moteur sécurisé ;
- jamais dans l’URL ;
- jamais logué ;
- renouvelé à la connexion ;
- renouvelé après élévation de privilège ;
- invalidé à la déconnexion.

## Cookies

```text
Secure
HttpOnly
SameSite
Path adapté
Domain non élargi sans nécessité
```

## Durées

- expiration inactive ;
- expiration absolue ;
- sessions administratives plus courtes ;
- option « se souvenir » séparée et sécurisée.

## Invalidation

Permettre :

- déconnexion de la session courante ;
- déconnexion de toutes les sessions ;
- révocation après changement de mot de passe ;
- révocation après incident.

---

# 20. Autorisation

## Contrôle serveur

Chaque action vérifie :

- utilisateur ;
- rôle ;
- permission ;
- ressource ;
- propriété ;
- état ;
- contexte.

## Interdiction

Ne jamais se fier uniquement :

- au menu masqué ;
- au bouton absent ;
- à une route JavaScript ;
- à un champ hidden ;
- à un rôle transmis par le client.

## IDOR

Toute ressource identifiée par un ID doit être autorisée indépendamment
de la connaissance de cet ID.

---

# 21. Rôles

Rôles actuels ou prévus :

- Super Admin ;
- Admin ;
- Editor ;
- Demo ;
- Seller ;
- Customer.

## Matrice

Documenter pour chaque fonction :

```text
view
create
edit
publish
delete
restore
configure
install
activate
export
```

## Démo

Le rôle Demo doit être particulièrement limité :

- pas de secrets ;
- pas de modules ;
- pas de licences ;
- pas de comptes ;
- pas de serveur ;
- pas de données réelles ;
- reset régulier.

---

# 22. CSRF

Toutes les actions modifiant un état doivent être protégées.

## Exemples

- connexion si risque applicable ;
- profil ;
- contenu ;
- suppression ;
- publication ;
- licence ;
- upload ;
- module ;
- thème ;
- paramètres ;
- contact authentifié ;
- compte ;
- paiement.

## Mécanisme

Préférer :

```text
Synchronizer Token Pattern
```

pour une application basée sur session.

## Défense complémentaire

- cookies `SameSite`;
- validation `Origin` ou `Referer` adaptée ;
- Fetch Metadata si compatible ;
- confirmation utilisateur pour action sensible.

## Règle

`SameSite` ne remplace pas systématiquement un token CSRF.

---

# 23. Validation des entrées

## Principe

Valider selon le type et le contexte.

## Contrôles

- type ;
- longueur ;
- format ;
- valeurs autorisées ;
- encodage ;
- plage ;
- cohérence métier ;
- taille.

## Liste blanche

Utiliser une liste de valeurs autorisées pour :

- locale ;
- rôle ;
- statut ;
- thème ;
- module ;
- variante ;
- méthode ;
- format ;
- extension.

## Données complexes

Les structures JSON doivent être validées contre :

- schéma ;
- version ;
- propriétés requises ;
- propriétés supplémentaires ;
- profondeur ;
- taille.

---

# 24. Encodage des sorties

L’échappement dépend du contexte :

- HTML ;
- attribut HTML ;
- URL ;
- JavaScript ;
- CSS ;
- JSON ;
- e-mail ;
- shell.

## Règle

Ne pas créer une fonction universelle `sanitize()` censée convenir à
tous les contextes.

## Templates

L’échappement doit être activé par défaut.

Le HTML autorisé doit passer par un nettoyeur explicite.

---

# 25. XSS et contenu riche

## SunEditor

Le HTML produit doit être filtré côté serveur.

## Autoriser explicitement

- balises ;
- attributs ;
- protocoles ;
- classes ;
- embeds.

## Interdire

- scripts ;
- handlers `on*`;
- `javascript:`;
- iframes non autorisées ;
- objets ;
- styles dangereux ;
- SVG arbitraire non nettoyé.

## Prévisualisation

La preview ne doit pas contourner le nettoyage appliqué au rendu public.

---

# 26. URLs et redirections

## Validation

- protocoles autorisés ;
- domaine ;
- chemin ;
- longueur ;
- caractères ;
- URL relative ou absolue selon fonction.

## Redirections

Ne jamais rediriger vers une URL fournie sans validation.

Pour les redirections internes :

- chemins locaux ;
- liste autorisée ;
- prévention des boucles.

## Liens externes

Protéger si nouvel onglet :

```text
noopener
noreferrer selon politique
```

---

# 27. Fichiers JSON

## Risques

- corruption ;
- concurrence ;
- traversée ;
- injection ;
- écrasement ;
- divulgation ;
- lien symbolique ;
- fichier partiel.

## Exigences

- chemin construit depuis un identifiant validé ;
- aucun chemin fourni directement par l’utilisateur ;
- verrouillage ;
- écriture atomique ;
- fichier temporaire ;
- `rename` final ;
- sauvegarde ;
- validation après écriture ;
- taille maximale ;
- permissions.

## Écriture atomique conceptuelle

1. sérialiser ;
2. écrire dans un fichier temporaire du même volume ;
3. `fsync` si nécessaire ;
4. valider ;
5. remplacer atomiquement ;
6. conserver une version ou sauvegarde.

---

# 28. Traversée de répertoire

Refuser :

```text
../
..\
encodages équivalents
chemins absolus
liens symboliques non autorisés
```

## Résolution

Comparer le chemin réel à une racine autorisée.

Ne pas se fier uniquement à une expression régulière.

---

# 29. Uploads

## Approche

Défense en profondeur.

## Exigences

- utilisateur autorisé ;
- CSRF ;
- limite de taille ;
- liste d’extensions ;
- MIME vérifié côté serveur ;
- signature vérifiée si possible ;
- nom généré ;
- caractères contrôlés ;
- stockage hors webroot lorsque possible ;
- aucune exécution ;
- traitement sécurisé ;
- quota ;
- antivirus ou sandbox si disponible ;
- journalisation.

## Images

Réencoder les images acceptées lorsque possible.

## SVG

Le SVG est du contenu actif.

Options :

- interdire ;
- nettoyer avec une bibliothèque fiable ;
- convertir ;
- servir avec politique restrictive.

## ZIP

Éviter les ZIP provenant d’utilisateurs.

Si nécessaires :

- taille décompressée ;
- nombre de fichiers ;
- profondeur ;
- traversée ;
- type ;
- quota ;
- extraction isolée.

---

# 30. Médias publics

## Servir

- MIME correct ;
- `nosniff`;
- nom sûr ;
- cache ;
- Content-Disposition selon usage ;
- aucune exécution ;
- SVG contrôlé.

## Privés

Les médias privés doivent passer par un contrôleur d’autorisation, pas
par une URL publique prévisible.

---

# 31. Modules et thèmes

## Installation

Avant installation :

- manifeste ;
- signature ;
- version ;
- compatibilité ;
- licence ;
- structure ;
- chemins ;
- fichiers ;
- permissions ;
- origine.

## Interdictions

- archive avec traversée ;
- fichier hors du dossier cible ;
- exécution pendant extraction ;
- remplacement du Core sans autorisation ;
- symlink ;
- script de post-install non contrôlé.

## Marketplace

Les signatures ne remplacent pas l’audit du contenu.

---

# 32. Dépendances

## Composer

- fichier lock ;
- installation reproductible ;
- source officielle ;
- aucune dépendance abandonnée sans décision ;
- audit ;
- mise à jour contrôlée ;
- licence.

## JavaScript

- dépendances locales ;
- versions verrouillées ;
- aucune dépendance CDN critique ;
- audit ;
- arbre minimal.

## SBOM

Générer un inventaire de composants pour les releases lorsque possible.

---

# 33. Chaîne de release

## Séparation

Le dépôt runtime et la chaîne de packaging restent distincts.

## Release officielle

- build propre ;
- source identifiée ;
- version ;
- tests ;
- package ;
- checksum ;
- signature si prévue ;
- notes ;
- publication.

## Clés

Les clés de signature ne doivent pas être présentes dans :

- dépôt ;
- runner non protégé ;
- archive ;
- logs.

## Immutabilité

Une release publiée ne doit pas être silencieusement remplacée.

Publier une nouvelle version si le contenu change.

---

# 34. Téléchargements

## Page officielle

Afficher :

- version ;
- taille ;
- checksum ;
- date ;
- licence ;
- prérequis.

## Fichier

- nom stable ;
- MIME ;
- checksum ;
- HTTPS ;
- stockage contrôlé ;
- aucun script serveur ;
- logs limités.

## Vérification

Codex doit comparer l’archive publiée au checksum affiché.

---

# 35. Builders et licences

Le système doit respecter :

```text
BUILDER_LICENSE_ENFORCEMENT.md
```

## Invariant critique

Aucun état de licence ne doit :

- supprimer ;
- masquer ;
- altérer ;
- empêcher ;

le frontend déjà construit.

## API de licence

- HTTPS ;
- timeout ;
- cache ;
- backoff ;
- authentification ;
- réponse signée si retenue ;
- données minimales ;
- aucune donnée de contenu ;
- aucun secret dans les logs.

## Rendu public

Aucun appel au service de licence dans le chemin critique.

---

# 36. Paiement

Si activé :

- prestataire spécialisé ;
- aucune carte complète stockée ;
- webhooks authentifiés ;
- vérification de signature ;
- idempotence ;
- montant recalculé côté serveur ;
- devise vérifiée ;
- produit vérifié ;
- événement journalisé ;
- protection contre le rejeu.

## Webhook

Ne jamais accorder une licence sur la seule base d’un paramètre client.

---

# 37. Formulaire de contact

Appliquer :

- validation serveur ;
- CSRF ;
- limitation ;
- honeypot ;
- encodage ;
- protection des headers e-mail ;
- taille ;
- absence de pièce jointe au lancement ;
- stockage limité ;
- rétention ;
- information RGPD.

## E-mail

Les valeurs utilisateur ne doivent pas contrôler :

- destinataire ;
- CC ;
- BCC ;
- From ;
- headers.

---

# 38. E-mails

## SMTP

- TLS ;
- secret serveur ;
- timeout ;
- erreurs filtrées ;
- rate limiting ;
- journal d’état ;
- SPF ;
- DKIM ;
- DMARC.

## Contenu

- HTML nettoyé ;
- version texte ;
- liens officiels ;
- aucune clé ;
- aucune donnée inutile ;
- aucun mot de passe en clair.

---

# 39. Journalisation

## Journaliser

- authentification ;
- échecs ;
- changements de rôle ;
- actions administratives ;
- licence ;
- modules ;
- upload ;
- sauvegarde ;
- restauration ;
- release ;
- incident ;
- paiement ;
- IA sensible.

## Ne pas journaliser

- mot de passe ;
- session complète ;
- token ;
- clé API ;
- carte ;
- données sensibles ;
- contenu complet non nécessaire.

## Protection

- accès restreint ;
- rotation ;
- rétention ;
- intégrité ;
- horodatage ;
- fuseau ;
- corrélation.

---

# 40. Gestion des erreurs

## Public

Afficher :

- message générique ;
- identifiant de corrélation ;
- action utile.

## Logs

Conserver le détail utile sans secret.

## Interdictions

- stack trace publique ;
- chemin absolu ;
- version interne ;
- configuration ;
- requête contenant un secret ;
- dump de session.

## 404

Véritable statut `404`.

## 500

Véritable statut `500`.

---

# 41. Rate limiting

Appliquer selon la fonction :

- login ;
- reset ;
- contact ;
- recherche ;
- API ;
- upload ;
- licence ;
- IA ;
- paiement ;
- export.

## Stratégie

Combiner selon le cas :

- compte ;
- IP ;
- session ;
- clé ;
- action ;
- fenêtre ;
- coût.

## Réponse

Utiliser :

```text
429 Too Many Requests
```

avec `Retry-After` si pertinent.

---

# 42. Déni de service

## Limites

- taille requête ;
- JSON ;
- profondeur ;
- upload ;
- image ;
- archive ;
- pagination ;
- recherche ;
- génération IA ;
- export ;
- nombre de widgets.

## Timeouts

Configurer :

- proxy ;
- serveur ;
- PHP ;
- HTTP client ;
- IA ;
- SMTP ;
- licence.

## Filesystem

Protéger contre :

- remplissage ;
- fichiers temporaires ;
- logs sans rotation ;
- sauvegardes illimitées ;
- uploads massifs.

---

# 43. Cache

## Public

Ne jamais mettre en cache publiquement :

- administration ;
- profil ;
- checkout ;
- licence ;
- données privées ;
- réponse contenant un token ;
- formulaire personnalisé.

## Clés

Le cache doit inclure les dimensions nécessaires :

- locale ;
- thème ;
- rôle si contenu privé ;
- URL ;
- version.

## Poisoning

Valider les headers et paramètres utilisés dans la clé.

---

# 44. Sauvegardes

## Contenu

- données ;
- médias ;
- configuration ;
- code de version ;
- informations de restauration.

## Secrets

Décider explicitement si les secrets sont :

- exclus et sauvegardés séparément ;
- inclus dans une sauvegarde chiffrée.

## Exigences

- chiffrement si stockage externe ;
- accès restreint ;
- rotation ;
- suppression ;
- test de restauration ;
- journalisation ;
- copie hors site selon besoin.

## Public

Aucune archive dans un dossier accessible par URL.

---

# 45. Restauration

Une sauvegarde n’est validée qu’après restauration testée.

## Vérifier

- intégrité ;
- permissions ;
- comptes ;
- médias ;
- URLs ;
- licences ;
- secrets ;
- cache ;
- version ;
- absence de fichier malveillant restauré.

---

# 46. Tâches planifiées

## Sécurité

- compte dédié ;
- environnement minimal ;
- chemins absolus ;
- verrouillage ;
- idempotence ;
- timeout ;
- logs ;
- alerte ;
- aucune sortie de secret.

## Exécution

Ne pas déclencher une tâche sensible par une URL publique non protégée.

---

# 47. Administration

## URL

La dissimulation de l’URL ne constitue pas une protection.

## Exigences

- authentification ;
- autorisation ;
- CSRF ;
- sessions ;
- HTTPS ;
- logs ;
- limitation ;
- 2FA privilégiée ;
- noindex ;
- cache privé ;
- messages génériques.

## Actions critiques

Confirmation renforcée pour :

- suppression ;
- rôle ;
- module ;
- thème ;
- licence ;
- sauvegarde ;
- restauration ;
- clé ;
- paiement.

---

# 48. Démo

## Isolation

La démo ne doit pas partager :

- secrets de production ;
- données ;
- clés ;
- stockage ;
- compte admin ;
- système de paiement réel.

## Reset

- automatique ;
- journalisé ;
- vérifié ;
- données fictives ;
- uploads supprimés ;
- comptes réinitialisés.

## Rôle Demo

Limitations strictes et contrôles serveur.

---

# 49. IA

## Principes

- provider abstrait ;
- secrets côté serveur ;
- permissions ;
- outils en liste blanche ;
- validation des paramètres ;
- confirmation humaine ;
- limite de coût ;
- logs filtrés ;
- résilience ;
- aucun impact sur le frontend.

## Prompt injection

Traiter tout contenu externe comme non fiable.

Ne jamais permettre à un texte de contenu de :

- changer les permissions ;
- révéler un secret ;
- activer un outil ;
- contourner une confirmation ;
- modifier une règle système.

## Outils

Chaque outil doit avoir :

- nom ;
- but ;
- schéma ;
- permission ;
- portée ;
- confirmation ;
- timeout ;
- journalisation ;
- résultat validé.

## Publication

Par défaut :

```text
brouillon
```

et non publication automatique.

---

# 50. RAG et documents

Si une recherche documentaire IA est utilisée :

- indexer uniquement les documents autorisés ;
- séparer public et privé ;
- conserver la source ;
- limiter les données ;
- filtrer les instructions malveillantes ;
- citer les documents ;
- contrôler la fraîcheur ;
- permettre la suppression.

---

# 51. Services externes

## Inventaire

Maintenir une liste :

- fournisseur ;
- fonction ;
- données ;
- secrets ;
- domaine ;
- timeout ;
- fallback ;
- contrat ;
- localisation.

## Indisponibilité

Tout service optionnel doit échouer sans casser :

- frontend ;
- administration essentielle ;
- contenus ;
- sauvegarde.

---

# 52. Webhooks

## Exigences

- signature ;
- timestamp ;
- anti-rejeu ;
- taille ;
- type ;
- idempotence ;
- allowlist réseau facultative ;
- log filtré ;
- réponse rapide ;
- traitement asynchrone si lourd.

## Secrets

Un secret webhook doit être rotatif.

---

# 53. APIs

## Contrat

- HTTPS ;
- authentification ;
- autorisation ;
- schéma ;
- taille ;
- version ;
- rate limit ;
- erreurs ;
- logs ;
- CORS.

## CORS

Ne pas utiliser :

```text
Access-Control-Allow-Origin: *
```

avec des credentials.

Définir une liste d’origines si l’API doit être appelée par navigateur.

---

# 54. Cookies

## Session

- `Secure`;
- `HttpOnly`;
- `SameSite`;
- nom non révélateur ;
- portée minimale ;
- durée.

## Consentement

Les cookies non nécessaires sont bloqués avant choix.

## Préfixes

Envisager :

```text
__Host-
```

pour un cookie compatible avec les contraintes nécessaires.

---

# 55. CSP et scripts tiers

## Règle

Chaque tiers doit être justifié.

## Inventaire actuel à revoir

Le template Nginx mentionne notamment :

- Cloudflare challenge ;
- Axeptio ;
- TinyMCE/Tiny Cloud ;
- Google Maps.

Le site officiel ne doit pas autoriser ces domaines par défaut si la page
ne les utilise pas.

## Stratégie

- CSP par application ou zone ;
- frontend public minimal ;
- administration séparée si besoins différents ;
- nonce/hash ;
- rapport ;
- suppression des sources obsolètes.

---

# 56. Polices

Préférer :

- polices système ;
- polices auto-hébergées ;
- sous-ensembles ;
- fichiers versionnés.

Éviter une requête à un fournisseur de polices externe sans nécessité.

---

# 57. Images externes

Éviter :

```text
img-src https:
```

trop large pour le site officiel.

Préférer :

- `self`;
- domaines précis ;
- proxy média si nécessaire ;
- CSP adaptée.

---

# 58. Données personnelles

## Minimisation

Collecter uniquement les données nécessaires.

## Protection

- accès ;
- rétention ;
- suppression ;
- export ;
- journalisation ;
- sous-traitants ;
- violation.

## Logs

L’adresse IP et les identifiants peuvent être des données personnelles.

La sécurité doit rester compatible avec la politique de confidentialité.

---

# 59. Incident de sécurité

## Phases

1. détection ;
2. qualification ;
3. confinement ;
4. préservation des preuves ;
5. correction ;
6. restauration ;
7. notification ;
8. analyse post-incident.

## Canal

Prévoir :

```text
security@flat-cms.fr
```

uniquement lorsque la boîte est active et surveillée.

## `security.txt`

Publier :

```text
/.well-known/security.txt
```

avec une date d’expiration maintenue.

---

# 60. Classification des incidents

## Critique

- exécution de code ;
- fuite de secrets ;
- compte admin compromis ;
- package officiel compromis ;
- paiement compromis ;
- données personnelles massives.

## Élevé

- élévation de privilège ;
- accès non autorisé ;
- upload actif ;
- contournement de licence avec impact serveur ;
- action IA non autorisée.

## Modéré

- XSS limité ;
- CSRF limité ;
- information non sensible ;
- DoS partiel.

## Faible

- header manquant sans exploitation directe ;
- information mineure ;
- durcissement.

---

# 61. Vulnérabilités

## Réception

- accusé ;
- référence ;
- confidentialité ;
- triage ;
- reproduction ;
- communication.

## Divulgation

Définir une politique responsable.

Ne pas promettre :

- bounty ;
- délai fixe ;
- protection juridique absolue ;

sans programme formalisé.

---

# 62. Mises à jour de sécurité

## Processus

- identifier ;
- prioriser ;
- corriger ;
- tester ;
- publier ;
- documenter ;
- notifier ;
- surveiller.

## Advisory

Pour une vulnérabilité importante :

- versions affectées ;
- impact ;
- correction ;
- mitigation ;
- crédit avec accord ;
- CVE si pertinent.

---

# 63. CI/CD

## Contrôles

- lint ;
- tests ;
- analyse statique ;
- secrets ;
- dépendances ;
- licences ;
- archive ;
- checksums ;
- fichiers sensibles ;
- manifestes ;
- signature.

## Branches

- protection ;
- revue ;
- statut CI ;
- interdiction de force push selon politique ;
- moindre privilège des tokens.

## Runners

- isolation ;
- secrets limités ;
- images à jour ;
- nettoyage ;
- logs filtrés.

---

# 64. Revue de code

## Revue renforcée

Requise pour :

- authentification ;
- autorisation ;
- upload ;
- fichiers ;
- licences ;
- paiement ;
- IA ;
- release ;
- crypto ;
- sessions ;
- middleware.

## Checklist

- validation ;
- sortie ;
- permission ;
- erreur ;
- log ;
- secret ;
- concurrence ;
- test ;
- migration.

---

# 65. Tests automatisés prioritaires

```text
testPublicDocrootOnly
testSensitiveFilesAreNotPublic
testOnlyFrontControllerExecutesPhp
testSessionCookieIsSecure
testSessionIdRotatesAfterLogin
testCsrfProtectsStateChanges
testAuthorizationIsServerSide
testJsonPathTraversalIsRejected
testJsonWritesAreAtomic
testUploadsCannotExecute
testSvgIsRejectedOrSanitized
testSecretsNeverReachLogs
testCspBlocksUnapprovedSources
testBuilderExpirationNeverBreaksFrontend
testLicenseApiTimeoutDoesNotBreakFrontend
testAiToolsRequirePermission
testAiSensitiveActionsRequireConfirmation
testReleaseChecksumMatchesArtifact
testBackupIsNotPublic
testRestoreProcedureWorks
```

---

# 66. Tests manuels prioritaires

- tentative d’accès à `.env.local`;
- tentative d’accès à `data/`;
- upload `.php`;
- double extension ;
- SVG actif ;
- traversée ;
- modification de rôle ;
- action sans CSRF ;
- session volée simulée ;
- reset de mot de passe ;
- erreur volontaire ;
- API licence indisponible ;
- prompt injection ;
- restauration ;
- rollback.

---

# 67. Audit demandé à Codex

## Inventaire

```text
Contrôle
Fichier
Route
Configuration
Statut
Test
Risque
```

## Écart

```text
ID
Exigence
Comportement actuel
Impact
Sévérité
Correction
Test
```

## Confirmations critiques

- [ ] Le docroot est `public/`.
- [ ] Les fichiers sensibles sont inaccessibles.
- [ ] Seul `index.php` est exécutable par URL.
- [ ] Les uploads ne sont pas exécutables.
- [ ] Les secrets sont hors dépôt.
- [ ] Les sessions sont durcies.
- [ ] Les permissions sont contrôlées côté serveur.
- [ ] Les actions d’état sont protégées par CSRF.
- [ ] Les écritures JSON sont atomiques.
- [ ] La CSP est adaptée à chaque zone.
- [ ] Les logs ne contiennent pas de secrets.
- [ ] Les Builders restent visibles après expiration.
- [ ] L’IA ne peut pas agir sans permission.
- [ ] Les releases ont checksum.
- [ ] Les sauvegardes sont privées et restaurables.
- [ ] Le plan d’incident est opérationnel.

---

# 68. Critères de rejet d’une release

Une release est rejetée si :

- un secret est présent ;
- un fichier sensible est public ;
- une action admin est accessible sans autorisation ;
- un upload peut exécuter du code ;
- le frontend casse après expiration ;
- le package ne correspond pas au checksum ;
- une sauvegarde est publiquement accessible ;
- une erreur expose une stack trace ;
- une régression P0 est ouverte ;
- la restauration n’est pas possible.

---

# 69. Matrice de responsabilité

| Domaine | Responsable | Validation |
|---|---|---|
| Code Core | Mainteneur | Tests + revue |
| Serveur | Exploitant | Audit config |
| Thème | Développeur thème | Tests frontend |
| Builders | Produit premium | Contrat licence |
| Paiement | Prestataire + intégration | Sandbox |
| IA | Admin du site | Permissions et coûts |
| Sauvegardes | Exploitant | Restauration |
| Incident | Responsable sécurité | Procédure |
| Release | Mainteneur | Artefact et checksum |

À adapter à la structure juridique et à l’équipe réelles.

---

# 70. Priorités de mise en œuvre

## P0 avant lancement

- docroot public ;
- fichiers sensibles ;
- HTTPS ;
- secrets ;
- authentification ;
- sessions ;
- autorisation ;
- CSRF ;
- XSS ;
- upload ;
- logs ;
- sauvegardes ;
- 404/500 ;
- licences ;
- release ;
- monitoring.

## P1

- CSP stricte ;
- 2FA renforcée ;
- SBOM ;
- signature ;
- sécurité IA avancée ;
- scan upload ;
- automatisation d’audit.

## P2

- programme de divulgation mature ;
- bug bounty ;
- attestations ;
- durcissement avancé ;
- tests externes récurrents.

---

# 71. Checklist serveur

- [ ] Docroot `public/`.
- [ ] Listing désactivé.
- [ ] Seul index.php exécuté.
- [ ] Fichiers cachés refusés.
- [ ] Configs refusées.
- [ ] App/data/vendor refusés.
- [ ] TLS.
- [ ] Headers.
- [ ] Timeouts.
- [ ] Limites.
- [ ] Logs.
- [ ] Rotation.
- [ ] Permissions.
- [ ] Certificat surveillé.
- [ ] HSTS validé.

---

# 72. Checklist application

- [ ] Validation.
- [ ] Encodage.
- [ ] Auth.
- [ ] Autorisation.
- [ ] Sessions.
- [ ] CSRF.
- [ ] Upload.
- [ ] JSON atomique.
- [ ] Erreurs.
- [ ] Logs.
- [ ] Rate limits.
- [ ] Secrets.
- [ ] Modules.
- [ ] Thèmes.
- [ ] Builders.
- [ ] IA.
- [ ] Sauvegardes.

---

# 73. Checklist release

- [ ] Version.
- [ ] Tests.
- [ ] Analyse statique.
- [ ] Secrets.
- [ ] Dépendances.
- [ ] Licences.
- [ ] Package.
- [ ] Checksum.
- [ ] Signature si prévue.
- [ ] Notes.
- [ ] Rollback.
- [ ] Monitoring.
- [ ] Advisory si nécessaire.

---

# 74. Références internes

- `PREPRODUCTION_TEST_PLAN.md`
- `BUILDER_LICENSE_ENFORCEMENT.md`
- `CONTACT_CONTENT.md`
- `PRIVACY_CONTENT.md`
- `404_CONTENT.md`
- `AGENT_READY_CONTENT.md`
- `DOWNLOAD_CONTENT.md`
- `LICENSING_CONTENT.md`
- `nginx.conf`
- `README.md`
- code du routeur ;
- middleware ;
- services ;
- installateur ;
- module Media ;
- module Auth ;
- module AiAgent.

---

# 75. Références externes

- OWASP Application Security Verification Standard 5.0.0  
  https://owasp.org/www-project-application-security-verification-standard/

- OWASP Session Management Cheat Sheet  
  https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html

- OWASP Cross-Site Request Forgery Prevention Cheat Sheet  
  https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html

- OWASP File Upload Cheat Sheet  
  https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html

- OWASP Secrets Management Cheat Sheet  
  https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html

- OWASP AI Agent Security Cheat Sheet  
  https://cheatsheetseries.owasp.org/cheatsheets/AI_Agent_Security_Cheat_Sheet.html

- MDN Content Security Policy  
  https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CSP

- PHP Supported Versions  
  https://www.php.net/supported-versions.php

---

# 76. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première baseline de sécurité FlatCMS | ChatGPT / Alain BROYE |

---

# 77. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer BENCHMARK_PROTOCOL.md
```

Ce document définira un protocole reproductible pour mesurer FlatCMS
sans slogan invérifiable :

- environnement ;
- versions ;
- scénarios ;
- cache froid et chaud ;
- pages ;
- concurrence ;
- CPU ;
- mémoire ;
- temps de réponse ;
- poids ;
- comparaison ;
- publication des résultats.
