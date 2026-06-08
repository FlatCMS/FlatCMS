# CONTENT_STYLE_GUIDE — Guide éditorial du futur site officiel FlatCMS

> **Document directeur éditorial**
>
> Projet : FlatCMS  
> Domaine cible : `https://flat-cms.fr`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Documents parents : `SEO.md`, `SITE_ARCHITECTURE.md`, `CONTENT_MATRIX.md`, `DOCUMENTATION_MAP.md`, `KEYWORDS.md`, `REDIRECTS.md`, `STRUCTURED_DATA.md`, `CRAWL_POLICY.md`  
> Statut : version initiale applicable aux contenus français avant adaptation aux cinq autres locales

---

## 1. Objet du document

Ce guide définit la manière dont FlatCMS doit s’exprimer sur son futur site officiel.

Il couvre :

- la voix de marque ;
- le ton selon le type de page ;
- les termes officiels ;
- les titres et sous-titres ;
- la sémantique HTML ;
- les paragraphes et listes ;
- les preuves et sources ;
- les contenus SEO, GEO et GIO ;
- les appels à l’action ;
- les exemples techniques ;
- les blocs de code ;
- les tableaux ;
- les images et textes alternatifs ;
- les dates, versions et nombres ;
- l’utilisation de l’intelligence artificielle ;
- les traductions dans les six locales ;
- la validation éditoriale.

Ce guide s’applique :

```text
aux pages produit
à la documentation
au blog
aux comparatifs
aux pages commerciales
aux tutoriels
aux interfaces publiques
aux métadonnées SEO
aux scripts vidéo et contenus associés
```

---

# 2. Identité éditoriale de FlatCMS

## 2.1 Promesse centrale

```text
FlatCMS simplifie la création et l’administration de sites web grâce à
un cœur PHP natif, une architecture HMVC, l’autoloading PSR-4 et un
stockage JSON sans dépendance à un serveur SQL.
```

Cette formulation doit être ajustée au contexte sans être déformée.

---

## 2.2 Valeurs éditoriales

### Simplicité

Expliquer avec des mots compréhensibles.

La simplicité ne signifie pas masquer les limites ou supprimer les détails utiles.

### Rigueur

Chaque affirmation technique doit pouvoir être vérifiée.

### Transparence

Distinguer clairement :

- ce qui existe ;
- ce qui est optionnel ;
- ce qui est premium ;
- ce qui est expérimental ;
- ce qui est prévu.

### Autonomie

Le lecteur doit pouvoir accomplir son objectif sans dépendre d’une information cachée.

### Respect

Ne pas dénigrer les concurrents, les utilisateurs débutants ou les choix techniques différents.

### Sobriété

Éviter le marketing excessif, les promesses vagues et les artifices de langage.

---

# 3. Voix de marque

## 3.1 Caractéristiques

La voix FlatCMS est :

```text
professionnelle
claire
directe
calme
technique lorsque nécessaire
pédagogique
honnête
accessible
```

Elle n’est pas :

```text
arrogante
agressive
hyperbolique
opaque
corporate artificielle
infantilisante
familière à l’excès
```

---

## 3.2 Personne grammaticale

### Pages produit

Utiliser principalement :

```text
vous
```

Exemple :

```text
Créez vos pages, articles et menus depuis une administration modulaire.
```

### Documentation

Utiliser l’impératif ou une formulation directe :

```text
Ouvrez le fichier.
Vérifiez la version de PHP.
Configurez le document root sur public/.
```

### Architecture

Utiliser une formulation descriptive :

```text
Le routeur associe la requête à un contrôleur.
```

### Projet et auteur

Utiliser :

```text
FlatCMS
le projet
nous
```

Le « nous » doit représenter clairement le projet ou son équipe, jamais une entité indéfinie.

---

# 4. Ton selon le type de contenu

## 4.1 Accueil et pages produit

Ton :

```text
confiant
sobre
orienté bénéfices
factuel
```

Exemple correct :

```text
FlatCMS supprime la dépendance à un serveur SQL et stocke ses contenus
dans des fichiers JSON structurés.
```

Exemple à éviter :

```text
FlatCMS révolutionne totalement le Web avec une technologie imbattable.
```

---

## 4.2 Documentation

Ton :

```text
précis
actionnable
sans ambiguïté
```

Exemple correct :

```text
Configurez la racine publique du serveur sur le dossier public/.
```

Exemple à éviter :

```text
Il serait probablement préférable de faire pointer le serveur vers le bon dossier.
```

---

## 4.3 Blog

Ton :

```text
analytique
argumenté
ouvert
```

Le blog peut être plus narratif, mais doit rester fidèle aux faits.

---

## 4.4 Comparatifs

Ton :

```text
neutre
méthodique
équitable
daté
```

Exemple correct :

```text
WordPress bénéficie d’un écosystème beaucoup plus vaste. FlatCMS vise
un périmètre plus léger, sans serveur SQL, adapté à d’autres besoins.
```

Exemple à éviter :

```text
WordPress est dépassé et FlatCMS est meilleur dans tous les domaines.
```

---

## 4.5 Pages commerciales

Ton :

```text
transparent
précis
sans pression artificielle
```

Toujours indiquer :

- le prix ;
- HT ou TTC ;
- la durée ;
- le nombre de sites ;
- les mises à jour ;
- le support ;
- les limites.

---

# 5. Terminologie officielle

## 5.1 Nom du produit

Écrire :

```text
FlatCMS
```

Ne pas écrire, sauf citation ou besoin exceptionnel :

```text
Flat CMS
Flat Cms
flatcms
FLAT CMS
```

### Formes autorisées

```text
FlatCMS v1.0.0
FlatCMS LTS Core
le CMS FlatCMS
le projet FlatCMS
```

---

## 5.2 Architecture

Termes préférés :

```text
PHP natif
architecture HMVC
autoloading PSR-4
stockage JSON
CMS flat-file
sans base de données SQL
module
service
hook
thème frontend
thème d’administration
document root public
```

### Prudence sur « sans base de données »

Formulation recommandée :

```text
sans serveur de base de données SQL
```

ou :

```text
sans dépendance à MySQL ou MariaDB
```

Le terme « base de données JSON » doit être utilisé avec prudence.

---

## 5.3 Intelligence artificielle

Termes :

```text
agent-ready
AI-ready
agent IA
service IA
fournisseur IA
génération assistée
```

Ne pas écrire comme fait établi :

```text
intelligence autonome
IA infaillible
génération sans contrôle
```

---

## 5.4 SEO, GEO et GIO

Définitions du projet :

```text
SEO — optimisation pour les moteurs de recherche
GEO — optimisation pour la visibilité dans les moteurs génératifs
GIO — organisation du contenu pour son indexation et sa réutilisation
      par les systèmes génératifs
```

Préciser que GEO et GIO ne constituent pas des certifications universelles.

---

## 5.5 Builders

Noms officiels :

```text
PagesBuilder
MenuBuilder
FooterBuilder
```

Conserver la casse officielle.

Au premier emploi :

```text
PagesBuilder, le builder visuel de pages de FlatCMS
```

---

# 6. Règles de clarté

## 6.1 Commencer par la réponse

Une page ou section doit répondre rapidement à sa question principale.

Exemple :

```text
FlatCMS stocke ses pages et articles dans des fichiers JSON organisés
dans le dossier data/. Aucun serveur MySQL n’est requis.
```

Puis développer :

- fonctionnement ;
- avantages ;
- limites ;
- exemples.

---

## 6.2 Une idée principale par paragraphe

Préférer des paragraphes courts.

Longueur indicative :

```text
2 à 5 phrases
```

Une phrase ou un paragraphe plus long reste acceptable lorsque la compréhension l’exige.

---

## 6.3 Phrases directes

Préférer :

```text
FlatCMS valide le fichier avant l’écriture.
```

Éviter :

```text
Une validation du fichier est effectuée par FlatCMS avant que l’écriture
puisse éventuellement être réalisée.
```

---

## 6.4 Voix active

Préférer :

```text
Le routeur charge le contrôleur.
```

La voix passive reste acceptable lorsque l’acteur importe peu :

```text
Le fichier est supprimé après validation.
```

---

## 6.5 Jargon

Définir un terme spécialisé lors de son premier emploi.

Exemple :

```text
HMVC, ou Hierarchical Model–View–Controller, organise les fonctionnalités
en modules autonomes.
```

Ne pas redéfinir systématiquement les termes sur chaque page.

---

## 6.6 Abréviations

Premier emploi :

```text
Search Engine Optimization (SEO)
```

ou en français :

```text
optimisation pour les moteurs de recherche (SEO)
```

Ensuite :

```text
SEO
```

---

# 7. Titres SEO

## 7.1 Balise `<title>`

Chaque page possède un titre :

- unique ;
- descriptif ;
- concis ;
- fidèle au contenu ;
- distinct des autres pages.

Exemple :

```html
<title>Installer FlatCMS sur Nginx | Documentation</title>
```

Éviter :

```html
<title>Documentation</title>
```

Éviter le bourrage :

```html
<title>FlatCMS CMS PHP CMS JSON CMS rapide CMS gratuit</title>
```

---

## 7.2 Marque dans le title

Accueil :

```text
FlatCMS — CMS PHP open source sans base de données
```

Pages internes :

```text
Installer FlatCMS sur Nginx | FlatCMS
```

ou :

```text
Architecture HMVC de FlatCMS
```

Ne pas répéter inutilement la marque deux fois.

---

## 7.3 Longueur

Il n’existe pas de longueur absolue imposée.

La règle FlatCMS :

```text
être complet sans devenir verbeux
```

Le titre doit rester compréhensible même lorsqu’il est tronqué.

---

# 8. Hiérarchie H1, H2, H3

## 8.1 H1

Une page comporte un H1 principal visible.

Le H1 décrit le sujet principal.

```html
<h1>Installer FlatCMS sur Nginx</h1>
```

Le logo ou le nom du site dans le header ne doit pas remplacer le H1 du contenu.

---

## 8.2 H2

Les H2 structurent les grandes sections.

```html
<h2>Prérequis</h2>
<h2>Configuration Nginx</h2>
<h2>Vérification</h2>
```

---

## 8.3 H3

Les H3 structurent une section H2.

```html
<h2>Configuration Nginx</h2>
<h3>Définir le document root</h3>
<h3>Configurer PHP-FPM</h3>
```

---

## 8.4 Ne pas sauter les niveaux

Éviter :

```text
H2
→ H4
```

sauf fermeture logique d’une sous-section précédente.

La taille visuelle doit être gérée en CSS, pas en choisissant un niveau sémantique incorrect.

---

## 8.5 Titres descriptifs

Correct :

```text
Configurer les permissions du dossier data/
```

Trop vague :

```text
Configuration
```

Excessif :

```text
La configuration absolument indispensable que vous devez effectuer
maintenant sous peine de tout casser
```

---

# 9. Structure des contenus GEO/GIO

## 9.1 Blocs recommandés

Selon le type de page :

```text
En bref
Définition
Prérequis
Fonctionnement
Procédure
Exemple
Résultat attendu
Vérification
Limites
Erreurs fréquentes
Sources
```

Ces blocs doivent être utilisés parce qu’ils aident le lecteur, pas pour produire une structure artificielle identique partout.

---

## 9.2 Définitions autonomes

Une définition doit pouvoir être comprise hors contexte.

Correct :

```text
FlatCMS est un CMS flat-file en PHP natif qui stocke ses contenus dans
des fichiers JSON au lieu de dépendre d’un serveur SQL.
```

Moins utile :

```text
Il s’agit donc d’une solution de ce type.
```

---

## 9.3 Réponses directes

Pour une question :

```text
FlatCMS nécessite-t-il MySQL ?
```

Réponse :

```text
Non. FlatCMS n’a pas besoin de MySQL pour stocker ses pages, articles
et configurations principales.
```

Puis détailler les limites et cas particuliers.

---

## 9.4 Tableaux

Utiliser un tableau lorsque l’utilisateur compare des attributs structurés.

Ne pas utiliser un tableau pour des paragraphes longs ou une narration.

---

## 9.5 Citabilité

Une phrase forte doit :

- contenir son sujet ;
- éviter les pronoms ambigus ;
- indiquer les conditions ;
- rester factuelle.

Exemple :

```text
Dans FlatCMS v1.0.0, le dossier public/ constitue le document root
recommandé pour empêcher l’accès direct à app/, config/ et data/.
```

---

# 10. Preuves, sources et confiance

## 10.1 Hiérarchie des sources

Ordre de préférence :

1. code FlatCMS ;
2. documentation officielle FlatCMS ;
3. norme ou documentation primaire ;
4. documentation officielle d’un produit tiers ;
5. étude ou publication scientifique ;
6. source secondaire reconnue ;
7. témoignage clairement identifié.

---

## 10.2 Affirmations internes

Exemple :

```text
FlatCMS utilise une architecture HMVC.
```

Preuve :

```text
arborescence app/Modules
README
code du runtime
```

---

## 10.3 Affirmations externes

Exemple :

```text
PSR-4 définit une correspondance entre namespaces et chemins de fichiers.
```

Citer la PHP-FIG.

---

## 10.4 Performances

Toujours préciser :

- version de FlatCMS ;
- matériel ;
- système ;
- serveur ;
- version PHP ;
- cache ;
- nombre de requêtes ;
- outil ;
- date ;
- résultat brut.

Ne jamais extrapoler un benchmark local à tous les environnements.

---

## 10.5 Dates

Ne pas modifier une date uniquement pour donner une impression de fraîcheur.

Mettre à jour `dateModified` lorsqu’un changement substantiel est effectué.

---

## 10.6 Incertitude

Formulations autorisées :

```text
À ce stade
Selon les tests réalisés
Cette fonctionnalité est prévue
Ce comportement doit encore être validé
Les résultats peuvent varier selon l’hébergement
```

Ne pas masquer une incertitude derrière une affirmation catégorique.

---

# 11. Superlatifs et promesses

## Interdits sans preuve

```text
le plus rapide au monde
le meilleur CMS
100 % sécurisé
sans aucune limite
zéro risque
garanti premier sur Google
garanti cité par ChatGPT
révolutionnaire
imbattable
```

## Formulations recommandées

```text
léger
conçu pour limiter la complexité
sans dépendance à un serveur SQL
mesuré dans un environnement documenté
adapté à
pensé pour
```

---

# 12. Appels à l’action

## 12.1 CTA principaux

```text
Télécharger FlatCMS
Tester la démo
Consulter la documentation
Découvrir l’architecture
Voir les tarifs
```

## 12.2 Verbes explicites

Correct :

```text
Télécharger FlatCMS v1.0.0
```

Moins bon :

```text
Commencer
```

sauf si le contexte est évident.

---

## 12.3 Pas de pression artificielle

Éviter :

```text
Achetez maintenant avant qu’il ne soit trop tard
Ne manquez surtout pas cette occasion
```

---

## 12.4 CTA et destination

Le texte doit décrire exactement la destination.

```text
Consulter le guide d’installation Nginx
```

plutôt que :

```text
En savoir plus
```

---

# 13. Liens

## 13.1 Ancres descriptives

Correct :

```html
<a href="/fr-FR/architecture/stockage-json/">
  comprendre le stockage JSON de FlatCMS
</a>
```

À éviter :

```html
<a href="...">cliquez ici</a>
```

---

## 13.2 Liens externes

Indiquer le nom de la source dans le texte.

Exemple :

```text
Consultez la spécification PSR-4 publiée par la PHP-FIG.
```

Ne pas ajouter automatiquement `nofollow` aux sources éditoriales légitimes.

---

## 13.3 Nouvelle fenêtre

Ne pas forcer systématiquement `target="_blank"`.

Lorsqu’il est utilisé :

```html
rel="noopener noreferrer"
```

et informer l’utilisateur si nécessaire.

---

# 14. Listes

## Utiliser une liste pour

- étapes ;
- prérequis ;
- choix ;
- fonctionnalités ;
- critères ;
- contrôles.

## Éviter

- listes contenant des paragraphes sans rapport ;
- dizaines de puces sans regroupement ;
- ponctuation incohérente.

## Ponctuation

Éléments courts :

```text
- PHP 8.3 ou version validée ;
- serveur Apache ou Nginx ;
- accès en écriture au dossier data/.
```

Étapes complètes :

```text
1. Téléchargez l’archive.
2. Extrayez les fichiers.
3. Configurez le serveur.
```

---

# 15. Code et commandes

## 15.1 Code inline

Utiliser pour :

```text
public/
data/
index.php
App\Modules\Pages
```

---

## 15.2 Bloc de code

Toujours indiquer le langage :

```php
<?php

declare(strict_types=1);
```

```nginx
server {
    root /var/www/flatcms/public;
}
```

---

## 15.3 Code complet

Lorsqu’un fichier complet est annoncé, fournir réellement :

- imports ;
- namespace ;
- classe ;
- gestion d’erreur ;
- fermeture correcte ;
- contexte d’emplacement.

---

## 15.4 Variables

Ne jamais publier :

```text
vraie clé API
mot de passe
jeton
adresse privée
secret de production
```

Utiliser :

```env
OPENAI_API_KEY=your_api_key_here
```

---

## 15.5 Commandes

Préciser l’environnement :

```text
macOS / Linux
Windows PowerShell
Windows CMD
Synology DSM
```

Éviter de présenter une commande Unix comme universelle.

---

## 15.6 Résultat attendu

Après une commande, indiquer ce que l’utilisateur doit observer.

Exemple :

```text
La commande doit afficher la version active de PHP.
```

---

# 16. Messages d’erreur

Conserver le message exact :

```text
Unable to write public/.htaccess.
```

Puis traduire ou expliquer :

```text
FlatCMS ne peut pas écrire le fichier public/.htaccess.
```

Structure :

```text
Message
Cause probable
Diagnostic
Correction
Vérification
```

---

# 17. Tableaux

## Règles

- en-têtes explicites ;
- une donnée par cellule ;
- alignement cohérent ;
- éviter les cellules gigantesques ;
- ajouter une introduction ;
- rendre les tableaux utilisables sur mobile.

## Accessibilité

Utiliser de vrais éléments :

```html
<table>
<thead>
<th>
<tbody>
```

Ne pas simuler un tableau avec des espaces ou des images.

---

# 18. Images et textes alternatifs

## 18.1 Image informative

Le texte alternatif exprime l’information utile dans son contexte.

```html
alt="Arborescence de FlatCMS avec les dossiers app, config, data et public"
```

---

## 18.2 Image décorative

```html
alt=""
```

Ne pas répéter un texte adjacent déjà suffisant.

---

## 18.3 Image fonctionnelle

Décrire l’action ou la destination.

```html
alt="Télécharger FlatCMS"
```

pour une image utilisée seule comme lien de téléchargement.

---

## 18.4 Image contenant du texte

Lorsque le texte n’existe pas ailleurs, le reproduire dans l’alternative ou dans le contenu adjacent.

Éviter les images de texte lorsque du HTML réel convient.

---

## 18.5 Schémas complexes

Ajouter :

- un texte alternatif bref ;
- une légende ;
- une description complète dans le contenu.

---

## 18.6 Noms de fichiers

Correct :

```text
architecture-hmvc-flatcms.webp
installation-flatcms-nginx.webp
```

À éviter :

```text
IMG_4837.webp
image-final-v2-bis.webp
```

---

# 19. Vidéo et audio

Chaque vidéo importante doit disposer de :

- titre ;
- description ;
- vignette ;
- transcription ;
- sous-titres ;
- date ;
- durée ;
- liens vers la documentation.

Le texte essentiel ne doit pas être disponible uniquement dans la vidéo.

---

# 20. Métadonnées SEO

## 20.1 Meta description

Elle doit :

- résumer la page ;
- rester spécifique ;
- expliquer le bénéfice ;
- éviter les listes de mots-clés ;
- ne pas promettre davantage que le contenu.

Exemple :

```html
<meta name="description"
      content="Installez FlatCMS sur Nginx avec PHP-FPM, un document root
      pointant vers public/ et les permissions recommandées.">
```

---

## 20.2 Open Graph

Le titre social peut être légèrement plus éditorial que le title SEO, sans devenir trompeur.

L’image doit :

- correspondre à la page ;
- respecter la marque ;
- contenir peu de texte ;
- être traduite si elle contient du texte.

---

# 21. Nombres, unités et devises

## Français

```text
29,90 €
1 000 fichiers
2,5 secondes
8 Mo
PHP 8.3
```

Utiliser une espace insécable lorsque le système le permet.

## Prix

Toujours préciser :

```text
29,90 € HT
35,88 € TTC
```

selon le contexte et les obligations applicables.

---

# 22. Dates et heures

## Texte français

```text
8 juin 2026
```

Éviter :

```text
08/06/26
```

lorsque l’ambiguïté est possible.

## Métadonnées

ISO 8601 :

```text
2026-06-08T14:30:00+02:00
```

## Fuseau

Préciser lorsque nécessaire :

```text
heure de Paris
Europe/Paris
UTC
```

---

# 23. Casse et ponctuation

## Titres français

Utiliser la casse de phrase :

```text
Installer FlatCMS sur Nginx
```

Éviter :

```text
Installer FlatCMS Sur Nginx
```

## Deux-points

En français :

```text
FlatCMS : un CMS sans base de données
```

Respecter les espaces typographiques selon les capacités de l’éditeur.

## Points de suspension

Utiliser :

```text
…
```

avec modération.

---

# 24. Emphase

## Gras

Utiliser pour :

- notion importante ;
- avertissement court ;
- libellé.

Ne pas mettre des paragraphes entiers en gras.

## Italique

Utiliser pour :

- terme étranger ;
- titre d’œuvre ;
- nuance ponctuelle.

## Majuscules

Éviter les phrases entièrement en majuscules.

---

# 25. Avertissements et notes

Types recommandés :

```text
Information
Conseil
Attention
Important
Danger
```

## Exemple

```text
> **Attention**
> Le dossier data/ ne doit pas être accessible directement depuis le Web.
```

Réserver « Danger » aux conséquences réellement graves :

- perte de données ;
- sécurité ;
- interruption de service.

---

# 26. Auteurs et transparence

Les contenus éditoriaux doivent indiquer lorsque pertinent :

- auteur ;
- relecteur ;
- date de publication ;
- date de mise à jour ;
- version testée ;
- méthode de test.

Une page auteur doit fournir un contexte réel sur l’expertise.

---

# 27. Contenu assisté par IA

## 27.1 Principe

L’IA peut assister :

- le plan ;
- la reformulation ;
- la traduction ;
- la génération d’exemples ;
- la détection d’incohérences.

Elle ne remplace pas :

- la validation du code ;
- les tests ;
- la décision juridique ;
- l’expertise ;
- la relecture humaine.

---

## 27.2 Divulgation

Une mention est recommandée lorsque l’automatisation joue un rôle substantiel et que le lecteur peut raisonnablement se demander comment le contenu a été produit.

Exemple :

```text
Ce guide a été préparé avec une assistance IA, puis vérifié et testé
manuellement sur FlatCMS v1.0.0.
```

Une mention systématique sur chaque microcorrection n’est pas nécessaire.

---

## 27.3 Interdits

- publier sans vérification ;
- inventer une fonctionnalité ;
- inventer une source ;
- inventer un benchmark ;
- traduire sans relecture ;
- changer les dates pour simuler la fraîcheur ;
- produire massivement des pages redondantes.

---

# 28. Traduction et localisation

## 28.1 Langues

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

## 28.2 Traduire le sens

Ne pas reproduire mécaniquement l’ordre des mots français.

Chaque locale doit :

- employer un vocabulaire naturel ;
- adapter les exemples ;
- respecter la ponctuation ;
- conserver les noms de produit ;
- utiliser les termes techniques reconnus localement.

---

## 28.3 Éléments à ne pas traduire

Sauf convention officielle :

```text
FlatCMS
PagesBuilder
MenuBuilder
FooterBuilder
PHP
HMVC
PSR-4
JSON
OpenAI
Nginx
Apache
```

---

## 28.4 Code

Ne pas traduire :

- namespaces ;
- classes ;
- clés JSON ;
- noms de fichiers ;
- commandes ;
- variables.

Traduire :

- commentaires si le fichier l’autorise ;
- explications ;
- libellés d’interface selon le projet.

---

## 28.5 Slugs

Les slugs peuvent être localisés après validation.

Une URL publiée ne doit pas être changée pour une simple préférence stylistique.

---

## 28.6 Relecture

Chaque traduction doit être relue pour :

- sens ;
- termes techniques ;
- ton ;
- ponctuation ;
- liens ;
- dates ;
- devises ;
- captures ;
- textes alternatifs.

---

# 29. Conventions par locale

## fr-FR

- vouvoiement ;
- casse de phrase ;
- espace avant `:`, `;`, `?`, `!` ;
- virgule décimale.

## en-US

- anglais américain ;
- sentence case pour les titres, sauf convention UI ;
- point décimal ;
- dates non ambiguës en toutes lettres.

## de-DE

- noms communs avec majuscule ;
- ton formel cohérent ;
- terminologie technique validée.

## es-ES

- espagnol d’Espagne ;
- signes d’interrogation et d’exclamation doubles ;
- éviter les calques français.

## it-IT

- syntaxe naturelle italienne ;
- validation des anglicismes techniques.

## pt-PT

- portugais européen ;
- ne pas employer automatiquement les formes pt-BR ;
- terminologie locale validée.

---

# 30. Interface utilisateur

## Boutons

Verbe + objet :

```text
Créer la page
Enregistrer les modifications
Supprimer le module
Télécharger FlatCMS
```

## Confirmations

```text
La page a été enregistrée.
```

## Erreurs

```text
Impossible d’enregistrer la page. Vérifiez les permissions du dossier data/.
```

## Actions destructrices

Mentionner l’objet :

```text
Supprimer définitivement l’article « Titre »
```

---

# 31. Microcopy

## Formulaires

Label :

```text
Adresse e-mail
```

Placeholder facultatif :

```text
nom@exemple.fr
```

Ne pas utiliser le placeholder comme seul label.

## Aide

```text
Cette adresse sera utilisée pour les notifications administratives.
```

## Validation

Donner une solution :

```text
Le mot de passe doit contenir au moins 12 caractères.
```

---

# 32. Comparatifs

Chaque comparatif doit afficher :

- date ;
- versions ;
- critères ;
- méthode ;
- sources ;
- limites ;
- conclusion nuancée.

Éviter les tableaux construits pour faire gagner FlatCMS artificiellement.

---

# 33. Contenus juridiques et commerciaux

Les textes juridiques doivent être validés par une personne compétente.

Ce guide ne remplace pas un avis juridique.

Les pages de licence doivent reprendre fidèlement :

- `LICENSE`
- `LICENSING.md`
- `COMMERCIAL_LICENSE.md`
- `CLA.md`
- `TRADEMARK.md`

---

# 34. Workflow éditorial

```text
1. Définir l’audience
2. Définir l’intention
3. Vérifier la fonctionnalité
4. Rassembler les sources
5. Rédiger le plan
6. Rédiger la réponse essentielle
7. Ajouter les détails
8. Ajouter les exemples
9. Ajouter les limites
10. Ajouter les liens internes
11. Vérifier la sémantique
12. Vérifier l’accessibilité
13. Relire techniquement
14. Relire éditorialement
15. Tester
16. Traduire
17. Publier
18. Mesurer
19. Mettre à jour
```

---

# 35. Checklist de validation

- [ ] audience identifiée ;
- [ ] intention claire ;
- [ ] réponse principale visible rapidement ;
- [ ] titre descriptif ;
- [ ] H1 unique ;
- [ ] hiérarchie H2/H3 cohérente ;
- [ ] paragraphes lisibles ;
- [ ] termes officiels respectés ;
- [ ] assertions vérifiées ;
- [ ] limites expliquées ;
- [ ] sources primaires privilégiées ;
- [ ] code testé ;
- [ ] résultats attendus indiqués ;
- [ ] liens descriptifs ;
- [ ] CTA précis ;
- [ ] images utiles ;
- [ ] textes alternatifs corrects ;
- [ ] tableaux accessibles ;
- [ ] auteur et dates présents ;
- [ ] données structurées cohérentes ;
- [ ] traduction relue ;
- [ ] aucune promesse non démontrée.

---

# 36. Exemples avant / après

## Exemple produit

### Avant

```text
FlatCMS est le CMS révolutionnaire le plus rapide et le plus puissant.
```

### Après

```text
FlatCMS est un CMS PHP flat-file conçu pour fonctionner sans serveur SQL
et limiter la complexité de déploiement.
```

---

## Exemple documentation

### Avant

```text
Ensuite, configurez correctement le serveur.
```

### Après

```text
Configurez le document root du serveur sur le dossier public/.
```

---

## Exemple lien

### Avant

```text
Cliquez ici pour en savoir plus.
```

### Après

```text
Consultez le guide de configuration Nginx.
```

---

## Exemple limite

### Avant

```text
Le stockage JSON convient à tous les sites.
```

### Après

```text
Le stockage JSON convient particulièrement aux sites dont le volume
d’écriture et les besoins transactionnels restent compatibles avec une
architecture flat-file.
```

---

# 37. Références externes

- Google Search Central — Helpful, reliable, people-first content  
  https://developers.google.com/search/docs/fundamentals/creating-helpful-content

- Google Search Central — Title links  
  https://developers.google.com/search/docs/appearance/title-link

- W3C WAI — Headings  
  https://www.w3.org/WAI/tutorials/page-structure/headings/

- W3C WAI — Alt decision tree  
  https://www.w3.org/WAI/tutorials/images/decision-tree/

- Digital.gov — Plain language guides  
  https://digital.gov/guides/plain-language

- Diátaxis  
  https://diataxis.fr/

---

# 38. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Création du guide éditorial initial | ChatGPT / Alain BROYE |

---

# 39. Prochaine action

Créer :

```text
MULTILINGUAL.md
```

Ce document définira :

- l’architecture des six locales ;
- les conventions de slugs ;
- les traductions ;
- les `hreflang` ;
- `x-default` ;
- les canonicals ;
- les fallbacks ;
- les sélecteurs de langue ;
- les dates et devises ;
- la validation des traductions ;
- la migration des contenus multilingues.
