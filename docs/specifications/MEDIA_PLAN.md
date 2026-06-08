# MEDIA_PLAN — Plan média du site officiel FlatCMS

> **Inventaire de production visuelle et audiovisuelle**
>
> Projet : FlatCMS  
> Thème : `flatcms`  
> Date : 8 juin 2026  
> Documents parents : `THEME_SPECIFICATION.md`, `DESIGN_SYSTEM.md`, `COMPONENT_LIBRARY.md`, `HOMEPAGE_WIREFRAME.md`  
> Statut : plan de production de référence pour le site officiel  
> Portée : page d’accueil, pages P0, documentation, blog, Builders, réseaux sociaux et téléchargements

---

# 1. Objectif

Ce document définit tous les médias nécessaires au futur site officiel
FlatCMS.

Il doit permettre de :

- planifier la production ;
- éviter les doublons ;
- définir les formats ;
- définir les dimensions ;
- organiser les variantes clair et sombre ;
- prévoir les six locales ;
- documenter les textes alternatifs ;
- maîtriser les droits ;
- optimiser les performances ;
- garantir la cohérence de marque ;
- fournir à Codex des chemins et contrats stables.

Le plan couvre :

- logos ;
- icônes ;
- captures d’écran ;
- diagrammes ;
- illustrations ;
- images Open Graph ;
- vidéos ;
- miniatures ;
- médias de documentation ;
- assets des Builders ;
- médias du blog ;
- placeholders et fallbacks.

---

# 2. Principes directeurs

## 2.1 Originalité

Aucun média ne doit reproduire directement :

- le site Webby ;
- ses illustrations ;
- ses captures ;
- ses composants ;
- sa typographie ;
- ses animations ;
- ses compositions exactes.

L’inspiration porte uniquement sur :

- le niveau de finition ;
- le rythme ;
- l’ambiance technologique premium ;
- l’usage de panneaux techniques ;
- les contrastes ;
- les halos subtils.

## 2.2 Identité FlatCMS

Les médias doivent utiliser prioritairement :

```text
Indigo officiel : #4F46E5
Bleu nuit : #070A12
Surface : #0D121C
Violet : #7C3AED
Cyan : #06B6D4
Blanc : #F8FAFC
```

## 2.3 Contenu avant décoration

Aucune information essentielle ne doit exister uniquement dans une image.

Les éléments suivants restent en HTML :

- titres ;
- tarifs ;
- CTA ;
- fonctionnalités ;
- versions ;
- licences ;
- mentions juridiques ;
- étapes ;
- comparatifs ;
- statuts.

## 2.4 Performance

Chaque média doit être :

- dimensionné ;
- compressé ;
- chargé de manière adaptée ;
- accompagné d’un fallback ;
- généré dans les formats réellement utiles ;
- testé sur mobile.

## 2.5 Accessibilité

Chaque média doit avoir :

- un texte alternatif pertinent ;
- ou `alt=""` lorsqu’il est purement décoratif ;
- une légende lorsque nécessaire ;
- une version textuelle pour les diagrammes complexes ;
- un contraste suffisant ;
- aucune information dépendant uniquement de la couleur.

---

# 3. Arborescence recommandée

```text
themes/flatcms/assets/
├── img/
│   ├── brand/
│   ├── home/
│   ├── pages/
│   ├── builders/
│   ├── documentation/
│   ├── blog/
│   ├── og/
│   ├── icons/
│   ├── diagrams/
│   ├── screenshots/
│   └── placeholders/
├── video/
│   ├── home/
│   ├── builders/
│   └── tutorials/
└── media-manifest.json
```

## Convention de nommage

```text
<page>-<section>-<locale>-<theme>-<width>x<height>.<ext>
```

Exemples :

```text
home-agent-ready-fr-FR-dark-1600x900.webp
og-home-fr-FR-1200x630.webp
pagesbuilder-interface-fr-FR-dark-1600x1000.webp
architecture-request-flow-neutral-dark.svg
```

## Règles

- minuscules ;
- tirets ;
- aucun espace ;
- locale explicite si texte intégré ;
- thème explicite si variante ;
- dimensions dans le nom uniquement lorsque utile ;
- pas de suffixes `final-final-v2`.

---

# 4. Formats

## SVG

À utiliser pour :

- logo ;
- icônes ;
- diagrammes ;
- schémas ;
- illustrations vectorielles ;
- formes décoratives.

## WebP

À utiliser pour :

- captures ;
- illustrations raster ;
- images Open Graph ;
- photos ;
- miniatures.

## AVIF

Possible pour :

- photos ;
- grandes illustrations ;
- captures complexes.

Toujours fournir un fallback WebP si nécessaire.

## PNG

Réservé à :

- transparence nécessitant une compatibilité particulière ;
- assets source ;
- cas où WebP ne convient pas.

## JPEG

À éviter pour les interfaces et diagrammes.

## Vidéo

Préférer :

```text
MP4 H.264
WebM si utile
```

## Audio

Aucun audio automatique.

---

# 5. Dimensions standard

## Open Graph

```text
1200 × 630 px
```

## Couverture de page

```text
1600 × 900 px
```

## Couverture blog

Standard FlatCMS existant :

```text
1671 × 941 px
```

## Capture interface large

```text
1600 × 1000 px
```

## Capture interface mobile

```text
720 × 1440 px
```

## Miniature carte

```text
800 × 500 px
```

## Logo rectangulaire

Source SVG.

Exports :

```text
512 × 128 px
1024 × 256 px
```

## Icône carrée

```text
512 × 512 px
256 × 256 px
64 × 64 px
```

## Favicon

```text
16 × 16
32 × 32
48 × 48
180 × 180
192 × 192
512 × 512
```

---

# 6. Budget de poids

## Logo SVG

```text
< 50 Ko
```

## Icône SVG

```text
< 10 Ko
```

## Image Hero

```text
< 250 Ko
```

## Capture interface

```text
< 300 Ko
```

## Image Open Graph

```text
< 300 Ko
```

## Miniature

```text
< 120 Ko
```

## Diagramme SVG

```text
< 100 Ko
```

## Vidéo Hero

Non recommandée au lancement.

Si retenue :

```text
< 3 Mo
sans audio
poster obligatoire
```

---

# 7. Manifest média

Créer :

```text
themes/flatcms/assets/media-manifest.json
```

## Structure conceptuelle

```json
{
  "version": "1.0.0",
  "media": [
    {
      "id": "home-architecture-flow",
      "type": "diagram",
      "src": "img/diagrams/architecture-request-flow-neutral-dark.svg",
      "alt": {
        "fr-FR": "Cycle d’une requête dans FlatCMS",
        "en-US": "FlatCMS request lifecycle"
      },
      "width": 1600,
      "height": 900,
      "theme": "dark",
      "locales": ["fr-FR", "en-US"],
      "license": "FlatCMS-Official"
    }
  ]
}
```

## Champs

- `id` ;
- `type` ;
- `src` ;
- `alt` ;
- `width` ;
- `height` ;
- `theme` ;
- `locales` ;
- `license` ;
- `source` ;
- `author` ;
- `createdAt` ;
- `updatedAt` ;
- `usage` ;
- `priority`.

---

# 8. Médias de marque

# 8.1 Logo rectangulaire officiel

## Identifiant

```text
brand-logo-horizontal
```

## Formats

```text
SVG
PNG transparent
WebP transparent
```

## Variantes

- sombre ;
- claire ;
- monochrome ;
- petite taille.

## Usages

- header ;
- footer ;
- documentation ;
- Open Graph ;
- e-mails ;
- GitHub.

## Texte alternatif

```text
FlatCMS
```

## Priorité

```text
P0
```

---

# 8.2 Icône carrée

## Identifiant

```text
brand-icon-square
```

## Usages

- favicon ;
- application ;
- réseaux sociaux ;
- cartes ;
- raccourcis.

## Priorité

```text
P0
```

---

# 8.3 Signature visuelle

## Identifiant

```text
brand-luminous-lines
```

## Concept

Traits lumineux indigo et cyan utilisés de manière subtile :

- header ;
- Open Graph ;
- CTA ;
- Hero.

## Format

```text
SVG transparent
```

## Règle

Ne pas reproduire exactement les traits du modèle Webby.

## Priorité

```text
P1
```

---

# 9. Médias de la page d’accueil

# 9.1 Visualisation Hero

## Identifiant

```text
home-hero-technical-visual
```

## Concept

Panneau original combinant :

- routeur ;
- modules ;
- JSON ;
- réponse ;
- lignes lumineuses ;
- interface sombre.

## Format

Préférence :

```text
HTML/CSS + SVG
```

Fallback :

```text
WebP 1600 × 900
```

## Variante

- sombre ;
- claire.

## Texte alternatif

```text
Visualisation de l’architecture modulaire de FlatCMS
```

## Priorité

```text
P0
```

---

# 9.2 Diagramme cycle de requête

## Identifiant

```text
home-request-lifecycle
```

## Contenu

```text
Request
Router
Module
Service
JSON
View
Response
```

## Formats

```text
SVG
version textuelle HTML
```

## Mobile

Version verticale.

## Texte alternatif

```text
Cycle d’une requête dans FlatCMS, du routeur à la réponse
```

## Priorité

```text
P0
```

---

# 9.3 Diagramme stockage JSON

## Identifiant

```text
home-flat-file-storage
```

## Contenu

```text
data/
pages/
posts/
media/
settings/
```

## Format

```text
SVG ou CodePanel HTML
```

## Texte alternatif

```text
Organisation du stockage JSON de FlatCMS
```

## Priorité

```text
P0
```

---

# 9.4 Illustration manifeste

## Identifiant

```text
home-manifesto-portrait
```

## Option A

Portrait officiel d’Alain BROYE.

## Option B

Illustration abstraite représentant :

- simplicité ;
- code ;
- architecture ;
- modularité.

## Règle

Ne pas utiliser un portrait généré si la page présente une personne réelle
sans identification claire.

## Priorité

```text
P2
```

---

# 9.5 Illustration sans SQL

## Identifiant

```text
home-no-sql
```

## Concept

```text
Code + Data + Media = Site FlatCMS
```

## Format

```text
SVG
```

## Texte alternatif

```text
FlatCMS regroupe le code, les données JSON et les médias sans serveur SQL
```

## Priorité

```text
P1
```

---

# 9.6 Icônes fonctionnalités

## Nombre

```text
9 à 12
```

## Sujets

- pages ;
- articles ;
- médias ;
- menus ;
- footer ;
- utilisateurs ;
- thèmes ;
- langues ;
- sauvegardes ;
- données structurées ;
- modules ;
- sécurité.

## Format

```text
SVG local
```

## Priorité

```text
P0
```

---

# 9.7 Diagramme architecture

## Identifiant

```text
home-architecture-stack
```

## Contenu

```text
public/index.php
Bootstrap
App / Router
Modules HMVC
Services
JSON
View / Response
```

## Variantes

- desktop horizontal ;
- mobile vertical ;
- sombre ;
- claire.

## Texte alternatif

```text
Architecture de FlatCMS du point d’entrée public au rendu de la réponse
```

## Priorité

```text
P0
```

---

# 9.8 Illustration multilingue

## Identifiant

```text
home-multilingual
```

## Concept

Logo FlatCMS central et six locales :

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

## Format

```text
SVG
```

## Règle

Ne pas utiliser les drapeaux comme seul indicateur de langue.

## Texte alternatif

```text
Les six locales natives de FlatCMS
```

## Priorité

```text
P1
```

---

# 9.9 Captures Builders

## Identifiants

```text
pagesbuilder-interface
menubuilder-interface
footerbuilder-interface
```

## Dimensions

```text
1600 × 1000 px
```

## Variantes

- sombre ;
- claire si réellement disponible ;
- locale française ;
- locale anglaise facultative.

## Contenu

- données fictives ;
- aucune clé ;
- aucun e-mail privé ;
- aucun nom de client ;
- aucune URL sensible.

## Textes alternatifs

```text
Interface de PagesBuilder dans FlatCMS
Interface de MenuBuilder dans FlatCMS
Interface de FooterBuilder dans FlatCMS
```

## Priorité

```text
P0
```

---

# 9.10 Diagramme sécurité

## Identifiant

```text
home-security-principles
```

## Contenu

- document root public ;
- permissions ;
- secrets ;
- rôles.

## Format

```text
SVG
```

## Texte alternatif

```text
Principes de sécurité appliqués à l’architecture FlatCMS
```

## Priorité

```text
P1
```

---

# 9.11 Panneau données structurées

## Identifiant

```text
home-structured-data
```

## Format

```text
CodePanel HTML
```

Fallback image facultatif.

## Contenu

Extrait JSON-LD valide et simplifié.

## Priorité

```text
P1
```

---

# 9.12 Diagramme agent-ready

## Identifiant

```text
home-agent-ready-flow
```

## Contenu

```text
Utilisateur
AiAgent
AIManager
Provider
Validation humaine
Brouillon / Action
```

## Format

```text
SVG + version HTML
```

## Texte alternatif

```text
Flux d’une demande IA contrôlée dans FlatCMS
```

## Priorité

```text
P0
```

---

# 10. Images Open Graph P0

Créer une image par page et par locale lorsque du texte est intégré.

## Liste

```text
og-home
og-why-flatcms
og-features
og-architecture
og-documentation
og-installation
og-download
og-licensing
og-pricing
og-agent-ready
og-about
og-contact
og-legal
og-privacy
```

La page 404 n’a pas besoin d’image Open Graph dédiée.

## Dimensions

```text
1200 × 630 px
```

## Locales

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

## Total maximal

```text
14 pages × 6 locales = 84 images
```

## Optimisation recommandée

Créer un système de templates afin de produire :

- fond commun ;
- logo ;
- titre localisé ;
- motif propre à la page ;
- export automatisé.

## Priorité

### P0 lancement

- home ;
- features ;
- architecture ;
- documentation ;
- download ;
- pricing ;
- agent-ready.

### P1

- why ;
- installation ;
- licensing ;
- about ;
- contact ;
- legal ;
- privacy.

---

# 11. Système visuel des Open Graph

## Fond

- bleu nuit ;
- dégradé indigo ;
- lignes lumineuses ;
- texture technique légère.

## Logo

Position :

```text
haut gauche ou haut centre
```

## Titre

Maximum :

```text
2 à 3 lignes
```

## Sous-titre

Facultatif.

## Motif

Chaque page possède un motif distinct :

| Page | Motif |
|---|---|
| Home | architecture modulaire |
| Features | grille de composants |
| Architecture | flux HMVC |
| Documentation | cartes documentaires |
| Installation | archive vers serveur |
| Download | package et checksum |
| Licensing | Core / premium / tiers |
| Pricing | trois Builders |
| Agent-ready | outils et validation |
| About | manifeste |
| Contact | canaux |
| Legal | document |
| Privacy | données et protection |

## Règle

Aucun texte essentiel trop proche des bords.

---

# 12. Pages intérieures

# 12.1 Pourquoi FlatCMS

Médias :

- diagramme sans SQL ;
- comparaison architecture ;
- bénéfices ;
- capture d’arborescence.

Priorité :

```text
P1
```

---

# 12.2 Fonctionnalités

Médias :

- icônes ;
- captures administration ;
- vues modules ;
- grille fonctions.

Priorité :

```text
P0
```

---

# 12.3 Architecture

Médias :

- cycle requête ;
- HMVC ;
- PSR-4 ;
- stockage ;
- hooks ;
- thèmes ;
- IA.

Priorité :

```text
P0
```

---

# 12.4 Documentation

Médias :

- carte Diátaxis adaptée ;
- parcours par profil ;
- recherche ;
- sommaire ;
- code ;
- version.

Priorité :

```text
P1
```

---

# 12.5 Installation

Médias :

- téléchargement ;
- checksum ;
- arborescence ;
- document root ;
- installateur ;
- finalisation ;
- admin.

Priorité :

```text
P0
```

---

# 12.6 Téléchargement

Médias :

- package ;
- checksum ;
- version ;
- release ;
- archive officielle.

Priorité :

```text
P0
```

---

# 12.7 Licences

Médias :

- arbre SPDX ;
- Core / premium / tiers ;
- marque ;
- contribution.

Priorité :

```text
P1
```

---

# 12.8 Tarifs

Médias :

- cartes Solo / Duo / Suite ;
- trois Builders ;
- cycle abonnement ;
- garantie frontend.

Priorité :

```text
P0
```

---

# 12.9 Agent-ready

Médias :

- fondation AI ;
- provider ;
- outils ;
- permissions ;
- validation ;
- agents spécialisés.

Priorité :

```text
P0
```

---

# 12.10 À propos

Médias :

- logo ;
- manifeste ;
- chronologie ;
- portrait officiel éventuel ;
- valeurs.

Priorité :

```text
P1
```

---

# 12.11 Contact

Médias :

- icônes canaux ;
- illustration formulaire ;
- sécurité.

Priorité :

```text
P2
```

---

# 12.12 Pages légales

Médias :

- aucun média obligatoire ;
- icône document facultative ;
- pas d’illustration décorative lourde.

Priorité :

```text
P2
```

---

# 13. Documentation technique

## Captures nécessaires

- dashboard ;
- paramètres ;
- pages ;
- articles ;
- médias ;
- menus ;
- thèmes ;
- utilisateurs ;
- modules ;
- sauvegardes ;
- langues ;
- Builders ;
- AiAgent lorsque disponible.

## Règles

- version affichée ;
- locale indiquée ;
- données fictives ;
- dimensions cohérentes ;
- annotations simples ;
- aucune information sensible ;
- mise à jour lors des changements d’interface.

## Convention

```text
docs-<module>-<action>-<locale>-<version>.webp
```

---

# 14. Captures et confidentialité

Avant chaque capture, vérifier :

- e-mail ;
- nom réel ;
- domaine ;
- IP ;
- chemin local ;
- clé API ;
- jeton ;
- licence ;
- logs ;
- URL privée ;
- nom de client ;
- métadonnées du fichier.

## Procédure

1. utiliser une installation de démonstration ;
2. injecter des données fictives ;
3. nettoyer l’écran ;
4. capturer ;
5. vérifier visuellement ;
6. supprimer les métadonnées ;
7. optimiser ;
8. enregistrer dans le manifest.

---

# 15. Diagrammes

## Style commun

- fond transparent ou neutre ;
- nœuds arrondis ;
- bordures fines ;
- indigo dominant ;
- cyan pour les flux ;
- violet pour les services ;
- vert pour validation ;
- rouge uniquement pour erreur ;
- texte lisible ;
- version sombre et claire.

## Diagrammes prioritaires

```text
request-lifecycle
hmvc-modules
psr4-mapping
json-storage
public-document-root
builder-license-flow
agent-ready-flow
multilingual-flow
seo-structured-data
deployment-flow
```

## Accessibilité

Chaque diagramme complexe doit avoir :

- alternative textuelle ;
- légende ;
- description longue dans le contenu ;
- ordre logique.

---

# 16. Icônes

## Source

Créer ou utiliser une bibliothèque locale cohérente.

## Style

- trait ou duotone ;
- coins arrondis ;
- épaisseur constante ;
- grille 24 × 24 ;
- couleur via CSS.

## Interdictions

- mélange de styles ;
- icônes raster floues ;
- marque tierce sans droit ;
- Font Awesome chargé depuis CDN ;
- icône seule sans nom accessible.

---

# 17. Vidéos

## Vidéos prioritaires

### P1

- installation FlatCMS ;
- présentation générale ;
- PagesBuilder ;
- MenuBuilder ;
- FooterBuilder.

### P2

- architecture ;
- création d’un module ;
- multilingue ;
- déploiement Nginx ;
- déploiement Synology.

## Format

```text
16:9
1080p
MP4
poster WebP
sous-titres
transcription
```

## Durées

- teaser : 15 à 30 secondes ;
- présentation : 2 à 4 minutes ;
- tutoriel : 6 à 12 minutes.

## Règles

- pas d’autoplay sonore ;
- contrôles ;
- sous-titres ;
- transcription ;
- hébergement ou embed documenté ;
- consentement si tiers ;
- fallback vers une page ou une image.

---

# 18. Posters vidéo

## Dimensions

```text
1280 × 720 px
```

## Contenu

- logo ;
- titre ;
- capture ou illustration ;
- durée facultative ;
- bouton lecture visuel.

## Règle

Le bouton visuel ne doit pas être inclus dans l’image si le lecteur
ajoute déjà un contrôle accessible trompeur.

---

# 19. Blog

## Couverture standard

```text
1671 × 941 px
```

## Système

- fond FlatCMS ;
- logo officiel ;
- titre localisé ;
- style unique par article ;
- palette cohérente ;
- gabarit PSD ou équivalent ;
- export WebP.

## Règles

- une image par locale si texte intégré ;
- logo officiel ;
- texte relu ;
- aucun slogan erroné ;
- aucun texte trop proche des bords ;
- cohérence de série sans répétition visuelle excessive.

---

# 20. Localisation

## Médias sans texte

Préférés lorsque possible.

Avantages :

- une seule source ;
- moins d’erreurs ;
- maintenance simplifiée ;
- meilleure réutilisation.

## Médias avec texte

Créer une variante par locale.

## Locales

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

## Règles

- traduction validée ;
- longueur adaptée ;
- pas de simple remplacement automatique ;
- vérification typographique ;
- correspondance des noms de fichiers ;
- manifest localisé.

---

# 21. Modes clair et sombre

## Médias neutres

Préférer :

- transparence ;
- couleurs sémantiques ;
- SVG piloté par CSS.

## Médias raster

Créer une variante si :

- contraste insuffisant ;
- fond intégré ;
- capture d’interface différente ;
- halo spécifique.

## Convention

```text
-dark
-light
-neutral
```

---

# 22. Textes alternatifs

## Principe

Décrire la fonction ou l’information, pas l’apparence complète.

## Exemples

Mauvais :

```text
Image bleue avec des boîtes
```

Bon :

```text
Cycle d’une requête FlatCMS du routeur jusqu’à la réponse HTML
```

## Captures

Exemple :

```text
Interface de PagesBuilder affichant une page composée de plusieurs sections
```

## Décoratif

```html
alt=""
```

## Logo lié

```text
FlatCMS — Accueil
```

selon le contexte.

---

# 23. Légendes

Utiliser une légende lorsque le média nécessite :

- contexte ;
- version ;
- date ;
- environnement ;
- source ;
- avertissement.

Exemple :

```text
Interface de PagesBuilder dans FlatCMS v1.0.0 LTS, locale fr-FR.
```

---

# 24. Droits et licences

Chaque média doit indiquer :

- auteur ;
- source ;
- licence ;
- droit de modification ;
- droit de diffusion ;
- attribution ;
- date.

## Catégories

```text
FlatCMS-Official
ThirdParty-Licensed
Generated-Validated
Screenshot-Official
User-Contributed
```

## IA

Un média généré par IA doit être :

- relu ;
- vérifié ;
- documenté ;
- débarrassé des marques ou éléments non autorisés ;
- compatible avec la communication du projet.

---

# 25. Métadonnées

Supprimer avant publication :

- GPS ;
- nom d’utilisateur local ;
- chemin ;
- logiciel interne inutile ;
- commentaires ;
- miniatures cachées ;
- données personnelles.

Conserver uniquement les métadonnées utiles si la politique le prévoit.

---

# 26. Pipeline de production

## Étapes

1. brief ;
2. choix du format ;
3. production source ;
4. validation éditoriale ;
5. validation marque ;
6. validation juridique ;
7. export ;
8. optimisation ;
9. texte alternatif ;
10. manifest ;
11. intégration ;
12. test ;
13. archivage source.

## Sources éditables

Conserver séparément :

```text
PSD
SVG source
Figma
Photopea
Illustrator
fichier vidéo source
```

Ne pas placer les sources lourdes dans le package public du thème.

---

# 27. Outils

## Design

- Photopea ;
- Figma ;
- Affinity ;
- Photoshop ;
- Illustrator ;
- Inkscape.

## Optimisation

- Squoosh ;
- ImageMagick ;
- Sharp ;
- SVGO ;
- ffmpeg.

## Automatisation

Codex peut mettre en place :

- génération des tailles ;
- conversion WebP/AVIF ;
- compression SVG ;
- vérification dimensions ;
- manifest ;
- détection fichiers orphelins.

---

# 28. Responsive images

Utiliser :

```html
<picture>
  <source type="image/avif" srcset="...">
  <source type="image/webp" srcset="...">
  <img src="..." width="..." height="..." alt="...">
</picture>
```

## Largeurs possibles

```text
480
768
1024
1280
1600
```

## Règle

Ne pas générer toutes les tailles pour un média qui n’est jamais affiché
en grand.

---

# 29. Lazy loading

## Eager

- logo ;
- image Hero principale si LCP ;
- image essentielle au-dessus de la ligne de flottaison.

## Lazy

- captures Builders ;
- images de sections basses ;
- articles liés ;
- vidéos ;
- diagrammes hors écran.

## Règle

Ne pas appliquer `loading="lazy"` à l’image LCP sans mesure.

---

# 30. Cache

## Assets versionnés

```text
logo-flatcms.abc123.svg
home-hero.abc123.webp
```

## En-têtes

Long cache pour fichiers fingerprintés.

## Purge

Prévoir la purge lors :

- changement de logo ;
- correction locale ;
- mise à jour majeure ;
- remplacement d’une capture.

---

# 31. Médias et CSP

Les médias doivent fonctionner avec une CSP restrictive.

## Préférence

```text
self
data: uniquement si nécessaire
```

Éviter :

- images distantes non contrôlées ;
- polices distantes ;
- scripts d’embed automatiques ;
- iframes sans consentement.

---

# 32. Fallbacks

## Image manquante

Ne pas afficher une icône cassée.

Utiliser :

- placeholder local ;
- fond neutre ;
- texte.

## Vidéo indisponible

Afficher :

- poster ;
- description ;
- lien vers transcription ou page.

## Diagramme indisponible

Afficher la version textuelle.

---

# 33. Placeholders

## Types

- image générique ;
- avatar ;
- capture ;
- vidéo ;
- logo partenaire.

## Règle

Un placeholder ne doit pas être publié comme média final sans indication.

---

# 34. Inventaire prioritaire P0

| ID | Média | Page | Format | Priorité |
|---|---|---|---|---|
| brand-logo-horizontal | Logo horizontal | Global | SVG | P0 |
| brand-icon-square | Icône | Global | SVG/PNG | P0 |
| home-hero-technical-visual | Hero | Home | HTML/SVG/WebP | P0 |
| home-request-lifecycle | Cycle requête | Home/Architecture | SVG | P0 |
| home-flat-file-storage | Stockage JSON | Home | SVG/HTML | P0 |
| feature-icons | Icônes fonctions | Home/Features | SVG | P0 |
| home-architecture-stack | Architecture | Home | SVG | P0 |
| pagesbuilder-interface | Capture | Home/Tarifs | WebP | P0 |
| menubuilder-interface | Capture | Home/Tarifs | WebP | P0 |
| footerbuilder-interface | Capture | Home/Tarifs | WebP | P0 |
| home-agent-ready-flow | Agent-ready | Home | SVG | P0 |
| og-home | Open Graph | Home | WebP | P0 |
| og-features | Open Graph | Features | WebP | P0 |
| og-architecture | Open Graph | Architecture | WebP | P0 |
| og-documentation | Open Graph | Documentation | WebP | P0 |
| og-download | Open Graph | Download | WebP | P0 |
| og-pricing | Open Graph | Pricing | WebP | P0 |
| og-agent-ready | Open Graph | Agent-ready | WebP | P0 |

---

# 35. Inventaire P1

- diagramme sans SQL ;
- multilingue ;
- sécurité ;
- structured data ;
- documentation Diátaxis ;
- installation ;
- licensing ;
- about ;
- vidéos principales ;
- posters ;
- Open Graph restantes ;
- captures administration.

---

# 36. Inventaire P2

- portrait ;
- animations complexes ;
- illustrations de contact ;
- variantes sociales ;
- vidéos avancées ;
- captures secondaires ;
- médias de roadmap ;
- assets partenaires.

---

# 37. Planning de production recommandé

## Phase 1 — Identité

- logo ;
- icône ;
- favicon ;
- signature ;
- tokens.

## Phase 2 — Home

- Hero ;
- cycle requête ;
- architecture ;
- icônes ;
- Builders ;
- agent-ready ;
- OG home.

## Phase 3 — Pages P0

- OG ;
- diagrammes ;
- captures ;
- installation ;
- tarifs.

## Phase 4 — Documentation

- captures ;
- tutoriels ;
- vidéos ;
- posters.

## Phase 5 — Blog et réseaux

- templates ;
- automatisation des locales ;
- séries.

---

# 38. Tests automatisés demandés à Codex

```text
testAllManifestMediaExist
testAllRasterImagesHaveDimensions
testAllRequiredImagesHaveAltText
testNoImageExceedsBudget
testNoUnapprovedRemoteImage
testNoSensitiveMetadata
testNoOrphanedMedia
testNoDuplicateMediaId
testLocaleMediaExistsWhenTextEmbedded
testDarkLightVariantsAreConsistent
testHeroImageIsNotLazyLoadedWhenLcp
testOffscreenImagesAreLazyLoaded
testSvgIsSanitized
testVideoHasPoster
testVideoHasCaptionsOrTranscript
```

---

# 39. Audit demandé à Codex

## Inventaire

```text
ID
Fichier
Format
Dimensions
Poids
Page
Locale
Theme
Alt
Licence
Statut
```

## Écarts

```text
Média
Problème
Impact
Correction
Priorité
```

## Confirmations

- [ ] Aucun média Webby copié.
- [ ] Tous les médias ont une source.
- [ ] Tous les médias ont une licence.
- [ ] Les captures sont nettoyées.
- [ ] Les textes sont localisés.
- [ ] Les formats sont optimisés.
- [ ] Les dimensions sont renseignées.
- [ ] Les alternatives sont présentes.
- [ ] Les budgets sont respectés.
- [ ] Le manifest est valide.
- [ ] Aucun secret ou donnée personnelle n’est visible.
- [ ] Les variantes sombre et claire sont cohérentes.
- [ ] Les médias essentiels ont un fallback.

---

# 40. Critères d’acceptation

Le plan média est considéré comme correctement appliqué si :

1. tous les médias P0 existent ;
2. le logo officiel est cohérent partout ;
3. les diagrammes ont une version textuelle ;
4. les captures ne contiennent aucune donnée sensible ;
5. les images Open Graph P0 sont disponibles ;
6. les six locales sont gérées lorsque du texte est intégré ;
7. les budgets sont respectés ;
8. aucun média tiers n’est utilisé sans droit ;
9. le manifest est à jour ;
10. les tests automatisés passent.

---

# 41. Checklist éditoriale

- [ ] Texte exact.
- [ ] Logo officiel.
- [ ] Locale correcte.
- [ ] Aucun slogan erroné.
- [ ] Aucun prix obsolète.
- [ ] Version exacte.
- [ ] Aucune donnée fictive trompeuse.
- [ ] Légende correcte.
- [ ] Texte alternatif.
- [ ] Source documentée.

---

# 42. Checklist technique

- [ ] Dimensions.
- [ ] Poids.
- [ ] Format.
- [ ] Compression.
- [ ] Srcset.
- [ ] Lazy loading.
- [ ] Cache.
- [ ] CSP.
- [ ] Fallback.
- [ ] Manifest.
- [ ] Métadonnées nettoyées.
- [ ] Test mobile.
- [ ] Test sombre.
- [ ] Test clair.
- [ ] Test sans JavaScript.

---

# 43. Sources internes

- `THEME_SPECIFICATION.md`
- `DESIGN_SYSTEM.md`
- `COMPONENT_LIBRARY.md`
- `HOMEPAGE_WIREFRAME.md`
- `HOMEPAGE_CONTENT.md`
- `ARCHITECTURE_CONTENT.md`
- `PRICING_CONTENT.md`
- `AGENT_READY_CONTENT.md`
- `DOCUMENTATION_CONTENT.md`
- assets officiels FlatCMS existants ;
- captures administration ;
- templates PSD existants.

---

# 44. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction du plan média du site officiel | ChatGPT / Alain BROYE |

---

# 45. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer PREPRODUCTION_TEST_PLAN.md
```

Ce document définira la validation complète du futur site avant
publication :

- contenus ;
- navigation ;
- responsive ;
- accessibilité ;
- performances ;
- SEO ;
- données structurées ;
- sécurité ;
- formulaires ;
- multilingue ;
- redirections ;
- analytics ;
- licences ;
- sauvegardes ;
- monitoring.
