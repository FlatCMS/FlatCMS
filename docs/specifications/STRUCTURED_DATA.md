# STRUCTURED_DATA — Stratégie JSON-LD et Schema.org du futur site FlatCMS

> **Document directeur des données structurées**
>
> Projet : FlatCMS  
> Domaine cible : `https://flat-cms.fr`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Documents parents : `SEO.md`, `SITE_ARCHITECTURE.md`, `CONTENT_MATRIX.md`, `DOCUMENTATION_MAP.md`, `KEYWORDS.md`, `REDIRECTS.md`  
> Statut : architecture initiale à valider contre le rendu réel de `app/Services/StructuredData`

---

## 1. Objet du document

Ce document définit la stratégie de données structurées du futur site officiel FlatCMS.

Il précise :

- les entités Schema.org ;
- la structure du graphe JSON-LD ;
- les identifiants `@id` ;
- les types par modèle de page ;
- les propriétés obligatoires dans le projet ;
- les règles de cohérence avec le contenu visible ;
- la gestion des locales ;
- les articles et pages techniques ;
- les vidéos ;
- les téléchargements ;
- les composants premium ;
- les auteurs ;
- les fils d’Ariane ;
- les tests et validations ;
- les responsabilités du service `StructuredData`.

L’objectif n’est pas de multiplier les types Schema.org, mais de produire un graphe :

```text
exact
stable
maintenable
relié
vérifiable
conforme au contenu visible
```

---

## 2. Fondations existantes dans FlatCMS

La version de référence contient déjà une couche dédiée :

```text
app/Services/StructuredData/
├── Contracts/
│   └── StructuredDataProviderInterface.php
├── Providers/
│   ├── SiteSchemaProvider.php
│   ├── PageSchemaProvider.php
│   └── PostSchemaProvider.php
├── StructuredDataManager.php
└── SchemaGraphBuilder.php
```

Cette organisation constitue la base technique du futur système.

### Responsabilités proposées

| Composant | Responsabilité |
|---|---|
| `StructuredDataManager` | Orchestration des providers |
| `SchemaGraphBuilder` | Construction et déduplication du `@graph` |
| `StructuredDataProviderInterface` | Contrat commun |
| `SiteSchemaProvider` | Entités globales du site |
| `PageSchemaProvider` | Pages institutionnelles et produit |
| `PostSchemaProvider` | Articles et actualités |
| Futurs providers | Documentation, vidéo, logiciel, produit, profil |

La stratégie définie ici doit être confrontée au code réel avant implémentation finale.

---

## 3. Principes obligatoires

## 3.1 JSON-LD comme format principal

Le format retenu est :

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": []
}
</script>
```

JSON-LD est privilégié parce qu’il :

- sépare les données structurées du HTML visible ;
- facilite les graphes imbriqués ;
- simplifie la maintenance ;
- réduit les erreurs de balisage dans les templates ;
- est recommandé par Google lorsque l’architecture du site le permet.

---

## 3.2 Le contenu visible reste la source

Aucune propriété ne doit décrire une information absente de la page ou du site.

Interdit :

```text
fausse note
faux avis
prix inexistant
fonctionnalité prévue présentée comme disponible
auteur fictif
date artificielle
adresse non publique
logo différent de la marque réelle
```

La donnée structurée doit être une représentation fidèle du contenu principal.

---

## 3.3 Les données structurées ne garantissent aucun affichage

Un balisage valide :

- aide les moteurs à comprendre la page ;
- peut rendre une page éligible à certaines présentations ;
- ne garantit pas un résultat enrichi ;
- ne garantit pas un classement ;
- ne garantit pas une citation par un moteur génératif.

La communication FlatCMS ne doit pas parler de « garantie de rich result ».

---

## 3.4 Précision avant quantité

Priorité :

```text
peu de propriétés
mais exactes, complètes et maintenues
```

Éviter :

```text
beaucoup de propriétés
mais vides, fausses ou obsolètes
```

---

## 3.5 Un graphe unique par page

Le projet privilégie un seul script JSON-LD contenant un `@graph`.

Avantages :

- entités reliées par `@id` ;
- moins de répétitions ;
- cohérence globale ;
- fusion plus simple des providers ;
- meilleure déduplication.

Plusieurs scripts restent techniquement possibles, mais ne constituent pas le modèle cible.

---

# 4. Convention des identifiants `@id`

## 4.1 Organisation

```text
https://flat-cms.fr/#organization
```

## 4.2 Site web

```text
https://flat-cms.fr/#website
```

## 4.3 Logiciel

```text
https://flat-cms.fr/#software
```

## 4.4 Page

```text
https://flat-cms.fr/fr-FR/architecture/#webpage
```

## 4.5 Article

```text
https://flat-cms.fr/fr-FR/blog/article/#article
```

## 4.6 Fil d’Ariane

```text
https://flat-cms.fr/fr-FR/architecture/#breadcrumb
```

## 4.7 Image principale

```text
https://flat-cms.fr/fr-FR/blog/article/#primaryimage
```

## 4.8 Auteur

```text
https://flat-cms.fr/fr-FR/a-propos/alain-broye/#person
```

si une page publique d’auteur est créée.

## 4.9 Offre

```text
https://flat-cms.fr/fr-FR/tarifs/pagesbuilder/#offer
```

## Règles

- utiliser des URLs absolues ;
- ne pas modifier les identifiants sans migration ;
- conserver les mêmes identifiants dans toutes les pages qui référencent une entité globale ;
- ne pas utiliser un UUID aléatoire ;
- éviter de créer plusieurs `Organization` concurrentes.

---

# 5. Entités globales

## 5.1 `Organization`

### `@id`

```text
https://flat-cms.fr/#organization
```

### Type

```text
Organization
```

Un sous-type plus précis ne doit être utilisé que s’il correspond réellement à la structure juridique et publique du projet.

### Propriétés cibles

```json
{
  "@type": "Organization",
  "@id": "https://flat-cms.fr/#organization",
  "name": "FlatCMS",
  "alternateName": "FlatCMS France",
  "url": "https://flat-cms.fr/",
  "logo": {
    "@id": "https://flat-cms.fr/#logo"
  },
  "description": "FlatCMS est un CMS PHP open source sans base de données SQL.",
  "email": "contact@flat-cms.fr",
  "sameAs": []
}
```

### Validation requise

Avant publication :

- raison sociale ou identité éditrice ;
- adresse éventuelle ;
- téléphone éventuel ;
- e-mail public ;
- profils sociaux officiels ;
- dépôt GitHub officiel ;
- statut juridique ;
- identifiants fiscaux si publiés et pertinents.

Ne pas ajouter d’information personnelle ou administrative qui n’a pas vocation à être publique.

---

## 5.2 Logo

```json
{
  "@type": "ImageObject",
  "@id": "https://flat-cms.fr/#logo",
  "url": "https://flat-cms.fr/assets/brand/logo-flatcms.png",
  "contentUrl": "https://flat-cms.fr/assets/brand/logo-flatcms.png",
  "caption": "FlatCMS",
  "inLanguage": "fr-FR"
}
```

### Règles

- image officielle ;
- URL stable ;
- accessible au crawl ;
- dimensions suffisantes ;
- pas de variante temporaire ;
- fond et contraste appropriés.

---

## 5.3 `WebSite`

### `@id`

```text
https://flat-cms.fr/#website
```

### Exemple

```json
{
  "@type": "WebSite",
  "@id": "https://flat-cms.fr/#website",
  "url": "https://flat-cms.fr/",
  "name": "FlatCMS",
  "alternateName": "FlatCMS France",
  "description": "Site officiel de FlatCMS.",
  "publisher": {
    "@id": "https://flat-cms.fr/#organization"
  },
  "inLanguage": [
    "fr-FR",
    "en-US",
    "de-DE",
    "es-ES",
    "it-IT",
    "pt-PT"
  ]
}
```

### Recherche interne

Un `SearchAction` ne doit être ajouté que si :

- la recherche publique existe ;
- son URL fonctionne ;
- le paramètre est stable ;
- le résultat est utile ;
- la fonction est maintenue.

Exemple conceptuel :

```json
{
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "https://flat-cms.fr/fr-FR/recherche/?q={search_term_string}"
    },
    "query-input": "required name=search_term_string"
  }
}
```

Le support visuel historique d’une boîte de recherche dans les résultats ne doit pas être promis.

---

## 5.4 `SoftwareApplication`

### `@id`

```text
https://flat-cms.fr/#software
```

### Type recommandé

```text
SoftwareApplication
```

### Propriétés cibles

```json
{
  "@type": "SoftwareApplication",
  "@id": "https://flat-cms.fr/#software",
  "name": "FlatCMS",
  "alternateName": "FlatCMS LTS Core",
  "description": "CMS flat-file en PHP natif avec architecture HMVC, autoloading PSR-4 et stockage JSON.",
  "url": "https://flat-cms.fr/fr-FR/",
  "downloadUrl": "https://flat-cms.fr/fr-FR/telechargement/",
  "softwareVersion": "1.0.0",
  "applicationCategory": "ContentManagementApplication",
  "applicationSubCategory": "Flat-file CMS",
  "operatingSystem": "Server environment with a supported PHP runtime",
  "programmingLanguage": "PHP",
  "license": "URL de la licence officielle",
  "author": {
    "@id": "https://flat-cms.fr/#organization"
  },
  "publisher": {
    "@id": "https://flat-cms.fr/#organization"
  },
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "EUR",
    "url": "https://flat-cms.fr/fr-FR/telechargement/",
    "availability": "https://schema.org/InStock"
  }
}
```

### Attention au rich result Google

Pour l’éligibilité spécifique au résultat enrichi « Software App », Google demande notamment :

- `name` ;
- `offers.price` ;
- et une note agrégée ou un avis selon sa documentation actuelle.

FlatCMS ne doit pas inventer de note ou d’avis pour satisfaire cette condition.

Le balisage `SoftwareApplication` reste utile sémantiquement même sans rich result.

### Propriétés à ne publier qu’après validation

- `aggregateRating` ;
- `review` ;
- nombre de téléchargements ;
- compatibilités PHP exactes ;
- systèmes d’exploitation ;
- captures ;
- taille du fichier ;
- exigences serveur ;
- version de développement.

---

# 6. Graphe de la page d’accueil

La page d’accueil doit contenir au minimum :

```text
Organization
WebSite
SoftwareApplication
WebPage
ImageObject
```

### Exemple conceptuel

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://flat-cms.fr/#organization"
    },
    {
      "@type": "WebSite",
      "@id": "https://flat-cms.fr/#website",
      "publisher": {
        "@id": "https://flat-cms.fr/#organization"
      }
    },
    {
      "@type": "SoftwareApplication",
      "@id": "https://flat-cms.fr/#software",
      "publisher": {
        "@id": "https://flat-cms.fr/#organization"
      }
    },
    {
      "@type": "WebPage",
      "@id": "https://flat-cms.fr/fr-FR/#webpage",
      "url": "https://flat-cms.fr/fr-FR/",
      "isPartOf": {
        "@id": "https://flat-cms.fr/#website"
      },
      "about": {
        "@id": "https://flat-cms.fr/#software"
      }
    }
  ]
}
```

---

# 7. Pages institutionnelles et produit

## 7.1 Type de base

```text
WebPage
```

Sous-types possibles :

- `AboutPage`
- `ContactPage`
- `CollectionPage`
- `ProfilePage`

Le sous-type doit correspondre à la fonction principale.

---

## 7.2 Propriétés standard

```json
{
  "@type": "WebPage",
  "@id": "URL/#webpage",
  "url": "URL canonique",
  "name": "H1 ou titre principal",
  "description": "Résumé de la page",
  "inLanguage": "fr-FR",
  "isPartOf": {
    "@id": "https://flat-cms.fr/#website"
  },
  "about": {
    "@id": "https://flat-cms.fr/#software"
  },
  "publisher": {
    "@id": "https://flat-cms.fr/#organization"
  },
  "breadcrumb": {
    "@id": "URL/#breadcrumb"
  },
  "primaryImageOfPage": {
    "@id": "URL/#primaryimage"
  },
  "datePublished": "ISO 8601",
  "dateModified": "ISO 8601"
}
```

### Dates

Ne pas ajouter une fausse date de publication à une page purement institutionnelle si elle n’est pas gérée.

Si les dates sont publiées :

- elles doivent correspondre au contenu visible ou aux métadonnées fiables ;
- `dateModified` doit changer lors d’une modification significative ;
- une modification automatique insignifiante ne doit pas actualiser toutes les pages.

---

# 8. Pages d’architecture et documentation

## 8.1 Type principal

```text
TechArticle
```

Schema.org définit `TechArticle` comme un article technique.

### Exemple

```json
{
  "@type": "TechArticle",
  "@id": "https://flat-cms.fr/fr-FR/architecture/hmvc/#article",
  "url": "https://flat-cms.fr/fr-FR/architecture/hmvc/",
  "headline": "Architecture HMVC de FlatCMS",
  "description": "Explication de l’architecture HMVC utilisée par FlatCMS.",
  "inLanguage": "fr-FR",
  "isPartOf": {
    "@id": "https://flat-cms.fr/#website"
  },
  "mainEntityOfPage": {
    "@id": "https://flat-cms.fr/fr-FR/architecture/hmvc/#webpage"
  },
  "about": {
    "@id": "https://flat-cms.fr/#software"
  },
  "author": {
    "@id": "https://flat-cms.fr/fr-FR/a-propos/alain-broye/#person"
  },
  "publisher": {
    "@id": "https://flat-cms.fr/#organization"
  },
  "datePublished": "2026-06-08T00:00:00+02:00",
  "dateModified": "2026-06-08T00:00:00+02:00",
  "proficiencyLevel": "Intermediate",
  "dependencies": "PHP, FlatCMS v1.0.0",
  "image": {
    "@id": "https://flat-cms.fr/fr-FR/architecture/hmvc/#primaryimage"
  }
}
```

### Champs FlatCMS internes à conserver hors Schema.org si nécessaire

- version minimale ;
- version maximale ;
- type documentaire ;
- statut produit ;
- niveau ;
- environnement testé ;
- date de dernière vérification.

Ces champs peuvent :

- être affichés visuellement ;
- être stockés dans les modèles JSON ;
- alimenter des propriétés Schema.org pertinentes lorsqu’elles existent ;
- rester hors balisage si aucune correspondance fiable n’existe.

---

## 8.2 Tutoriels et guides

Le type `HowTo` peut être utilisé au sens Schema.org lorsque la page décrit réellement une suite d’étapes.

Cependant :

- l’objectif principal reste la compréhension sémantique ;
- aucune présentation enrichie Google ne doit être promise ;
- les étapes doivent être visibles ;
- les outils, durées et coûts ne doivent pas être inventés ;
- un guide conceptuel ne doit pas être forcé en `HowTo`.

### Critères d’utilisation

Utiliser `HowTo` si la page contient :

```text
un résultat concret
des prérequis
une suite ordonnée d’étapes
un résultat final vérifiable
```

Sinon, utiliser :

```text
TechArticle
```

---

# 9. Articles de blog

## 9.1 Type

```text
BlogPosting
```

### Propriétés cibles

```json
{
  "@type": "BlogPosting",
  "@id": "URL/#article",
  "headline": "Titre de l’article",
  "description": "Résumé",
  "url": "URL canonique",
  "mainEntityOfPage": {
    "@id": "URL/#webpage"
  },
  "author": {
    "@id": "URL auteur/#person"
  },
  "publisher": {
    "@id": "https://flat-cms.fr/#organization"
  },
  "datePublished": "ISO 8601",
  "dateModified": "ISO 8601",
  "image": {
    "@id": "URL/#primaryimage"
  },
  "articleSection": "Architecture",
  "keywords": [
    "FlatCMS",
    "HMVC",
    "PHP"
  ],
  "inLanguage": "fr-FR",
  "about": {
    "@id": "https://flat-cms.fr/#software"
  }
}
```

### Propriétés recommandées en priorité

- `author`
- `author.url`
- `headline`
- `image`
- `datePublished`
- `dateModified`

### Règles

- titre identique ou cohérent avec le H1 ;
- auteur réel ;
- page auteur publique si utilisée ;
- dates exactes ;
- image pertinente et accessible ;
- aucune fausse catégorie ;
- contenu principal visible.

---

# 10. Auteurs

## 10.1 Page auteur

Si le site crée une page auteur :

```text
/fr-FR/a-propos/alain-broye/
```

Type :

```text
ProfilePage
```

Entité principale :

```text
Person
```

### Exemple

```json
{
  "@type": "ProfilePage",
  "@id": "https://flat-cms.fr/fr-FR/a-propos/alain-broye/#profilepage",
  "url": "https://flat-cms.fr/fr-FR/a-propos/alain-broye/",
  "mainEntity": {
    "@type": "Person",
    "@id": "https://flat-cms.fr/fr-FR/a-propos/alain-broye/#person",
    "name": "Alain BROYE",
    "url": "https://flat-cms.fr/fr-FR/a-propos/alain-broye/",
    "sameAs": []
  }
}
```

### Prudence

Ne publier que :

- informations biographiques validées ;
- profils officiels ;
- photo autorisée ;
- fonctions réelles ;
- liens publics.

---

# 11. Fil d’Ariane

## Type

```text
BreadcrumbList
```

### Exemple

```json
{
  "@type": "BreadcrumbList",
  "@id": "https://flat-cms.fr/fr-FR/architecture/hmvc/#breadcrumb",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Accueil",
      "item": "https://flat-cms.fr/fr-FR/"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Architecture",
      "item": "https://flat-cms.fr/fr-FR/architecture/"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "HMVC",
      "item": "https://flat-cms.fr/fr-FR/architecture/hmvc/"
    }
  ]
}
```

### Règles

- refléter le fil visible ;
- positions continues ;
- URLs absolues ;
- locale cohérente ;
- dernier élément égal à la page courante ;
- ne pas inventer une hiérarchie uniquement pour le moteur.

---

# 12. Images

## Type

```text
ImageObject
```

### Exemple

```json
{
  "@type": "ImageObject",
  "@id": "URL/#primaryimage",
  "url": "URL de l’image",
  "contentUrl": "URL de l’image",
  "width": 1671,
  "height": 941,
  "caption": "Description de l’image",
  "inLanguage": "fr-FR"
}
```

### Règles

- image accessible ;
- URL indexable ;
- dimensions réelles ;
- image directement liée au contenu ;
- format pris en charge ;
- variante locale si le texte est intégré dans l’image ;
- pas d’image générique trompeuse.

---

# 13. Vidéos

## Type

```text
VideoObject
```

### Propriétés essentielles

```json
{
  "@type": "VideoObject",
  "@id": "URL/#video",
  "name": "Installer FlatCMS en local",
  "description": "Tutoriel vidéo d’installation.",
  "thumbnailUrl": [
    "URL vignette"
  ],
  "uploadDate": "ISO 8601",
  "duration": "PT8M",
  "contentUrl": "URL vidéo directe si disponible",
  "embedUrl": "URL d’intégration",
  "inLanguage": "fr-FR",
  "publisher": {
    "@id": "https://flat-cms.fr/#organization"
  }
}
```

Pour l’éligibilité vidéo Google, les propriétés obligatoires actuelles doivent être suivies dans la documentation officielle.

### Moments clés

`Clip` ou `SeekToAction` ne doivent être ajoutés que si :

- les liens temporels fonctionnent ;
- les timestamps sont corrects ;
- le lecteur vidéo les prend en charge ;
- la structure reste stable.

---

# 14. Téléchargement et version logicielle

## Page téléchargement

Graphe conseillé :

```text
WebPage
SoftwareApplication
Offer
BreadcrumbList
```

### Version

```json
{
  "@type": "SoftwareApplication",
  "@id": "https://flat-cms.fr/#software",
  "softwareVersion": "1.0.0",
  "downloadUrl": "https://flat-cms.fr/fr-FR/telechargement/",
  "softwareRequirements": "PHP version validée et extensions requises",
  "releaseNotes": "URL des notes de version"
}
```

### Distribution

Les propriétés suivantes ne doivent être ajoutées qu’après validation :

- `fileSize`
- `encodingFormat`
- checksum
- date de publication
- exigences exactes
- système d’exploitation
- architecture processeur

Schema.org ne doit pas être détourné pour stocker des données sans propriété appropriée.

---

# 15. Produits premium et offres

## 15.1 Types

```text
Product
Offer
```

ou éventuellement :

```text
SoftwareApplication
Offer
```

selon la nature exacte du produit.

### Exemple PagesBuilder

```json
{
  "@type": "Product",
  "@id": "https://flat-cms.fr/fr-FR/builders/pagesbuilder/#product",
  "name": "FlatCMS PagesBuilder",
  "description": "Builder visuel de pages pour FlatCMS.",
  "brand": {
    "@id": "https://flat-cms.fr/#organization"
  },
  "isRelatedTo": {
    "@id": "https://flat-cms.fr/#software"
  },
  "offers": {
    "@type": "Offer",
    "@id": "https://flat-cms.fr/fr-FR/builders/pagesbuilder/#offer",
    "url": "https://flat-cms.fr/fr-FR/tarifs/",
    "price": "29.90",
    "priceCurrency": "EUR",
    "availability": "https://schema.org/InStock"
  }
}
```

### Règles commerciales

Le prix structuré doit correspondre exactement au prix visible.

Préciser visuellement :

- HT ou TTC ;
- durée ;
- nombre de sites ;
- renouvellement ;
- licence ;
- restrictions.

Ne pas utiliser `aggregateRating` tant que de vrais avis vérifiables ne sont pas disponibles.

---

# 16. Comparatifs

## Type

```text
Article
```

ou :

```text
TechArticle
```

selon le contenu.

Ne pas utiliser :

```text
Review
```

si la page n’est pas une véritable évaluation éditoriale répondant aux règles applicables.

Ne jamais ajouter de notes artificielles aux concurrents ou à FlatCMS.

---

# 17. FAQ

Au 8 juin 2026, Google a annoncé la suppression de l’apparence enrichie FAQ de ses résultats à partir du 7 mai 2026, avec retrait progressif des rapports et outils associés en juin et août 2026.

Conséquence pour FlatCMS :

- ne pas construire la stratégie autour du rich result FAQ ;
- conserver les questions-réponses visibles pour leur utilité éditoriale ;
- `FAQPage` peut rester un type Schema.org sémantique si le contenu correspond ;
- ne pas promettre de bénéfice visuel Google ;
- éviter le balisage répétitif sur toutes les pages.

### Décision recommandée

Par défaut :

```text
ne pas générer FAQPage automatiquement
```

L’activer uniquement via une option explicite et documentée.

---

# 18. Pages de listes

## Types possibles

```text
CollectionPage
ItemList
```

Exemples :

- liste d’articles ;
- liste de fonctionnalités ;
- documentation ;
- modules ;
- thèmes.

### Prudence

Ne pas créer un `ItemList` volumineux sur toutes les archives si cela n’apporte aucune information stable.

---

# 19. Page de contact

## Type

```text
ContactPage
```

Avec référence à :

```text
Organization
```

Un `ContactPoint` peut être ajouté à l’organisation si les informations sont publiques et réellement destinées à ce type de demande.

```json
{
  "@type": "ContactPoint",
  "contactType": "customer support",
  "email": "contact@flat-cms.fr",
  "availableLanguage": [
    "French",
    "English"
  ]
}
```

Ne pas annoncer une langue de support non réellement prise en charge.

---

# 20. Page À propos

## Type

```text
AboutPage
```

Liens principaux :

```text
about → SoftwareApplication
publisher → Organization
mainEntity → Organization ou projet
```

Cette page doit jouer un rôle central pour :

- désambiguïser FlatCMS ;
- expliquer l’histoire ;
- identifier l’éditeur ;
- présenter l’auteur ;
- relier les profils officiels ;
- clarifier la gouvernance et les licences.

---

# 21. Multilingue

## 21.1 Entités globales

Les mêmes identifiants globaux sont réutilisés :

```text
https://flat-cms.fr/#organization
https://flat-cms.fr/#website
https://flat-cms.fr/#software
```

## 21.2 Pages locales

Chaque version locale possède son propre `@id`.

```text
/fr-FR/architecture/#webpage
/en-US/architecture/#webpage
/de-DE/architektur/#webpage
```

## 21.3 `inLanguage`

Utiliser :

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

## 21.4 Traductions d’un même article

Chaque version :

- possède son propre `Article` ou `TechArticle` ;
- utilise la même entité logicielle ;
- utilise une URL canonique locale ;
- porte ses propres dates si la traduction est publiée ultérieurement ;
- est reliée par `hreflang` dans le HTML.

Schema.org ne remplace pas `hreflang`.

---

# 22. Canonicals et redirections

Les URLs utilisées dans le JSON-LD doivent être :

- finales ;
- canoniques ;
- en HTTPS ;
- sans redirection ;
- dans la bonne locale ;
- cohérentes avec le HTML.

Après migration :

```text
aucune entité ne doit référencer wiki.flat-cms.fr
```

sauf une archive volontaire clairement identifiée.

---

# 23. Graphe par template

| Template | Entités |
|---|---|
| Accueil | Organization, WebSite, SoftwareApplication, WebPage, ImageObject |
| Page produit | WebPage, SoftwareApplication, BreadcrumbList, ImageObject |
| Fonctionnalité | WebPage, SoftwareApplication, BreadcrumbList |
| Architecture | WebPage, TechArticle, BreadcrumbList, ImageObject |
| Documentation | WebPage, TechArticle ou HowTo, BreadcrumbList |
| Article blog | WebPage, BlogPosting, BreadcrumbList, ImageObject, Person |
| Comparatif | WebPage, Article/TechArticle, BreadcrumbList |
| Téléchargement | WebPage, SoftwareApplication, Offer, BreadcrumbList |
| Tarif premium | WebPage, Product/SoftwareApplication, Offer, BreadcrumbList |
| Vidéo | WebPage ou Article, VideoObject, BreadcrumbList |
| Contact | ContactPage, Organization, BreadcrumbList |
| À propos | AboutPage, Organization, Person, BreadcrumbList |
| Auteur | ProfilePage, Person, BreadcrumbList |
| Liste | CollectionPage, éventuellement ItemList, BreadcrumbList |

---

# 24. Architecture des providers FlatCMS

## 24.1 Providers existants

```text
SiteSchemaProvider
PageSchemaProvider
PostSchemaProvider
```

## 24.2 Providers recommandés

```text
OrganizationSchemaProvider
SoftwareSchemaProvider
BreadcrumbSchemaProvider
DocumentationSchemaProvider
VideoSchemaProvider
ProductSchemaProvider
AuthorSchemaProvider
ImageSchemaProvider
```

La création de nouveaux providers doit rester proportionnée.

Un provider unique peut gérer plusieurs types proches si le code reste clair.

---

## 24.3 Contrat conceptuel

```php
interface StructuredDataProviderInterface
{
    public function supports(array $context): bool;

    public function provide(array $context): array;
}
```

Le contrat réel existant doit être conservé ou adapté avec précaution.

### Contexte possible

```text
route
locale
canonical_url
content_type
content
site_settings
theme
author
breadcrumbs
software_version
offers
media
```

---

# 25. Responsabilités du `SchemaGraphBuilder`

Le builder doit :

- ajouter les entités ;
- vérifier la présence de `@id` ;
- fusionner les références ;
- dédupliquer les entités ;
- préserver les tableaux ;
- rejeter les structures invalides ;
- normaliser les URLs ;
- produire un graphe sérialisable ;
- gérer les erreurs sans casser le frontend.

### Déduplication

Deux entités avec le même `@id` ne doivent pas apparaître séparément avec des données contradictoires.

Le builder doit soit :

- fusionner proprement ;
- soit signaler le conflit.

---

# 26. Responsabilités du `StructuredDataManager`

Le manager doit :

1. collecter le contexte ;
2. sélectionner les providers compatibles ;
3. construire le graphe ;
4. valider les propriétés internes minimales ;
5. sérialiser avec échappement sûr ;
6. injecter le script dans le rendu ;
7. journaliser les erreurs ;
8. permettre la désactivation ;
9. éviter la duplication par le thème.

---

# 27. Sécurité de sérialisation

Le JSON-LD doit être généré avec un sérialiseur JSON fiable.

En PHP :

```php
json_encode(
    $graph,
    JSON_UNESCAPED_SLASHES
    | JSON_UNESCAPED_UNICODE
    | JSON_THROW_ON_ERROR
);
```

Selon le contexte d’injection, il faut empêcher la fermeture malveillante du script et les injections.

Les données éditoriales non fiables doivent être normalisées.

Ne jamais concaténer manuellement une chaîne JSON.

---

# 28. Validation interne

Avant rendu, le système doit vérifier au minimum :

- `@context` ;
- `@type` ;
- `@id` si exigé par le projet ;
- URL absolue ;
- langue ;
- dates ISO 8601 ;
- prix numérique cohérent ;
- devise ISO 4217 ;
- image accessible ;
- aucune propriété vide ;
- aucune valeur `null` inutile ;
- absence de doublon.

---

# 29. Outils de validation

## 29.1 Rich Results Test

Utiliser pour les types pris en charge par Google.

## 29.2 Schema Markup Validator

Utiliser pour la validité Schema.org générale.

## 29.3 URL Inspection

Utiliser après publication pour vérifier le rendu vu par Google.

## 29.4 Tests automatisés FlatCMS

Prévoir :

- tests unitaires du builder ;
- tests des providers ;
- snapshots JSON ;
- tests par template ;
- validation des URLs ;
- validation des dates ;
- détection des duplications ;
- test de sérialisation Unicode ;
- test des six locales.

---

# 30. Checklist par page

- [ ] le type décrit la page ;
- [ ] les données sont visibles ;
- [ ] les informations sont exactes ;
- [ ] les URLs sont absolues ;
- [ ] les URLs sont canoniques ;
- [ ] les `@id` sont stables ;
- [ ] le fil d’Ariane correspond à l’interface ;
- [ ] l’auteur est réel ;
- [ ] les dates sont exactes ;
- [ ] l’image est accessible ;
- [ ] la locale est correcte ;
- [ ] le logiciel pointe vers l’entité globale ;
- [ ] l’organisation n’est pas dupliquée ;
- [ ] les offres correspondent aux prix visibles ;
- [ ] aucun avis fictif n’est présent ;
- [ ] le JSON est valide ;
- [ ] le rendu HTML reste fonctionnel si le JSON-LD échoue.

---

# 31. Checklist globale

- [ ] Organization validée
- [ ] logo officiel validé
- [ ] profils `sameAs` validés
- [ ] WebSite validé
- [ ] SoftwareApplication validé
- [ ] version logicielle dynamique
- [ ] licence liée
- [ ] offre gratuite correcte
- [ ] offres premium correctes
- [ ] auteurs validés
- [ ] providers documentés
- [ ] graph builder testé
- [ ] erreurs journalisées
- [ ] Rich Results Test
- [ ] Schema Markup Validator
- [ ] Search Console
- [ ] test des six locales
- [ ] test après migration
- [ ] aucun ancien domaine résiduel

---

# 32. Priorités d’implémentation

## P0 — Lancement

1. Organization
2. Logo
3. WebSite
4. SoftwareApplication
5. WebPage
6. BreadcrumbList
7. BlogPosting
8. TechArticle
9. ImageObject

## P1 — Contenus enrichis

1. ProfilePage
2. Person
3. VideoObject
4. ContactPage
5. AboutPage
6. CollectionPage

## P2 — Commerce

1. Product
2. Offer
3. bundles
4. licences par site

## P3 — Avancé

1. ItemList
2. HowTo sémantique
3. moments clés vidéo
4. entités de versions logicielles
5. providers tiers

---

# 33. Risques

## 33.1 Surcharge

Trop d’entités peuvent :

- complexifier la maintenance ;
- créer des contradictions ;
- rendre le débogage difficile.

## 33.2 Automatisation aveugle

Un provider ne doit pas déduire :

- un auteur inexistant ;
- une note ;
- un prix ;
- une image ;
- une date ;
- un type précis sans preuve.

## 33.3 Obsolescence

Les recommandations des moteurs évoluent.

Exemple : l’apparence FAQ a été retirée par Google en 2026.

Le projet doit réviser ce document au minimum :

```text
à chaque version majeure
et deux fois par an
```

---

# 34. SEO, GEO et GIO

Les données structurées :

- clarifient les entités ;
- relient le logiciel, l’organisation, les auteurs et les contenus ;
- facilitent l’interprétation machine ;
- ne remplacent pas le contenu visible ;
- ne constituent pas une certification GEO/GIO ;
- ne garantissent aucune citation.

La priorité reste :

```text
contenu utile
faits vérifiables
architecture claire
identités stables
sources primaires
HTML accessible
```

---

# 35. Références officielles

- Introduction aux données structurées Google Search  
  https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data

- Règles générales Google  
  https://developers.google.com/search/docs/appearance/structured-data/sd-policies

- Organization  
  https://developers.google.com/search/docs/appearance/structured-data/organization

- Article  
  https://developers.google.com/search/docs/appearance/structured-data/article

- Breadcrumb  
  https://developers.google.com/search/docs/appearance/structured-data/breadcrumb

- Software App  
  https://developers.google.com/search/docs/appearance/structured-data/software-app

- Video  
  https://developers.google.com/search/docs/appearance/structured-data/video

- Schema.org  
  https://schema.org/

---

# 36. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Création de la stratégie Schema.org et JSON-LD | ChatGPT / Alain BROYE |

---

# 37. Prochaine action

Créer :

```text
CRAWL_POLICY.md
```

Ce document définira :

- `robots.txt` ;
- robots des moteurs classiques ;
- robots des moteurs génératifs ;
- robots d’entraînement ;
- accès aux pages, médias et fichiers ;
- `noindex` ;
- `X-Robots-Tag` ;
- politiques par environnement ;
- contrôle de charge ;
- journalisation ;
- stratégie de la démo.
