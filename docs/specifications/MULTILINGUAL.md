# MULTILINGUAL — Architecture multilingue du futur site officiel FlatCMS

> **Document directeur international**
>
> Projet : FlatCMS  
> Domaine cible : `https://flat-cms.fr`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Locales natives : `fr-FR`, `en-US`, `de-DE`, `es-ES`, `it-IT`, `pt-PT`  
> Documents parents : `SEO.md`, `SITE_ARCHITECTURE.md`, `CONTENT_MATRIX.md`, `DOCUMENTATION_MAP.md`, `KEYWORDS.md`, `REDIRECTS.md`, `STRUCTURED_DATA.md`, `CRAWL_POLICY.md`, `CONTENT_STYLE_GUIDE.md`  
> Statut : architecture initiale à valider avant développement

---

## 1. Objet du document

Ce document définit l’architecture multilingue du futur site officiel FlatCMS.

Il encadre :

- les six locales ;
- la structure des URLs ;
- les slugs ;
- la page racine ;
- le sélecteur de langue ;
- les balises `hreflang` ;
- `x-default` ;
- les canonicals ;
- l’attribut HTML `lang` ;
- les contenus partiellement traduits ;
- les fallbacks ;
- les dates, nombres et devises ;
- les médias localisés ;
- les données structurées ;
- les sitemaps ;
- la migration du wiki ;
- le workflow de traduction ;
- les tests automatiques et éditoriaux.

L’objectif est de proposer une expérience multilingue :

```text
compréhensible
stable
indexable
accessible
maintenable
cohérente avec FlatCMS
```

---

# 2. Principes fondamentaux

## 2.1 Une URL distincte par locale

Chaque version linguistique d’une page doit disposer d’une URL propre.

Exemple :

```text
https://flat-cms.fr/fr-FR/fonctionnalites/
https://flat-cms.fr/en-US/features/
https://flat-cms.fr/de-DE/funktionen/
https://flat-cms.fr/es-ES/funcionalidades/
https://flat-cms.fr/it-IT/funzionalita/
https://flat-cms.fr/pt-PT/funcionalidades/
```

Ne pas servir plusieurs langues sur une même URL à partir :

- d’un cookie ;
- de `Accept-Language` ;
- d’une détection IP ;
- d’un paramètre de session.

Ces mécanismes peuvent personnaliser une suggestion, mais ne doivent pas déterminer seuls le contenu canonique de l’URL.

---

## 2.2 Une page contient une langue principale

Chaque page doit employer une langue dominante pour :

- le contenu ;
- la navigation ;
- le footer ;
- les messages ;
- les métadonnées ;
- les textes alternatifs ;
- les données structurées.

Éviter les traductions côte à côte dans la même page.

Les termes techniques internationaux peuvent rester dans leur langue officielle :

```text
PHP
HMVC
PSR-4
JSON
Nginx
OpenAI
FlatCMS
```

---

## 2.3 Pas de redirection automatique forcée

FlatCMS ne doit pas rediriger automatiquement un utilisateur vers une autre locale uniquement à partir :

- du navigateur ;
- du pays ;
- de l’adresse IP ;
- d’un cookie historique.

Une suggestion peut être affichée :

```text
Cette page est disponible en français.
Afficher la version française
```

L’utilisateur doit pouvoir :

- rester sur la page actuelle ;
- fermer la suggestion ;
- choisir une autre langue ;
- conserver son choix.

---

## 2.4 Le choix utilisateur prime

Lorsqu’un utilisateur choisit une locale :

- enregistrer sa préférence dans un cookie ou un stockage approprié ;
- utiliser ce choix pour les visites suivantes ;
- ne pas bloquer l’accès aux autres locales ;
- conserver un sélecteur visible ;
- ne pas rediriger les robots selon ce cookie.

---

## 2.5 Une traduction doit être réelle

Une page ne doit être déclarée comme traduction disponible que lorsque :

- le contenu principal est traduit ;
- le title est traduit ;
- la meta description est traduite ;
- le H1 est traduit ;
- les CTA sont traduits ;
- les textes alternatifs sont traduits ;
- la navigation correspond à la locale ;
- les liens internes pointent vers la bonne locale.

Traduire seulement le header et le footer ne suffit pas.

---

# 3. Locales officielles

| Locale | Langue | Région éditoriale | Répertoire |
|---|---|---|---|
| `fr-FR` | Français | France | `/fr-FR/` |
| `en-US` | Anglais | États-Unis | `/en-US/` |
| `de-DE` | Allemand | Allemagne | `/de-DE/` |
| `es-ES` | Espagnol | Espagne | `/es-ES/` |
| `it-IT` | Italien | Italie | `/it-IT/` |
| `pt-PT` | Portugais | Portugal | `/pt-PT/` |

## Règle de casse

Les répertoires conservent la casse BCP 47 du projet :

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

Les valeurs `hreflang` peuvent être sérialisées avec cette même casse.

Les comparaisons internes doivent être insensibles à la casse lorsque le standard le permet, mais les URLs publiques doivent rester stables.

---

# 4. Décision sur la racine du domaine

## Option retenue

```text
https://flat-cms.fr/
```

devient une page internationale `x-default`.

Elle présente :

- FlatCMS en quelques lignes ;
- les six langues disponibles ;
- une suggestion non forcée selon la langue du navigateur ;
- un accès direct à la locale choisie ;
- les principaux liens institutionnels.

## Pourquoi cette option

Elle permet :

- une destination neutre pour `x-default` ;
- une séparation claire de `/fr-FR/` ;
- l’évolution vers de nouvelles langues ;
- l’absence de préférence forcée pour le français ;
- une architecture internationale explicite.

## Alternative possible

Si la page racine doit finalement servir directement le français, `x-default` pourra pointer vers `/fr-FR/`.

Cette décision devra être figée avant la mise en production et reportée dans `REDIRECTS.md`.

---

# 5. Architecture des URLs

## 5.1 Structure générale

```text
/
├── fr-FR/
├── en-US/
├── de-DE/
├── es-ES/
├── it-IT/
└── pt-PT/
```

## 5.2 Structure française

```text
/fr-FR/
├── fonctionnalites/
├── architecture/
├── documentation/
├── blog/
├── comparatifs/
├── telechargement/
├── tarifs/
├── licences/
├── roadmap/
├── a-propos/
└── contact/
```

## 5.3 Structure anglaise

```text
/en-US/
├── features/
├── architecture/
├── documentation/
├── blog/
├── comparisons/
├── download/
├── pricing/
├── licensing/
├── roadmap/
├── about/
└── contact/
```

## 5.4 Structure allemande

```text
/de-DE/
├── funktionen/
├── architektur/
├── dokumentation/
├── blog/
├── vergleiche/
├── download/
├── preise/
├── lizenzen/
├── roadmap/
├── ueber-uns/
└── kontakt/
```

## 5.5 Structure espagnole

```text
/es-ES/
├── funcionalidades/
├── arquitectura/
├── documentacion/
├── blog/
├── comparativas/
├── descargar/
├── precios/
├── licencias/
├── hoja-de-ruta/
├── acerca-de/
└── contacto/
```

## 5.6 Structure italienne

```text
/it-IT/
├── funzionalita/
├── architettura/
├── documentazione/
├── blog/
├── confronti/
├── download/
├── prezzi/
├── licenze/
├── roadmap/
├── chi-siamo/
└── contatti/
```

## 5.7 Structure portugaise

```text
/pt-PT/
├── funcionalidades/
├── arquitetura/
├── documentacao/
├── blog/
├── comparacoes/
├── transferir/
├── precos/
├── licencas/
├── roteiro/
├── sobre/
└── contacto/
```

Les traductions définitives des slugs doivent être relues par un locuteur compétent avant publication.

---

# 6. Convention des slugs

## 6.1 Principes

Un slug doit être :

- court ;
- descriptif ;
- stable ;
- en minuscules ;
- séparé par des tirets ;
- sans paramètre ;
- sans mot inutile ;
- naturel dans la locale.

## 6.2 Caractères

Recommandation initiale :

```text
ASCII uniquement dans les slugs
```

Exemples :

```text
/fonctionnalites/
/funcionalita/
/precos/
/ueber-uns/
```

Cette convention simplifie :

- les copier-coller ;
- les configurations serveur ;
- les redirections ;
- les intégrations externes.

UTF-8 reste techniquement possible, mais doit être adopté comme politique globale si choisi.

---

## 6.3 Accents

Préférer :

```text
/fonctionnalites/
/telechargement/
/precos/
```

Éviter :

```text
/fonctionnalités/
/téléchargement/
/preços/
```

Cette règle concerne les URLs, pas les textes visibles.

---

## 6.4 Slugs techniques

Les termes techniques peuvent rester identiques :

```text
/hmvc/
/psr-4/
/json/
/nginx/
/apache/
```

Les catégories parentes restent localisées.

Exemple :

```text
/fr-FR/architecture/hmvc/
/de-DE/architektur/hmvc/
/es-ES/arquitectura/hmvc/
```

---

## 6.5 Modification d’un slug

Une fois publié :

- éviter tout changement stylistique ;
- créer une redirection permanente si le changement est nécessaire ;
- mettre à jour les liens internes ;
- mettre à jour les canonicals ;
- mettre à jour les `hreflang` ;
- mettre à jour les sitemaps.

---

# 7. Attribut HTML `lang`

Chaque page utilise sa locale dans la balise racine.

```html
<html lang="fr-FR">
```

```html
<html lang="en-US">
```

```html
<html lang="de-DE">
```

Le W3C recommande de déclarer la langue du document avec l’attribut `lang` sur `<html>`.

## Contenu ponctuellement étranger

Exemple :

```html
<p>
  FlatCMS applique le principe
  <span lang="en">separation of concerns</span>.
</p>
```

## Ne pas utiliser

```html
<meta http-equiv="Content-Language" content="fr">
```

comme méthode principale de déclaration de langue.

---

# 8. Balises `hreflang`

## 8.1 Principe

Chaque version publiée d’une page doit lister :

- sa propre URL ;
- toutes les traductions disponibles ;
- la page `x-default` lorsque pertinente.

Toutes les pages du groupe doivent contenir le même ensemble.

---

## 8.2 Exemple complet

```html
<link rel="alternate"
      hreflang="fr-FR"
      href="https://flat-cms.fr/fr-FR/fonctionnalites/">

<link rel="alternate"
      hreflang="en-US"
      href="https://flat-cms.fr/en-US/features/">

<link rel="alternate"
      hreflang="de-DE"
      href="https://flat-cms.fr/de-DE/funktionen/">

<link rel="alternate"
      hreflang="es-ES"
      href="https://flat-cms.fr/es-ES/funcionalidades/">

<link rel="alternate"
      hreflang="it-IT"
      href="https://flat-cms.fr/it-IT/funzionalita/">

<link rel="alternate"
      hreflang="pt-PT"
      href="https://flat-cms.fr/pt-PT/funcionalidades/">

<link rel="alternate"
      hreflang="x-default"
      href="https://flat-cms.fr/">
```

---

## 8.3 URLs absolues

Toujours utiliser :

```text
https://flat-cms.fr/fr-FR/fonctionnalites/
```

Ne pas utiliser :

```text
/fr-FR/fonctionnalites/
```

dans `hreflang`.

---

## 8.4 Réciprocité

Si la page française pointe vers l’anglais, la page anglaise doit pointer vers la page française.

Une traduction non réciproque peut être ignorée.

---

## 8.5 Auto-référence

Chaque page doit s’inclure dans son propre groupe.

La page française inclut :

```html
hreflang="fr-FR"
```

vers elle-même.

---

## 8.6 Traductions absentes

Une page ne doit pas inclure un `hreflang` vers :

- une page inexistante ;
- une traduction vide ;
- une redirection ;
- une erreur ;
- une page dans la mauvaise langue.

Exemple :

Si un article n’existe qu’en français et en anglais :

```html
<link rel="alternate" hreflang="fr-FR" ...>
<link rel="alternate" hreflang="en-US" ...>
```

Ne pas générer les quatre autres locales.

---

## 8.7 `x-default`

Utiliser `x-default` pour :

- la page de sélection de langue ;
- une page générique internationale ;
- une destination de fallback non ciblée.

Pour une page produit précise, `x-default` peut pointer :

- vers la page racine internationale ;
- ou vers une version générique si elle existe.

Décision recommandée pour le lancement :

```text
Accueil des locales
→ x-default vers https://flat-cms.fr/

Pages internes
→ x-default vers la version fr-FR tant qu’aucune page générique
   internationale équivalente n’existe
```

Cette règle devra être appliquée uniformément.

---

# 9. Méthode d’implémentation `hreflang`

Google accepte :

- HTML ;
- en-têtes HTTP ;
- sitemap XML.

## Décision FlatCMS

Utiliser principalement :

```text
balises HTML dans le <head>
```

Avantages :

- faciles à inspecter ;
- générées avec la page ;
- liées au modèle de contenu ;
- accessibles aux moteurs.

Pour les PDF traduits :

```text
en-têtes HTTP Link
```

peuvent être utilisés.

## Sitemaps

Les sitemaps par locale peuvent aussi contenir les alternates, mais FlatCMS évitera de multiplier les implémentations sans système automatique fiable.

Règle :

```text
HTML comme source principale
Sitemap comme contrôle complémentaire seulement si généré automatiquement
```

---

# 10. Canonicals

## 10.1 Canonique locale

Chaque page traduite utilise une canonique auto-référencée.

Page française :

```html
<link rel="canonical"
      href="https://flat-cms.fr/fr-FR/fonctionnalites/">
```

Page anglaise :

```html
<link rel="canonical"
      href="https://flat-cms.fr/en-US/features/">
```

---

## 10.2 Ne pas canonicaliser vers le français

Incorrect :

```text
page en-US
→ canonical fr-FR
```

Une traduction complète est une page distincte, pas un duplicata à canonicaliser vers la source française.

---

## 10.3 Cohérence

Une URL déclarée dans `hreflang` doit être :

- canonique ;
- indexable ;
- finale ;
- en `200 OK` ;
- dans la langue annoncée.

---

# 11. Sélecteur de langue

## 11.1 Présence

Le sélecteur doit être disponible :

- dans le header desktop ;
- dans le menu mobile ;
- éventuellement dans le footer.

## 11.2 Libellés

Afficher le nom de la langue dans sa propre langue :

```text
Français
English
Deutsch
Español
Italiano
Português
```

Ne pas utiliser uniquement les drapeaux.

Une langue n’est pas équivalente à un pays.

---

## 11.3 Lien vers la page équivalente

Lorsque la traduction existe :

```text
page FR actuelle
→ page EN équivalente
```

Ne pas renvoyer systématiquement vers l’accueil anglais.

---

## 11.4 Traduction absente

Lorsque la page n’existe pas dans la locale choisie :

Option recommandée :

1. afficher une information claire ;
2. proposer la page d’accueil ou la catégorie équivalente ;
3. conserver un lien vers la version disponible ;
4. ne pas fabriquer une traduction vide.

Exemple :

```text
Cette page n’est pas encore disponible en allemand.
Consulter la version française
Accéder à la documentation allemande
```

---

## 11.5 Accessibilité

Le sélecteur doit :

- être utilisable au clavier ;
- avoir un libellé accessible ;
- indiquer la langue active ;
- ne pas dépendre uniquement de la couleur ;
- utiliser de vrais liens ;
- conserver le focus après interaction.

Exemple :

```html
<nav aria-label="Choisir la langue">
```

---

# 12. Détection de langue

## 12.1 Détection autorisée

La langue du navigateur peut servir à :

- suggérer une locale ;
- pré-sélectionner une option ;
- afficher une bannière non bloquante.

## 12.2 Détection interdite comme seule règle

Ne pas remplacer silencieusement le contenu de l’URL selon `Accept-Language`.

Ne pas rediriger systématiquement Googlebot ou un utilisateur.

---

## 12.3 Première visite de la racine

Flux recommandé :

```text
Accès à /
→ afficher la page internationale
→ suggérer une langue
→ laisser le choix
→ mémoriser la préférence après action
```

---

# 13. Fallbacks de contenu

## 13.1 Interface d’administration

FlatCMS peut utiliser un fallback interne pour une chaîne d’interface manquante.

Exemple :

```text
pt-PT manquant
→ fr-FR ou en-US selon configuration
```

## 13.2 Site public

Pour une page éditoriale, ne pas afficher silencieusement le français sous une URL allemande.

Incorrect :

```text
/de-DE/architektur/hmvc/
→ contenu français
```

Correct :

- ne pas publier l’URL ;
- ou afficher une vraie page d’information locale en `noindex` ;
- ou rediriger temporairement si une décision éditoriale le justifie.

---

## 13.3 Fallback des champs

Une traduction partiellement vide ne doit pas produire un mélange de langues.

Avant publication, valider les champs obligatoires :

```text
title
slug
H1
contenu
meta description
CTA
```

---

# 14. Modèle de données multilingue

## 14.1 Identifiant commun

Les traductions d’un même contenu doivent partager un identifiant logique stable.

Exemple conceptuel :

```json
{
  "translation_group_id": "page-features",
  "locale": "fr-FR",
  "slug": "fonctionnalites"
}
```

Version anglaise :

```json
{
  "translation_group_id": "page-features",
  "locale": "en-US",
  "slug": "features"
}
```

---

## 14.2 Champs recommandés

```text
id
translation_group_id
locale
title
slug
content
excerpt
meta_title
meta_description
canonical_url
publication_status
published_at
updated_at
translation_status
source_locale
source_updated_at
translator
reviewer
```

---

## 14.3 Statuts de traduction

```text
missing
draft
machine_translated
human_review_required
reviewed
approved
published
outdated
```

Affichage administratif conseillé :

```text
À traduire
Brouillon
Traduction automatique
À relire
Relu
Validé
Publié
Obsolète
```

---

# 15. Détection d’obsolescence

Lorsqu’une page source est modifiée :

- enregistrer `source_updated_at` ;
- comparer avec la date de traduction ;
- marquer les traductions concernées comme `outdated` ;
- ne pas les dépublier automatiquement si elles restent correctes ;
- afficher une alerte dans l’administration ;
- organiser la relecture.

Une modification purement typographique ne doit pas forcément invalider toutes les traductions.

---

# 16. Workflow de traduction

```text
1. Valider le contenu source fr-FR
2. Geler la version à traduire
3. Créer le groupe de traduction
4. Produire une première traduction
5. Adapter le vocabulaire local
6. Traduire le title et la meta description
7. Valider le slug
8. Vérifier les liens internes
9. Vérifier les captures et médias
10. Vérifier les dates, nombres et devises
11. Relire techniquement
12. Relire linguistiquement
13. Publier
14. Générer les hreflang
15. Mettre à jour le sitemap
16. Tester
```

---

# 17. Utilisation de l’IA pour traduire

## Autorisée pour

- produire un premier brouillon ;
- harmoniser la terminologie ;
- repérer les chaînes manquantes ;
- comparer les versions ;
- proposer des variantes de title.

## Validation humaine obligatoire pour

- page d’accueil ;
- tarifs ;
- licences ;
- sécurité ;
- architecture ;
- documentation critique ;
- contenus juridiques ;
- pages de téléchargement ;
- messages d’erreur.

## Mention interne

Conserver dans les métadonnées :

```text
translation_method
model
date
reviewer
```

Cette information ne doit pas nécessairement être publique.

---

# 18. Glossaire multilingue

Le projet doit maintenir un glossaire central.

## Exemple initial

| Concept | fr-FR | en-US | de-DE | es-ES | it-IT | pt-PT |
|---|---|---|---|---|---|---|
| Page | Page | Page | Seite | Página | Pagina | Página |
| Article | Article | Post | Beitrag | Artículo | Articolo | Artigo |
| Catégorie | Catégorie | Category | Kategorie | Categoría | Categoria | Categoria |
| Média | Média | Media | Medium | Medio | Media | Multimédia |
| Menu | Menu | Menu | Menü | Menú | Menu | Menu |
| Thème | Thème | Theme | Theme | Tema | Tema | Tema |
| Module | Module | Module | Modul | Módulo | Modulo | Módulo |
| Sauvegarde | Sauvegarde | Backup | Sicherung | Copia de seguridad | Backup | Cópia de segurança |
| Corbeille | Corbeille | Trash | Papierkorb | Papelera | Cestino | Lixo |
| Paramètres | Paramètres | Settings | Einstellungen | Ajustes | Impostazioni | Definições |

Ce tableau doit être relu avant utilisation définitive.

---

# 19. Noms non traduits

Conserver :

```text
FlatCMS
FlatCMS LTS Core
PagesBuilder
MenuBuilder
FooterBuilder
PHP
HMVC
PSR-4
JSON
Schema.org
OpenAI
Nginx
Apache
MAMP
WAMP
```

Les descriptions de ces termes sont traduites.

---

# 20. Titles et meta descriptions

Chaque locale possède ses propres métadonnées.

## Exemple

### fr-FR

```text
FlatCMS — CMS PHP open source sans base de données
```

### en-US

```text
FlatCMS — Open-source PHP CMS without a SQL database
```

### de-DE

```text
FlatCMS — Open-Source-PHP-CMS ohne SQL-Datenbank
```

Les traductions doivent être naturelles et adaptées aux requêtes locales.

Ne pas copier la même suite de mots-clés dans toutes les langues.

---

# 21. Mots-clés par locale

Les termes ne doivent pas être traduits littéralement sans recherche.

Exemple :

```text
CMS sans base de données
```

peut correspondre selon le marché à :

```text
database-free CMS
flat-file CMS
CMS ohne Datenbank
CMS sin base de datos
CMS senza database
CMS sem base de dados
```

La page cible peut employer plusieurs variantes naturelles.

Les données Search Console devront guider les ajustements.

---

# 22. Dates

## fr-FR

```text
8 juin 2026
```

## en-US

```text
June 8, 2026
```

## de-DE

```text
8. Juni 2026
```

## es-ES

```text
8 de junio de 2026
```

## it-IT

```text
8 giugno 2026
```

## pt-PT

```text
8 de junho de 2026
```

## Métadonnées

Toujours ISO 8601 :

```text
2026-06-08T14:30:00+02:00
```

---

# 23. Nombres et unités

Le formatage doit utiliser les conventions de la locale.

Exemples conceptuels :

| Locale | Décimal | Millier |
|---|---|---|
| fr-FR | `2,5` | `1 000` |
| en-US | `2.5` | `1,000` |
| de-DE | `2,5` | `1.000` |
| es-ES | `2,5` | `1.000` |
| it-IT | `2,5` | `1.000` |
| pt-PT | `2,5` | `1 000` |

Utiliser une bibliothèque d’internationalisation plutôt que des remplacements manuels.

---

# 24. Devises et tarifs

Les prix officiels sont définis en euros.

## Affichage

```text
29,90 € HT
```

selon les conventions françaises.

Pour les autres locales :

- adapter le format ;
- conserver la devise EUR si aucun tarif local n’existe ;
- traduire l’indication fiscale ;
- ne pas convertir automatiquement sans politique commerciale ;
- indiquer la date du taux si une conversion est proposée.

---

# 25. Liens internes

## Règle

Une page française pointe par défaut vers les pages françaises équivalentes.

Éviter :

```text
/fr-FR/architecture/
→ /en-US/documentation/
```

sauf lien explicitement présenté comme ressource anglaise.

## Traduction absente

Le lien peut pointer vers la source française avec :

```html
hreflang="fr-FR"
```

et un libellé explicite :

```text
Documentation disponible en français
```

---

# 26. Médias

## 26.1 Images sans texte

Une même image peut être réutilisée entre les locales.

Traduire :

- texte alternatif ;
- légende ;
- titre ;
- description.

## 26.2 Images avec texte

Créer une variante par locale.

Exemple :

```text
architecture-hmvc-fr-FR.webp
architecture-hmvc-en-US.webp
```

## 26.3 Captures d’interface

Utiliser une interface dans la même langue que la page lorsque possible.

Si la capture reste française :

- le signaler ;
- éviter de présenter un libellé intraduisible ;
- fournir une explication locale.

---

# 27. Vidéos

Pour chaque locale :

- titre traduit ;
- description traduite ;
- sous-titres ;
- transcription ;
- vignette traduite si elle contient du texte ;
- `inLanguage` correct.

Une seule vidéo peut servir plusieurs locales si elle propose des pistes de sous-titres adaptées.

---

# 28. Données structurées

## Entités globales

Réutiliser :

```text
https://flat-cms.fr/#organization
https://flat-cms.fr/#website
https://flat-cms.fr/#software
```

## Pages locales

Chaque page possède son propre `@id`.

```text
https://flat-cms.fr/fr-FR/architecture/#webpage
https://flat-cms.fr/en-US/architecture/#webpage
```

## `inLanguage`

Utiliser la locale exacte.

```json
"inLanguage": "fr-FR"
```

## Contenus traduits

Chaque traduction possède :

- son propre article ;
- son propre H1 ;
- ses dates ;
- sa canonique ;
- sa langue.

Schema.org ne remplace pas `hreflang`.

---

# 29. Sitemaps multilingues

## Structure proposée

```text
/sitemap.xml
/sitemaps/sitemap-fr-FR.xml
/sitemaps/sitemap-en-US.xml
/sitemaps/sitemap-de-DE.xml
/sitemaps/sitemap-es-ES.xml
/sitemaps/sitemap-it-IT.xml
/sitemaps/sitemap-pt-PT.xml
```

## Règles

Chaque sitemap contient :

- URLs finales ;
- canoniques ;
- indexables ;
- en `200 OK` ;
- de sa locale.

## Alternates

Les liens `xhtml:link` peuvent être générés dans les sitemaps si FlatCMS garantit :

- la réciprocité ;
- la synchronisation ;
- la suppression des traductions inexistantes.

Sinon, conserver les `hreflang` dans le HTML uniquement.

---

# 30. Pages non traduites

## Ne pas publier

```text
URL locale vide
contenu source copié
page avec uniquement le menu traduit
```

## Options

1. ne pas créer l’URL ;
2. publier une page locale d’information en `noindex` ;
3. conserver le lien vers la source ;
4. planifier la traduction.

---

# 31. Pages partiellement équivalentes

Deux pages reliées par `hreflang` doivent représenter le même sujet principal.

Des adaptations locales sont autorisées :

- exemples ;
- captures ;
- prix ;
- législation ;
- liens ;
- expressions.

Ne pas relier :

```text
guide complet français
→ courte page commerciale anglaise
```

---

# 32. Catégories et archives

Une catégorie n’est publiée dans une locale que si elle possède :

- un titre traduit ;
- une description ;
- des contenus locaux ;
- une valeur éditoriale.

Éviter les archives vides.

---

# 33. Recherche interne

La recherche doit être locale.

Exemple :

```text
/fr-FR/recherche/?q=module
```

recherche d’abord les contenus français.

Options :

- filtrer par langue ;
- proposer les résultats dans une autre langue ;
- indiquer clairement la langue ;
- ne pas indexer les pages de résultats.

---

# 34. Formulaires

Traduire :

- labels ;
- aides ;
- erreurs ;
- confirmations ;
- e-mails transactionnels ;
- mentions de confidentialité.

Les données envoyées doivent conserver la locale pour générer la réponse adaptée.

---

# 35. E-mails

Chaque modèle doit posséder :

- sujet localisé ;
- corps localisé ;
- version texte ;
- liens vers la bonne locale ;
- dates et nombres localisés ;
- fallback contrôlé.

---

# 36. Erreurs et pages système

Traduire :

```text
404
403
500
maintenance
connexion
mot de passe oublié
confirmation
```

Une erreur peut contenir le message technique original, accompagné d’une explication locale.

---

# 37. Migration du wiki

## Inventaire

Chaque ancienne page doit enregistrer :

```text
ancienne URL
locale
groupe de traduction
nouvelle URL
statut
```

## Redirections

Conserver la locale :

```text
wiki fr-FR
→ nouveau fr-FR

wiki de-DE
→ nouveau de-DE
```

## Page absente

Ne pas rediriger une ancienne traduction vers une autre langue sans justification.

---

# 38. Tests automatiques

Pour chaque groupe de traduction :

- [ ] toutes les URLs répondent `200` ;
- [ ] toutes les canonicals sont auto-référencées ;
- [ ] tous les `hreflang` sont absolus ;
- [ ] chaque page s’auto-référence ;
- [ ] les liens sont réciproques ;
- [ ] aucune URL ne redirige ;
- [ ] chaque locale est valide ;
- [ ] le HTML `lang` est correct ;
- [ ] les titles sont traduits ;
- [ ] les H1 sont traduits ;
- [ ] les données structurées utilisent la bonne langue ;
- [ ] les liens internes restent dans la locale ;
- [ ] aucune traduction inexistante n’est déclarée.

---

# 39. Tests éditoriaux

- [ ] contenu naturel ;
- [ ] terminologie cohérente ;
- [ ] aucune chaîne source résiduelle ;
- [ ] CTA localisés ;
- [ ] erreurs localisées ;
- [ ] captures adaptées ;
- [ ] textes alternatifs traduits ;
- [ ] format des dates correct ;
- [ ] format des nombres correct ;
- [ ] contexte culturel respecté ;
- [ ] relecture humaine effectuée.

---

# 40. Administration FlatCMS

## Tableau des traductions

Afficher :

| Contenu | fr-FR | en-US | de-DE | es-ES | it-IT | pt-PT |
|---|---|---|---|---|---|---|
| Fonctionnalités | Publié | Relu | À relire | Manquant | Publié | Obsolète |

## Actions

- créer traduction ;
- dupliquer comme brouillon ;
- traduire avec IA ;
- comparer les versions ;
- marquer comme relue ;
- publier ;
- dépublier ;
- synchroniser les métadonnées communes.

---

# 41. Permissions

Définir les rôles capables de :

- créer une traduction ;
- utiliser l’IA ;
- relire ;
- valider ;
- publier ;
- modifier un slug ;
- supprimer une traduction.

Une suppression doit prévenir des conséquences :

- `hreflang` ;
- sitemap ;
- liens internes ;
- redirections.

---

# 42. Performance

Ne pas charger les six contenus dans une seule page.

Chaque URL sert uniquement la locale demandée.

Le sélecteur de langue contient des liens, sans précharger inutilement toutes les traductions.

---

# 43. Analytics

Conserver la locale dans les dimensions :

```text
locale
langue du navigateur
pays
page
conversion
```

Mesurer :

- trafic par locale ;
- pages non traduites demandées ;
- changement de langue ;
- conversion ;
- erreurs ;
- recherche interne ;
- performance SEO.

---

# 44. Search Console

Utiliser une propriété domaine :

```text
flat-cms.fr
```

Puis analyser les performances par filtre de page :

```text
/fr-FR/
/en-US/
/de-DE/
/es-ES/
/it-IT/
/pt-PT/
```

Surveiller :

- indexation ;
- pages choisies par Google ;
- requêtes locales ;
- CTR ;
- canonicals ;
- erreurs de redirection ;
- pages exclues.

---

# 45. Priorité de traduction

## Phase 1

```text
fr-FR
```

Source complète et validée.

## Phase 2

```text
en-US
```

Visibilité internationale et documentation technique.

## Phase 3

```text
de-DE
es-ES
it-IT
pt-PT
```

Selon les ressources et les données de demande.

## Pages P0 dans chaque locale

1. Accueil
2. Fonctionnalités
3. Architecture
4. Documentation
5. Installation
6. Téléchargement
7. Licences
8. Agent-ready
9. Contact
10. Mentions légales adaptées

---

# 46. Critères de lancement d’une locale

Une locale peut être annoncée comme disponible lorsque :

- [ ] accueil complet ;
- [ ] navigation complète ;
- [ ] footer complet ;
- [ ] pages P0 traduites ;
- [ ] documentation de démarrage disponible ;
- [ ] erreurs système traduites ;
- [ ] sélecteur fonctionnel ;
- [ ] canonicals correctes ;
- [ ] `hreflang` réciproques ;
- [ ] sitemap disponible ;
- [ ] données structurées localisées ;
- [ ] formulaires localisés ;
- [ ] relecture réalisée.

---

# 47. Règles de gouvernance

## Source éditoriale

```text
fr-FR
```

sauf contenu créé directement pour un autre marché.

## Modification

Une modification majeure de la source doit déclencher :

- notification des traducteurs ;
- marquage `outdated` ;
- comparaison ;
- mise à jour planifiée.

## Glossaire

Toute nouvelle terminologie officielle doit être ajoutée au glossaire avant traduction massive.

---

# 48. Décisions à valider

- [x] six locales initiales ;
- [x] répertoire de locale en première position ;
- [x] slugs localisés ;
- [x] canonique locale ;
- [x] `hreflang` HTML ;
- [x] sélecteur avec noms de langue ;
- [x] aucune redirection automatique forcée ;
- [x] source éditoriale fr-FR ;
- [ ] racine internationale `x-default` définitivement validée ;
- [ ] règle `x-default` des pages internes ;
- [ ] fallback administratif exact ;
- [ ] glossaire relu ;
- [ ] slugs traduits validés ;
- [ ] workflow IA intégré à l’administration.

---

# 49. Références officielles

- Google Search Central — Managing multi-regional and multilingual sites  
  https://developers.google.com/search/docs/specialty/international/managing-multi-regional-sites

- Google Search Central — Localized versions and `hreflang`  
  https://developers.google.com/search/docs/specialty/international/localized-versions

- W3C Internationalization — Declaring language in HTML  
  https://www.w3.org/International/questions/qa-html-language-declarations

- RFC 5646 — Tags for Identifying Languages  
  https://www.rfc-editor.org/rfc/rfc5646

- IANA Language Subtag Registry  
  https://www.iana.org/assignments/language-subtag-registry/

---

# 50. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Création de l’architecture multilingue initiale | ChatGPT / Alain BROYE |

---

# 51. Prochaine action

Créer :

```text
LAUNCH_CHECKLIST.md
```

Ce document regroupera :

- la validation technique ;
- les contenus ;
- le SEO ;
- les données structurées ;
- le crawl ;
- les traductions ;
- les redirections ;
- la sécurité ;
- la performance ;
- les sauvegardes ;
- le déploiement ;
- les contrôles après mise en ligne.
