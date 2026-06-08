# DOCUMENTATION_MAP — Cartographie de la documentation officielle FlatCMS

> **Document directeur de documentation**
>
> Projet : FlatCMS  
> Domaine cible : `https://flat-cms.fr`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Documents parents : `SEO.md`, `SITE_ARCHITECTURE.md`, `CONTENT_MATRIX.md`  
> Statut : cartographie initiale à valider avant migration du wiki

---

## 1. Objet du document

Ce document définit l’organisation complète de la future documentation officielle de FlatCMS.

Il précise :

- les catégories documentaires ;
- les parcours selon le profil du lecteur ;
- la hiérarchie des pages ;
- les dépendances entre contenus ;
- les prérequis ;
- les conventions d’URL ;
- la gestion des versions ;
- la stratégie multilingue ;
- la migration des contenus du wiki ;
- les modèles de pages ;
- les preuves techniques attendues ;
- les lacunes documentaires à combler ;
- l’ordre de production recommandé.

La documentation sera publiée sous :

```text
https://flat-cms.fr/{locale}/documentation/
```

La version française `fr-FR` constituera la source éditoriale initiale avant traduction.

---

## 2. Principes directeurs

## 2.1 Le code réel reste la source de vérité

La documentation doit décrire uniquement :

- les composants présents ;
- les comportements vérifiés ;
- les conventions réellement utilisées ;
- les versions explicitement concernées ;
- les fonctionnalités dont le statut est connu.

Toute page devra indiquer, lorsque nécessaire :

```text
Core
Optionnel
Premium
Expérimental
Prévu
Obsolète
```

Aucune fonctionnalité future ne doit être présentée comme disponible.

---

## 2.2 Quatre types de documentation

La documentation FlatCMS distingue quatre besoins.

### Tutoriel

Le tutoriel accompagne un nouvel utilisateur vers un résultat complet.

Exemples :

- installer FlatCMS en local ;
- créer son premier site ;
- publier une première page ;
- créer un premier module simple.

### Guide pratique

Le guide répond à un objectif précis.

Exemples :

- configurer Nginx ;
- activer une langue ;
- restaurer une sauvegarde ;
- créer une redirection ;
- résoudre une erreur de permissions.

### Référence

La référence décrit précisément un composant, une option ou un contrat.

Exemples :

- structure d’un module ;
- configuration `app.php` ;
- routes ;
- hooks ;
- manifestes ;
- structure des fichiers JSON.

### Explication

L’explication permet de comprendre les choix et concepts.

Exemples :

- pourquoi HMVC ;
- comment fonctionne le stockage flat-file ;
- pourquoi le document root pointe vers `public/` ;
- différences entre Core, module et service.

Ces quatre types ne doivent pas être fusionnés dans une même page sauf nécessité claire.

---

## 2.3 Une page canonique par sujet

Chaque sujet possède une page principale.

Exemple :

```text
Sujet : document root public

Explication :
/fr-FR/architecture/securite/document-root-public/

Guide :
/fr-FR/documentation/deploiement/configurer-document-root-public/

Référence :
/fr-FR/documentation/reference/arborescence/public/
```

Les trois pages peuvent exister, mais chacune répond à une intention différente et pointe vers les deux autres.

---

## 2.4 Documentation orientée résultat

Une page doit annoncer clairement :

- ce que le lecteur va apprendre ou accomplir ;
- les prérequis ;
- la version concernée ;
- le résultat attendu ;
- la méthode de vérification ;
- les erreurs courantes ;
- l’étape suivante.

---

## 3. Architecture générale

```text
/fr-FR/documentation/
├── demarrage/
├── tutoriels/
├── guides/
├── reference/
├── explications/
├── administration/
├── developpement/
├── deploiement/
├── securite/
├── maintenance/
├── depannage/
├── versions/
└── contribuer/
```

Cette architecture peut être présentée dans la navigation sous des libellés plus accessibles :

```text
Commencer
Utiliser FlatCMS
Personnaliser
Développer
Déployer
Maintenir
Résoudre un problème
Référence
```

---

# 4. Page d’entrée de la documentation

## URL

```text
/fr-FR/documentation/
```

## Objectif

Orienter rapidement le lecteur selon :

- son niveau ;
- son rôle ;
- son objectif ;
- son environnement ;
- la version de FlatCMS.

## Blocs proposés

```text
Nouveau sur FlatCMS
Administrer un site
Créer des contenus
Personnaliser un thème
Développer un module
Déployer en production
Sécuriser une installation
Résoudre un problème
Consulter la référence
```

## Recherche documentaire

La recherche doit :

- indexer les titres ;
- indexer les sous-titres ;
- indexer le contenu textuel ;
- filtrer par version ;
- filtrer par catégorie ;
- filtrer par statut produit ;
- afficher le chemin hiérarchique ;
- privilégier les correspondances exactes.

Les pages de résultats internes ne doivent pas devenir des pages SEO concurrentes.

---

# 5. Parcours par profil

## 5.1 Nouveau propriétaire de site

```text
Présentation
→ Prérequis
→ Installation
→ Premier accès
→ Paramètres essentiels
→ Créer une page
→ Créer un article
→ Ajouter des médias
→ Configurer le menu
→ Mettre en production
→ Sauvegarder
```

## 5.2 Administrateur

```text
Tableau de bord
→ Utilisateurs et rôles
→ Langues
→ Thèmes
→ Modules
→ Sauvegardes
→ Corbeille
→ Maintenance
→ Sécurité
→ Journaux
```

## 5.3 Rédacteur

```text
Créer une page
→ Publier un article
→ Catégories
→ Médias
→ SEO d’un contenu
→ Planification
→ Traductions
→ Révisions
```

## 5.4 Intégrateur frontend

```text
Architecture des thèmes
→ Thème frontend
→ Assets
→ Vues
→ Menus
→ Footer
→ Widgets
→ Responsive
→ Accessibilité
→ Performance
```

## 5.5 Développeur PHP

```text
Architecture FlatCMS
→ HMVC
→ PSR-4
→ Cycle de requête
→ Routes
→ Contrôleurs
→ Services
→ Stockage JSON
→ Modules
→ Hooks
→ Sécurité
→ Tests
```

## 5.6 Exploitant serveur

```text
Prérequis
→ Document root public
→ Apache
→ Nginx
→ PHP-FPM
→ Permissions
→ HTTPS
→ Cache
→ Sauvegardes
→ Mise à jour
→ Journaux
→ Dépannage
```

---

# 6. Section Démarrage

```text
/documentation/demarrage/
├── presentation/
├── prerequis/
├── telechargement/
├── installation-rapide/
├── premier-acces/
├── premier-site/
├── concepts-essentiels/
└── prochaine-etape/
```

## Pages

### Présentation de FlatCMS

Objectif :

- expliquer le périmètre ;
- présenter le Core ;
- distinguer gratuit, premium et expérimental ;
- indiquer la version stable.

### Prérequis

Doit couvrir :

- version PHP ;
- extensions PHP requises ;
- serveur web ;
- droits d’écriture ;
- HTTPS ;
- espace disque ;
- limites d’hébergement ;
- recommandations de production.

### Téléchargement

Doit indiquer :

- source officielle ;
- version ;
- checksum ;
- licence ;
- contenu de l’archive ;
- vérification de l’intégrité.

### Installation rapide

Parcours générique minimal :

```text
Télécharger
→ Extraire
→ Configurer le document root
→ Vérifier les droits
→ Lancer index.php?step=1
→ Finaliser
```

### Premier accès

Doit couvrir :

- connexion à l’administration ;
- compte initial ;
- changement du mot de passe ;
- réglages principaux ;
- sécurité immédiate.

### Premier site

Tutoriel complet :

```text
Créer une page d’accueil
Créer un article
Ajouter une image
Créer un menu
Choisir un thème
Publier
```

---

# 7. Section Tutoriels

```text
/documentation/tutoriels/
├── premier-site/
├── blog-multilingue/
├── theme-personnalise/
├── module-simple/
├── site-vitrine/
└── migration-site-statique/
```

## Règle

Un tutoriel :

- accompagne le lecteur ;
- limite les variantes ;
- utilise un environnement de référence ;
- produit un résultat visible ;
- ne tente pas de couvrir toutes les options ;
- renvoie vers les pages de référence pour les détails.

## Tutoriels prioritaires

### Créer un site vitrine

Résultat :

- accueil ;
- services ;
- à propos ;
- contact ;
- menu ;
- footer ;
- SEO de base.

### Créer un blog multilingue

Résultat :

- deux locales actives ;
- articles traduits ;
- catégories ;
- sélecteur de langue ;
- URLs locales ;
- vérification `hreflang`.

### Créer un module simple

Résultat :

- module chargé ;
- route ;
- contrôleur ;
- vue ;
- manifeste ;
- activation.

### Créer un thème frontend

Résultat :

- thème détecté ;
- template principal ;
- assets ;
- page affichée ;
- activation dans l’administration.

---

# 8. Section Guides pratiques

```text
/documentation/guides/
├── contenus/
├── medias/
├── navigation/
├── utilisateurs/
├── langues/
├── themes/
├── modules/
├── builders/
├── seo/
├── sauvegardes/
└── mises-a-jour/
```

## 8.1 Contenus

```text
creer-page/
modifier-page/
publier-article/
programmer-publication/
creer-categorie/
definir-slug/
gerer-brouillon/
dupliquer-contenu/
traduire-contenu/
```

## 8.2 Médias

```text
televerser-image/
optimiser-image/
texte-alternatif/
remplacer-media/
supprimer-media/
restaurer-media/
organiser-bibliotheque/
```

## 8.3 Navigation

```text
creer-menu/
ajouter-lien/
reordonner-menu/
menu-multilingue/
configurer-footer/
```

## 8.4 Utilisateurs

```text
creer-utilisateur/
attribuer-role/
reinitialiser-mot-de-passe/
activer-2fa/
desactiver-compte/
```

## 8.5 Langues

```text
activer-locale/
definir-langue-par-defaut/
traduire-interface/
traduire-contenu/
verifier-chaines-manquantes/
```

## 8.6 Thèmes

```text
installer-theme/
activer-theme/
personnaliser-theme/
mettre-a-jour-theme/
desinstaller-theme/
```

## 8.7 Modules

```text
installer-module/
activer-module/
configurer-module/
mettre-a-jour-module/
desactiver-module/
desinstaller-module/
```

## 8.8 SEO

```text
definir-title/
meta-description/
url-canonique/
open-graph/
donnees-structurees/
sitemap/
robots/
redirections/
```

---

# 9. Section Administration

```text
/documentation/administration/
├── tableau-de-bord/
├── pages/
├── articles/
├── categories/
├── medias/
├── menus/
├── footer/
├── utilisateurs/
├── langues/
├── themes/
├── modules/
├── sauvegardes/
├── corbeille/
└── parametres/
```

Chaque page de module doit préciser :

- rôle du module ;
- statuts disponibles ;
- permissions nécessaires ;
- données manipulées ;
- actions destructrices ;
- liens vers les guides ;
- liens vers la référence.

---

# 10. Section Développement

```text
/documentation/developpement/
├── introduction/
├── environnement/
├── architecture/
├── psr-4/
├── hmvc/
├── routes/
├── controleurs/
├── vues/
├── services/
├── modeles/
├── stockage-json/
├── modules/
├── hooks/
├── extensions/
├── themes/
├── widgets/
├── securite/
├── internationalisation/
└── bonnes-pratiques/
```

## 10.1 Environnement

Doit documenter :

- PHP pris en charge ;
- serveur local ;
- configuration recommandée ;
- mode développement ;
- journaux ;
- validation syntaxique ;
- validation JSON.

## 10.2 Routes

Pages distinctes :

```text
comprendre-routes/
declarer-route/
parametres-route/
routes-administration/
routes-module/
depanner-route/
```

## 10.3 Contrôleurs et vues

```text
base-controller/
creer-controleur/
retourner-reponse/
rendre-vue/
transmettre-donnees/
echappement-sorties/
```

## 10.4 Services

```text
role-services/
creer-service/
injecter-dependance/
services-transversaux/
services-module/
```

## 10.5 Modules

```text
structure-module/
manifest-module/
cycle-vie/
routes-module/
controleurs-module/
services-module/
vues-module/
assets-module/
configuration-module/
permissions-module/
internationalisation-module/
tests-module/
distribution-module/
```

## 10.6 Hooks

```text
concept-hooks/
declarer-hook/
ecouter-hook/
priorite-hooks/
donnees-transmises/
securite-hooks/
```

## 10.7 Stockage JSON

```text
organisation-data/
classe-flatfile/
lire-json/
ecrire-json/
validation/
verrouillage/
atomicite/
sauvegardes/
migration-schema/
```

Les notions de verrouillage, atomicité et migration ne doivent être documentées comme garanties que si le code réel les implémente.

---

# 11. Section Déploiement

```text
/documentation/deploiement/
├── vue-ensemble/
├── document-root-public/
├── apache/
├── nginx/
├── php-fpm/
├── mamp/
├── wamp/
├── linux/
├── windows/
├── macos/
├── synology/
├── raspberry-pi/
├── hebergement-mutualise/
├── https/
├── permissions/
└── checklist-production/
```

## Guides P0

1. Document root `public/`
2. Apache
3. Nginx
4. MAMP
5. WAMP
6. Permissions
7. HTTPS
8. Checklist de production

## Structure type d’un guide serveur

```text
Environnement testé
Prérequis
Arborescence attendue
Configuration complète
Commandes
Redémarrage
Vérification
Résultat attendu
Erreurs fréquentes
Sécurité
```

Chaque configuration doit être datée et mentionner les versions testées.

---

# 12. Section Sécurité

```text
/documentation/securite/
├── vue-ensemble/
├── document-root/
├── permissions/
├── authentification/
├── sessions/
├── csrf/
├── roles/
├── secrets/
├── fichiers-sensibles/
├── mises-a-jour/
├── sauvegardes/
├── signaler-vulnerabilite/
└── checklist/
```

## Règles éditoriales

- ne jamais présenter une protection comme absolue ;
- distinguer mécanisme, configuration et recommandation ;
- ne jamais publier de secret réel ;
- expliquer l’impact d’une mauvaise configuration ;
- indiquer la version concernée ;
- fournir une méthode de vérification.

---

# 13. Section Maintenance

```text
/documentation/maintenance/
├── sauvegardes/
├── restauration/
├── cache/
├── journaux/
├── mises-a-jour/
├── migration-version/
├── verification-integrite/
├── nettoyage/
└── surveillance/
```

## Guides prioritaires

- sauvegarder le site complet ;
- restaurer une sauvegarde ;
- vider le cache ;
- consulter les journaux ;
- mettre à jour FlatCMS ;
- vérifier l’intégrité JSON ;
- préparer un retour arrière.

---

# 14. Section Dépannage

```text
/documentation/depannage/
├── installation/
├── permissions/
├── routage/
├── apache/
├── nginx/
├── php/
├── pages/
├── medias/
├── modules/
├── themes/
├── multilingue/
├── email/
└── performances/
```

## Modèle de page dépannage

```text
Symptôme
Message exact
Environnement
Causes probables
Diagnostic
Solution
Vérification
Prévention
Pages liées
```

## Pages prioritaires issues des problèmes réels

- installateur bloqué à l’étape 1 ;
- module introuvable ;
- impossible d’écrire `.htaccess` ;
- impossible d’écrire `nginx.conf` ;
- erreur 500 après installation ;
- URLs propres indisponibles ;
- médias non inscriptibles ;
- PHP non détecté ;
- mauvais document root ;
- droits du serveur web insuffisants.

---

# 15. Section Référence

```text
/documentation/reference/
├── arborescence/
├── configuration/
├── routes/
├── hooks/
├── modules/
├── manifestes/
├── services/
├── stockage/
├── modeles-json/
├── roles-permissions/
├── locales/
├── themes/
├── cli/
├── erreurs/
└── constantes/
```

## Règle

La référence doit être :

- précise ;
- exhaustive dans son périmètre ;
- peu narrative ;
- versionnée ;
- facilement consultable ;
- accompagnée d’exemples minimaux.

---

# 16. Section Explications

```text
/documentation/explications/
├── pourquoi-flat-file/
├── pourquoi-hmvc/
├── pourquoi-psr-4/
├── core-et-modules/
├── services/
├── hooks/
├── stockage-json/
├── document-root-public/
├── multilingue/
├── donnees-structurees/
└── agent-ready/
```

Ces pages répondent principalement à « pourquoi ? » et « comment cela fonctionne ? ».

Elles ne doivent pas devenir des guides procéduraux trop détaillés.

---

# 17. Versionnement documentaire

## 17.1 Version stable

```text
/fr-FR/documentation/
```

pointe vers la documentation de la version LTS stable courante.

## 17.2 Versions archivées

```text
/fr-FR/documentation/versions/1.0/
```

ou, si nécessaire :

```text
/fr-FR/documentation/v1/
```

## 17.3 Version en développement

```text
/fr-FR/documentation/next/
```

Cette section doit être clairement marquée :

```text
Documentation de développement
Non applicable à la version stable
```

## 17.4 Règles

Chaque page doit indiquer :

- version minimale ;
- version maximale si obsolète ;
- date de dernière vérification ;
- statut ;
- remplaçant éventuel.

---

# 18. Multilingue

Locales :

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

## 18.1 Principe

Chaque traduction dispose d’une URL distincte.

```text
/fr-FR/documentation/installation/
/en-US/documentation/installation/
/de-DE/dokumentation/installation/
/es-ES/documentacion/instalacion/
/it-IT/documentazione/installazione/
/pt-PT/documentacao/instalacao/
```

## 18.2 Correspondances

Chaque page traduite doit :

- se référencer elle-même ;
- référencer toutes les versions disponibles ;
- être référencée en retour ;
- inclure `x-default` lorsque pertinent ;
- contenir un contenu réellement traduit.

Google recommande que chaque version locale liste sa propre URL et toutes les autres versions, avec des liens réciproques et des URL absolues.

## 18.3 Traductions partielles

Si une page n’est pas encore traduite :

- ne pas créer une page vide ;
- ne pas dupliquer automatiquement le français sous une locale étrangère ;
- proposer un lien explicite vers la version française ;
- ne déclarer dans `hreflang` que les versions réellement disponibles.

---

# 19. Navigation documentaire

## 19.1 Fil d’Ariane

Exemple :

```text
Documentation
› Développement
› Modules
› Créer un module
```

Le fil d’Ariane :

- doit être visible ;
- doit être constitué de liens HTML ;
- doit refléter la hiérarchie logique ;
- peut être accompagné de `BreadcrumbList`.

## 19.2 Navigation latérale

Elle doit :

- afficher la section active ;
- permettre de développer les catégories ;
- conserver une profondeur raisonnable ;
- fonctionner au clavier ;
- rester utilisable sur mobile.

## 19.3 Précédent / Suivant

Utilisé pour :

- tutoriels ;
- parcours guidés ;
- chapitres ordonnés.

Éviter sur les pages de référence indépendantes lorsque l’ordre n’apporte rien.

## 19.4 Pages liées

Chaque page affiche :

- concepts associés ;
- guides associés ;
- référence associée ;
- dépannage associé ;
- prochaine étape.

---

# 20. Modèles de pages

## 20.1 Tutoriel

```text
Titre
Résultat final
Niveau
Durée indicative facultative
Version
Prérequis
Étapes
Points de contrôle
Résultat
Étape suivante
```

## 20.2 Guide pratique

```text
Titre orienté action
Objectif
Environnement
Prérequis
Procédure
Vérification
Erreurs fréquentes
Retour arrière
Pages liées
```

## 20.3 Référence

```text
Nom du composant
Disponibilité
Emplacement
Signature ou structure
Paramètres
Valeurs
Retour
Erreurs
Exemple minimal
Notes de version
```

## 20.4 Explication

```text
Question centrale
Résumé
Contexte
Concepts
Fonctionnement
Choix de conception
Avantages
Limites
Liens pratiques
```

## 20.5 Dépannage

```text
Symptôme
Erreur exacte
Causes
Diagnostic
Correction
Vérification
Prévention
```

---

# 21. Métadonnées documentaires

Chaque page doit stocker ou afficher :

```text
title
description
locale
version
type_documentation
statut_produit
niveau
auteur
date_publication
date_modification
date_derniere_verification
prerequis
pages_liees
ancienne_url
```

Types documentaires :

```text
tutorial
how-to
reference
explanation
troubleshooting
release-note
```

---

# 22. SEO, GIO et citabilité

Chaque page doit proposer :

- une réponse directe au début ;
- un vocabulaire précis ;
- des titres autonomes ;
- des exemples reproductibles ;
- des résultats attendus ;
- des sources primaires ;
- une version et une date ;
- un auteur ou responsable ;
- une hiérarchie visible ;
- des liens vers les concepts nécessaires.

## Blocs recommandés

```text
En bref
Prérequis
Procédure
Résultat attendu
Vérification
Limites
Erreurs fréquentes
Sources
```

Le contenu essentiel doit être présent dans le HTML rendu et ne pas dépendre uniquement d’une interface JavaScript.

---

# 23. Données structurées

## Types possibles

| Type de page | Données structurées |
|---|---|
| Page documentation | `TechArticle`, `BreadcrumbList` |
| Tutoriel | `TechArticle`, éventuellement `HowTo` |
| Guide | `TechArticle`, éventuellement `HowTo` |
| Référence | `TechArticle`, `BreadcrumbList` |
| Vidéo | `VideoObject` |
| Auteur | `Person` ou `ProfilePage` si pertinent |

## Règles

- le balisage décrit le contenu visible ;
- le fil d’Ariane reflète la hiérarchie réelle ;
- le JSON-LD est validé ;
- aucune donnée fictive n’est ajoutée ;
- les types non pertinents sont évités.

---

# 24. Migration du wiki actuel

## 24.1 Inventaire

Créer un tableau contenant :

| Ancienne URL | Locale | Type | Sujet | Qualité | Décision | Nouvelle URL |
|---|---|---|---|---|---|---|

## 24.2 Décisions possibles

```text
Conserver
Réécrire
Fusionner
Scinder
Archiver
Supprimer
Rediriger
```

## 24.3 Classification

Chaque page existante doit être reclassée :

- tutoriel ;
- guide ;
- référence ;
- explication ;
- dépannage ;
- article de blog ;
- contenu obsolète.

## 24.4 Règles de migration

- une redirection 301 vers l’équivalent le plus proche ;
- pas de redirection massive vers l’accueil ;
- correction des contenus obsolètes ;
- conservation des dates pertinentes ;
- mise à jour des captures ;
- vérification des liens ;
- migration des médias ;
- suppression des doublons ;
- ajout des pages au bon cluster.

La table opérationnelle sera stockée dans :

```text
REDIRECTS.md
```

---

# 25. Lacunes documentaires prioritaires

## P0 — Avant lancement

- [ ] Prérequis exacts
- [ ] Installation générique
- [ ] Installation Apache
- [ ] Installation Nginx
- [ ] Document root public
- [ ] Permissions
- [ ] Premier accès
- [ ] Sauvegarde et restauration
- [ ] Mise à jour
- [ ] Dépannage installation
- [ ] Architecture générale
- [ ] Stockage JSON
- [ ] Modules
- [ ] Licences

## P1 — Adoption

- [ ] Pages
- [ ] Articles
- [ ] Catégories
- [ ] Médias
- [ ] Menus
- [ ] Utilisateurs
- [ ] Langues
- [ ] Thèmes
- [ ] Corbeille
- [ ] Sauvegardes
- [ ] SEO des contenus

## P1 — Développeurs

- [ ] PSR-4
- [ ] HMVC
- [ ] Routes
- [ ] Contrôleurs
- [ ] Vues
- [ ] Services
- [ ] Hooks
- [ ] Structure module
- [ ] Manifeste
- [ ] Modèles JSON
- [ ] Sécurité extension

## P2 — Écosystème

- [ ] Widgets
- [ ] Builders
- [ ] Module IA
- [ ] Données structurées
- [ ] Marketplace
- [ ] Contributions
- [ ] Traductions
- [ ] Création de thèmes avancés

---

# 26. Ordre de production

## Lot 1 — Mise en route

1. Présentation
2. Prérequis
3. Téléchargement
4. Installation
5. Document root public
6. Premier accès
7. Premier site

## Lot 2 — Exploitation

1. Apache
2. Nginx
3. Permissions
4. HTTPS
5. Sauvegardes
6. Restauration
7. Mise à jour
8. Dépannage

## Lot 3 — Administration

1. Pages
2. Articles
3. Catégories
4. Médias
5. Menus
6. Utilisateurs
7. Langues
8. Thèmes
9. Modules

## Lot 4 — Développement

1. Architecture
2. HMVC
3. PSR-4
4. Routes
5. Contrôleurs
6. Vues
7. Services
8. Stockage JSON
9. Modules
10. Hooks

## Lot 5 — Avancé

1. Thèmes
2. Widgets
3. Builders
4. Données structurées
5. IA
6. Marketplace
7. Contributions

---

# 27. Workflow de validation

Pour chaque page :

```text
1. Vérifier le comportement dans le code
2. Identifier la version
3. Définir le type documentaire
4. Rédiger
5. Tester la procédure
6. Vérifier les commandes
7. Vérifier le résultat
8. Ajouter les limites
9. Ajouter le dépannage
10. Ajouter les liens internes
11. Relire techniquement
12. Relire éditorialement
13. Publier
14. Traduire
15. Vérifier les hreflang
16. Réviser à chaque version
```

---

# 28. Responsabilités éditoriales

## Auteur

Rédige le contenu.

## Relecteur technique

Vérifie :

- code ;
- chemins ;
- commandes ;
- versions ;
- résultat attendu.

## Relecteur éditorial

Vérifie :

- clarté ;
- orthographe ;
- structure ;
- cohérence ;
- accessibilité.

## Responsable de version

Détermine :

- publication ;
- obsolescence ;
- archivage ;
- redirections ;
- mise à jour.

Une même personne peut occuper plusieurs rôles, mais les validations doivent rester explicites.

---

# 29. Critères de qualité

Une page est validée lorsque :

- [ ] son type documentaire est clair ;
- [ ] son public est identifié ;
- [ ] la version est indiquée ;
- [ ] les prérequis sont complets ;
- [ ] les étapes sont testées ;
- [ ] le résultat attendu est visible ;
- [ ] les limites sont expliquées ;
- [ ] les commandes sont copiables ;
- [ ] les chemins correspondent au code réel ;
- [ ] les liens internes sont valides ;
- [ ] le fil d’Ariane est correct ;
- [ ] les métadonnées sont présentes ;
- [ ] le contenu est accessible ;
- [ ] les données structurées correspondent au visible ;
- [ ] les traductions disponibles sont réciproques ;
- [ ] l’ancienne URL est redirigée si nécessaire.

---

# 30. Documents suivants

Après validation :

1. `KEYWORDS.md`
2. `REDIRECTS.md`
3. `STRUCTURED_DATA.md`
4. `CRAWL_POLICY.md`
5. `CONTENT_STYLE_GUIDE.md`
6. `MULTILINGUAL.md`
7. `LAUNCH_CHECKLIST.md`

---

# 31. Références externes

- Diátaxis — organisation des tutoriels, guides, références et explications  
  https://diataxis.fr/

- Google Search Central — fil d’Ariane  
  https://developers.google.com/search/docs/appearance/structured-data/breadcrumb

- Google Search Central — versions localisées et `hreflang`  
  https://developers.google.com/search/docs/specialty/international/localized-versions

---

# 32. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Création de la cartographie initiale de la documentation | ChatGPT / Alain BROYE |

---

# 33. Prochaine action

Créer :

```text
KEYWORDS.md
```

Ce document devra contenir :

- la cartographie des mots-clés ;
- les intentions ;
- les clusters ;
- les pages cibles ;
- les variantes linguistiques ;
- les niveaux de priorité ;
- les risques de cannibalisation ;
- les requêtes de marque ;
- les requêtes génériques ;
- les requêtes techniques ;
- les requêtes SEO/GEO/GIO.
