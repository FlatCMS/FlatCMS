# DOWNLOAD_CONTENT — Télécharger FlatCMS LTS Core

> **Contenu éditorial prêt à intégrer**
>
> Projet : FlatCMS  
> Page : Téléchargement `fr-FR`  
> URL cible : `https://flat-cms.fr/fr-FR/telechargement/`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Brief parent : `PAGE_BRIEFS.md` — `P0-DOWNLOAD-FR`  
> Documents associés : `INSTALLATION_CONTENT.md`, `LICENSING.md`, `COMMERCIAL_LICENSE.md`, `LAUNCH_CHECKLIST.md`  
> Statut : première version rédactionnelle à compléter avec l’artefact, le checksum, la taille et la date de publication définitifs

---

## 1. Objectif de la page

Cette page constitue la source officielle de téléchargement de FlatCMS.

Elle doit permettre au visiteur de :

- identifier la version stable actuelle ;
- télécharger l’archive de distribution officielle ;
- vérifier son intégrité ;
- connaître les prérequis ;
- comprendre la licence du LTS Core ;
- consulter les notes de version ;
- ouvrir le guide d’installation ;
- accéder au code source et aux versions précédentes ;
- distinguer la version stable des préversions et composants premium.

La page ne doit jamais afficher une version, une taille, un checksum ou une date qui ne correspondent pas exactement au fichier servi.

---

# 2. Métadonnées SEO

## URL canonique

```text
https://flat-cms.fr/fr-FR/telechargement/
```

## Balise `<title>`

```text
Télécharger FlatCMS LTS — CMS PHP open source
```

## Meta description

```text
Téléchargez la version stable de FlatCMS et consultez les prérequis,
la licence, le checksum, le changelog et les notes de version.
```

## Directive robots

```text
index, follow, max-image-preview:large
```

## Open Graph

### `og:title`

```text
Télécharger FlatCMS v1.0.0 LTS Core
```

### `og:description`

```text
Téléchargez l’archive officielle du CMS PHP flat-file FlatCMS, vérifiez
son intégrité et suivez le guide d’installation.
```

### `og:type`

```text
website
```

### `og:url`

```text
https://flat-cms.fr/fr-FR/telechargement/
```

### `og:image`

```text
https://flat-cms.fr/assets/images/og/telechargement-flatcms-fr-FR.webp
```

---

# 3. Hero

## Sur-titre

```text
Version stable
```

## H1

```text
Télécharger FlatCMS
```

## Introduction

```text
Téléchargez FlatCMS v1.0.0 LTS Core, la ligne stable et open source du
CMS PHP flat-file fondé sur une architecture HMVC, l’autoloading PSR-4
et un stockage JSON.
```

## Complément

```text
L’archive officielle contient le runtime du Core, les fonctions
éditoriales stables et l’installateur. Les composants expérimentaux,
commerciaux ou instables restent distribués séparément.
```

## CTA principal

```text
Télécharger FlatCMS v1.0.0 LTS
```

Destination à confirmer :

```text
https://flat-cms.fr/downloads/flatcms-1.0.0-lts.zip
```

## CTA secondaire

```text
Lire le guide d’installation
```

Destination :

```text
/fr-FR/documentation/installation/
```

## Lien tertiaire

```text
Consulter le code source sur GitHub
```

Destination à confirmer :

```text
URL du dépôt officiel FlatCMS LTS Core
```

## Réassurance courte

```text
Open source · AGPL-3.0-or-later · PHP 8.3+ · Sans serveur SQL
```

---

# 4. Version stable actuelle

## H2

```text
FlatCMS v1.0.0 LTS Core
```

## Badge

```text
Stable
```

## Description

```text
FlatCMS LTS Core est la ligne source stable du projet. Elle se concentre
sur le périmètre durable du CMS : runtime PHP natif, architecture HMVC,
autoloading PSR-4, stockage JSON, administration et frontend modulaires,
authentification, pages, articles, commentaires, contact, médias, menus,
footer, thèmes et installateur.
```

```text
Les lignes expérimentales, les outils commerciaux et les fonctions
d’authoring encore instables ne font pas partie du périmètre pris en
charge par ce package.
```

## Informations de version

| Information | Valeur à publier |
|---|---|
| Version | `1.0.0` |
| Canal | `LTS Core` |
| Statut | `Stable` |
| Date de publication | À confirmer |
| Format | ZIP à confirmer |
| Taille | À calculer après assemblage |
| PHP minimum | `8.3` |
| Licence du Core | `AGPL-3.0-or-later` |
| Checksum SHA-256 | À générer après assemblage |
| Signature | À définir si utilisée |

## Règle

```text
Les valeurs dynamiques doivent être alimentées depuis le catalogue de
release ou un manifeste de distribution, et non recopiées manuellement
dans plusieurs templates.
```

---

# 5. Carte de téléchargement

## Titre

```text
FlatCMS v1.0.0 LTS Core
```

## Résumé

```text
Archive officielle prête à installer pour les environnements PHP
compatibles.
```

## Informations visibles

```text
Version : 1.0.0
Canal : LTS Core
PHP : 8.3+
Licence : AGPL-3.0-or-later
Format : ZIP
Taille : à confirmer
Publié le : à confirmer
```

## Bouton

```text
Télécharger l’archive ZIP
```

## Liens associés

```text
Vérifier le checksum
Lire les notes de version
Consulter la licence
Installer FlatCMS
```

## Accessibilité

Le bouton doit indiquer le fichier ou la version téléchargée.

Correct :

```text
Télécharger FlatCMS v1.0.0 LTS au format ZIP
```

À éviter :

```text
Télécharger
```

sans contexte accessible.

---

# 6. Archive officielle et code source

## H2

```text
Choisir le bon téléchargement
```

## Archive de distribution

```text
L’archive proposée sur cette page est l’artefact officiel de
distribution. Elle doit être assemblée, validée et testée par la chaîne
de release de FlatCMS.
```

Elle peut inclure :

- le runtime prêt à installer ;
- les fichiers requis par l’installateur ;
- les assets distribués ;
- les licences ;
- la version ;
- les fichiers de configuration exemples ;
- les dépendances autorisées ;
- les manifestes nécessaires.

Elle doit exclure :

- secrets ;
- fichiers locaux de développement ;
- caches ;
- logs ;
- sauvegardes ;
- données personnelles ;
- outils de packaging internes ;
- branches expérimentales ;
- composants premium non autorisés ;
- fichiers temporaires ;
- métadonnées système inutiles.

## Archives automatiques du dépôt GitHub

```text
GitHub génère automatiquement une archive ZIP et une archive tar.gz du
code correspondant à chaque tag. Ces archives représentent le contenu
du dépôt à ce point de l’historique.
```

```text
Elles ne doivent pas être présentées comme le package installable
officiel si FlatCMS nécessite une étape d’assemblage, de validation ou
d’inclusion de fichiers distincts.
```

## Code source

Le dépôt officiel permet de :

- consulter le code ;
- suivre les tags ;
- comparer les versions ;
- consulter les issues et contributions selon la configuration ;
- vérifier le contenu de la ligne LTS Core.

## Recommandation éditoriale

Afficher deux actions distinctes :

```text
Télécharger le package prêt à installer
Consulter le code source
```

---

# 7. Vérifier l’intégrité du fichier

## H2

```text
Vérifier le checksum SHA-256
```

## Pourquoi vérifier ?

```text
Le checksum permet de confirmer que le fichier téléchargé correspond à
l’artefact publié et qu’il n’a pas été modifié ou corrompu pendant le
transfert.
```

## Valeur officielle

```text
SHA-256 : À GÉNÉRER APRÈS ASSEMBLAGE FINAL
```

La valeur doit être affichée :

- en texte sélectionnable ;
- dans un bloc monospace ;
- à proximité du téléchargement ;
- éventuellement dans un fichier `.sha256` séparé.

## macOS

```bash
shasum -a 256 flatcms-1.0.0-lts.zip
```

## Linux

```bash
sha256sum flatcms-1.0.0-lts.zip
```

## Windows PowerShell

```powershell
Get-FileHash .\flatcms-1.0.0-lts.zip -Algorithm SHA256
```

## Résultat attendu

La valeur calculée doit être strictement identique à celle publiée.

## En cas de différence

1. ne pas ouvrir ou installer l’archive ;
2. supprimer le fichier ;
3. télécharger à nouveau depuis la page officielle ;
4. vérifier que la bonne version a été sélectionnée ;
5. signaler le problème si la différence persiste.

## Limite

```text
Un checksum publié sur la même infrastructure que le fichier détecte
principalement une corruption ou une différence d’artefact. Une
signature cryptographique indépendante apporterait une vérification
supplémentaire si FlatCMS met ce mécanisme en place.
```

---

# 8. Nom et version du fichier

## H2

```text
Identifier clairement l’artefact
```

## Convention recommandée

```text
flatcms-1.0.0-lts.zip
```

## Variante avec date, si nécessaire

```text
flatcms-1.0.0-lts-2026-06-08.zip
```

## Fichiers associés possibles

```text
flatcms-1.0.0-lts.zip
flatcms-1.0.0-lts.zip.sha256
flatcms-1.0.0-release-notes.md
flatcms-1.0.0-sbom.spdx.json
flatcms-1.0.0-signature.asc
```

Ne publier que les fichiers réellement produits et maintenus.

## Règles

- version SemVer lisible ;
- minuscules ;
- tirets ;
- aucun espace ;
- canal visible ;
- extension correcte ;
- aucune mention `final-final` ou similaire ;
- nom identique dans les notes et le catalogue.

---

# 9. Prérequis techniques

## H2

```text
Vérifier la compatibilité avant le téléchargement
```

## PHP

```text
PHP 8.3 ou version ultérieure compatible
```

La compatibilité réelle doit être testée pour chaque branche annoncée.

Au lancement, la page peut afficher :

| Branche PHP | Statut FlatCMS |
|---|---|
| PHP 8.3 | Compatible et minimum contractuel |
| PHP 8.4 | À confirmer par les tests de release |
| PHP 8.5 | À confirmer par les tests de release |

## Serveur web

```text
Apache 2.4+ ou Nginx avec PHP-FPM
```

## Stockage

Prévoir suffisamment d’espace pour :

- le package ;
- les données JSON ;
- les médias ;
- le cache ;
- les logs ;
- les sauvegardes.

## Base de données

```text
Aucun serveur MySQL ou MariaDB n’est requis pour le fonctionnement
normal du LTS Core.
```

## Document root

```text
Le serveur de production doit pouvoir exposer le dossier public/.
```

## Extensions PHP

La liste contractuelle doit être issue des contrôles de l’installateur.

Ne pas publier une liste approximative comme exigence définitive.

## Permissions

Le processus PHP doit pouvoir écrire uniquement dans les emplacements
nécessaires au fonctionnement et à l’installation.

## CTA

```text
Consulter tous les prérequis
```

Destination :

```text
/fr-FR/documentation/demarrage/prerequis/
```

---

# 10. Contenu du LTS Core

## H2

```text
Ce que contient FlatCMS LTS Core
```

## Fondations

- PHP natif ;
- architecture HMVC ;
- autoloading PSR-4 ;
- stockage JSON ;
- runtime modulaire d’administration et de frontend ;
- installateur.

## Fonctions éditoriales annoncées

- authentification ;
- pages ;
- articles ;
- commentaires ;
- contact ;
- médias ;
- menus ;
- footer ;
- thèmes.

## Validation du dépôt

Le dépôt annonce une validation centrée sur :

- l’intégrité syntaxique PHP ;
- l’intégrité des fichiers JSON ;
- le comportement stable de l’administration et du frontend ;
- le parcours d’installation livré.

## Important

```text
La liste finale du package doit être comparée à l’archive distribuée.
La documentation ne doit pas présenter comme incluse une fonction absente,
désactivée ou livrée séparément.
```

---

# 11. Ce que le package ne contient pas

## H2

```text
Périmètre volontairement exclu
```

Le LTS Core n’est pas destiné à inclure automatiquement :

- branches expérimentales ;
- outils de packaging et d’exploitation internes ;
- fonctionnalités d’authoring instables ;
- composants commerciaux ;
- builders premium ;
- secrets ou configurations de production ;
- contenus propres à un client ;
- données de démonstration non explicitement sélectionnées ;
- dépendances non autorisées.

## Builders premium

```text
PagesBuilder, MenuBuilder et FooterBuilder sont des composants distincts
soumis à leur propre offre et licence commerciale.
```

## AiAgent et services avancés

```text
Le statut de chaque module IA doit être indiqué selon la distribution :
Core technique, optionnel, premium, expérimental ou prévu.
```

## CTA

```text
Comparer le Core et les composants premium
```

Destination :

```text
/fr-FR/fonctionnalites/
```

---

# 12. Licence open source

## H2

```text
Licence de FlatCMS LTS Core
```

## Règle par défaut

```text
Sauf mention différente dans l’en-tête d’un fichier, le code propriétaire
de première partie de FlatCMS est distribué sous GNU Affero General
Public License v3.0 ou ultérieure : AGPL-3.0-or-later.
```

## Autorité des en-têtes

```text
Si l’en-tête SPDX d’un fichier diffère du document général de licence,
l’en-tête du fichier est la référence pour ce fichier.
```

## Dépendances tierces

Les bibliothèques et assets tiers conservent leurs propres licences.

Cela peut concerner notamment :

```text
app/ThirdParty/**
public/assets/dists/**
vendor/** si présent
```

## Marque

```text
La licence du code n’accorde pas de droit sur la marque FlatCMS.
L’utilisation de la marque est encadrée séparément par TRADEMARK.md.
```

## Contributions

```text
Les conditions de contribution sont décrites dans CLA.md.
```

## CTA principal

```text
Lire la licence du Core
```

Destination :

```text
/fr-FR/licences/#core
```

## CTA secondaire

```text
Comprendre le modèle de licences
```

Destination :

```text
/fr-FR/licences/
```

---

# 13. Composants commerciaux

## H2

```text
Une licence distincte pour les composants premium
```

## Texte

```text
Les fichiers de première partie marqués
SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial sont des
composants propriétaires et ne sont pas distribués sous AGPL.
```

```text
Leur utilisation exige une licence commerciale valide accordée par
Alain BROYE / FlatCMS, sauf accord écrit différent.
```

## Conditions à présenter sur les pages commerciales

- produit concerné ;
- nombre de sites ;
- durée ;
- mises à jour ;
- support ;
- droits de déploiement ;
- restrictions ;
- prix HT et TTC lorsque requis.

## Règle de la page de téléchargement

```text
La page principale de téléchargement du LTS Core ne doit pas ajouter
silencieusement des composants commerciaux dans l’archive open source.
```

## CTA

```text
Voir les composants premium
```

Destination :

```text
/fr-FR/tarifs/
```

---

# 14. Notes de version

## H2

```text
Consulter les changements de FlatCMS v1.0.0
```

## Résumé attendu

La note de version doit présenter :

- objectif de la version ;
- fonctions principales ;
- changements importants ;
- corrections ;
- sécurité ;
- compatibilité PHP ;
- changements de configuration ;
- migrations ;
- problèmes connus ;
- instructions de mise à jour ;
- remerciements aux contributeurs.

## Exemple de structure

```text
FlatCMS v1.0.0 LTS Core

Nouveautés
Corrections
Sécurité
Compatibilité
Installation
Mise à jour
Problèmes connus
Checksums
Contributeurs
```

## Règle

```text
Les notes doivent être finalisées avant la publication de la release et
correspondre au contenu exact de l’artefact.
```

## CTA

```text
Lire les notes de version 1.0.0
```

Destination à confirmer :

```text
/fr-FR/versions/1.0.0/
```

---

# 15. Changelog

## H2

```text
Suivre l’historique des changements
```

## Différence avec les notes de version

```text
Les notes de version résument une publication pour ses utilisateurs.
Le changelog conserve un historique plus détaillé et structuré des
évolutions entre versions.
```

## Catégories recommandées

```text
Added
Changed
Deprecated
Removed
Fixed
Security
```

Une version française peut afficher :

```text
Ajouté
Modifié
Déprécié
Supprimé
Corrigé
Sécurité
```

## CTA

```text
Consulter le changelog
```

Destination à confirmer :

```text
/fr-FR/changelog/
```

---

# 16. Installation rapide

## H2

```text
Installer FlatCMS après le téléchargement
```

## Étapes essentielles

1. vérifier le checksum ;
2. extraire l’archive dans un dossier vide ;
3. vérifier PHP et les extensions ;
4. appliquer les permissions nécessaires ;
5. ouvrir l’entrée canonique `index.php?step=1` ;
6. terminer le parcours d’installation ;
7. configurer le serveur de production sur `public/` ;
8. protéger l’installateur ;
9. tester le frontend et l’administration ;
10. créer une première sauvegarde.

## CTA principal

```text
Lire le guide d’installation complet
```

Destination :

```text
/fr-FR/documentation/installation/
```

## Guides par environnement

```text
Apache
Nginx
MAMP
WAMP
Synology DSM
Raspberry Pi
```

---

# 17. Installer depuis Git

## H2

```text
Cloner le dépôt pour contribuer ou étudier le code
```

## Important

```text
Le clonage du dépôt s’adresse principalement aux développeurs et
contributeurs. Pour installer un site stable, utilisez l’artefact officiel
si le dépôt nécessite une chaîne d’assemblage distincte.
```

## Commande conceptuelle

```bash
git clone URL_DU_DEPOT_OFFICIEL flatcms
cd flatcms
git checkout v1.0.0
```

## Vérifications

- dépôt officiel ;
- tag signé si ce mécanisme est utilisé ;
- sous-modules éventuels ;
- dépendances ;
- fichiers générés ;
- procédure de build ;
- différence entre source et distribution.

## Ne pas publier avant confirmation

- URL exacte du dépôt ;
- commande de build ;
- sous-modules ;
- branches ;
- processus de packaging.

---

# 18. Versions précédentes

## H2

```text
Télécharger une version précédente
```

## Règle

```text
La version stable actuelle doit rester l’action principale. Les versions
précédentes sont disponibles pour la maintenance, les tests ou la
restauration d’un projet existant.
```

## Tableau proposé

| Version | Statut | Date | PHP | Téléchargement | Notes |
|---|---|---|---|---|---|
| 1.0.0 | Stable actuelle | À confirmer | 8.3+ | ZIP | Notes |
| Versions antérieures | Archive | À compléter | Selon version | Archive | Notes |

## Avertissement

```text
Une ancienne version peut ne plus recevoir de corrections de sécurité ou
de compatibilité. Ne l’utilisez pas pour un nouveau projet sans raison
technique documentée.
```

## Indexation

La page principale reste canonique.

Les pages de versions anciennes peuvent être indexables si elles apportent :

- notes historiques utiles ;
- documentation de maintenance ;
- checksum ;
- statut clair ;
- avertissement de fin de support.

---

# 19. Préversions

## H2

```text
Tester une version non stable
```

## Statuts possibles

```text
Alpha
Beta
Release Candidate
Preview
Nightly
```

## Règle

```text
Une préversion doit être clairement séparée de la version stable, ne pas
être proposée par défaut et afficher un avertissement visible.
```

## Avertissement type

```text
Cette version est destinée aux tests. Elle peut contenir des erreurs,
modifier ses formats de données et ne doit pas être utilisée sur un site
de production sans procédure de sauvegarde et de retour arrière.
```

## GitHub

Une release GitHub peut être marquée comme préversion afin de ne pas la
présenter comme la dernière version stable.

## Données de test

Ne jamais utiliser une préversion sur l’unique copie de données réelles.

---

# 20. Politique de support

## H2

```text
Comprendre le canal LTS Core
```

## Points à définir avant publication

- durée de support de la version 1.0 ;
- types de corrections ;
- correctifs de sécurité ;
- compatibilité PHP ;
- fréquence de publication ;
- politique de rétrocompatibilité ;
- migrations ;
- fin de support ;
- canaux de signalement.

## Formulation prudente

```text
LTS désigne la ligne stable et durable du Core. La durée exacte, les
engagements de maintenance et le calendrier doivent être publiés dans
une politique de support dédiée avant d’être présentés comme une garantie.
```

## CTA futur

```text
Consulter la politique de support
```

Destination proposée :

```text
/fr-FR/support/politique-lts/
```

---

# 21. Sécurité des releases

## H2

```text
Publier un artefact vérifiable
```

## Contrôles avant publication

- tag et version cohérents ;
- archive produite depuis la source attendue ;
- aucun secret ;
- aucun fichier local ;
- syntaxe PHP validée ;
- JSON validé ;
- installateur testé ;
- archive testée sur environnement vierge ;
- checksum généré après assemblage ;
- licences présentes ;
- dépendances vérifiées ;
- notes de version finalisées ;
- scan de sécurité ;
- taille et nom vérifiés.

## Publication GitHub

Une release peut regrouper :

- un tag ;
- un titre ;
- des notes ;
- les contributeurs ;
- des liens ;
- les assets binaires ou archives préparées.

## Recommandation

```text
Préparer la release en brouillon, joindre tous les assets, vérifier les
checksums et publier uniquement lorsque l’ensemble est cohérent.
```

## Release immuable

Si les releases immuables sont activées, tous les assets doivent être
attachés avant la publication définitive.

---

# 22. Source de vérité de la version

## H2

```text
Éviter les versions contradictoires
```

La version doit être cohérente dans :

- fichier `VERSION` ;
- `flatcms.json` si ce manifeste contient la version ;
- tag Git ;
- titre de release ;
- nom de l’archive ;
- notes de version ;
- catalogue de mise à jour ;
- page de téléchargement ;
- données structurées ;
- administration ;
- checksum.

## Règle

```text
Une seule source automatisée doit alimenter les affichages autant que
possible.
```

## Échec bloquant

Le lancement doit être bloqué si :

- deux versions différentes sont affichées ;
- le fichier téléchargé ne correspond pas au titre ;
- le checksum ne correspond pas ;
- les notes décrivent une autre archive.

---

# 23. Données structurées de la page

## H2

```text
Décrire le logiciel et son offre gratuite
```

## Types attendus

```text
WebPage
SoftwareApplication
Offer
BreadcrumbList
ImageObject
```

## Identifiants

```text
https://flat-cms.fr/#software
https://flat-cms.fr/fr-FR/telechargement/#webpage
https://flat-cms.fr/fr-FR/telechargement/#offer
https://flat-cms.fr/fr-FR/telechargement/#breadcrumb
https://flat-cms.fr/fr-FR/telechargement/#primaryimage
```

## Exemple conceptuel

```json
{
  "@type": "SoftwareApplication",
  "@id": "https://flat-cms.fr/#software",
  "name": "FlatCMS",
  "softwareVersion": "1.0.0",
  "applicationCategory": "ContentManagementApplication",
  "programmingLanguage": "PHP",
  "downloadUrl": "URL_DE_L_ARCHIVE",
  "license": "URL_DE_LA_LICENCE",
  "offers": {
    "@type": "Offer",
    "@id": "https://flat-cms.fr/fr-FR/telechargement/#offer",
    "price": "0",
    "priceCurrency": "EUR",
    "availability": "https://schema.org/InStock"
  }
}
```

## Prudence

- ne pas inventer de note ;
- ne pas inventer d’avis ;
- ne pas annoncer une compatibilité non testée ;
- garder `softwareVersion` synchronisé ;
- utiliser l’URL finale de l’artefact ;
- ne pas exposer une URL temporaire signée comme URL permanente.

---

# 24. Hébergement des fichiers

## H2

```text
Servir les archives depuis une infrastructure adaptée
```

## Options

- assets d’une release GitHub ;
- stockage objet ;
- CDN ;
- serveur officiel ;
- miroir vérifié.

## Exigences

- HTTPS ;
- disponibilité ;
- type MIME correct ;
- taille correcte ;
- nom de fichier conservé ;
- logs ;
- protection contre la modification ;
- cache maîtrisé ;
- pas d’exécution côté serveur ;
- liens stables ;
- contrôle des coûts et de la bande passante.

## Redirection

La page peut pointer vers un asset externe officiel.

Éviter une chaîne de redirections inutile.

## CDN

Une mise à jour d’archive ne doit jamais remplacer silencieusement un
fichier existant sous le même nom de version.

Publier une nouvelle version ou un nouvel identifiant d’artefact.

---

# 25. Téléchargement et confidentialité

## H2

```text
Mesurer sans imposer un suivi excessif
```

## Mesures possibles

- clic sur le bouton ;
- version ;
- locale ;
- source de trafic ;
- téléchargements de release GitHub ;
- erreurs de téléchargement.

## Ne pas collecter sans nécessité

- adresse IP conservée indéfiniment ;
- e-mail obligatoire pour télécharger le Core ;
- données personnelles sans information ;
- fingerprinting ;
- paramètres secrets dans l’URL.

## Principe

```text
Le téléchargement du Core open source ne devrait pas exiger la création
d’un compte, sauf décision explicitement justifiée et documentée.
```

---

# 26. Erreurs de téléchargement

## H2

```text
Résoudre un problème de téléchargement
```

## Fichier introuvable

Vérifier :

- URL de l’asset ;
- release publiée ;
- nom exact ;
- cache ;
- redirection ;
- droits du stockage ;
- état du CDN.

## Téléchargement incomplet

Vérifier :

- taille ;
- connexion ;
- proxy ;
- antivirus ;
- limite du serveur ;
- espace disque ;
- checksum.

## Archive impossible à ouvrir

- télécharger à nouveau ;
- vérifier le checksum ;
- utiliser un outil d’archive récent ;
- vérifier le format ;
- ne pas renommer seulement l’extension.

## Checksum différent

- ne pas installer ;
- supprimer le fichier ;
- télécharger depuis la page officielle ;
- vérifier la version ;
- signaler l’incident.

## Navigateur bloque le fichier

Vérifier :

- HTTPS ;
- réputation du domaine ;
- type MIME ;
- contenu de l’archive ;
- signature éventuelle ;
- rapport antivirus ;
- absence d’exécutable inattendu.

---

# 27. Questions fréquentes éditoriales

> Ces questions-réponses ne déclenchent pas automatiquement un balisage
> `FAQPage`.

## H2

```text
Questions fréquentes sur le téléchargement
```

### H3 — FlatCMS est-il gratuit ?

```text
Le LTS Core est destiné à être distribué sous une licence open source
AGPL-3.0-or-later. Certains composants premium utilisent une licence
commerciale distincte.
```

### H3 — Dois-je créer un compte pour télécharger FlatCMS ?

```text
Le téléchargement du Core ne devrait pas nécessiter de compte si la
politique de distribution finale confirme ce choix.
```

### H3 — Quelle archive dois-je utiliser ?

```text
Pour installer un site stable, utilisez le package officiel indiqué sur
cette page. Les archives automatiques du dépôt sont principalement des
instantanés du code source associé au tag.
```

### H3 — Comment savoir si le fichier est authentique ?

```text
Téléchargez-le depuis la page officielle et comparez son checksum SHA-256
avec la valeur publiée.
```

### H3 — FlatCMS fonctionne-t-il avec PHP 8.4 ou 8.5 ?

```text
PHP 8.3 constitue le minimum annoncé. Les versions ultérieures ne doivent
être présentées comme compatibles qu’après validation par les tests de
release FlatCMS.
```

### H3 — MySQL est-il nécessaire ?

```text
Non. Le LTS Core stocke ses données principales dans des fichiers JSON
et ne nécessite pas de serveur MySQL ou MariaDB pour son fonctionnement
normal.
```

### H3 — Les builders sont-ils inclus dans le ZIP ?

```text
PagesBuilder, MenuBuilder et FooterBuilder sont des composants premium
distincts. Leur inclusion et leur téléchargement suivent leur licence et
leur canal de distribution propres.
```

### H3 — Puis-je télécharger une ancienne version ?

```text
Oui si une archive officielle est conservée. Son statut de support et ses
prérequis doivent être vérifiés avant utilisation.
```

### H3 — Puis-je redistribuer FlatCMS ?

```text
La redistribution du Core doit respecter l’AGPL et les licences tierces.
Les droits sur la marque et les composants commerciaux restent séparés.
Consultez les textes officiels avant toute redistribution.
```

---

# 28. CTA final

## H2

```text
Téléchargez la version stable et commencez en local
```

## Texte

```text
Vérifiez le checksum, installez FlatCMS dans un environnement de test et
validez le parcours complet avant tout déploiement en production.
```

## CTA principal

```text
Télécharger FlatCMS v1.0.0 LTS
```

## CTA secondaire

```text
Lire le guide d’installation
```

## Lien tertiaire

```text
Tester la démo
```

Destination :

```text
https://demo.flat-cms.fr/
```

---

# 29. Maillage interne attendu

| Section | Destination |
|---|---|
| Hero | Archive, installation, GitHub |
| Version stable | Notes de version |
| Checksum | Guide d’installation |
| Prérequis | Page Prérequis |
| Contenu Core | Fonctionnalités |
| Exclusions | Fonctionnalités et tarifs |
| Licence | Page Licences |
| Premium | Tarifs |
| Notes | Page version 1.0.0 |
| Installation rapide | Installation |
| Versions précédentes | Archives des versions |
| Support | Politique LTS |
| Sécurité | Signalement de sécurité |
| CTA final | Archive, installation, démo |

---

# 30. Médias à produire

## Image Open Graph

Concept :

```text
Archive FlatCMS v1.0.0 LTS avec badges PHP 8.3+, HMVC, PSR-4, JSON et
Open Source
```

## Illustration principale

```text
Télécharger
→ Vérifier SHA-256
→ Extraire
→ Installer
→ Sécuriser
```

## Éléments graphiques

- badge Stable ;
- numéro de version ;
- poids ;
- format ;
- checksum ;
- licence ;
- PHP minimum ;
- date de publication.

## Capture facultative

- page GitHub Release officielle ;
- liste des assets ;
- notes de version ;
- bouton de téléchargement.

Ne pas utiliser une capture qui révèle un dépôt privé ou une information
non publique.

---

# 31. Textes alternatifs suggérés

## Archive

```text
Archive officielle FlatCMS v1.0.0 LTS Core prête à télécharger
```

## Processus

```text
Étapes pour télécharger, vérifier et installer FlatCMS
```

## Checksum

```text
Commande de vérification du checksum SHA-256 de l’archive FlatCMS
```

## Release GitHub

```text
Release GitHub de FlatCMS avec ses notes et ses fichiers téléchargeables
```

Les textes doivent être adaptés aux médias réels.

---

# 32. Composants du thème suggérés

```text
HeroDownload
StableReleaseCard
ReleaseMetadata
DownloadButton
ChecksumVerifier
RequirementsTable
CoreContents
LicenseSummary
ReleaseNotesPreview
PreviousVersionsTable
PrereleaseWarning
InstallationSteps
FaqAccordion
CallToActionBanner
```

Les noms définitifs doivent correspondre au thème ou aux widgets
réellement disponibles.

---

# 33. Données dynamiques recommandées

La page ne devrait pas dupliquer manuellement les informations de release.

Prévoir un modèle ou service contenant :

```json
{
  "version": "1.0.0",
  "channel": "lts",
  "status": "stable",
  "published_at": "À CONFIRMER",
  "filename": "flatcms-1.0.0-lts.zip",
  "size_bytes": 0,
  "sha256": "À CONFIRMER",
  "download_url": "À CONFIRMER",
  "release_notes_url": "À CONFIRMER",
  "source_url": "À CONFIRMER",
  "php_min": "8.3",
  "license": "AGPL-3.0-or-later"
}
```

## Validations

- URL HTTPS ;
- fichier existant ;
- taille positive ;
- checksum sur 64 caractères hexadécimaux ;
- version SemVer ;
- date ISO 8601 ;
- licence connue ;
- statut autorisé ;
- cohérence du nom de fichier.

---

# 34. Éléments à confirmer avant publication

- URL du dépôt officiel ;
- URL de l’archive ;
- nom exact du fichier ;
- format ZIP ou autre ;
- taille ;
- checksum SHA-256 ;
- date de publication ;
- liste exacte du package ;
- extensions PHP requises ;
- compatibilité PHP 8.4 et 8.5 ;
- notes de version ;
- changelog ;
- politique LTS ;
- URL de licence ;
- système de signature ;
- SBOM éventuel ;
- stockage/CDN ;
- versions archivées ;
- statut d’AiAgent ;
- distribution des builders ;
- politique de compte et de mesure des téléchargements.

---

# 35. Checklist éditoriale

- [ ] La version affichée correspond à `VERSION`.
- [ ] Le nom de l’archive est exact.
- [ ] La taille est exacte.
- [ ] Le checksum est exact.
- [ ] La date est exacte.
- [ ] Le bouton pointe vers le bon fichier.
- [ ] Le Core et les composants premium sont distingués.
- [ ] L’AGPL est décrite sans simplification trompeuse.
- [ ] Les licences tierces sont mentionnées.
- [ ] Les droits de marque sont séparés.
- [ ] Les prérequis sont validés.
- [ ] La compatibilité PHP n’est pas supposée.
- [ ] Les notes correspondent à l’artefact.
- [ ] Les préversions sont séparées.
- [ ] Les anciennes versions affichent leur statut.
- [ ] L’archive GitHub automatique n’est pas confondue avec la distribution.
- [ ] Aucun secret ou fichier interne n’est inclus.
- [ ] Les liens d’installation fonctionnent.

---

# 36. Checklist d’intégration

- [ ] URL correcte.
- [ ] Canonique auto-référencée.
- [ ] `<html lang="fr-FR">`.
- [ ] Groupe `hreflang`.
- [ ] Title.
- [ ] Meta description.
- [ ] Open Graph.
- [ ] H1 unique.
- [ ] Bouton accessible.
- [ ] Métadonnées dynamiques.
- [ ] Fichier disponible en HTTPS.
- [ ] Nom de téléchargement correct.
- [ ] Taille correcte.
- [ ] Checksum copiable.
- [ ] Liens vers licence et notes.
- [ ] Tableau responsive.
- [ ] Images responsive.
- [ ] Textes alternatifs.
- [ ] JSON-LD synchronisé.
- [ ] Sitemap.
- [ ] Directive robots.
- [ ] Mesure analytics respectueuse.
- [ ] Test mobile.
- [ ] Test clavier.
- [ ] Test des liens.
- [ ] Test HTTP du fichier.
- [ ] Test complet de l’archive sur environnement vierge.

---

# 37. Sources internes

- `README.md`
- `VERSION`
- `flatcms.json`
- `LICENSE`
- `LICENSING.md`
- `COMMERCIAL_LICENSE.md`
- `TRADEMARK.md`
- `CLA.md`
- package FlatCMS v1.0.0 final ;
- notes de version ;
- changelog ;
- catalogue de release ;
- checksums ;
- pipeline de distribution ;
- rapports de validation.

---

# 38. Références externes

- GitHub Docs — About releases  
  https://docs.github.com/en/repositories/releasing-projects-on-github/about-releases

- GitHub Docs — Managing releases in a repository  
  https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository

- PHP — Supported Versions  
  https://www.php.net/supported-versions.php

- GNU — Affero General Public License  
  https://www.gnu.org/licenses/agpl-3.0.html

Les sources externes encadrent la publication de releases GitHub, le
support PHP et le texte de la licence AGPL. Le contenu et le statut de
l’artefact FlatCMS restent définis par les fichiers et la chaîne de
release officiels du projet.

---

# 39. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction complète de la page Téléchargement | ChatGPT / Alain BROYE |

---

# 40. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer LICENSING_CONTENT.md
```

Ce document contiendra la rédaction complète de la page :

```text
/fr-FR/licences/
```

Il expliquera le Core sous AGPL-3.0-or-later, les licences tierces, les
composants commerciaux, la CLA et les règles d’utilisation de la marque,
sans remplacer les textes juridiques officiels.
