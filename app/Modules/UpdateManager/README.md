# FlatCMS Update Manager

Update Manager est le module officiel chargé de détecter et, pour le Core, d'appliquer les nouvelles versions de FlatCMS de manière contrôlée.

## Portée de la version 0.4.3

Le socle de détection de la version 0.1 reste actif :

- inventaire des composants installés ;
- interrogation des catalogues distants ;
- comparaison des versions ;
- compatibilité PHP / Core ;
- cache et vérification périodique via `tasks.run` ;
- badge d'état dans le Dashboard ;
- badges sur les cartes Modules, Extensions, Plugins et Thèmes ;
- compteur des mises à jour dans la sidebar ;
- écran `/admin/updates`.

La version 0.2 ajoute l'installation transactionnelle du **Core FlatCMS uniquement**.

La branche 0.3 ajoute la capsule Disaster Recovery et le lancement asynchrone de la mise à jour Core depuis l’interface web. La version 0.3.4 garantit ce lancement sous Unix même lorsque `proc_open` est désactivé par l’hébergeur : `shell_exec` est alors utilisé en mode détaché. La version 0.3.5 ajoute la détection sûre du binaire PHP CLI lorsque `open_basedir` masque les chemins système au processus PHP-FPM, comme avec la protection Anti-XSS d’aaPanel. La version 0.3.6 simplifie les messages de l’interface et ajoute une animation sobre pendant toute la mise à jour. Si aucun lanceur asynchrone n’est disponible, la mise à jour web est refusée avant toute mutation au lieu de basculer silencieusement vers une exécution longue et synchrone.

La version 0.4.0 introduit une frontière d’appartenance Core stricte et partagée par le builder, le validateur et la transaction. Une archive Core ne peut plus contenir ni modifier Modules, Extensions, Plugins ou Thèmes, même si une couche de validation est contournée. UpdateManager est lui-même distribué comme module officiel indépendant et n’est plus destiné à être transporté dans une mise à jour Core.

La version 0.4.3 ajoute des prérequis de composants signés dans `flatcms-update.json` et les expose aussi comme contraintes de compatibilité du catalogue. Une mise à jour Core est refusée avant toute sauvegarde ou mutation si un Module, une Extension, un Plugin ou un Thème requis est absent ou trop ancien.

Modules, Extensions, Plugins, Thèmes et Appliances restent en détection seule dans UpdateManager à ce stade. Leur cycle de mise à jour est indépendant du Core.

## Familles suivies

- `core`
- `modules`
- `extensions`
- `plugins`
- `themes`
- `appliances`

## Inventaire local

UpdateManager ne transforme jamais un catalogue distant en liste d'installation. Il part exclusivement de l'inventaire local :

- le Core FlatCMS ;
- les Modules, Extensions et Plugins effectivement activés ;
- les Thèmes installés, actifs ou non, afin de ne pas ignorer leurs correctifs ;
- les Appliances explicitement rattachées à l'installation lorsqu'un contrat local les déclare.

Une Extension ou un Plugin actif absent du catalogue reste visible avec l'état `not_in_catalog`. Sa carte indique alors qu'un composant est suivi hors catalogue FlatCMS au lieu d'afficher un faux état global « À jour ».

## Sources de distribution

- `https://flat-cms.fr` : Core, Appliances, Modules, Extensions, Plugins et Thèmes officiels.

Principe : **flat-cms.fr distribue ; Update Manager gère l'installation FlatCMS.**

Les URLs sont configurables par environnement :

- `FLATCMS_UPDATE_CORE_CATALOG_URL`
- `FLATCMS_UPDATE_APPLIANCES_CATALOG_URL`
- `FLATCMS_UPDATE_MODULES_CATALOG_URL`
- `FLATCMS_UPDATE_EXTENSIONS_CATALOG_URL`
- `FLATCMS_UPDATE_PLUGINS_CATALOG_URL`
- `FLATCMS_UPDATE_THEMES_CATALOG_URL`

HTTPS est obligatoire hors hôte local.

## Identité et compatibilité

L'identité compare au minimum `slug`, `vendor` et `channel`.
Pour les thèmes, `theme_type` (`frontend` ou `admin`) est également obligatoire.

La compatibilité prend en compte :

- `requires_php` ;
- `min_core_version` ;
- `max_core_version` ;
- le canal de publication.

`1.0.0 LTS` est comparé comme `1.0.0` tout en conservant son libellé d'affichage.

## Mise à jour transactionnelle du Core

Le flux Core 0.2 est :

1. vérifier que la version est réellement `update_available` dans le cache ;
2. vérifier l'espace disque disponible ;
3. télécharger l'artefact en streaming dans `storage/tmp/update-manager/` ;
4. vérifier taille, SHA-256 et signature OpenSSL ;
5. valider strictement `flatcms-update.json` ;
6. extraire uniquement les fichiers explicitement déclarés ;
7. vérifier chaque SHA-256 interne ;
8. activer temporairement la maintenance si nécessaire ;
9. sauvegarder exactement chaque cible qui sera modifiée ou supprimée ;
10. remplacer les fichiers par swaps atomiques ;
11. vérifier les fichiers installés et la nouvelle `VERSION` ;
12. lancer un health check dans un nouveau processus PHP ;
13. valider ou restaurer automatiquement le backup ;
14. restaurer l'état initial du mode maintenance.

Un verrou `flock` interdit deux installations simultanées.

Les transactions sont historisées dans :

`storage/logs/update-manager/history.jsonl`

Les backups transactionnels sont stockés sous :

`storage/backups/update-manager/transactions/`

## Contrat `flatcms-update.json`

Chaque archive Core contient à sa racine :

```json
{
  "schema": "flatcms-core-update-v1",
  "product": "flatcms",
  "version": "1.1.0",
  "files": {
    "VERSION": "<sha256>",
    "app/Core/App.php": "<sha256>"
  },
  "remove": ["app/Core/AncienFichier.php"]
}
```

Les fichiers non déclarés, manquants, les symlinks, chemins absolus et traversées `../` sont refusés.

Une mise à jour Core ne peut pas modifier directement :

- `.env` et `.env.local` ;
- `data/` et `storage/` ;
- `uploads/` et `public/uploads/` ;
- `app/Plugins/` et `app/Extensions/` ;
- `resources/updates/catalogs/`.

Ce dernier point protège notamment une installation utilisée comme Marketplace ou serveur de distribution.


## Frontière d’appartenance Core 0.4.0

La politique `CoreUpdatePathPolicy` est utilisée à trois niveaux :

1. le builder ne collecte que les racines appartenant au Core ;
2. le validateur refuse tout manifeste ou toute entrée ZIP hors périmètre ;
3. la transaction répète ce contrôle juste avant toute mutation du système de fichiers.

Les familles de composants sont donc hors périmètre d’une mise à jour Core par construction :

- `app/Modules/` ;
- `app/Extensions/` ;
- `app/Plugins/` ;
- `themes/` ;
- `public/themes/` ;
- `public/modules/`.

La distribution d’installation complète peut toujours contenir les composants officiels requis ; ce contrat concerne uniquement les artefacts incrémentaux **Core**.

## Rotation de la clé de signature Core 0.4.4

UpdateManager accepte plusieurs ancres publiques afin de permettre une rotation sans casser la vérification des anciennes releases. La clé active signe les nouvelles releases Core ; l’ancienne ancre reste en lecture seule pour valider les releases historiques déjà publiées.

## Signature des releases

Update Manager possède une ancre publique dédiée dans `Config/signing.php`.
La clé privée de signature ne doit jamais être déployée avec FlatCMS ni stockée dans le dépôt.

Le payload signé est stable et inclut :

- version du contrat de signature ;
- famille (`core`) ;
- slug (`flatcms`) ;
- version ;
- SHA-256 de l'archive.

Le builder utilise la clé privée uniquement si `FLATCMS_UPDATE_SIGNING_PRIVATE_KEY_FILE` est défini.

## Commandes CLI

```text
php bin/flatcms tasks:run
php bin/flatcms updates:apply core flatcms <version>
php bin/flatcms updates:build-core <version> [output.zip]
```

L'interface web délègue l'application à un processus PHP CLI distinct.
`FLATCMS_PHP_CLI` permet de forcer le binaire CLI si sa détection automatique ne convient pas.

## Prérequis du chemin Core 0.2

- cURL ;
- ZipArchive ;
- OpenSSL ;
- sous Unix, `proc_open` **ou** `shell_exec` disponible pour lancer le worker web en arrière-plan ;
- un binaire PHP CLI compatible ;
- suffisamment d'espace disque pour staging + backup + marge de sécurité.

## Cache et limites actuelles

Le statut est stocké dans `storage/cache/update-manager/status.json`.
Le TTL normal est de 24 h (`FLATCMS_UPDATE_CHECK_TTL`) et tombe au maximum à 1 h lorsqu'une source est indisponible.
Le cache contient aussi une empreinte de l'inventaire local. Toute activation, désactivation, installation, suppression ou modification de version invalide immédiatement le statut mémorisé.
Le Dashboard et les badges ne déclenchent pas de requête réseau.

Taille maximale de téléchargement :

- `FLATCMS_UPDATE_MAX_DOWNLOAD_BYTES` pour les packages ordinaires ;
- `FLATCMS_UPDATE_MAX_APPLIANCE_BYTES` pour les Appliances.

La version 0.2 ne gère pas encore :

- l'installation transactionnelle des Modules, Extensions, Plugins et Thèmes ;
- l'installation des Appliances ;
- les droits d'achat Marketplace et liens temporaires ;
- l'ordonnancement automatique des mises à jour dépendantes ;
- une interface d'historique avancée.

Ces fonctions doivent réutiliser le socle de sécurité de la transaction Core au lieu de créer un second mécanisme parallèle.
