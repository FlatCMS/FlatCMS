# Audit Widgets PagesBuilder

Derniere mise a jour : 2026-05-31

## Perimetre obligatoire

- Travail autorise uniquement dans `app/Extensions/PagesBuilder`
- Interdiction de toucher le core, les themes ou les modules partages sans autorisation explicite
- Si un correctif semble exiger autre chose que `PagesBuilder`, stop et escalade avant modification
- La source canonique de verite est `updates.flat-cms.fr`
- La LTS ne doit jamais servir de reference fonctionnelle ou visuelle face a un doute de migration
- Toute verification de contrat doit se faire dans le sens :
  - `updates` -> contrat valide
  - `LTS` -> cible a realigner

## Migration officielle PagesBuilder

Avant toute reprise d audit fonctionnel ou visuel, la migration officielle des widgets doit etre complete.

- Interdiction de traiter un widget comme `Termine` si sa structure n est pas completement migree depuis `updates`
- Interdiction de reprendre des correctifs de preview/front sur un widget hybride ou partiellement migre
- Si un widget officiel n a pas :
  - `Definition.php`
  - `Renderer.php`
  - `Assets/`
  - `Languages/`
  - `widget.json`
  - `widget.php`
  - `render.php`
  - `preview.js`
  alors il n est pas migre correctement

### Widgets officiels encore hybrides a migrer completement

- Aucun au 2026-06-01 sur le lot `media` actif

### Lot hybride migre structurellement le 2026-05-25

Le lot suivant a ete remonte depuis `updates` avec sa structure canonique complete :

- `Hero`
- `Heading`
- `Text`
- `Divider`
- `Spacer`

Regle de reprise :

- ne plus rouvrir une correction preview/front sur ces widgets au titre de la migration structurelle
- la migration est consideree comme faite
- la suite du travail sur eux repasse par les passes d audit `P1 -> P6`
- seule exception locale conservee cote LTS :
  - `Hero` supporte encore `headingTag` en interne pour la compatibilite des pages converties avec `H1`
  - ce champ n est pas expose dans le contrat inspecteur
  - le fallback legacy historique de `PagesBuilder` est maintenant isole dans `Widgets/Hero/legacy-fallback.php`

## Etats autorises

- `Non traite` : aucun audit complet n'a commence
- `En cours` : une passe est active sur le widget
- `Termine` : toutes les passes sont faites cote agent, en attente de validation utilisateur
- `Valide` : widget ferme apres validation utilisateur

## Passes exactes a suivre

Toujours executer les passes dans cet ordre, une seule a la fois.

### P0 - Source canonique

- Comparer le widget LTS avec `updates.flat-cms.fr`
- Verifier :
  - `Definition.php`
  - `Renderer.php`
  - assets CSS
  - assets JS
  - preview JS
  - locales du widget
- Si ecart de contrat : corriger avant tout test visuel

### P1 - Contrat inspecteur

- Verifier les onglets exacts :
  - `Tous`
  - `Contenu`
  - `Navigation`
  - `Medias`
  - `Mise en page`
  - `Avances`
  - `Design` si le widget le supporte
- Verifier :
  - presence des bons champs
  - absence de champs parasites
  - bons types de controles
  - bonnes options des `select`
  - bonnes conditions d'affichage
  - pas de fusion `Contenu` / `Navigation`

### P1B - Premier clic utile

Passe obligatoire sur tous les champs, dans tous les onglets, pour tous les widgets.

- Verifier que le premier clic n'est jamais consomme inutilement avant edition
- Le premier clic doit permettre immediatement :
  - de placer le curseur dans un `input`
  - d'ouvrir un `select`
  - d'activer un `toggle`
  - d'entrer dans un `textarea`
  - d'interagir avec un controle media
  - d'utiliser un controle repeater
- Verifier ce point dans :
  - `Tous`
  - `Contenu`
  - `Navigation`
  - `Medias`
  - `Mise en page`
  - `Avances`
  - `Design` si present
- Si le premier clic est absorbe par le shell du controle, le champ n'est pas valide
- Ne jamais considerer un widget `Termine` sans cette verification

### P2 - Preview admin

- Le canvas doit refleter :
  - le theme frontend actif
  - le mode light/dark actif cote front
  - les variables du customizer si elles existent
- Verifier :
  - structure HTML preview
  - etats hover/focus
  - medias
  - CTA
  - formulaires
  - tokens couleur/espacement/radius
- Verifier la bascule `default` <-> `modern-pro custom` avec `PagesBuilder` actif :
  - aucun fallback visuel d un theme nomme ne doit rester dans les CSS runtime ou preview de `PagesBuilder`
  - les boutons doivent consommer les variables du theme actif, jamais une palette codee en dur dans `PagesBuilder`
  - les styles de liens riches ne doivent jamais surclasser `.btn`, `.btn-*`, `.pb-btn` ou `.pb-btn-*`

### P3 - Front contract

- Verifier que chaque reglage du widget agit reellement cote front
- Verifier :
  - structure de section
  - `container` / `container-fluid`
  - alignements
  - hauteurs
  - espacements
  - variantes
  - hover
  - animation
  - debordements
  - wrappers runtime du widget
- Verifier que le rendu front reste coherent apres activation/desactivation de `PagesBuilder` :
  - le theme actif doit rester la source des couleurs, rayons, espacements et etats des boutons
  - aucun style preview/admin ne doit fuir vers le front
  - aucun style runtime `PagesBuilder` ne doit imposer une palette propre a `default`, `modern-pro` ou un theme custom

### P4 - Contenu, medias et navigation

- Verifier :
  - sources medias
  - fallback medias
  - liens
  - targets
  - labels
  - CTA
  - interactions specifiques au widget

### P5 - Design contract

- `useCustomDesign = off`
  - l'onglet `Design` reste ferme
  - aucun style custom parasite ne s'applique
- `useCustomDesign = on`
  - tous les champs de design agissent en preview et en front

### P6 - Regression et fermeture

- Verifier syntaxe PHP des fichiers touches
- Verifier syntaxe JS des fichiers touches
- Verifier JSON si touche
- Lancer :

```bash
bash /Users/alain/.codex/skills/flatcms-inline-zero-tolerance/scripts/strict_gate.sh
```

- Si un fichier partage `PagesBuilder` a ete modifie, revalider immediatement :
  - `Carousel`
  - `Hero`
  - `SnapCards`
  - `FeatureGrid`
- Passer le widget a `Termine`
- Attendre validation utilisateur avant `Valide`

## Correctifs partages deja identifies

Ces correctifs ne ferment pas un widget a eux seuls. Ils retirent seulement une cause de regression commune avant l'audit complet.

### Correctif partage A - Design textColor preview

- Symptom : la couleur de texte de l'onglet `Design` fonctionne cote front mais disparait ou n'est appliquee que partiellement dans le preview admin
- Cause : certains previews remettaient `color` a vide quand aucun `textStyle color` n'etait defini, ce qui ecrasait `designTextColor`
- Regle : un preview ne doit jamais vider `color` si la couleur vient du contrat `Design`
- Widgets touches par ce correctif partage :
  - `Hero`
  - `Carousel`
  - `ContactSection`
  - `ContentSplitMedia`
  - `FaqAccordion`
  - `FeatureGrid`
  - `LogoCloud`
  - `NewsletterSection`
  - `PricingPlans`
  - `TestimonialCards`
  - `VideoPlayer`
- Attention :
  - ce correctif ne remplace pas `P2`, `P3` ni `P5`
  - chaque widget doit quand meme etre verifie champ par champ

### Correctif partage B - Design visual parity

- Symptom : l onglet `Design` n utilisait pas exactement les memes primitives visuelles que `Reglages de section`
- Causes fermees :
  - surcouche CSS locale `Design` qui forcait :
    - un fond d input different
    - une bordure confondue avec le fond
    - des color pickers en `36px`
    - un `gap` de color row different
  - le contrat partage etait donc casse visuellement alors que les autres onglets utilisaient deja la bonne primitive
- Regle : `Design` doit reutiliser les memes controles que `Section`
  - meme fond d input
  - meme bordure
  - meme taille de color picker
  - meme spacing de row
  - meme slider `pb-layout-range`
- Verification runtime reelle faite sur la page test pour :
  - `VideoPlayer`

### Correctif partage C - Isolation du theme actif

- Symptom : apres bascule vers `default`, le front avec `PagesBuilder` actif conserve des comportements visuels venant de `modern-pro custom`
- Causes :
  - fallbacks CSS `PagesBuilder` codes avec une palette typique d un autre theme
  - styles de liens riches appliques aux liens bouton `.btn` ou `.pb-btn`
  - layout natif `.prose-action-links` perdu quand le contenu est rendu par le widget `Text`
- Regles :
  - `PagesBuilder` ne doit jamais contenir de fallback visuel lie a un theme nomme
  - les fallbacks doivent etre neutres et seulement de secours si aucune variable du theme actif n existe
  - les profils explicitement scopes, par exemple `data-frontend-theme`, restent autorises pour le preview admin
  - un profil scope ne doit jamais devenir le fallback global du front ou d un autre theme
  - `modern-pro custom` doit etre servi par les variables et CSS du theme actif, pas par des valeurs codees dans `PagesBuilder`
  - les liens riches doivent exclure explicitement `.btn`, `.btn-*`, `.pb-btn` et `.pb-btn-*`
- Verification obligatoire :
  - `default` actif + `PagesBuilder` actif
  - `modern-pro custom` actif + `PagesBuilder` actif
  - preview admin alignee sur le front pour les CTA
  - `LogoCloud`
  - `FaqAccordion`
  - `FeatureGrid`
  - `Heading`
  - `Hero`
  - `PricingPlans`
  - `SnapCards`
  - `StatsSection`
  - `TestimonialCards`
  - `Text`
  - `Carousel`
  - `ContactSection`
  - `ContentSplitMedia`
  - `NewsletterSection`
- Etat :
  - input `Design` = `rgb(30, 41, 59)`
  - color picker `Design` = `30px`
  - row `Design` = `44px minmax(0, 1fr) auto` avec `gap 8px`
  - onglet `Design` visible sur tous les widgets presents ci-dessus

### Correctif partage D - Resolution des vignettes medias en inspecteur

- Symptom : dans l onglet `Medias`, plusieurs widgets affichent une vignette vide ou incorrecte alors qu un media est bien choisi
- Cause :
  - les lignes repeater image de l inspecteur utilisaient la valeur brute stockee (`images/...`) comme `img.src`
  - contrairement au preview media standard, elles ne passaient pas par `resolveMediaSrc()`
  - en admin, un chemin relatif brut ne pointe donc pas toujours vers la vraie ressource
- Regle :
  - toute vignette media image dans l inspecteur doit toujours resoudre la source via `resolveMediaSrc()`
  - la primitive partagee `createBuilderMediaPickerGridRow` doit accepter un resolver partage
  - les fallbacks locaux de `PagesBuilder` doivent appliquer la meme resolution
- Widgets impactes :
  - `Carousel`
  - `SnapCards`
  - `LogoCloud`
  - `TestimonialCards`
  - tous les widgets qui passent par les repeater media partages de `PagesBuilder`

## Validation utilisateur groupee - Widgets de contenu

Validee le `2026-05-31`.

Decision :

- les widgets de categorie `content` sont consideres comme fermes cote audit
- la validation utilisateur couvre :
  - parite `preview` / `front`
  - comportement des reglages
  - contrat des onglets de l inspecteur

Widgets concernes :

- `LogoCloud`
- `ContactSection`
- `ContentSplitMedia`
- `NewsletterSection`
- `TestimonialCards`
- `Hero`
- `FeatureGrid`
- `SnapCards`
- `PricingPlans`
- `FaqAccordion`
- `StatsSection`
- `Text`
- `Heading`

Regle :

- ne pas rouvrir ces widgets au titre de l audit `content` sauf regression reelle constatee
- le prochain lot actif est le lot `media`
- le lot `media` comprend maintenant :
  - `VideoPlayer`
  - `Carousel`
  - `Image`

## Audit des primitives communes

Cette section sert a identifier ce qui doit etre refactorise au niveau des primitives partagees de `PagesBuilder`, au lieu de corriger widget par widget.

Regle :

- ne pas toucher a ces chantiers tant qu un pattern commun n est pas prouve sur plusieurs widgets
- si une variation reste trop specifique, la laisser widget-locale
- si un comportement est deja commun dans `PagesBuilder`, l etendre a la primitive plutot que de dupliquer un override CSS/JS

### Etat de l audit

Audit structurel repasse le 2026-05-26 sur :

- `Definition.php` de tous les widgets officiels
- primitives partagees dans :
  - `app/Extensions/PagesBuilder/Assets/js/pages-builder.js`
  - `app/Extensions/PagesBuilder/Assets/css/pages-builder.css`

Constat :

- le socle commun de l inspecteur est deja fort sur :
  - `pb-layout-choice`
  - `pb-align-control`
  - `pb-layout-range`
  - `pb-color-row`
  - `pb-switch-control`
  - `pb-textstyle-panel`
  - `pb-field-media-layout`
  - `pb-repeater-row`
- mais plusieurs patterns restent encore partiellement special-cases dans le CSS partage au lieu d etre vraiment promus au niveau primitive

### Primitive A - Design schema-driven

Priorite : Haute
Etat : En cours avance

Constat structurel :

- `17` widgets exposent :
  - `useCustomDesign`
  - `designSurfaceColor`
  - `designBorderWidth`
  - `designBorderColor`
  - `designRadius`
- `16` widgets exposent aussi :
  - `designTextColor`
- `4` widgets exposent aussi :
  - `designBorderStyle`
  - `designShadow`
- `1` widget expose aussi :
  - `designOverlayColor`
  - `designOverlayOpacity`

Ce qui peut encore etre promu dans la primitive :

- ordre des champs `Design` determine par schema commun, pas seulement par CSS cible
- paires verticales stables :
  - `Fond du widget` -> `Angles arrondis`
  - `Couleur du contour` -> `Epaisseur du contour`
  - `Couleur de l overlay` -> `Opacite de l overlay`
- rendu commun des selects `Design` en boutons de choix visuels
- gestion native des cas absents :
  - sans `designTextColor`
  - sans `designBorderStyle`
  - sans `designShadow`
  - sans overlay

Widgets impactes :

- pratiquement tout le catalogue officiel sauf variations mineures par widget

Decision :

- poursuivre les ajustements `Design` au niveau de la primitive partagee
- eviter tout nouveau CSS widget-local pour un simple probleme d ordre ou de densite dans `Design`
- Ferme au 2026-05-26 dans la couche partagee :
  - picker couleur embarque dans l input hex
  - paires verticales `Design` compactees
  - `Style du contour` et `Ombre` rendus comme `choice controls`
- Reste ouvert :
  - remplacer progressivement le ciblage CSS par cle de champ par un schema plus declaratif si besoin reel apparait
  - ne pas ajouter `Taille du texte` sans contrat preview/front complet

### Primitive B - Low-cardinality selects

Priorite : Haute
Etat : En cours avance

Constat structurel :

- `15` widgets ont `layout.align`
- `11` widgets ont `layout.variant`
- plusieurs widgets ont des `select` courts dans `layout`, `navigation`, `design` :
  - `preload`
  - `skin`
  - `transition`
  - `presentationModel`
  - `mediaPosition`
  - `ratio`
  - `textVerticalAlign`
  - `mediaFit`
  - `target`
  - `primaryTarget`
  - `secondaryTarget`

Ce qui peut encore etre promu dans la primitive :

- regle commune :
  - tout `select` de `2` a `5` options lisibles devient un `choice control`
- largeur auto par nombre d options conservee dans la primitive
- variantes de labels longues gerees au niveau de la primitive au lieu d overrides widget par widget

Widgets impactes :

- `Hero`
- `ContentSplitMedia`
- `VideoPlayer`
- `LogoCloud`
- `Carousel`
- `StatsSection`
- `FaqAccordion`
- `TestimonialCards`
- `SnapCards`
- `FeatureGrid`
- `PricingPlans`
- autres widgets avec `target` ou `variant`

Decision :

- continuer a tirer les `select` courts vers `pb-layout-choice`
- reduire les overrides widget-specifiques la ou le contrat partage suffit
- Ferme au 2026-05-26 dans la couche partagee :
  - les `select` du groupe `Design` avec faible cardinalite basculent maintenant automatiquement en `choice controls`
- Reste ouvert :
  - etendre uniquement les cas encore sains hors `Design`
  - ne pas convertir les `select` dynamiques ou longs (`formSlug`, listes variables, etc.)

### Primitive C - Navigation CTA matrices

Priorite : Haute

Constat structurel :

- plusieurs widgets exposent un contrat CTA voisin mais pas encore unifie :
  - `Hero`
  - `ContentSplitMedia`
  - `FeatureGrid`
  - `PricingPlans`
  - `Carousel`
  - `TestimonialCards`

Patterns reperes :

- `switch`
- `label`
- `url`
- `target`
- `align`
- parfois `variant`

Ce qui peut encore etre promu dans la primitive :

- scaffold commun de ligne CTA
- ligne primaire / secondaire standard
- ligne alignement seule
- support multi-CTA ou repeater simple

Risque :

- ne pas reintroduire un editeur custom casse dans `pages-builder.js`
- ne mutualiser que si la structure DOM et les callbacks sont strictement alignes

Decision :

- chantier commun possible
- a faire seulement apres la fermeture des widgets qui utilisent deja ces lignes

### Primitive D - Media field tiers

Priorite : Moyenne

Constat structurel :

- plusieurs widgets ont besoin d un meme contrat visuel de preview media dans l inspecteur :
  - preview image simple
  - preview video/poster
  - preview fichier
  - shell compact vs shell large

Ce qui reste trop local aujourd hui :

- hero background preview
- content/media preview compacte
- video poster preview
- galleries ou listes d assets

Ce qui peut encore etre promu dans la primitive :

- tiers de preview media :
  - `compact`
  - `standard`
  - `wide`
- ratio declaratif
- hauteur max declarative

Decision :

- possible refactor commun plus tard
- ne pas lancer tant que les widgets media ne sont pas tous valides visuellement

### Primitive E - Advanced text_style blocks

Priorite : Moyenne

Constat structurel :

- le groupe `Avances` contient une masse de `text_style` communs :
  - `titleTextStyle` sur `12` widgets
  - `subtitleTextStyle` sur `8` widgets
  - `featureTextStyle` sur `4` widgets
  - plusieurs styles `eyebrow`, `proof`, `question`, `answer`, `quote`, etc.

Ce qui peut encore etre promu dans la primitive :

- ordre stable des cartes `text_style`
- meme densite de toolbar
- meme preview sample
- meme logique de propagation preview des couleurs et alignements

Ce qui doit rester widget-local :

- badge inline
- row-reverse des puces
- realignement de sous-structures HTML specifiques

Decision :

- continuer a mutualiser la couche toolbar / preview sample
- laisser le comportement de structure au renderer/preview du widget

### Primitive F - Repeater row scaffolds

Priorite : Moyenne

Constat :

- `pb-repeater-row` existe deja, mais plusieurs families restent special-case :
  - `FeatureGrid`
  - `Carousel`
  - `PricingPlans`
  - `legal links`
  - medias / icones

Ce qui peut encore etre promu :

- gabarits communs :
  - repeater texte simple
  - repeater media + actions
  - repeater navigation CTA
  - repeater legal links

Decision :

- utile pour reduire le CSS special-case
- pas prioritaire avant la stabilisation complete des widgets fonctionnels

### Primitive G - Toggle row compaction

Priorite : Basse

Constat :

- beaucoup de `checkbox` restent sur le contrat standard et c est sain
- certains groupes enchainent cependant plusieurs switches qui pourraient etre compactes plus lisiblement

Ce qui peut etre mutualise plus tard :

- ligne de switches denses
- largeur minimale commune
- labels courts standardises

Decision :

- pas prioritaire
- a traiter seulement si le manque de place revient comme probleme apres fermeture des gros chantiers

## Ordre recommande pour les prochaines passes primitives

1. Fermer `Design schema-driven`
2. Etendre proprement `low-cardinality selects`
3. Stabiliser `Navigation CTA matrices`
4. Reprendre `Advanced text_style blocks`
5. Ouvrir ensuite seulement `Media field tiers`
6. Finir par `Repeater row scaffolds`

## Ecarts LTS intentionnels documentes

Ces ecarts ne sont pas des regressions de migration tant qu ils restent assumes, documentes et verifies en preview/front.

Verification structurelle repassee le 2026-05-26 :

- comparaison `Definition.php` sur le catalogue officiel `updates` vs LTS
- perimetre controle :
  - ordre de champs
  - presence / absence de champs
  - `group`
  - `type`
  - `control`
  - cardinalite des `options`
- resultat :
  - aucune derive structurelle restante hors les ecarts LTS ci-dessous

### ContentSplitMedia

- ecart volontaire vs `updates` :
  - `textVerticalAlign`
- raison :
  - ajout LTS pour l alignement vertical du contenu

### NewsletterSection

- ecart volontaire vs `updates` :
  - `newsletterFormSlug`
- raison :
  - integration LTS avec le catalogue de formulaires newsletter

### VideoPlayer

- ecarts volontaires vs `updates` :
  - `ambientMode`
  - `designOverlayColor`
  - `designOverlayOpacity`
- raison :
  - mode video decorative / ambiante
  - overlay de surface du widget pour les usages media immersifs

## Fermeture LogoCloud

Statut : `Valide`

Points fermes sur la LTS :

- parite `Definition.php` / `Renderer.php` / preview avec `updates`
- wrappers frontend `data-block-id` de `PagesBuilder` retablis pour que les styles runtime du widget s'appliquent
- contrat `container-fluid` reflechi cote front depuis `PagesBuilder`
- reglage `Mise en page` reflete cote front :
  - `presentationModel`
  - `columns`
  - `logoHeight`
  - `gap`
  - `animationSpeed`
  - `widgetHeight`
  - `align`
  - `variant`
  - `showHeader`
  - `showLabels`
  - `grayscale`
- correction du hover couleur en mode :
  - `Presentation classique`
  - `Logos en niveaux de gris`

## Historique VideoPlayer

Etat historique : ancienne reouverture avant stabilisation finale

### Reouverture apres verification reelle

- `VideoPlayer` avait ete passe trop tot a `Termine`
- le contrat officiel `fullscreen` de `updates` est bien un bouton de plein ecran visiteur
- le vrai ecart restant est le preview admin :
  - le shell ne passait pas de facon fiable en etat `is-enhanced`
  - la parite preview/front restait donc partielle sur les controles du player
- action en cours :
  - reprise de l initialisation preview locale avec tentative de re-initialisation bornee tant que le runtime n a pas encore accroche le shell

## Audit ContactSection

Statut : `Valide`

### P0 - Source canonique

Etat : `Valide avec ecarts LTS documentes`

Base comparee :

- [Definition.php](/Applications/MAMP/htdocs/updates.flat-cms.fr/app/Widgets/ContactSection/Definition.php)
- [Renderer.php](/Applications/MAMP/htdocs/updates.flat-cms.fr/app/Widgets/ContactSection/Renderer.php)
- [contact-section.css](/Applications/MAMP/htdocs/updates.flat-cms.fr/app/Widgets/ContactSection/Assets/css/contact-section.css)
- [contact-section-preview.js](/Applications/MAMP/htdocs/updates.flat-cms.fr/app/Widgets/ContactSection/Assets/js/contact-section-preview.js)

Inventaire :

- meme inventaire de fichiers entre `updates` et la LTS
- pas d asset runtime `contact-section.js` cote officiel

Ecarts identifies et deja documentes cote LTS :

- `Definition.php`
  - passage du champ `contactFormSlug` de `text` vers `select`
  - options alimentees par `PageBuilderContactFormCatalogService`
  - default dynamique au lieu de `contact-main` fixe
  - retrait dans `preview_css` de l injection `module_asset('Contact', 'css/contact-front.css')`
- `Renderer.php`
  - i18n via `PageBuilderWidgetLocaleService`
  - fallback de rendu formulaire natif quand le shortcode ne rend rien
  - resolution du formulaire via `PageBuilderContactFormCatalogService`
  - injection de variables CSS formulaire au niveau widget quand `Design` est actif
- `contact-section.css`
  - ajout du contrat local `--pb-contact-form-*`
  - surcouche formulaire locale pour aligner preview/front sans toucher le core
- `contact-section-preview.js`
  - correctif partage `Design -> textColor preview`
  - preview formulaire statique inertie locale
  - synchronisation des variables `--pb-contact-form-*`

Conclusion `P0` :

- le widget n est pas en derive accidentelle
- les ecarts sont des adaptations LTS intentionnelles pour :
  - le catalogue dynamique de formulaires
  - le fallback formulaire natif
  - la parite preview/front du formulaire
- les validations visuelles preview/front sont considerees comme fermees

## Audit ContentSplitMedia

Statut : `En cours`

### P0 - Source canonique

Etat : `Valide avec ecarts LTS documentes`

Base comparee :

- [Definition.php](/Applications/MAMP/htdocs/updates.flat-cms.fr/app/Widgets/ContentSplitMedia/Definition.php)
- [Renderer.php](/Applications/MAMP/htdocs/updates.flat-cms.fr/app/Widgets/ContentSplitMedia/Renderer.php)
- [content-split-media.css](/Applications/MAMP/htdocs/updates.flat-cms.fr/app/Widgets/ContentSplitMedia/Assets/css/content-split-media.css)
- [content-split-media-preview.js](/Applications/MAMP/htdocs/updates.flat-cms.fr/app/Widgets/ContentSplitMedia/Assets/js/content-split-media-preview.js)

Inventaire :

- meme inventaire de fichiers entre `updates` et la LTS
- meme socle widget local complet :
  - `Definition.php`
  - `Renderer.php`
  - `Assets/css/content-split-media.css`
  - `Assets/js/content-split-media-preview.js`
  - `widget.php`
  - `widget.json`
  - `render.php`
  - `preview.js`

Ecarts identifies et assumes cote LTS :

- `Definition.php`
  - ajout du champ `textVerticalAlign`
  - default `textVerticalAlign = center`
- `Renderer.php`
  - i18n via `PageBuilderWidgetLocaleService`
  - CTA publies avec les classes theme LTS :
    - `btn btn-primary pb-btn pb-btn-primary`
    - `btn btn-ghost pb-btn pb-btn-ghost`
  - support runtime de `textVerticalAlign` :
    - `pb-content-split-media-text-valign-top`
    - `pb-content-split-media-text-valign-center`
    - `pb-content-split-media-text-valign-bottom`
- `content-split-media-preview.js`
  - meme support `textVerticalAlign`
  - correctif partage `Design -> textColor preview`
  - CTA preview alignes sur les classes theme LTS
- `content-split-media.css`
  - support CSS de `textVerticalAlign`
  - shell media stabilise pour la parite preview/front :
    - `width: 100%`
    - `aspect-ratio: 4 / 3`
    - `min-height` reduit
  - support explicite du fit image/video sur le shell media stabilise

Conclusion `P0` :

- le widget n est pas en derive accidentelle par rapport a `updates`
- les ecarts LTS sont intentionnels et deja relies a des besoins de parite preview/front
- la suite logique est `P1`

### P1 - Contrat inspecteur

Etat : `Valide`

- onglets conformes :
  - `Contenu`
  - `Navigation`
  - `Medias`
  - `Mise en page`
  - `Avances`
  - `Design`
- aucun champ parasite detecte
- separation `Contenu` / `Navigation` respectee
- types de controle conformes a `updates`, hors ecart LTS deja documente :
  - `textVerticalAlign`
- conditions d affichage coherentes :
  - `eyebrow` depend de `showEyebrow`
  - `body` depend de `showBody`
  - `featureItems` depend de `showFeatures`
  - `primary*` depend de `showPrimaryCta`
  - `secondary*` depend de `showSecondaryCta`
  - `image*` depend de `mediaKind = image`
  - `video*`, `preload`, `autoplay`, `loop`, `muted` dependent de `mediaKind = video`
  - tous les champs `Design` dependent de `useCustomDesign = on`, hors le switch lui-meme
- options critiques confirmees :
  - `mediaPosition` : `left`, `right`
  - `ratio` : `balanced`, `content-wide`, `media-wide`
  - `align` : `left`, `center`, `right`
  - `textVerticalAlign` : `top`, `center`, `bottom`
  - `variant` : `subtle`, `strong`, `dark`
  - `mediaFit` : `cover`, `contain`
  - `preload` : `metadata`, `auto`, `none`
  - `designBorderStyle` : `inherit`, `none`, `solid`, `dashed`, `dotted`
  - `designShadow` : `inherit`, `none`, `soft`, `medium`, `strong`

Conclusion `P1` :

- le contrat inspecteur est sain
- la suite logique est `P1B`

### Verifications runtime deja confirmees

Etat : `Partiel, sans fermeture de passe`

- page test active :
  - `20260521210215_1df40685`
- bloc front controle :
  - `pb_mpgib8i2_k1sa6r`
- publication front reelle confirmee sur :
  - [http://localhost:8888/fr-FR/page/nouvelle-page](http://localhost:8888/fr-FR/page/nouvelle-page)
- structure publiee confirmee :
  - `pb-content-split-media`
  - `pb-content-split-media-variant-*`
  - `pb-content-split-media-align-*`
  - `pb-content-split-media-text-valign-*`
  - `pb-content-split-media-media-left|right`
  - `pb-content-split-media-ratio-balanced|content-wide|media-wide`
  - `pb-content-split-media-fit-cover|contain`
  - `pb-content-split-media-has-media`
- CTA front confirmes avec le contrat theme LTS :
  - `btn btn-primary pb-btn pb-btn-primary`
  - `btn btn-ghost pb-btn pb-btn-ghost`

Correctifs deja fermes sur ce widget pendant l audit :

- ratio CSS corrige quand `mediaPosition = left`
  - avant :
    - `media-wide` agrandissait encore le contenu
  - maintenant :
    - `media-left + media-wide` inverse bien le ratio
    - `media-left + content-wide` inverse aussi correctement le ratio
- `Navigation` alignee visuellement sur `Hero`
  - ligne 1 :
    - `showPrimaryCta`
    - `primaryLabel`
    - `primaryUrl`
    - `primaryTarget`
  - ligne 2 :
    - `showSecondaryCta`
    - `secondaryLabel`
    - `secondaryUrl`
    - `secondaryTarget`
  - ligne 3 :
    - `align`
- `Avances`
  - `Style du sur-titre` :
    - alignement reel en preview et en front
    - couleur refletee en preview
  - `Style des points cles` :
    - alignement reel en preview et en front
    - couleur refletee en preview
    - puces calees sur `currentColor`
    - puces deplacees a droite quand le texte est aligne a droite

Point restant pour fermeture honnete :

- `P1B`
  - premier clic utile sur tous les champs
  - tous les onglets
  - verification a faire en UI reelle authentifiee

## Audit TestimonialCards

Statut : `En cours`

### P0 - Source canonique

Etat : `Valide avec ecarts LTS documentes`

Base comparee :

- [Definition.php](/Applications/MAMP/htdocs/updates.flat-cms.fr/app/Widgets/TestimonialCards/Definition.php)
- [Renderer.php](/Applications/MAMP/htdocs/updates.flat-cms.fr/app/Widgets/TestimonialCards/Renderer.php)
- [testimonial-cards.css](/Applications/MAMP/htdocs/updates.flat-cms.fr/app/Widgets/TestimonialCards/Assets/css/testimonial-cards.css)
- [testimonial-cards-preview.js](/Applications/MAMP/htdocs/updates.flat-cms.fr/app/Widgets/TestimonialCards/Assets/js/testimonial-cards-preview.js)
- [testimonial-cards.js](/Applications/MAMP/htdocs/updates.flat-cms.fr/app/Widgets/TestimonialCards/Assets/js/testimonial-cards.js)

Inventaire :

- meme inventaire de fichiers entre `updates` et la LTS :
  - `Definition.php`
  - `Renderer.php`
  - `Assets/css/testimonial-cards.css`
  - `Assets/js/testimonial-cards-preview.js`
  - `Assets/js/testimonial-cards.js`
  - `widget.php`
  - `widget.json`
  - `render.php`
  - `preview.js`
  - `Languages/*.json`

Ecarts identifies et assumes cote LTS :

- `Renderer.php`
  - i18n via `PageBuilderWidgetLocaleService`
- `testimonial-cards.css`
  - adaptation du mode `container-fluid` au contrat runtime `PagesBuilder`
  - la LTS suit le wrapper fluide deja corrige au lieu du debordement `100vw` de `updates`
- `testimonial-cards-preview.js`
  - correctif partage `Design -> textColor preview`
- `testimonial-cards.js`
  - egalisation de hauteur renforcee :
    - `ResizeObserver`
    - attente `document.fonts.ready`
    - re-compute borne apres rendu
  - raison :
    - stabiliser la hauteur reelle des cartes quand les fontes et medias finalisent apres le premier layout

Conclusion `P0` :

- pas de derive structurelle accidentelle restante
- les ecarts constates sont des adaptations LTS deja validees fonctionnellement

### P1 - Contrat inspecteur

Etat : `Valide`

- `Definition.php` aligne le contrat inspecteur officiel
- pas de derive detectee sur :
  - groupes
  - types
  - controles
  - options
  - ordre des champs

Conclusion `P1` :

- le contrat inspecteur est sain
- la suite logique est `P1B`

## Audit VideoPlayer

Statut : `Termine`

### P0 - Source canonique

Etat : `Valide`

- `Definition.php` aligne bien le contrat officiel `updates`
- `Renderer.php` aligne bien le contrat officiel `updates`
- `Assets/js/video-player.js` aligne bien le runtime officiel
- `Assets/css/video-player.css` aligne bien le contrat visuel officiel
- `preview.js` reste un simple proxy vers `js/video-player-preview.js`, comme dans `updates`
- seul ecart connu par rapport a `updates` :
  - correctif partage `Design -> textColor preview`
  - attendu et documente dans ce fichier

### P1 - Contrat inspecteur

Etat : `Valide`

- onglets conformes :
  - `Contenu`
  - `Medias`
  - `Mise en page`
  - `Avances`
  - `Design`
- aucun champ parasite detecte
- mapping confirme entre `Definition.php` et le tri de l inspecteur dans `pages-builder.js`
- champs confirmes :
  - `title`
  - `subtitle`
  - `videoUrl`
  - `posterImage`
  - `height`
  - `preload`
  - `align`
  - `skin`
  - `showHeader`
  - `autoplay`
  - `loop`
  - `muted`
  - `useCustomDesign`
  - `designSurfaceColor`
  - `designTextColor`
  - `designBorderStyle`
  - `designBorderWidth`
  - `designBorderColor`
  - `designRadius`
  - `designShadow`
  - `titleTextStyle`
  - `subtitleTextStyle`

### P2/P3 - Verifications runtime deja confirmees

Etat : `Partiel`

- page test active :
  - `20260521210215_1df40685`
- bloc teste :
  - `pb_mpgiok7d_izucss`
- front publie bien les reglages actifs du bloc test :
  - `autoplay`
  - `loop`
  - `muted`
  - `preload`
  - `align`
  - `skin`
  - `showHeader`
- le preview reconstruit depuis `video-player-preview.js` produit la meme structure HTML que le front pour cet etat simple
- une matrice CLI preview/front a aussi ete verifiee sur :
  - `base`
  - `hidden_header`
  - `design_on`
  - `empty`
- points confirmes sur cette matrice :
  - classes `align`
  - classes `skin`
  - presence/absence du header
  - attributs `autoplay`
  - attributs `loop`
  - attributs `muted`
  - attribut `preload`
  - presence du poster
  - presence du placeholder
  - hauteur runtime
  - surface `Design`
  - contour `Design`
  - radius `Design`
  - ombre `Design`
  - couleur du texte `Design`
- aucun ecart de wiring detecte a ce stade entre :
  - handler preview
  - renderer front
  - bloc builder stocke

### Fermeture agent

Etat : `Termine`

- `P1B` verifie en UI reelle sur les controles critiques :
  - `title`
  - `subtitle`
  - `videoUrl`
  - `posterImage`
  - `height`
  - `preload`
  - `align`
  - `showHeader`
- `P2` verifie en UI reelle sur le preview :
  - surface `Design`
  - contour `Design`
  - radius `Design`
  - ombre `Design`
  - couleur de texte `Design`
- `P3` recontrole sur le front publie :
  - `preload`
  - `autoplay`
  - `loop`
  - `muted`
  - `align`
  - `skin`
  - `showHeader`
- `P4` couvert pour ce widget :
  - video source
  - poster
  - placeholder
- `P5` valide :
  - `Design off` sans style parasite
  - `Design on` applique bien les styles en preview et en front
- `P6` valide :
  - aucun fichier widget modifie
  - `strict gate` passe

## Audit Carousel

Statut : `En cours`

### P0 - Source canonique

Etat : `Valide`

Inventaire :

- meme inventaire de fichiers entre `updates` et la LTS :
  - `Definition.php`
  - `Renderer.php`
  - `Assets/css/carousel.css`
  - `Assets/js/carousel.js`
  - `Assets/js/carousel-preview.js`
  - `widget.php`
  - `widget.json`
  - `render.php`
  - `preview.js`
  - `Languages/*.json`

Ecarts identifies et assumes cote LTS :

- `Renderer.php`
  - i18n via `PageBuilderWidgetLocaleService`
  - CTA de slide rendus avec `btn btn-primary` en plus de `pb-btn pb-btn-primary`
  - raison :
    - aligner les boutons front avec le systeme bouton du theme actif
- `carousel-preview.js`
  - correctif partage `Design -> textColor preview`
  - CTA de slide rendus avec `btn btn-primary` en plus de `pb-btn pb-btn-primary`
  - raison :
    - garder la parite preview/front sur les boutons et la couleur `Design`
- `render.php`
  - namespace adapte a `app/Extensions/PagesBuilder`
- `widget.php`
  - namespace adapte a `app/Extensions/PagesBuilder`

Conclusion `P0` :

- pas de derive structurelle accidentelle restante
- les ecarts constates sont des adaptations LTS deja coherentes avec les correctifs partages validates

### P1 - Contrat inspecteur

Etat : `Valide`

- `Definition.php` reste aligne avec le contrat inspecteur officiel `updates`
- pas de derive detectee sur :
  - groupes
  - types
  - controles
  - options
  - ordre des champs
- champs et blocs attendus confirmes :
  - `title`
  - `mediaFullBleed`
  - `images`
  - `titles`
  - `texts`
  - `links`
  - `showIndicators`
  - `showArrows`
  - `indicatorStyle`
  - `arrowStyle`
  - `buttonEnableds`
  - `buttonLabels`
  - `buttonTargets`
  - `buttonAligns`
  - `buttonLabel`
  - `autoplay`
  - `autoplayDelay`
  - `loop`
  - `height`
  - `transition`
  - `align`
  - `target`
  - `useCustomDesign`
  - `designSurfaceColor`
  - `designTextColor`
  - `designBorderStyle`
  - `designBorderWidth`
  - `designBorderColor`
  - `designRadius`
  - `designShadow`

Conclusion `P1` :

- le contrat inspecteur est sain
- la suite logique est `P1B`

## Audit Image

Statut : `En cours`

### P0 - Source canonique

Etat : `Valide`

- la migration structurelle `PagesBuilder` est en place
- le widget expose bien son contrat local via :
  - `Definition.php`
  - `Renderer.php`
  - `render.php`
  - `widget.php`
  - `widget.json`
  - `Languages/*.json`
- compatibilite legacy preservee dans le renderer pour :
  - `alt`
  - `width`

### P1 - Contrat inspecteur

Etat : `Valide`

- onglets disponibles et cohérents :
  - `Médias`
  - `Mise en page`
  - `Design`
- champs confirms :
  - `src`
  - `altText`
  - `align`
  - `widthPercent`
  - `useCustomDesign`
  - `designSurfaceColor`
  - `designBorderStyle`
  - `designBorderWidth`
  - `designBorderColor`
  - `designRadius`
  - `designShadow`
- correctif structurel applique le `2026-06-01` :
  - declaration du preview admin via `assets.preview_js`
  - chargement du handler `js/widgets/image-preview.js`
  - suppression du fallback legacy sans support `Design`

Conclusion `P1` :

- le contrat inspecteur est sain
- la prochaine passe utile est `P2` pour finir la matrice preview/front :
  - `align`
  - `widthPercent`
  - `Design`

## Tableau de suivi

| Ordre | Widget | Passe active | Derniere passe validee | Prochaine passe exacte | Notes |
| --- | --- | --- | --- | --- | --- |
| 1 | LogoCloud | Valide | P6 | Aucune | Widget ferme |
| 2 | VideoPlayer | Valide | P6 | Aucune | Validation utilisateur explicite |
| 3 | ContactSection | Valide | P6 | Aucune | Widget ferme |
| 4 | ContentSplitMedia | Valide | P6 | Aucune | Validation utilisateur groupee du lot content |
| 5 | NewsletterSection | Valide | P6 | Aucune | Widget ferme |
| 6 | TestimonialCards | Valide | P6 | Aucune | Validation utilisateur groupee du lot content |
| 7 | Carousel | Valide | P6 | Aucune | Validation utilisateur explicite |
| 8 | Image | En cours | P1 | P2 - matrice preview/front `align + widthPercent + Design` | Preview admin dedie recharge via `assets.preview_js` le 2026-06-01 |
| 9 | Hero | Valide | P6 | Aucune | Validation utilisateur groupee du lot content |
| 10 | FeatureGrid | Valide | P6 | Aucune | Validation utilisateur groupee du lot content |
| 11 | SnapCards | Valide | P6 | Aucune | Validation utilisateur groupee du lot content |
| 12 | PricingPlans | Valide | P6 | Aucune | Validation utilisateur groupee du lot content |
| 13 | FaqAccordion | Valide | P6 | Aucune | Validation utilisateur groupee du lot content |
| 14 | StatsSection | Valide | P6 | Aucune | Validation utilisateur groupee du lot content |
| 15 | Contact | Non traite | Aucune | P0 - source canonique | Widget forms, hors lot content/media |
| 16 | Newsletter | Non traite | Aucune | P0 - source canonique | Widget forms, hors lot content/media |
| 17 | Text | Valide | P6 | Aucune | Validation utilisateur groupee du lot content |
| 18 | Heading | Valide | P6 | Aucune | Validation utilisateur groupee du lot content |
| 19 | Spacer | Non traite | Aucune | P0 - source canonique | Widget layout, hors lot content/media |
| 20 | Divider | Non traite | Aucune | P0 - source canonique | Widget layout, hors lot content/media |

## Regle de reprise de session

Au debut de chaque nouvelle session :

1. Lire ce fichier
2. Reprendre le premier widget non `Valide`
3. Redemarrer exactement a la `Prochaine passe exacte`
4. Ne jamais sauter une passe
5. Ne jamais ouvrir un second widget tant que le widget actif n'est pas `Termine` ou `Valide`
