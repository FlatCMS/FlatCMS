# SiteBackup portable v3

## Statut

Document de conception pour la branche `feature/sitebackup-portable-v3`.

Le chantier est développé exclusivement dans `FlatCMS-SiteBackup-Lab` jusqu'à validation complète.
Le dépôt principal FlatCMS v2.0.0 reste la référence stable et ne doit pas être modifié pendant les essais.

## Objectif produit

SiteBackup v3 doit permettre de reproduire un site FlatCMS finalisé sur une installation FlatCMS vierge.

Contrat fonctionnel :

```text
FlatCMS vierge compatible + SiteBackup portable = site reproduit
```

Le serveur cible fournit le moteur FlatCMS et son environnement.
L'archive fournit tout ce qui transforme ce moteur vierge en site livré au client.

Le cas nominal est la livraison agence / StudioFlatCMS sans accès à l'infrastructure finale du client.

## Rôles des sauvegardes

### SiteBackup

Sauvegarde portable du site.

Elle sert à :
- migrer local vers production ;
- livrer un site agence vers client ;
- reproduire une préproduction validée ;
- devenir à terme un format de sortie de StudioFlatCMS ;
- restaurer le contenu et les personnalisations d'un site sur un Core sain.

### FullBackup

Sauvegarde système / reprise après sinistre.

Elle reste destinée à :
- UpdateManager ;
- recovery.php ;
- retour à un état technique précédent ;
- reprise complète d'une instance endommagée.

SiteBackup ne remplace pas FullBackup et FullBackup ne devient pas le format de livraison utilisateur.

## Principe de séparation

SiteBackup v3 transporte les éléments appartenant au site, jamais les éléments appartenant au serveur cible.

### Le serveur cible conserve

- le Core FlatCMS fourni par package.zip ou flatcms.zip ;
- les modules natifs livrés avec cette version du Core ;
- les thèmes natifs livrés avec cette version du Core ;
- la configuration du serveur web ;
- les fichiers Apache, Nginx ou IIS générés pour l'environnement cible ;
- les chemins locaux du serveur ;
- les caches, logs, sessions, verrous et fichiers temporaires ;
- les artefacts UpdateManager et Recovery.

### SiteBackup restaure

- les données fonctionnelles ;
- les contenus éditoriaux ;
- les utilisateurs et rôles ;
- les réglages applicatifs transportables ;
- les médias publics ;
- les avatars ;
- les pièces jointes privées du module Contact ;
- les traductions personnalisées stockées dans data ;
- les thèmes ajoutés au Core ;
- les modules ajoutés au Core ;
- les extensions ;
- les plugins ;
- les assets de ces composants ;
- les licences nécessaires à ces composants ;
- les secrets applicatifs transportables, protégés par la clé compagnon.

## Version d'archive

Le format conserve :

- `kind = flatcms-site-backup`
- `version = 3`

Les restaurations v1 et v2 restent supportées.

Une archive v3 doit contenir un manifeste strict et un inventaire SHA-256 de chaque fichier restaurable.

La restauration doit refuser :
- les chemins relatifs dangereux ;
- les liens symboliques ;
- les entrées non déclarées ;
- les fichiers dont taille ou hash ne correspondent pas au manifeste ;
- les composants tentant d'écrire hors de leurs racines autorisées.

## Compatibilité Core

Pour le premier prototype v3, la restauration portable exige la même version FlatCMS source et cible.

Exemple :

`2.0.0 -> 2.0.0` : autorisé.

La compatibilité inter-version pourra être ouverte plus tard avec des migrations explicites.

## Baseline du Core

La décision « natif ou ajouté » ne peut pas reposer uniquement sur :
- `official` ;
- `origin` ;
- `vendor` ;
- `required`.

Un addon Marketplace officiel peut être `official: true` et `origin: flatcms` sans être livré dans le Core.

FlatCMS doit donc disposer d'une baseline explicite des composants fournis par le package de la version installée.

Cette baseline doit couvrir au minimum :
- modules natifs ;
- thèmes frontend natifs ;
- thèmes admin natifs.

Tout composant présent sur le site source mais absent de la baseline Core est candidat au transport SiteBackup.

Un composant natif modifié localement ne doit jamais être silencieusement embarqué comme addon.
Il doit être détecté et signalé comme modification du produit.

## Familles de fichiers v3

### 1. Données du site

Conserver le contrat v2 :
- `data/**/*.json`
- `data/**/*.html`

Les données runtime ou temporaires restent exclues.

### 2. Médias

Inclure :
- `public/uploads/**`
- `storage/uploads/avatars/**`
- `uploads/**` uniquement lorsqu'il ne s'agit pas d'un alias de public/uploads ;
- `resources/uploads/contact/**`.

Exclure :
- `public/uploads/cache/runtime-css/**`.

### 3. Composants ajoutés

Racines autorisées :
- `app/Modules/<Component>/**`
- `app/Extensions/<Component>/**`
- `app/Plugins/<Component>/**`

Seuls les composants absents de la baseline Core sont transportés.

### 4. Thèmes ajoutés

Racines autorisées :
- `themes/frontend/<theme>/**`
- `themes/admin/<theme>/**`

Seuls les thèmes absents de la baseline Core sont transportés.

Les copies publiques générées dans `public/themes/**` ne sont pas la source de vérité.
Elles doivent être republiées sur la cible par RuntimeAssetPublisher.

### 5. Assets de composants

Les assets sources présents dans les composants sont transportés avec leur composant.

Les copies générées dans :
- `public/modules/**`
- `public/assets/extensions/**`
- `public/assets/plugins/**`

ne doivent pas être considérées comme source de vérité.
Elles sont régénérées après restauration.

## Secrets et réglages

Le fichier `.env.local` complet ne doit pas être copié aveuglément d'une machine à l'autre.

La cible doit conserver ses paramètres d'environnement et de routage.

Les réglages applicatifs transportables doivent être restaurés de façon logique.

Les secrets applicatifs peuvent voyager uniquement :
- chiffrés dans l'archive ;
- avec une clé compagnon indépendante ;
- après validation du manifeste.

Le mécanisme v2 basé sur BackupSecretCipher doit être réutilisé.

`storage/app/secretbox.key` doit suivre le même contrat de portabilité que les secrets qu'elle protège.

Les valeurs liées exclusivement au serveur cible doivent rester celles de la cible.

## Licences

Les données de `resources/licenses/**` font partie du site livré lorsqu'elles concernent des composants transportés.

Elles doivent être protégées comme données sensibles.

La restauration d'une licence ne vaut pas validation définitive sur le nouveau domaine.

Après restauration :
1. FlatCMS restaure le coffre ;
2. le composant est installé ;
3. la licence est revalidée sur l'environnement cible selon son contrat ;
4. un échec de revalidation est remonté clairement sans corrompre la restauration.

Le mécanisme ne doit jamais contourner les règles Single / 5 Sites / Agency.

## Adaptation à l'environnement cible

La restauration v3 doit conserver le comportement existant qui remplace `site_url` par l'URL de l'installation cible.

Après écriture des fichiers, FlatCMS doit :
1. reconstruire l'état des composants ;
2. republier les assets via RuntimeAssetPublisher ;
3. purger les caches applicatifs ;
4. vérifier les permissions et chemins ;
5. exécuter un health check ;
6. confirmer seulement ensuite la restauration.

Aucune configuration Apache, Nginx ou IIS provenant de la source ne doit remplacer celle générée sur la cible.

## Transaction et rollback

La restauration reste transactionnelle.

Avant toute modification :
- valider l'archive ;
- valider la compatibilité Core ;
- valider les hashes ;
- valider les chemins ;
- valider les manifests de composants.

Pendant la restauration :
- utiliser StreamFileTransaction ;
- conserver une génération avant restauration ;
- ne publier les assets qu'après écriture réussie.

En cas d'échec :
- rollback automatique ;
- aucun composant partiellement installé ;
- aucun fichier sensible laissé en clair ;
- état cible cohérent.

## Manifest v3

Le manifeste doit exposer au minimum :

```json
{
  "kind": "flatcms-site-backup",
  "version": 3,
  "backup_id": "...",
  "flatcms_version": "2.0.0",
  "created_at": "...",
  "source_url": "...",
  "site_name": "...",
  "secret_key_required": true,
  "files": {},
  "components": [],
  "themes": [],
  "families": {}
}
```

Chaque composant doit préciser :
- type : module / extension / plugin ;
- nom ou clé ;
- version ;
- chemin source ;
- manifeste ;
- origine déclarée ;
- statut baseline : added / native_modified / native_unchanged.

Chaque thème doit fournir l'équivalent avec son type frontend/admin.

## Modifications du Core

Une sauvegarde portable ne doit pas transporter une modification directe du Core.

Sont notamment protégés :
- `app/Core/**` ;
- les modules natifs de la baseline ;
- les thèmes natifs de la baseline ;
- les fichiers produit hors espaces d'extension autorisés.

Si une modification locale d'un élément natif est détectée, la création doit échouer avec un message explicite.

La solution attendue est de déplacer cette personnalisation dans :
- un thème personnalisé ;
- un module ;
- une extension ;
- un plugin.

Ce garde-fou protège la reproductibilité du site et les futures mises à jour.

## UX cible

L'utilisateur ne doit pas avoir à connaître les termes SiteBackup / FullBackup.

Action principale :

**Créer une sauvegarde du site**

Description :
« Crée une archive portable contenant les contenus, médias, réglages et personnalisations nécessaires pour reproduire ce site sur une installation FlatCMS compatible. »

Restauration :

**Restaurer un site**

Description :
« Importez une archive ZIP générée par FlatCMS pour reproduire le site sur cette installation. »

FullBackup reste un mécanisme système avancé lié à la récupération et aux opérations sensibles.

## Scénario d'acceptation grandeur nature

1. Installer FlatCMS v2.0.0 en local.
2. Ajouter un thème personnalisé.
3. Ajouter un module personnalisé.
4. Ajouter une extension et un plugin de test.
5. Ajouter médias publics et pièce jointe privée Contact.
6. Configurer Settings avec au moins un secret.
7. Ajouter une licence de test.
8. Créer catégories, pages, articles, menus, footer, formulaires et utilisateurs.
9. Générer un SiteBackup v3.
10. Installer un FlatCMS v2.0.0 vierge dans un autre DocumentRoot.
11. Vérifier le site vierge.
12. Importer archive + clé.
13. Vérifier que l'URL cible reste celle du serveur cible.
14. Vérifier contenus, médias, composants, thèmes, assets, secrets et licences.
15. Vérifier qu'aucun fichier Core de la cible n'a été remplacé.
16. Vérifier le site frontend et l'administration.
17. Refaire le test sous une autre pile serveur lorsque le prototype MAMP est validé.

## Phases d'implémentation

### Phase A - Baseline et inventaire

- créer un contrat de baseline Core versionné ;
- inventorier composants et thèmes ajoutés ;
- détecter les modifications interdites du Core ;
- tests unitaires du périmètre.

### Phase B - Archive v3

- étendre SiteBackupService ;
- ajouter composants, thèmes, pièces jointes privées et licences ;
- étendre le manifeste ;
- conserver le streaming pour les gros fichiers ;
- réutiliser BackupSecretCipher.

### Phase C - Restauration

- valider composants et chemins ;
- restaurer transactionnellement ;
- reconstruire ModuleManager ;
- republier les assets ;
- adapter l'URL cible ;
- health check et rollback.

### Phase D - UX et tests réels

- afficher le périmètre de la sauvegarde ;
- améliorer les messages utilisateur ;
- test local -> vierge local ;
- test local -> Apache/Nginx/IIS ;
- test de livraison agence sans accès serveur.

