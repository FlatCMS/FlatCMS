# FEATURES_CONTENT — Fonctionnalités officielles de FlatCMS

> **Contenu éditorial prêt à intégrer**
>
> Projet : FlatCMS  
> Page : Fonctionnalités `fr-FR`  
> URL cible : `https://flat-cms.fr/fr-FR/fonctionnalites/`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Brief parent : `PAGE_BRIEFS.md` — `P0-FEATURES-FR`  
> Statut : première version rédactionnelle à relire, illustrer et valider contre le package de distribution final

---

## 1. Objectif de la page

Cette page présente les fonctionnalités réelles de FlatCMS et leur statut dans la gamme.

Elle doit permettre au visiteur de distinguer clairement :

- les fonctions incluses dans le **LTS Core open source** ;
- les modules optionnels ;
- les composants premium ;
- les fonctions expérimentales ;
- les évolutions prévues.

La page ne doit pas transformer une fondation technique, un prototype ou une intention de roadmap en fonctionnalité disponible.

Chaque bloc fonctionnel doit pouvoir être relié à :

- un module ou un service réel ;
- une page de documentation ;
- une capture de la version stable ;
- un statut de licence ;
- une version de disponibilité.

---

# 2. Métadonnées SEO

## URL canonique

```text
https://flat-cms.fr/fr-FR/fonctionnalites/
```

## Balise `<title>`

```text
Fonctionnalités de FlatCMS : pages, articles, médias et modules
```

## Meta description

```text
Explorez les fonctionnalités de FlatCMS : pages, articles, commentaires,
contact, médias, menus, footer, thèmes, utilisateurs et modules.
```

## Directive robots

```text
index, follow, max-image-preview:large
```

## Open Graph

### `og:title`

```text
Les fonctionnalités de FlatCMS
```

### `og:description`

```text
Découvrez le périmètre du LTS Core, les modules optionnels et les
composants premium du CMS PHP flat-file FlatCMS.
```

### `og:type`

```text
website
```

### `og:url`

```text
https://flat-cms.fr/fr-FR/fonctionnalites/
```

### `og:image`

```text
https://flat-cms.fr/assets/images/og/fonctionnalites-flatcms-fr-FR.webp
```

---

# 3. Statuts fonctionnels

Chaque fonctionnalité doit afficher un statut visible.

## Core

```text
Incluse dans la ligne stable FlatCMS LTS Core.
```

## Optionnelle

```text
Disponible sous la forme d’un module activable ou d’une configuration
complémentaire.
```

## Premium

```text
Composant commercial distinct du cœur open source.
```

## Expérimentale

```text
Fonction en cours de validation, non destinée à un usage de production
sans précaution.
```

## Prévue

```text
Évolution annoncée ou étudiée, mais non disponible dans la version
stable actuelle.
```

## Règle de publication

Une fonctionnalité ne doit porter le badge `Core` que si elle est :

- présente dans le package stable ;
- activable ou opérationnelle selon son contrat ;
- documentée ;
- testée dans le flux de validation de la version.

---

# 4. Hero

## Sur-titre

```text
FlatCMS v1.0.0 LTS Core
```

## H1

```text
Les fonctionnalités de FlatCMS
```

## Introduction

```text
FlatCMS réunit les fonctions essentielles d’un CMS dans un runtime PHP
natif, modulaire et sans serveur SQL : authentification, pages, articles,
commentaires, contact, médias, menus, footer, thèmes et installation.
```

```text
Des modules optionnels et des composants premium peuvent compléter ce
socle. Leur statut est indiqué clairement afin de distinguer ce qui est
inclus, activable, commercial, expérimental ou prévu.
```

## CTA principal

```text
Tester les fonctionnalités
```

Destination :

```text
https://demo.flat-cms.fr/
```

## CTA secondaire

```text
Consulter la documentation
```

Destination :

```text
/fr-FR/documentation/
```

## Lien tertiaire

```text
Télécharger le LTS Core
```

Destination :

```text
/fr-FR/telechargement/
```

## Réassurance

```text
PHP natif · HMVC · PSR-4 · JSON · Modules · Thèmes · Multilingue
```

---

# 5. Vue d’ensemble du LTS Core

## H2

```text
Un cœur stable centré sur les fonctions essentielles
```

## Texte

```text
FlatCMS LTS Core constitue la ligne stable du projet. Son périmètre est
volontairement limité aux fondations durables du CMS et à ses fonctions
éditoriales principales.
```

```text
Les branches d’expérimentation, les outils commerciaux et les fonctions
d’authoring encore instables restent séparés du dépôt du cœur.
```

## Périmètre annoncé du Core

| Fonction | Statut initial | Description courte |
|---|---|---|
| Authentification | Core | Connexion et protection de l’administration |
| Pages | Core | Gestion des contenus permanents |
| Articles | Core | Publication de contenus éditoriaux |
| Commentaires | Core | Gestion des échanges liés aux contenus |
| Contact | Core | Formulaires et demandes de contact |
| Médias | Core | Gestion des images et fichiers |
| Menus | Core | Organisation de la navigation |
| Footer | Core | Gestion du pied de page |
| Thèmes | Core | Présentation frontend et administration |
| Installateur | Core | Initialisation et configuration du CMS |

## Note de validation

```text
Le tableau final publié devra être comparé au package FlatCMS v1.0.0
effectivement distribué. Toute fonction absente ou désactivée par défaut
devra être reclassée avant publication.
```

---

# 6. Gestion des pages

## H2

```text
Créer et organiser les pages du site
```

## Statut

```text
Core
```

## Texte

```text
Le module Pages permet de gérer les contenus permanents du site :
accueil, présentation, services, documentation, contact ou toute autre
page structurante.
```

## Fonctions attendues

- création d’une page ;
- modification du contenu ;
- titre et slug ;
- statut de publication ;
- métadonnées SEO ;
- association à une locale ;
- ordre ou hiérarchie selon l’implémentation ;
- sélection du thème ou du template lorsque disponible ;
- suppression et restauration selon le module Corbeille ;
- prévisualisation selon le workflow validé.

## Stockage

```text
Les pages sont enregistrées dans le stockage flat-file de FlatCMS,
selon le modèle JSON et l’arborescence de la version utilisée.
```

## Bénéfices

### Contenu distinct du thème

```text
Le contenu éditorial reste séparé du rendu visuel, ce qui permet de
faire évoluer l’apparence sans réécrire toutes les pages.
```

### URLs descriptives

```text
Chaque page peut utiliser une URL lisible et adaptée à sa langue.
```

### Métadonnées locales

```text
Le title, la meta description et les autres informations éditoriales
peuvent être gérés par contenu et par locale selon les fonctions SEO
disponibles.
```

## CTA

```text
Découvrir la gestion des pages
```

Destination :

```text
/fr-FR/fonctionnalites/pages/
```

## Preuves attendues

- capture de la liste des pages ;
- capture de l’éditeur ;
- modèle JSON réel ;
- documentation du cycle de publication.

---

# 7. Articles et blog

## H2

```text
Publier des articles et construire un blog intégré
```

## Statut

```text
Core
```

## Texte

```text
Le module Posts permet de publier des contenus éditoriaux datés et de
les intégrer directement au site officiel, sans installer une plateforme
de blog séparée.
```

## Fonctions attendues

- création et modification d’un article ;
- titre, slug et résumé ;
- contenu principal ;
- auteur ;
- date de publication ;
- date de modification ;
- statut de publication ;
- catégorie ;
- image principale ;
- métadonnées SEO ;
- locale ;
- commentaires selon configuration ;
- publication programmée uniquement si la fonction est validée.

## Cas d’usage

- actualités du projet ;
- tutoriels ;
- analyses techniques ;
- comparatifs ;
- notes de version ;
- études de cas ;
- contenus multilingues.

## Données structurées

```text
Les articles peuvent alimenter un graphe JSON-LD de type BlogPosting
lorsque les informations visibles et les métadonnées nécessaires sont
disponibles.
```

## CTA

```text
Découvrir les articles FlatCMS
```

Destination :

```text
/fr-FR/fonctionnalites/articles/
```

## Preuves attendues

- liste des articles ;
- écran d’édition ;
- modèle de données ;
- rendu d’un article ;
- provider `PostSchemaProvider`.

---

# 8. Catégories éditoriales

## H2

```text
Classer les contenus par catégorie
```

## Statut

```text
Core ou optionnelle à confirmer dans le package final
```

## Texte

```text
Les catégories permettent de regrouper les articles par sujet et de
construire une navigation éditoriale cohérente.
```

## Fonctions possibles

- création d’une catégorie ;
- titre et slug ;
- description ;
- locale ;
- association aux articles ;
- page d’archive ;
- ordre d’affichage ;
- métadonnées de catégorie selon l’implémentation.

## Règle SEO

```text
Une archive de catégorie ne doit être indexée que si elle contient des
articles et apporte une description éditoriale utile.
```

## Point de validation

```text
Le statut Core de la gestion des catégories doit être confirmé contre
le package LTS distribué et sa documentation.
```

---

# 9. Commentaires

## H2

```text
Gérer les commentaires liés aux contenus
```

## Statut

```text
Core selon le périmètre annoncé du LTS Core
```

## Texte

```text
FlatCMS peut intégrer un système de commentaires afin de permettre les
échanges autour des articles ou des contenus compatibles.
```

## Fonctions à confirmer

- activation globale ;
- activation par contenu ;
- modération ;
- validation avant publication ;
- suppression ;
- signalement ;
- protection anti-spam ;
- notification ;
- identité de l’auteur ;
- consentement et confidentialité ;
- statut des liens publiés.

## Sécurité

```text
Les commentaires constituent du contenu généré par les utilisateurs.
Ils doivent être validés, échappés, protégés contre le spam et soumis
aux règles de modération du site.
```

## SEO

```text
Les liens et contenus publiés par des utilisateurs ne doivent pas
devenir une source de spam ou de manipulation du référencement.
```

## Preuves attendues

- module ou contrôleur réel ;
- interface de modération ;
- protection CSRF ;
- validation des entrées ;
- règles de confidentialité.

---

# 10. Contact

## H2

```text
Recevoir les demandes depuis le site
```

## Statut

```text
Core selon le périmètre annoncé du LTS Core
```

## Texte

```text
Le module Contact permet de publier un formulaire afin de recevoir les
demandes des visiteurs depuis une page du site.
```

## Fonctions à confirmer

- création ou configuration du formulaire ;
- champs obligatoires ;
- validation serveur ;
- protection CSRF ;
- protection anti-spam ;
- destinataire ;
- objet du message ;
- confirmation utilisateur ;
- journalisation contrôlée ;
- consentement ;
- pièces jointes éventuelles ;
- modèles d’e-mail ;
- localisation des messages.

## Prérequis

- configuration d’envoi d’e-mail ;
- DNS d’authentification du domaine ;
- adresse de destination ;
- politique de confidentialité ;
- protection contre les abus.

## Transparence

```text
La présence du module ne garantit pas la délivrabilité des messages.
SPF, DKIM, DMARC, la réputation du domaine et la configuration du
fournisseur de messagerie restent déterminants.
```

## CTA

```text
Consulter la documentation du formulaire de contact
```

Destination future :

```text
/fr-FR/documentation/guides/contact/
```

---

# 11. Médiathèque

## H2

```text
Centraliser les images et les fichiers
```

## Statut

```text
Core
```

## Texte

```text
La médiathèque centralise les ressources utilisées par les pages, les
articles et les thèmes : images, documents et autres fichiers autorisés.
```

## Fonctions attendues

- téléversement ;
- aperçu ;
- métadonnées ;
- texte alternatif ;
- titre ;
- légende ;
- dimensions ;
- type MIME ;
- suppression ;
- réutilisation ;
- filtrage ou recherche selon l’interface ;
- restauration selon le périmètre de la corbeille ;
- optimisation selon les fonctions disponibles.

## Sécurité

- contrôle de l’extension ;
- contrôle du type MIME ;
- noms de fichiers normalisés ;
- taille maximale ;
- permissions ;
- prévention de l’exécution de fichiers ;
- protection des chemins ;
- nettoyage des métadonnées sensibles si prévu.

## Accessibilité

```text
Le texte alternatif doit décrire la fonction ou l’information de
l’image dans son contexte. Une image décorative doit pouvoir utiliser
une alternative vide.
```

## Performance

```text
La médiathèque doit encourager des dimensions adaptées, des formats
modernes et une utilisation responsive des images.
```

## CTA

```text
Découvrir la médiathèque
```

Destination :

```text
/fr-FR/fonctionnalites/medias/
```

---

# 12. Menus et navigation

## H2

```text
Organiser la navigation du site
```

## Statut

```text
Core
```

## Texte

```text
Le module Menu permet d’organiser les liens principaux du site sans
modifier directement les templates du thème.
```

## Fonctions attendues

- création d’un menu ;
- ajout de pages ;
- liens externes ;
- liens personnalisés ;
- ordre des éléments ;
- niveaux de navigation selon l’implémentation ;
- menu par emplacement ;
- menu par locale ;
- activation ou désactivation ;
- rendu accessible dans le thème.

## Bonnes pratiques

- liens HTML réels ;
- ancres descriptives ;
- navigation clavier ;
- état actif ;
- menu mobile ;
- nombre raisonnable d’entrées ;
- cohérence entre locales.

## MenuBuilder

```text
Les fonctions visuelles avancées de construction de menus peuvent
relever du composant commercial MenuBuilder et doivent être distinguées
du module Menu inclus dans le Core.
```

## CTA

```text
Découvrir la gestion des menus
```

Destination :

```text
/fr-FR/fonctionnalites/menus/
```

---

# 13. Footer

## H2

```text
Gérer les informations du pied de page
```

## Statut

```text
Core
```

## Texte

```text
Le module Footer permet de gérer les informations communes affichées au
bas du site : navigation secondaire, mentions, coordonnées, réseaux ou
éléments éditoriaux selon le thème.
```

## Fonctions attendues

- contenus du footer ;
- menus secondaires ;
- blocs ou colonnes selon le thème ;
- liens légaux ;
- informations de contact ;
- locale ;
- activation des éléments ;
- rendu responsive.

## FooterBuilder

```text
FooterBuilder est un composant premium destiné à proposer une composition
visuelle plus avancée. Il ne doit pas être confondu avec le module Footer
du Core.
```

## Accessibilité

- titres de colonnes cohérents ;
- liens descriptifs ;
- ordre logique ;
- contraste ;
- navigation clavier ;
- absence de contenu essentiel uniquement dans une image.

---

# 14. Authentification

## H2

```text
Protéger l’administration et les comptes
```

## Statut

```text
Core
```

## Texte

```text
Le module Auth gère l’accès à l’administration et constitue une fondation
de sécurité du CMS.
```

## Fonctions attendues

- connexion ;
- déconnexion ;
- session ;
- vérification du compte ;
- protection des routes d’administration ;
- récupération ou réinitialisation du mot de passe selon la version ;
- contrôle des tentatives selon l’implémentation ;
- 2FA uniquement si validée et activée.

## Sécurité

- mots de passe hachés ;
- cookies `HttpOnly` ;
- cookies `Secure` en HTTPS ;
- politique `SameSite` ;
- régénération de session ;
- CSRF ;
- limitation de débit ;
- messages d’erreur non révélateurs ;
- journalisation contrôlée.

## Règle

```text
La page publique ne doit pas annoncer une fonction de sécurité précise
avant validation de son implémentation dans le package stable.
```

---

# 15. Utilisateurs, rôles et permissions

## H2

```text
Gérer les utilisateurs et leurs responsabilités
```

## Statut

```text
Core ou module stable à confirmer dans la distribution finale
```

## Texte

```text
La gestion des utilisateurs permet d’attribuer les accès à
l’administration selon le rôle de chaque personne.
```

## Rôles possibles selon la configuration

- Super Administrateur ;
- Administrateur ;
- Éditeur ;
- Démonstration ;
- rôles métier optionnels.

## Fonctions attendues

- créer un utilisateur ;
- modifier le profil ;
- attribuer un rôle ;
- activer ou désactiver un compte ;
- réinitialiser un mot de passe ;
- consulter les permissions ;
- limiter les actions ;
- gérer les comptes de démonstration.

## Principe du moindre privilège

```text
Chaque utilisateur doit disposer uniquement des permissions nécessaires
à son activité.
```

## Point de validation

```text
Les rôles réellement livrés, leurs permissions et leurs noms doivent
être confirmés avant publication.
```

## CTA

```text
Découvrir la gestion des utilisateurs
```

Destination :

```text
/fr-FR/fonctionnalites/utilisateurs/
```

---

# 16. Thèmes frontend et administration

## H2

```text
Personnaliser le site sans confondre le frontend et l’administration
```

## Statut

```text
Core
```

## Texte

```text
FlatCMS sépare les thèmes destinés aux visiteurs et les thèmes de
l’interface d’administration.
```

## Deux périmètres

### Thème frontend

```text
Il contrôle le rendu public : mise en page, navigation, composants,
styles et intégration des contenus.
```

### Thème d’administration

```text
Il contrôle l’interface de gestion sans modifier le rendu public.
```

## Fonctions attendues

- découverte des thèmes ;
- activation ;
- informations du thème ;
- assets ;
- vues ;
- configuration selon le contrat ;
- prévisualisation selon la version ;
- séparation admin/frontend ;
- mises à jour contrôlées.

## Développement

```text
Un thème doit respecter les conventions de FlatCMS, l’échappement des
sorties, l’accessibilité, la performance et les emplacements de rendu.
```

## CTA

```text
Découvrir les thèmes FlatCMS
```

Destination :

```text
/fr-FR/fonctionnalites/themes/
```

---

# 17. Modules et extensions

## H2

```text
Étendre FlatCMS avec des modules
```

## Statut

```text
Core pour le runtime modulaire
```

## Texte

```text
L’architecture HMVC de FlatCMS organise les fonctionnalités en modules
indépendants autour d’un périmètre métier.
```

## Un module peut regrouper

- routes ;
- contrôleurs ;
- vues ;
- services ;
- configuration ;
- assets ;
- traductions ;
- permissions ;
- manifeste ;
- stockage propre selon le contrat.

## Gestion des modules

Fonctions à confirmer :

- détection ;
- activation ;
- désactivation ;
- informations du manifeste ;
- compatibilité de version ;
- dépendances ;
- installation ;
- désinstallation ;
- mise à jour ;
- vérification de signature selon le canal.

## Séparation du Core

```text
Le runtime modulaire appartient au cœur stable. Un module tiers ou
commercial reste un composant distinct dont la compatibilité, la licence
et la sécurité doivent être évaluées.
```

## CTA

```text
Comprendre les modules FlatCMS
```

Destination :

```text
/fr-FR/architecture/modules/
```

---

# 18. Hooks

## H2

```text
Ajouter des comportements sans modifier systématiquement le cœur
```

## Statut

```text
Core
```

## Texte

```text
Le système de hooks permet à un module ou à une extension de réagir à
certains événements du CMS sans insérer directement son code dans les
fondations du runtime.
```

## Cas d’usage

- enrichir un rendu ;
- déclencher une action ;
- compléter une validation ;
- ajouter une donnée ;
- intégrer un service ;
- réagir à une publication ;
- étendre un module.

## Conditions

- hook documenté ;
- données transmises identifiées ;
- ordre ou priorité maîtrisés ;
- gestion des erreurs ;
- permissions ;
- absence de dépendance cachée ;
- compatibilité de version.

## CTA

```text
Découvrir le système de hooks
```

Destination :

```text
/fr-FR/architecture/hooks/
```

---

# 19. Multilingue

## H2

```text
Gérer plusieurs locales dans un même projet
```

## Statut

```text
Core ou fondation stable à confirmer contre le package final
```

## Texte

```text
FlatCMS prévoit les locales fr-FR, en-US, de-DE, es-ES, it-IT et pt-PT
pour l’interface et les contenus du projet.
```

## Fonctions attendues

- activation d’une locale ;
- langue par défaut ;
- contenus par langue ;
- chaînes d’interface ;
- sélecteur de langue ;
- slugs localisés ;
- métadonnées localisées ;
- relations entre traductions ;
- détection des chaînes manquantes ;
- fallback contrôlé.

## SEO international

- URL distincte par locale ;
- canonique locale ;
- `hreflang` réciproque ;
- attribut `lang` ;
- liens vers la page équivalente ;
- sitemap par locale selon la stratégie ;
- aucune traduction vide.

## CTA

```text
Découvrir le multilingue FlatCMS
```

Destination :

```text
/fr-FR/fonctionnalites/multilingue/
```

---

# 20. Installateur

## H2

```text
Initialiser FlatCMS avec un parcours d’installation
```

## Statut

```text
Core
```

## Texte

```text
FlatCMS inclut un runtime d’installation destiné à vérifier
l’environnement, préparer les données et configurer le premier accès.
```

## Point d’entrée canonique

```text
index.php?step=1
```

## Alias de compatibilité Apache

```text
/install/
```

pour les déploiements utilisant temporairement la racine du projet,
selon le contrat annoncé du dépôt.

## Étapes à confirmer

- vérification PHP ;
- extensions ;
- permissions ;
- configuration du site ;
- création du compte initial ;
- choix de la langue ;
- configuration des URLs ;
- installation des données ;
- contrôle final ;
- protection après installation.

## Règle de production

```text
Après l’installation, le serveur doit pointer vers public/ et les routes
d’installation doivent être protégées ou désactivées selon le contrat.
```

## CTA

```text
Lire le guide d’installation
```

Destination :

```text
/fr-FR/documentation/installation/
```

---

# 21. Sauvegardes

## H2

```text
Sauvegarder les données, les médias et la configuration
```

## Statut

```text
Optionnelle ou Core à confirmer dans le package final
```

## Texte

```text
Un site flat-file doit pouvoir sauvegarder de manière cohérente ses
données JSON, ses médias, sa configuration privée et la version du code
nécessaire à la restauration.
```

## Fonctions attendues si le module Backups est livré

- création d’une sauvegarde ;
- choix du périmètre ;
- nom et date ;
- téléchargement ;
- conservation ;
- restauration ;
- suppression ;
- contrôle d’intégrité ;
- protection des archives ;
- journalisation.

## Précaution

```text
Une archive de sauvegarde ne doit jamais être accessible publiquement.
```

## Transparence

```text
La présence d’un module de sauvegarde ne remplace pas une stratégie
externe, une copie hors site et un test régulier de restauration.
```

## CTA

```text
Comprendre les sauvegardes FlatCMS
```

Destination :

```text
/fr-FR/fonctionnalites/sauvegardes/
```

---

# 22. Corbeille

## H2

```text
Récupérer les contenus supprimés
```

## Statut

```text
Optionnelle ou Core à confirmer dans le package final
```

## Texte

```text
Le module Corbeille peut conserver temporairement certains contenus
supprimés afin de permettre leur restauration avant suppression définitive.
```

## Fonctions à confirmer

- types de contenus pris en charge ;
- restauration ;
- suppression définitive ;
- sélection multiple ;
- durée de conservation ;
- permissions ;
- médias ;
- journalisation.

## Limite

```text
La corbeille n’est pas une sauvegarde. Une suppression définitive ou une
corruption des données doit pouvoir être traitée par une vraie procédure
de restauration.
```

---

# 23. Données structurées

## H2

```text
Décrire le site, les pages et les articles en JSON-LD
```

## Statut

```text
Core technique, couverture fonctionnelle à valider par template
```

## Texte

```text
FlatCMS dispose d’une couche dédiée aux données structurées, avec un
manager, un constructeur de graphe et des fournisseurs pour le site,
les pages et les articles.
```

## Composants identifiés

```text
StructuredDataManager
SchemaGraphBuilder
StructuredDataProviderInterface
SiteSchemaProvider
PageSchemaProvider
PostSchemaProvider
```

## Types visés

- `Organization` ;
- `WebSite` ;
- `SoftwareApplication` ;
- `WebPage` ;
- `TechArticle` ;
- `BlogPosting` ;
- `BreadcrumbList` ;
- `ImageObject`.

## Règle

```text
Le JSON-LD doit décrire uniquement le contenu visible et ne garantit
aucun résultat enrichi.
```

## CTA

```text
Découvrir les données structurées
```

Destination :

```text
/fr-FR/architecture/donnees-structurees/
```

---

# 24. Services IA et module AiAgent

## H2

```text
Préparer des intégrations IA sans mélanger le cœur et les fournisseurs
```

## Statut

```text
Fondation technique, module optionnel ou premium selon la distribution
```

## Texte

```text
FlatCMS prévoit une couche de services destinée à isoler les fournisseurs
d’intelligence artificielle du reste du CMS.
```

## Capacités envisagées ou disponibles selon la version

- conversation ou assistant ;
- génération d’articles ;
- amélioration de texte ;
- traduction ;
- métadonnées SEO ;
- génération de structures de pages ;
- génération de médias ;
- suivi d’usage ;
- limites par rôle ;
- configuration de fournisseur.

## Sécurité

- clés dans un stockage privé ;
- permissions ;
- limitation d’usage ;
- journalisation ;
- consentement ;
- validation humaine ;
- protection des contenus sensibles ;
- possibilité de désactivation.

## Règle de transparence

```text
La page finale doit distinguer les contrats présents dans le code, les
fonctionnalités réellement opérationnelles et les évolutions encore
prévues.
```

## CTA

```text
Découvrir l’approche agent-ready
```

Destination :

```text
/fr-FR/agent-ready/
```

---

# 25. PagesBuilder

## H2

```text
Construire des pages avec des sections et des widgets
```

## Statut

```text
Premium
```

## Texte

```text
PagesBuilder est un composant commercial destiné à accélérer la création
de pages à partir de sections et de widgets réutilisables.
```

## Capacités à confirmer

- ajout de sections ;
- déplacement ;
- duplication ;
- suppression ;
- configuration des widgets ;
- responsive ;
- aperçu ;
- publication ;
- sérialisation JSON ;
- permissions ;
- compatibilité des thèmes ;
- import de scénarios ;
- médias.

## Widgets possibles

- titre ;
- texte ;
- image ;
- bouton ;
- Hero ;
- grille de fonctionnalités ;
- témoignages ;
- statistiques ;
- FAQ ;
- vidéo ;
- formulaire ;
- tarifs ;
- séparateur ;
- espaceur.

## Règle commerciale

```text
Le prix, la licence, le nombre de sites, les mises à jour et le support
doivent être affichés sur la page Tarifs.
```

## CTA

```text
Découvrir PagesBuilder
```

Destination future :

```text
/fr-FR/builders/pagesbuilder/
```

---

# 26. MenuBuilder

## H2

```text
Concevoir des navigations avancées
```

## Statut

```text
Premium
```

## Texte

```text
MenuBuilder complète la gestion des menus du Core avec une interface
visuelle destinée aux structures de navigation plus avancées.
```

## Capacités à confirmer

- construction visuelle ;
- sous-menus ;
- méga-menus ;
- colonnes ;
- icônes ;
- images ;
- groupes ;
- responsive ;
- conditions d’affichage ;
- menus par locale ;
- accessibilité.

## Distinction

```text
Le module Menu du Core gère la navigation standard. MenuBuilder ajoute
des outils avancés de composition.
```

## CTA

```text
Découvrir MenuBuilder
```

Destination future :

```text
/fr-FR/builders/menubuilder/
```

---

# 27. FooterBuilder

## H2

```text
Composer un footer modulaire
```

## Statut

```text
Premium
```

## Texte

```text
FooterBuilder permet de composer visuellement le pied de page avec des
colonnes, widgets, liens, informations et contenus adaptés au thème.
```

## Capacités à confirmer

- sections ;
- colonnes ;
- widgets ;
- menus ;
- coordonnées ;
- réseaux ;
- newsletter ;
- logos ;
- mentions ;
- responsive ;
- variantes par locale.

## Distinction

```text
Le module Footer du Core fournit la gestion du footer standard.
FooterBuilder ajoute une composition visuelle avancée.
```

## CTA

```text
Découvrir FooterBuilder
```

Destination future :

```text
/fr-FR/builders/footerbuilder/
```

---

# 28. Marketplace et distribution de modules

## H2

```text
Installer des composants vérifiés
```

## Statut

```text
Optionnelle, premium ou prévue selon la version
```

## Texte

```text
L’écosystème FlatCMS prévoit une gestion des modules et composants
distribués, avec des contrôles de structure, de compatibilité et de
signature selon le canal.
```

## Fonctions potentielles

- catalogue ;
- recherche ;
- fiche du module ;
- version ;
- compatibilité ;
- licence ;
- installation ;
- mise à jour ;
- signature ;
- éditeur tiers ;
- désinstallation ;
- avis uniquement s’ils sont réels et modérés.

## Prudence

```text
Aucune marketplace publique ne doit être annoncée comme opérationnelle
avant validation de son catalogue, de ses paiements, de ses signatures
et de ses conditions.
```

---

# 29. Mises à jour

## H2

```text
Suivre les versions et les artefacts de mise à jour
```

## Statut

```text
Fondation technique ou optionnelle selon le package
```

## Texte

```text
FlatCMS contient des services liés au catalogue et aux artefacts de mise
à jour. Le processus final doit rester séparé du dépôt runtime lorsque
la génération des distributions relève d’une chaîne de release distincte.
```

## Fonctions à confirmer

- détection d’une version ;
- canal stable ;
- catalogue ;
- téléchargement ;
- checksum ;
- signature ;
- notes de version ;
- compatibilité ;
- sauvegarde avant mise à jour ;
- retour arrière.

## Règle

```text
Une mise à jour ne doit jamais être présentée comme sans risque. Une
sauvegarde et une procédure de retour arrière restent nécessaires.
```

---

# 30. Tableau Core, optionnel, premium et prévu

> Ce tableau constitue une base éditoriale. Il doit être validé contre le
> package v1.0.0 final avant publication.

| Fonctionnalité | Statut proposé | Validation nécessaire |
|---|---|---|
| Runtime PHP natif | Core | Confirmée par le README |
| Architecture HMVC | Core | Confirmée |
| Autoloading PSR-4 | Core | Confirmée |
| Stockage JSON | Core | Confirmée |
| Authentification | Core | Confirmée par le périmètre annoncé |
| Pages | Core | Confirmée |
| Articles | Core | Confirmée |
| Commentaires | Core | Confirmée par le README, test fonctionnel requis |
| Contact | Core | Confirmée par le README, test fonctionnel requis |
| Médias | Core | Confirmée |
| Menus | Core | Confirmée |
| Footer | Core | Confirmée |
| Thèmes | Core | Confirmée |
| Installateur | Core | Confirmée |
| Catégories | À confirmer | Présence et contrat à vérifier |
| Utilisateurs et rôles | À confirmer | Présence et permissions à vérifier |
| Langues | À confirmer | Présence et périmètre à vérifier |
| Sauvegardes | À confirmer | Distribution finale |
| Corbeille | À confirmer | Types couverts |
| Données structurées | Fondation Core | Couverture par template |
| Services IA | Fondation technique | Statut produit à préciser |
| AiAgent | Optionnel/Premium | Distribution à préciser |
| PagesBuilder | Premium | Offre commerciale |
| MenuBuilder | Premium | Offre commerciale |
| FooterBuilder | Premium | Offre commerciale |
| Marketplace | À confirmer | Disponibilité réelle |
| Mise à jour intégrée | À confirmer | Chaîne de release |
| Analytics | Prévue/optionnelle | Ne pas annoncer comme native avant validation |
| Scheduler | À confirmer | Fonction réelle à vérifier |

---

# 31. Section — Une interface modulaire

## H2

```text
Activer uniquement les fonctions utiles
```

## Texte

```text
L’organisation modulaire de FlatCMS permet de structurer
l’administration autour des fonctionnalités nécessaires au projet.
```

## Bénéfices

- interface adaptée ;
- responsabilités séparées ;
- débogage plus ciblé ;
- dépendances visibles ;
- évolution indépendante ;
- permissions par fonction ;
- documentation par module.

## Limite

```text
Désactiver un module peut affecter les contenus, les routes ou les autres
composants qui en dépendent. Les dépendances doivent être vérifiées avant
toute modification.
```

---

# 32. Section — Une fonction doit rester documentée

## H2

```text
Des fonctionnalités reliées à leur documentation
```

## Texte

```text
Chaque fonction importante du site officiel doit renvoyer vers une page
de documentation qui explique son usage, ses prérequis, ses permissions,
ses limites et sa version de disponibilité.
```

## Parcours attendu

```text
Page Fonctionnalités
→ fiche de fonctionnalité
→ guide pratique
→ référence technique
→ dépannage
```

## Exemple

```text
Médiathèque
→ Gérer les médias
→ Optimiser une image
→ Référence du modèle média
→ Résoudre une erreur de permission
```

---

# 33. Questions fréquentes éditoriales

> Ces questions-réponses n’impliquent pas automatiquement un balisage
> `FAQPage`.

## H2

```text
Questions fréquentes sur les fonctionnalités
```

### H3 — Toutes les fonctionnalités sont-elles gratuites ?

```text
Le LTS Core regroupe les fondations open source et les fonctions
éditoriales stables. Les builders visuels et certains modules peuvent
être distribués sous une licence commerciale distincte.
```

### H3 — Puis-je désactiver un module ?

```text
Le runtime est modulaire, mais la désactivation doit respecter les
dépendances et le contrat de chaque module.
```

### H3 — FlatCMS inclut-il un page builder ?

```text
PagesBuilder est un composant premium distinct du cœur open source.
Le Core conserve sa propre gestion des pages.
```

### H3 — Puis-je créer mon propre module ?

```text
Oui. L’architecture HMVC et l’autoloading PSR-4 sont conçus pour organiser
des modules fonctionnels, à condition de respecter les conventions et
contrats documentés.
```

### H3 — FlatCMS inclut-il une intelligence artificielle ?

```text
Le projet possède une fondation de services IA et un module AiAgent.
Les fonctions réellement livrées, leur licence et leur disponibilité
doivent être vérifiées pour la distribution utilisée.
```

### H3 — Les commentaires sont-ils protégés contre le spam ?

```text
La protection exacte doit être vérifiée dans la version déployée.
L’activation de commentaires publics nécessite toujours une stratégie
de modération et de lutte contre les abus.
```

### H3 — Puis-je créer un site dans plusieurs langues ?

```text
FlatCMS prévoit six locales natives. Chaque traduction doit cependant
être rédigée, relue et maintenue comme un contenu distinct.
```

---

# 34. CTA final

## H2

```text
Découvrez les fonctions de FlatCMS sur un site réel
```

## Texte

```text
Explorez l’administration, vérifiez le périmètre du LTS Core et
identifiez les modules ou composants nécessaires à votre projet.
```

## CTA principal

```text
Tester la démo
```

Destination :

```text
https://demo.flat-cms.fr/
```

## CTA secondaire

```text
Télécharger FlatCMS
```

Destination :

```text
/fr-FR/telechargement/
```

## Lien tertiaire

```text
Consulter la documentation
```

Destination :

```text
/fr-FR/documentation/
```

---

# 35. Maillage interne attendu

| Section | Destination |
|---|---|
| Hero | Démo, documentation, téléchargement |
| Pages | Fonctionnalité Pages |
| Articles | Fonctionnalité Articles |
| Médias | Fonctionnalité Médias |
| Menus | Fonctionnalité Menus |
| Utilisateurs | Fonctionnalité Utilisateurs |
| Thèmes | Fonctionnalité Thèmes |
| Multilingue | Fonctionnalité Multilingue |
| Modules | Architecture Modules |
| Hooks | Architecture Hooks |
| Données structurées | Architecture Données structurées |
| IA | Agent-ready |
| Builders | PagesBuilder, MenuBuilder, FooterBuilder, Tarifs |
| Installation | Guide Installation |
| Sauvegardes | Fonctionnalité Sauvegardes |
| CTA final | Démo, téléchargement, documentation |

---

# 36. Médias à produire

## Image Open Graph

Concept :

```text
Interface FlatCMS entourée des principaux modules :
Pages, Articles, Médias, Menus, Utilisateurs, Thèmes et Langues
```

## Captures P0

- tableau de bord ;
- liste des pages ;
- éditeur de page ;
- liste des articles ;
- médiathèque ;
- gestion des menus ;
- thèmes ;
- modules ;
- langues ;
- installateur.

## Captures premium

- PagesBuilder ;
- MenuBuilder ;
- FooterBuilder.

Elles doivent porter un badge visuel ou une légende `Premium`.

## Diagramme

```text
LTS Core
├── Contenus
├── Administration
├── Navigation
├── Présentation
├── Sécurité
└── Extensions

Premium
├── PagesBuilder
├── MenuBuilder
└── FooterBuilder
```

---

# 37. Textes alternatifs suggérés

## Vue fonctionnelle

```text
Fonctionnalités principales de FlatCMS organisées autour du LTS Core
```

## Pages

```text
Interface de gestion des pages dans FlatCMS
```

## Articles

```text
Liste des articles dans l’administration FlatCMS
```

## Médias

```text
Médiathèque FlatCMS affichant les images et fichiers du site
```

## Menu

```text
Interface de gestion de la navigation dans FlatCMS
```

## Modules

```text
Liste des modules activables dans FlatCMS
```

## Builders

```text
Composants premium PagesBuilder, MenuBuilder et FooterBuilder
```

Les textes finaux doivent correspondre aux images produites.

---

# 38. Données structurées attendues

```text
CollectionPage
WebPage
SoftwareApplication
BreadcrumbList
ImageObject
```

## Identifiants

```text
https://flat-cms.fr/fr-FR/fonctionnalites/#webpage
https://flat-cms.fr/fr-FR/fonctionnalites/#breadcrumb
https://flat-cms.fr/fr-FR/fonctionnalites/#primaryimage
https://flat-cms.fr/#software
```

## Règle

```text
Les fonctionnalités décrites dans le JSON-LD doivent être visibles sur
la page et correspondre à la version réellement proposée.
```

Ne pas utiliser une longue liste de propriétés non standard pour encoder
le catalogue fonctionnel.

---

# 39. Composants du thème suggérés

```text
HeroFeatures
StatusLegend
CoreFeatureGrid
ContentFeatureCards
AdminFeatureCards
ArchitectureFeatureCards
PremiumProductCards
FeatureComparisonTable
DocumentationPath
FaqAccordion
CallToActionBanner
```

Les composants définitifs doivent correspondre au thème et aux widgets
réellement disponibles.

---

# 40. Éléments à confirmer avant intégration

- package exact de FlatCMS v1.0.0 ;
- modules activés par défaut ;
- présence et état des catégories ;
- fonctionnement des commentaires ;
- fonctionnement du contact ;
- rôles livrés ;
- fonctions multilingues exactes ;
- périmètre de la corbeille ;
- périmètre des sauvegardes ;
- scheduler ;
- analytics ;
- marketplace ;
- fonctions du module AiAgent ;
- liste des widgets des builders ;
- prix et licences premium ;
- captures finales.

---

# 41. Checklist éditoriale

- [ ] Le périmètre du Core correspond au README et au package.
- [ ] Chaque fonction porte un statut.
- [ ] Les catégories à confirmer ne sont pas présentées comme acquises.
- [ ] Les builders sont identifiés comme premium.
- [ ] Les fonctions IA réelles et prévues sont séparées.
- [ ] Les commentaires et le contact sont décrits avec leurs contraintes.
- [ ] La sécurité n’est pas présentée comme absolue.
- [ ] Les fonctions de sauvegarde et corbeille sont validées.
- [ ] Les captures correspondent à la version stable.
- [ ] Les liens mènent vers des pages prévues ou publiées.
- [ ] Les fonctionnalités ne sont pas répétées sans information nouvelle.
- [ ] Les descriptions restent compréhensibles par un non-développeur.
- [ ] Les termes techniques sont exacts.
- [ ] Les limitations sont visibles.
- [ ] Les informations commerciales sont exactes.

---

# 42. Checklist d’intégration

- [ ] URL correcte.
- [ ] Canonique auto-référencée.
- [ ] `<html lang="fr-FR">`.
- [ ] Groupe `hreflang`.
- [ ] Title.
- [ ] Meta description.
- [ ] Open Graph.
- [ ] H1 unique.
- [ ] Légende des statuts.
- [ ] Badges accessibles.
- [ ] Liens HTML explorables.
- [ ] Images responsive.
- [ ] Textes alternatifs.
- [ ] Tableau responsive.
- [ ] JSON-LD.
- [ ] Sitemap.
- [ ] Directive robots.
- [ ] Test mobile.
- [ ] Test clavier.
- [ ] Test des liens.
- [ ] Test HTTP `200`.
- [ ] Vérification des fonctionnalités contre le package final.

---

# 43. Sources internes

- `README.md`
- `VERSION`
- `flatcms.json`
- `LICENSE`
- `LICENSING.md`
- `COMMERCIAL_LICENSE.md`
- `TRADEMARK.md`
- `CLA.md`
- modules du package v1.0.0 ;
- services FlatCMS ;
- arborescence `app/` ;
- arborescence `data/` ;
- arborescence `themes/` ;
- documentation technique validée.

---

# 44. Références externes

- Google Search Central — Creating helpful, reliable, people-first content  
  https://developers.google.com/search/docs/fundamentals/creating-helpful-content

- Google Search Central — Influencing title links  
  https://developers.google.com/search/docs/appearance/title-link

- PHP-FIG — PSR-4 Autoloader  
  https://www.php-fig.org/psr/psr-4/

Les sources externes encadrent la qualité éditoriale, les titres et la
terminologie PSR-4. Le périmètre fonctionnel de FlatCMS reste défini par
le code et les documents officiels du projet.

---

# 45. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction complète de la page Fonctionnalités | ChatGPT / Alain BROYE |

---

# 46. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer ARCHITECTURE_CONTENT.md
```

Ce document contiendra la rédaction complète de la page :

```text
/fr-FR/architecture/
```

Il expliquera le cycle de requête, le cœur applicatif, les modules HMVC,
l’autoloading PSR-4, les services, les hooks, le stockage JSON, les thèmes
et le document root public.
