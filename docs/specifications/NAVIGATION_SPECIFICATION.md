# NAVIGATION_SPECIFICATION — Navigation du thème officiel `flatcms`

> **Spécification fonctionnelle, éditoriale et technique destinée à Codex**
>
> Projet : FlatCMS  
> Thème : `flatcms`  
> Site cible : `https://flat-cms.fr`  
> Date : 8 juin 2026  
> Documents parents : `THEME_SPECIFICATION.md`, `DESIGN_SYSTEM.md`, `SITE_ARCHITECTURE.md`, `MULTILINGUAL.md`  
> Composants concernés : thème frontend, Menu, MenuBuilder, header, navigation mobile, footer, fil d’Ariane  
> Statut : spécification de conception à implémenter et tester  
> Priorité : critique

---

## 1. Objet

Ce document définit la navigation complète du futur site officiel FlatCMS.

Il couvre :

- le header principal ;
- la navigation desktop ;
- les sous-menus ;
- le méga-menu ;
- la navigation mobile ;
- le sélecteur de langue ;
- le changement de thème clair ou sombre ;
- les CTA du header ;
- le fil d’Ariane ;
- la navigation contextuelle de la documentation ;
- la navigation du blog ;
- le footer ;
- les comportements sticky ;
- le clavier et les lecteurs d’écran ;
- la compatibilité avec MenuBuilder ;
- les fallbacks sans JavaScript ;
- les tests demandés à Codex.

La navigation doit être originale, cohérente avec l’identité FlatCMS et inspirée du rythme compact et technologique observé sur le site Webby fourni comme référence visuelle, sans en reproduire la composition exacte.

---

# 2. Principes non négociables

## 2.1 Navigation disponible sans JavaScript

Sans JavaScript, un visiteur doit pouvoir :

- accéder à toutes les pages principales ;
- ouvrir les sous-menus via des liens HTML ;
- atteindre la documentation ;
- changer de locale par des liens ;
- accéder aux pages juridiques ;
- utiliser le footer ;
- revenir à l’accueil.

JavaScript améliore :

- l’ouverture des panneaux ;
- le focus ;
- la fermeture par `Escape` ;
- le menu mobile ;
- les animations ;
- la persistance du thème.

Il ne doit pas rendre la navigation indispensable inaccessible.

## 2.2 Liens HTML réels

Les destinations doivent utiliser des éléments :

```html
<a href="...">...</a>
```

Les boutons sont réservés aux actions :

```html
<button type="button">...</button>
```

Ne pas utiliser un `<div>` cliquable pour remplacer un lien ou un bouton.

## 2.3 Une navigation cohérente dans les six locales

Locales :

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

Chaque locale doit disposer :

- d’un menu traduit ;
- de destinations réellement publiées ;
- d’un sélecteur de langue cohérent ;
- de liens juridiques localisés ;
- d’un fallback explicite lorsqu’une page n’est pas traduite.

## 2.4 Aucun lien vers un contenu inexistant

Avant publication :

- vérifier le statut de chaque page ;
- masquer les entrées non publiées ;
- éviter les liens vers une roadmap fictive ;
- ne pas créer une entrée IA si la page n’existe pas ;
- ne pas afficher une offre commerciale indisponible.

## 2.5 MenuBuilder non requis pour le rendu

Le thème doit fournir une navigation standard sans MenuBuilder.

MenuBuilder permet :

- de composer visuellement les groupes ;
- d’ajouter des cartes ;
- d’ajouter des icônes ;
- de réorganiser les colonnes ;
- de personnaliser certains libellés.

Une licence absente ou expirée ne doit jamais supprimer le menu publié.

Référence obligatoire :

```text
BUILDER_LICENSE_ENFORCEMENT.md
```

---

# 3. Architecture générale de navigation

```text
Header global
├── Logo
├── Navigation principale
│   ├── Pourquoi FlatCMS
│   ├── Fonctionnalités
│   ├── Architecture
│   ├── Documentation
│   ├── Tarifs
│   └── Blog
├── Sélecteur de langue
├── Bascule clair/sombre
└── CTA Télécharger

Navigation contextuelle
├── Fil d’Ariane
├── Sommaire local
├── Sidebar documentation
├── Navigation précédent/suivant
└── Liens associés

Footer
├── Produit
├── Documentation
├── Communauté
├── Ressources
└── Légal
```

---

# 4. Header principal

## 4.1 Structure

Ordre desktop recommandé :

```text
Logo | Navigation | Langue | Apparence | Télécharger
```

Structure HTML conceptuelle :

```html
<header class="site-header" data-site-header>
  <div class="site-header__inner container">
    <a class="site-brand" href="/fr-FR/" aria-label="FlatCMS — Accueil">
      <img src="/assets/img/logo-flatcms.svg" alt="FlatCMS">
    </a>

    <nav class="primary-navigation" aria-label="Navigation principale">
      <!-- items -->
    </nav>

    <div class="site-header__actions">
      <!-- locale, theme, CTA -->
    </div>
  </div>
</header>
```

## 4.2 Hauteur

Desktop :

```text
72 px à 80 px
```

Mobile :

```text
64 px à 72 px
```

Le header doit rester compact et ne pas occuper une part excessive du viewport.

## 4.3 Conteneur

```css
.site-header__inner {
  width: min(100% - 2rem, var(--fc-container-xl));
  margin-inline: auto;
}
```

## 4.4 Logo

Le logo :

- utilise la version rectangulaire officielle ;
- conserve ses proportions ;
- possède un texte alternatif `FlatCMS` ;
- renvoie vers l’accueil de la locale active ;
- ne doit pas être agrandi au point de dominer la navigation ;
- dispose de variantes sombre et claire si nécessaire.

Taille indicative :

```text
largeur 120 à 150 px
hauteur maximale 34 px
```

---

# 5. Navigation principale desktop

## 5.1 Entrées initiales

Ordre recommandé :

```text
Pourquoi FlatCMS
Fonctionnalités
Architecture
Documentation
Tarifs
Blog
```

Le CTA `Télécharger` reste séparé de la navigation.

## 5.2 Destinations `fr-FR`

```text
Pourquoi FlatCMS → /fr-FR/pourquoi-flatcms/
Fonctionnalités → /fr-FR/fonctionnalites/
Architecture → /fr-FR/architecture/
Documentation → /fr-FR/documentation/
Tarifs → /fr-FR/tarifs/
Blog → /fr-FR/blog/
Télécharger → /fr-FR/telechargement/
```

## 5.3 Libellés

Les libellés doivent :

- rester courts ;
- décrire la destination ;
- ne pas utiliser de jargon inutile ;
- être traduits naturellement ;
- ne pas mélanger français et anglais.

## 5.4 État actif

L’entrée correspondant à la section active utilise :

```html
aria-current="page"
```

ou pour une section parente :

```html
aria-current="true"
```

Style recommandé :

- texte plus clair ;
- fond indigo très léger ;
- bordure ou indicateur discret ;
- aucun changement reposant uniquement sur la couleur.

## 5.5 Hover

Le survol doit rester sobre :

- transition 120 à 180 ms ;
- couleur ou fond subtil ;
- déplacement vertical maximal de 1 px ;
- aucune animation agressive.

## 5.6 Focus

Le focus clavier doit être visible :

```css
:focus-visible {
  outline: 3px solid var(--fc-focus);
  outline-offset: 3px;
}
```

Ne jamais supprimer l’outline sans alternative équivalente.

---

# 6. Sous-menus

## 6.1 Usage

Un sous-menu simple convient lorsque le groupe comporte :

```text
2 à 6 liens homogènes
```

Exemples :

```text
Architecture
├── Vue d’ensemble
├── HMVC
├── PSR-4
├── Stockage JSON
├── Services
└── Sécurité
```

## 6.2 Déclencheur

Le libellé parent doit pouvoir :

- mener vers une page hub ;
- ouvrir le panneau avec un bouton adjacent ou un bouton incluant le libellé.

Approche recommandée :

```html
<div class="nav-item nav-item--has-menu">
  <a href="/fr-FR/architecture/">Architecture</a>
  <button type="button"
          aria-expanded="false"
          aria-controls="submenu-architecture"
          aria-label="Ouvrir le menu Architecture">
    <!-- icon -->
  </button>
</div>
```

Cette structure conserve le lien parent même sans JavaScript.

## 6.3 Ouverture

Desktop :

- clic ou clavier ;
- hover facultatif uniquement comme amélioration ;
- pas d’ouverture sur simple passage si cela crée des erreurs de trajectoire ;
- fermeture au clic extérieur ;
- fermeture avec `Escape` ;
- retour du focus au déclencheur.

## 6.4 Positionnement

- aligné avec l’élément parent ;
- ne dépasse pas le viewport ;
- largeur adaptée au contenu ;
- z-index documenté ;
- pas de clipping par un parent `overflow: hidden`.

## 6.5 Style

```text
fond : surface élevée
bordure : fine
rayon : 14 à 18 px
ombre : douce
padding : 8 à 12 px
```

---

# 7. Méga-menu

## 7.1 Usage

Le méga-menu doit être réservé aux sections riches :

- Fonctionnalités ;
- Documentation.

Il ne doit pas être utilisé pour chaque entrée.

## 7.2 Méga-menu Fonctionnalités

Structure proposée :

```text
Contenus
- Pages
- Articles
- Catégories
- Médias

Site
- Menus
- Footer
- Thèmes
- Multilingue

Architecture
- Modules HMVC
- Services
- Hooks
- Données structurées

Premium
- PagesBuilder
- MenuBuilder
- FooterBuilder
```

Carte mise en avant possible :

```text
Découvrez la Suite complète
Les trois Builders pour un domaine de production.
```

CTA :

```text
Voir tous les tarifs
```

## 7.3 Méga-menu Documentation

Structure proposée :

```text
Commencer
- Prérequis
- Installation
- Premier site
- Première connexion

Utiliser
- Administration
- Contenus
- Navigation
- Thèmes

Développer
- Architecture
- Créer un module
- Hooks
- Référence

Déployer
- Apache
- Nginx
- Sécurité
- Dépannage
```

Carte mise en avant :

```text
Installer FlatCMS
Guide principal pour configurer public/, les permissions et l’installateur.
```

## 7.4 Nombre de colonnes

Desktop large :

```text
3 ou 4 colonnes
```

Desktop compact :

```text
2 ou 3 colonnes
```

Mobile :

```text
accordéons verticaux
```

## 7.5 Icônes

Les icônes :

- sont locales ;
- ont une taille cohérente ;
- restent décoratives si le texte suffit ;
- utilisent `aria-hidden="true"` dans ce cas ;
- ne remplacent jamais le libellé.

## 7.6 Cartes promotionnelles

Une carte dans un méga-menu :

- ne doit pas ressembler à une publicité tierce ;
- doit être clairement reliée à FlatCMS ;
- utilise un CTA descriptif ;
- ne bloque pas la navigation ;
- reste limitée à une seule carte par panneau.

---

# 8. Navigation sticky

## 8.1 Comportement

Le header peut devenir sticky :

```css
position: sticky;
top: 0;
```

## 8.2 Apparence au scroll

À partir d’un seuil léger :

- fond plus opaque ;
- bordure inférieure ;
- ombre discrète ;
- backdrop blur facultatif ;
- aucune réduction brutale de hauteur.

## 8.3 Préférence de mouvement

Avec :

```css
@media (prefers-reduced-motion: reduce)
```

- aucune translation animée ;
- aucun effet de glissement ;
- transitions réduites ou supprimées.

## 8.4 Ancres

Le header sticky ne doit pas masquer les ancres :

```css
[id] {
  scroll-margin-top: calc(var(--fc-header-height) + 1.5rem);
}
```

---

# 9. Actions du header

## 9.1 Sélecteur de langue

Affichage compact recommandé :

```text
FR
```

avec un libellé accessible :

```text
Choisir la langue
```

Le menu affiche :

```text
Français
English
Deutsch
Español
Italiano
Português
```

Ne pas afficher uniquement des drapeaux.

## 9.2 Bascule clair/sombre

La bascule :

- utilise un bouton ;
- possède un nom accessible ;
- respecte `prefers-color-scheme` au premier chargement ;
- mémorise le choix localement ;
- ne provoque pas de flash visuel important ;
- ne nécessite pas de compte.

Libellés accessibles :

```text
Activer le mode clair
Activer le mode sombre
Utiliser le thème du système
```

## 9.3 CTA Télécharger

Desktop : bouton primaire compact.

Libellé :

```text
Télécharger
```

ou :

```text
Télécharger FlatCMS
```

Destination localisée.

Le CTA doit rester visible sans dominer la marque.

---

# 10. Navigation mobile

## 10.1 Déclenchement

Breakpoint indicatif :

```text
< 1024 px
```

Le breakpoint final doit dépendre du contenu réel et non d’un appareil précis.

## 10.2 Bouton menu

```html
<button type="button"
        aria-expanded="false"
        aria-controls="mobile-navigation"
        aria-label="Ouvrir le menu">
</button>
```

L’icône hamburger reste accompagnée d’un nom accessible.

## 10.3 Type de panneau

Approche recommandée :

```text
panneau plein écran ou tiroir occupant presque toute la largeur
```

Le panneau doit :

- couvrir le contenu sans le supprimer ;
- empêcher le scroll du fond pendant l’ouverture ;
- restaurer le scroll à la fermeture ;
- gérer le focus ;
- se fermer avec `Escape` ;
- contenir une action de fermeture visible.

## 10.4 Structure mobile

```text
Logo + Fermer
Navigation principale
Accordéons de sous-navigation
Sélecteur de langue
Bascule apparence
CTA Télécharger
Liens secondaires
```

## 10.5 Accordéons

Les groupes utilisent des boutons avec :

```html
aria-expanded="false"
aria-controls="mobile-group-documentation"
```

Les liens parents restent accessibles indépendamment du bouton d’ouverture.

## 10.6 Cibles tactiles

Taille minimale recommandée :

```text
44 × 44 px
```

Espacement suffisant entre les actions.

## 10.7 Focus trap

Lorsque le panneau est modal :

- le focus reste dans le panneau ;
- le premier élément reçoit le focus ;
- la fermeture restitue le focus au bouton menu ;
- `aria-modal="true"` est utilisé si le composant se comporte comme un dialogue.

## 10.8 Sans JavaScript

Fallback :

- navigation HTML visible dans le document ;
- ou lien vers une page sitemap/menu ;
- ne jamais laisser le visiteur sans navigation.

---

# 11. Fil d’Ariane

## 11.1 Usage

Afficher sur :

- documentation ;
- architecture ;
- blog ;
- pages Builders ;
- pages légales ;
- pages profondes.

Peut être omis sur :

- accueil ;
- landing page très simple ;
- page 404 selon choix visuel.

## 11.2 Structure

```text
Accueil > Documentation > Installation > Nginx
```

HTML conceptuel :

```html
<nav aria-label="Fil d’Ariane">
  <ol>
    <li><a href="/fr-FR/">Accueil</a></li>
    <li><a href="/fr-FR/documentation/">Documentation</a></li>
    <li aria-current="page">Installation</li>
  </ol>
</nav>
```

## 11.3 Séparateur

Le séparateur est décoratif :

```html
aria-hidden="true"
```

## 11.4 JSON-LD

`BreadcrumbList` peut être produit lorsque :

- le fil est visible ;
- les URLs sont canoniques ;
- les éléments existent ;
- la page est indexable.

Ne pas l’utiliser sur les réponses 404.

---

# 12. Navigation de documentation

## 12.1 Layout desktop

```text
Sidebar gauche | Contenu principal | Sommaire local droit
```

Selon la largeur :

- sidebar persistante ;
- contenu flexible ;
- sommaire local facultatif.

## 12.2 Sidebar

Sections :

```text
Démarrage
Tutoriels
Administration
Contenus
Navigation
Thèmes
Développement
Déploiement
Sécurité
Maintenance
Dépannage
Référence
```

## 12.3 Comportement

- section active ouverte ;
- page active marquée ;
- sections longues repliables ;
- scroll indépendant uniquement si testé ;
- position sticky ;
- lien vers le hub ;
- version visible.

## 12.4 Mobile

La sidebar devient :

```text
bouton « Navigation de la documentation »
```

ouvrant un panneau ou un accordéon.

## 12.5 Sommaire local

Généré à partir des H2 et éventuellement H3.

Règles :

- liens d’ancre stables ;
- hiérarchie correcte ;
- section active facultative ;
- pas de mutation du contenu ;
- intitulés identiques aux titres visibles.

## 12.6 Précédent / suivant

En bas d’un guide :

```text
← Précédent
Suivant →
```

Chaque lien affiche :

- type ;
- titre ;
- destination.

Ne pas calculer selon un ordre non déterministe.

## 12.7 Version de documentation

Afficher :

```text
Version 1.0 LTS
```

Le sélecteur de version :

- ne redirige pas vers une page inexistante ;
- signale les archives ;
- conserve la locale ;
- est accessible au clavier.

---

# 13. Navigation du blog

## 13.1 Entrées

- Tous les articles ;
- Catégories ;
- Tags si utilisés ;
- Archives si utiles ;
- Flux RSS si disponible.

## 13.2 Catégories

Les catégories doivent correspondre au contenu réel.

Exemples possibles :

```text
Architecture
Développement
Agents IA
SEO, GEO et GIO
Versions
Communauté
Tutoriels
```

## 13.3 Article

Navigation de fin :

- article précédent ;
- article suivant ;
- articles associés ;
- retour à la catégorie ;
- retour au blog.

## 13.4 Pagination

Utiliser des liens HTML explorables.

Ne pas dépendre uniquement d’un chargement infini.

---

# 14. Navigation des pages marketing

## 14.1 Sommaire facultatif

Pour les pages longues :

- Fonctionnalités ;
- Architecture ;
- Agent-ready ;
- Tarifs ;
- Licences.

Le sommaire peut être :

- inline sous le Hero ;
- sticky compact ;
- menu d’ancres.

## 14.2 Ancres

Les IDs doivent être :

- stables ;
- lisibles ;
- localisés ou conservés selon la stratégie ;
- uniques ;
- sans caractères problématiques.

Exemple :

```text
#architecture
#builders
#tarifs
#faq
```

---

# 15. Footer

## 15.1 Rôle

Le footer :

- complète la navigation ;
- donne accès aux pages légales ;
- rassure ;
- présente les ressources principales ;
- reste cohérent dans les six locales.

## 15.2 Structure recommandée

```text
Colonne marque
- logo
- description courte
- GitHub / réseaux validés

Produit
- Pourquoi FlatCMS
- Fonctionnalités
- Architecture
- Agent-ready
- Tarifs

Documentation
- Installer
- Administration
- Développement
- Déploiement
- Dépannage

Communauté
- Blog
- GitHub
- Contribuer
- Roadmap
- Contact

Ressources
- Télécharger
- Démo
- Licences
- Sécurité
- Statut si disponible
```

Ligne légale :

```text
Mentions légales
Confidentialité
Cookies
Licences
CGV
Résilier votre contrat
Sécurité
```

## 15.3 Mention de marque

Formulation de travail :

```text
© 2026 Alain BROYE / FlatCMS. Certains droits réservés.
```

À adapter au futur statut juridique.

## 15.4 FooterBuilder

FooterBuilder peut gérer :

- colonnes ;
- ordre ;
- widgets ;
- menus ;
- logos ;
- newsletter ;
- locales.

Le thème doit conserver un fallback standard lorsque FooterBuilder est absent.

## 15.5 Mobile

Les colonnes deviennent :

- accordéons ;
- ou sections empilées.

Les liens légaux restent directement accessibles.

---

# 16. Sélecteur de langue

## 16.1 Comportement

Le sélecteur doit viser l’équivalent traduit de la page active.

Exemple :

```text
/fr-FR/fonctionnalites/
→ /en-US/features/
```

## 16.2 Traduction absente

Si l’équivalent n’existe pas :

- ne pas déclarer un lien `hreflang` ;
- afficher un état indisponible ;
- proposer l’accueil de la langue ;
- ne pas effectuer un fallback silencieux vers une autre langue.

Message :

```text
Cette page n’est pas encore disponible en allemand.
```

## 16.3 Persistance

Le choix peut être enregistré dans :

- cookie nécessaire ;
- préférence locale ;
- compte.

La locale de l’URL reste la source principale.

## 16.4 Accessibilité

- libellé complet ;
- langue de chaque option avec `lang` si nécessaire ;
- pas de drapeau comme seule information ;
- focus visible ;
- fermeture avec `Escape`.

---

# 17. Recherche globale

## 17.1 Statut initial

La recherche globale peut être intégrée si elle est réellement disponible au lancement.

## 17.2 Déclencheur

Bouton dans le header ou raccourci :

```text
Rechercher
```

Raccourci facultatif :

```text
Ctrl+K / Cmd+K
```

Le raccourci ne doit pas empêcher les fonctions du navigateur.

## 17.3 Périmètre

- documentation ;
- pages ;
- articles ;
- fonctionnalités ;
- classes et fichiers si indexés.

## 17.4 Résultats

Chaque résultat affiche :

- type ;
- titre ;
- extrait ;
- version ;
- locale.

## 17.5 SEO

Les pages de résultats :

```text
noindex, follow
```

Les paramètres ne doivent pas générer une infinité d’URLs indexables.

---

# 18. États de navigation

## 18.1 Chargement

Le header doit apparaître immédiatement.

Ne pas masquer toute la navigation en attendant JavaScript.

## 18.2 Erreur

Si un menu dynamique échoue :

- utiliser le fallback du thème ;
- conserver les liens essentiels ;
- journaliser l’erreur ;
- ne pas afficher une page blanche.

## 18.3 Mode maintenance

La page de maintenance conserve :

- logo ;
- message ;
- contact ou statut ;
- aucun menu vers des pages indisponibles.

## 18.4 Licence expirée

Aucun impact sur :

- header ;
- menu publié ;
- navigation mobile ;
- footer.

---

# 19. Modèle de données de navigation

## 19.1 Structure conceptuelle

```json
{
  "id": "main-navigation",
  "locale": "fr-FR",
  "location": "header-primary",
  "items": [
    {
      "id": "features",
      "label": "Fonctionnalités",
      "url": "/fr-FR/fonctionnalites/",
      "type": "mega-menu",
      "children": []
    }
  ]
}
```

## 19.2 Champs possibles

- `id` ;
- `label` ;
- `url` ;
- `type` ;
- `target` ;
- `rel` ;
- `icon` ;
- `description` ;
- `badge` ;
- `children` ;
- `permission` ;
- `visibility` ;
- `locale` ;
- `order`.

## 19.3 Validation

- ID unique ;
- URL valide ;
- protocole autorisé ;
- cible externe explicitement signalée ;
- pas de `javascript:` ;
- profondeur limitée ;
- cycle interdit ;
- label requis ;
- locale valide ;
- destination publiée.

## 19.4 Profondeur

Header desktop :

```text
2 niveaux visibles maximum
```

Documentation sidebar :

```text
3 niveaux maximum si nécessaire
```

Au-delà, revoir l’architecture de contenu.

---

# 20. Emplacements de menu

Le thème doit déclarer au minimum :

```text
header-primary
header-secondary
mobile-primary
footer-product
footer-documentation
footer-community
footer-resources
footer-legal
documentation-sidebar
```

Emplacements facultatifs :

```text
blog-categories
account-navigation
social-links
```

## Règle

Chaque emplacement possède :

- un fallback ;
- un libellé d’administration ;
- une locale ;
- une limite de profondeur ;
- une documentation.

---

# 21. Compatibilité MenuBuilder

## 21.1 Contrat

MenuBuilder doit produire une structure que le thème peut rendre sans code spécifique dispersé.

## 21.2 Widgets envisagés

- lien ;
- groupe ;
- colonne ;
- titre ;
- icône ;
- image ;
- carte mise en avant ;
- séparateur ;
- CTA ;
- badge.

## 21.3 Restrictions

- pas de script arbitraire ;
- pas de HTML non filtré ;
- pas de profondeur illimitée ;
- pas de cible sans libellé ;
- pas de média externe obligatoire ;
- pas de données privées.

## 21.4 Prévisualisation

La prévisualisation doit représenter :

- desktop ;
- tablette ;
- mobile ;
- sombre ;
- clair ;
- clavier.

## 21.5 Expiration

Après expiration :

- rendu public conservé ;
- données conservées ;
- éditeur en lecture seule selon la politique ;
- aucun bandeau injecté dans le menu public d’un ancien client valide.

---

# 22. Sécurité

## 22.1 URLs

Valider :

- schéma ;
- hôte ;
- chemin ;
- ancre ;
- cible.

Interdire :

```text
javascript:
data:
```

sauf cas interne strictement contrôlé.

## 22.2 Liens externes

Si `target="_blank"` :

```html
rel="noopener noreferrer"
```

Le lien externe peut afficher une indication accessible si utile.

## 22.3 HTML

Les libellés sont échappés.

Les descriptions autorisent seulement un sous-ensemble contrôlé ou du texte brut.

## 22.4 Permissions

La visibilité d’un lien d’administration ne remplace pas le contrôle serveur de la destination.

---

# 23. Performance

## 23.1 Budget

Navigation CSS spécifique :

```text
≤ 20 Ko compressés idéalement
```

JavaScript navigation :

```text
≤ 15 Ko compressés idéalement
```

## 23.2 Chargement

- aucun framework lourd ;
- icônes SVG locales ;
- événements délégués ;
- pas de polling ;
- pas de requête réseau à chaque ouverture ;
- données du menu rendues côté serveur.

## 23.3 Layout shift

Réserver la hauteur du header.

Éviter :

- logo sans dimensions ;
- police tardive modifiant fortement la largeur ;
- menu injecté après chargement ;
- CTA apparaissant tardivement.

---

# 24. Accessibilité

## 24.1 Clavier

Tous les éléments doivent être atteignables avec :

```text
Tab
Shift+Tab
Enter
Space
Escape
Flèches si le pattern retenu le justifie
```

Ne pas imposer des interactions de type application lorsque des liens HTML simples suffisent.

## 24.2 Lecteurs d’écran

- `nav` avec libellé ;
- état ouvert/fermé ;
- `aria-current` ;
- nom accessible des icônes ;
- ordre DOM cohérent ;
- pas de contenu dupliqué annoncé deux fois.

## 24.3 Zoom et reflow

À 200 % et 400 % :

- aucun texte coupé ;
- menu mobile accessible ;
- pas de scroll horizontal général ;
- sous-menus repositionnés.

## 24.4 Contraste

Respecter les tokens du `DESIGN_SYSTEM.md`.

## 24.5 Réduction des animations

Tous les effets d’ouverture respectent `prefers-reduced-motion`.

---

# 25. Responsive

## 25.1 Mobile-first

Styles de base pour petits écrans, puis enrichissement.

## 25.2 Breakpoints conceptuels

```text
sm : 640 px
md : 768 px
lg : 1024 px
xl : 1280 px
2xl : 1536 px
```

Ils peuvent être adaptés au contenu réel.

## 25.3 Règles

- bascule vers menu mobile avant collision ;
- CTA raccourci si nécessaire ;
- langue et thème restent accessibles ;
- aucune colonne de méga-menu comprimée ;
- footer empilé proprement.

---

# 26. Animations

## 26.1 Autorisées

- opacité ;
- translation de 4 à 8 px ;
- rotation légère du chevron ;
- fade du backdrop.

## 26.2 Durées

```text
120 à 220 ms
```

## 26.3 Interdites

- zoom important ;
- rebond ;
- parallaxe ;
- animation continue ;
- mouvement déclenché uniquement au hover et indispensable à la compréhension.

---

# 27. JavaScript recommandé

Structure conceptuelle :

```text
assets/js/navigation/
├── primary-navigation.js
├── mobile-navigation.js
├── locale-switcher.js
├── theme-switcher.js
├── documentation-navigation.js
└── focus-utils.js
```

## API interne possible

```js
initPrimaryNavigation();
initMobileNavigation();
initLocaleSwitcher();
initThemeSwitcher();
initDocumentationNavigation();
```

## Règles

- modules ES locaux ;
- initialisation idempotente ;
- erreurs isolées ;
- absence d’effet global non documenté ;
- attributs `data-*` comme hooks ;
- classes CSS non utilisées comme API JavaScript lorsque possible.

---

# 28. CSS recommandé

```text
assets/css/components/
├── site-header.css
├── primary-navigation.css
├── mega-menu.css
├── mobile-navigation.css
├── breadcrumb.css
├── documentation-sidebar.css
├── table-of-contents.css
├── locale-switcher.css
├── theme-switcher.css
└── site-footer.css
```

## BEM ou convention équivalente

Exemple :

```text
.site-header
.site-header__inner
.primary-nav
.primary-nav__item
.primary-nav__link
.mega-menu
.mega-menu__column
```

La convention doit rester cohérente dans tout le thème.

---

# 29. Fallbacks

## Header minimal

Si le menu est absent :

```text
Logo
Accueil
Documentation
Télécharger
Contact
```

## Footer minimal

```text
Mentions légales
Confidentialité
Licences
Contact
```

## Erreurs 404/500

Les pages d’erreur utilisent une navigation réduite indépendante des Builders.

---

# 30. Analytics et confidentialité

Les interactions de navigation ne doivent pas être suivies avant consentement si l’outil utilisé l’exige.

Événements possibles après validation :

```text
navigation_click
mega_menu_open
locale_change
theme_change
download_cta_click
```

Ne pas enregistrer :

- contenu saisi dans la recherche ;
- identifiant personnel sans nécessité ;
- URL contenant un token ;
- historique complet nominatif.

---

# 31. Données structurées et navigation

## SiteNavigationElement

Son usage n’est pas nécessaire pour chaque lien et n’apporte pas une garantie de fonctionnalité enrichie.

Priorité :

- HTML sémantique ;
- liens explorables ;
- fil d’Ariane visible ;
- `BreadcrumbList` cohérent.

## SearchAction

Ne pas ajouter un `SearchAction` tant que la recherche n’est pas réellement disponible et stable.

---

# 32. Maquette textuelle desktop

```text
┌───────────────────────────────────────────────────────────────────┐
│ FlatCMS   Pourquoi  Fonctionnalités⌄  Architecture⌄  Docs⌄       │
│           Tarifs  Blog                     FR  ◐  Télécharger     │
└───────────────────────────────────────────────────────────────────┘
```

Méga-menu :

```text
┌──────────────────────────────────────────────────────────────┐
│ Contenus        Site             Architecture      Premium   │
│ Pages           Menus            Modules HMVC      PagesB.   │
│ Articles        Footer           Services          MenuB.    │
│ Médias          Thèmes           Hooks             FooterB.  │
│                                                          CTA │
└──────────────────────────────────────────────────────────────┘
```

---

# 33. Maquette textuelle mobile

```text
┌─────────────────────────────┐
│ FlatCMS                 ☰   │
└─────────────────────────────┘

Panneau ouvert
┌─────────────────────────────┐
│ FlatCMS                 ×   │
│                             │
│ Pourquoi FlatCMS            │
│ Fonctionnalités          +  │
│ Architecture             +  │
│ Documentation            +  │
│ Tarifs                      │
│ Blog                        │
│                             │
│ Langue : Français        >  │
│ Apparence : Système      >  │
│                             │
│ [ Télécharger FlatCMS ]     │
└─────────────────────────────┘
```

---

# 34. Tests unitaires obligatoires

```text
testPrimaryMenuRendersPublishedItems
testPrimaryMenuEscapesLabels
testPrimaryMenuRejectsUnsafeUrl
testActiveItemUsesAriaCurrent
testSubmenuButtonUsesAriaExpanded
testMegaMenuHasUniqueIds
testMobileMenuRestoresFocus
testMobileMenuClosesOnEscape
testLocaleSwitcherUsesPublishedEquivalent
testMissingTranslationIsNotLinkedAsEquivalent
testThemeToggleHasAccessibleName
testBreadcrumbUsesCurrentPage
testFooterLegalLinksAlwaysRender
testMenuFallbackRendersWithoutBuilder
testExpiredMenuBuilderKeepsPublishedMenu
```

---

# 35. Tests d’intégration obligatoires

## Desktop

1. charger la home ;
2. naviguer au clavier ;
3. ouvrir chaque sous-menu ;
4. fermer avec `Escape` ;
5. vérifier le focus ;
6. suivre les liens.

## Mobile

1. ouvrir le menu ;
2. vérifier le blocage du scroll ;
3. ouvrir les groupes ;
4. changer de langue ;
5. fermer ;
6. vérifier le retour du focus.

## Sans JavaScript

1. désactiver JavaScript ;
2. vérifier les liens principaux ;
3. atteindre la documentation ;
4. atteindre les pages légales ;
5. télécharger FlatCMS.

## MenuBuilder

1. publier un méga-menu ;
2. expirer la licence ;
3. vérifier le rendu identique ;
4. vérifier la lecture seule ;
5. renouveler ;
6. vérifier l’absence de perte.

## Multilingue

1. tester les six locales ;
2. vérifier les libellés ;
3. vérifier les URLs ;
4. vérifier les absences de traduction ;
5. vérifier le footer.

---

# 36. Tests end-to-end

## Scénario A — Visiteur desktop

```text
Étant donné la page d’accueil
Quand le visiteur ouvre le méga-menu Documentation
Alors les catégories sont visibles
Et les liens sont accessibles au clavier
Et Escape ferme le panneau
```

## Scénario B — Visiteur mobile

```text
Étant donné un écran mobile
Quand le visiteur ouvre le menu
Alors le focus entre dans le panneau
Et le fond ne défile plus
Et la fermeture restitue le focus
```

## Scénario C — Changement de langue

```text
Étant donné une page disponible en anglais
Quand le visiteur choisit English
Alors il arrive sur la page équivalente en-US
Et non sur l’accueil générique
```

## Scénario D — Traduction absente

```text
Étant donné une page non traduite en allemand
Quand le sélecteur est ouvert
Alors l’absence est indiquée
Et aucune fausse URL équivalente n’est générée
```

## Scénario E — Licence expirée

```text
Étant donné un menu publié avec MenuBuilder
Quand la licence expire
Alors le menu public reste identique
Et aucune erreur de licence n’est affichée
```

---

# 37. Audit demandé à Codex

Codex doit inventorier :

- modèles de menus ;
- fichiers JSON ;
- contrôleurs ;
- services ;
- renderers ;
- widgets MenuBuilder ;
- scripts ;
- styles ;
- emplacements ;
- traductions ;
- fallbacks ;
- permissions ;
- contrôles de licence.

## Rapport attendu

```text
Fichier
Ligne
Composant
Comportement actuel
Écart
Risque
Correction
Test associé
```

## Confirmations critiques

- [ ] Navigation principale fonctionnelle sans JavaScript.
- [ ] Aucun lien construit avec un protocole dangereux.
- [ ] Tous les menus sont accessibles au clavier.
- [ ] Le menu mobile restitue le focus.
- [ ] Les six locales sont prises en charge.
- [ ] Les pages non traduites ne produisent pas de faux équivalents.
- [ ] Le thème possède un fallback sans MenuBuilder.
- [ ] L’expiration ne modifie pas le menu public.
- [ ] Le footer légal est toujours disponible.
- [ ] Les liens actifs utilisent `aria-current`.
- [ ] Les méga-menus respectent la largeur du viewport.
- [ ] Aucun script tiers n’est requis pour naviguer.

---

# 38. Critères d’acceptation

L’implémentation est acceptée uniquement si :

1. toutes les destinations P0 sont accessibles ;
2. la navigation fonctionne sans JavaScript ;
3. les sous-menus sont utilisables au clavier ;
4. le menu mobile gère correctement le focus ;
5. les six locales utilisent des liens réels ;
6. le header reste compact et lisible ;
7. le footer contient les liens juridiques ;
8. le thème fonctionne sans MenuBuilder ;
9. une licence expirée n’altère aucun rendu public ;
10. tous les tests automatisés passent.

---

# 39. Éléments à confirmer

- libellés finaux du header ;
- présence de Blog au lancement ;
- présence de Roadmap ;
- recherche globale ;
- raccourci `Cmd/Ctrl+K` ;
- format du logo clair/sombre ;
- contenu exact des méga-menus ;
- page Agent-ready dans le header ou sous Fonctionnalités ;
- présence de compte client ;
- lien GitHub officiel ;
- réseaux sociaux officiels ;
- politique des sous-domaines ;
- structure exacte des données MenuBuilder ;
- profondeur maximale réelle ;
- comportement de staging ;
- gestion du mode clair ;
- CGV et résiliation dans le footer.

---

# 40. Checklist éditoriale

- [ ] Libellés courts et traduits.
- [ ] Ordre stable.
- [ ] Aucun lien mort.
- [ ] Pages futures masquées.
- [ ] CTA descriptifs.
- [ ] Navigation juridique complète.
- [ ] Langues écrites en toutes lettres.
- [ ] Pages hubs disponibles.
- [ ] Aucune terminologie contradictoire.
- [ ] Documentation organisée selon sa carte.

---

# 41. Checklist accessibilité

- [ ] Navigation sémantique.
- [ ] Focus visible.
- [ ] Clavier complet.
- [ ] Escape.
- [ ] Restitution du focus.
- [ ] `aria-expanded`.
- [ ] `aria-controls`.
- [ ] `aria-current`.
- [ ] Cibles tactiles suffisantes.
- [ ] Contraste.
- [ ] Reflow 400 %.
- [ ] Réduction des animations.
- [ ] Libellés accessibles.
- [ ] Aucun drapeau seul.

---

# 42. Checklist performance

- [ ] Menu rendu côté serveur.
- [ ] Aucun CDN obligatoire.
- [ ] JS limité.
- [ ] CSS limité.
- [ ] Logo dimensionné.
- [ ] Aucun layout shift important.
- [ ] Aucun appel réseau à l’ouverture.
- [ ] Icônes locales.
- [ ] Fallback instantané.
- [ ] Cache cohérent.

---

# 43. Sources internes

- `THEME_SPECIFICATION.md`
- `DESIGN_SYSTEM.md`
- `SITE_ARCHITECTURE.md`
- `DOCUMENTATION_MAP.md`
- `MULTILINGUAL.md`
- `PAGE_BRIEFS.md`
- `REDIRECTS.md`
- `BUILDER_LICENSE_ENFORCEMENT.md`
- `404_CONTENT.md`
- modules `Menu` et `MenuBuilder` ;
- données de menus réelles ;
- thèmes existants ;
- composants de navigation existants.

---

# 44. Références techniques

- W3C WAI — Menus and menu buttons  
  https://www.w3.org/WAI/ARIA/apg/patterns/menu-button/

- W3C WAI — Disclosure pattern  
  https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/

- W3C WAI — Breadcrumb pattern  
  https://www.w3.org/WAI/ARIA/apg/patterns/breadcrumb/

- WCAG 2.2  
  https://www.w3.org/TR/WCAG22/

Le pattern ARIA `menu` ne doit pas être appliqué automatiquement à une
navigation de site. Des listes de liens et des disclosures simples sont
souvent plus adaptées et plus robustes.

---

# 45. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première spécification complète de la navigation du thème `flatcms` | ChatGPT / Alain BROYE |

---

# 46. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer COMPONENT_LIBRARY.md
```

Ce document définira les composants réutilisables du thème :

- boutons ;
- badges ;
- cartes ;
- Hero ;
- panneaux de code ;
- tableaux ;
- FAQ ;
- alertes ;
- formulaires ;
- CTA ;
- composants de documentation ;
- contrats avec les Builders.
