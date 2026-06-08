# ABOUT_CONTENT — À propos de FlatCMS

> **Contenu éditorial prêt à intégrer**
>
> Projet : FlatCMS  
> Page : À propos `fr-FR`  
> URL cible : `https://flat-cms.fr/fr-FR/a-propos/`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Brief parent : `PAGE_BRIEFS.md` — `P0-ABOUT-FR`  
> Documents associés : `README.md`, `LICENSING.md`, `CLA.md`, `TRADEMARK.md`, `SITE_ARCHITECTURE.md`, `CONTENT_STYLE_GUIDE.md`  
> Statut : première version rédactionnelle à relire, compléter et valider avant publication

---

## 1. Objectif de la page

Cette page présente :

- l’origine de FlatCMS ;
- sa mission ;
- les problèmes auxquels le projet cherche à répondre ;
- ses principes techniques ;
- son auteur et responsable ;
- son modèle open source et commercial ;
- sa gouvernance ;
- sa politique de contribution ;
- sa feuille de route ;
- ses engagements de transparence.

Elle doit renforcer la confiance sans transformer l’histoire du projet en récit promotionnel excessif.

Chaque affirmation importante doit pouvoir être reliée à :

- une version ;
- un document ;
- un dépôt ;
- une fonctionnalité réelle ;
- une décision publiée ;
- une personne clairement identifiée.

---

# 2. Métadonnées SEO

## URL canonique

```text
https://flat-cms.fr/fr-FR/a-propos/
```

## Balise `<title>`

```text
À propos de FlatCMS et de son projet open source
```

## Meta description

```text
Découvrez l’origine de FlatCMS, sa mission, son architecture, son auteur,
ses valeurs, son modèle open source et la gouvernance du projet.
```

## Directive robots

```text
index, follow, max-image-preview:large
```

## Open Graph

### `og:title`

```text
À propos de FlatCMS
```

### `og:description`

```text
L’histoire, la mission, les valeurs techniques et la gouvernance du CMS
PHP flat-file créé par Alain BROYE.
```

### `og:type`

```text
website
```

### `og:url`

```text
https://flat-cms.fr/fr-FR/a-propos/
```

### `og:image`

```text
https://flat-cms.fr/assets/images/og/a-propos-flatcms-fr-FR.webp
```

---

# 3. Hero

## Sur-titre

```text
Projet open source français
```

## H1

```text
À propos de FlatCMS
```

## Introduction

```text
FlatCMS est un CMS PHP flat-file conçu pour administrer des sites sans
serveur de base de données SQL, autour d’un cœur modulaire, d’un stockage
JSON et d’une architecture lisible.
```

```text
Le projet est développé et maintenu par Alain BROYE avec l’objectif de
proposer une base simple à déployer, compréhensible pour les développeurs
et capable d’évoluer vers des usages multilingues, modulaires et
agent-ready.
```

## Message de transparence

```text
FlatCMS est un projet indépendant en cours de structuration. Son
écosystème, sa documentation, ses composants premium et sa gouvernance
évoluent progressivement autour de la ligne stable LTS Core.
```

## CTA principal

```text
Découvrir la roadmap
```

Destination :

```text
/fr-FR/roadmap/
```

## CTA secondaire

```text
Consulter le code source
```

Destination :

```text
https://github.com/ABroye/FlatCMS
```

## Lien tertiaire

```text
Contribuer au projet
```

Destination :

```text
/fr-FR/contribuer/
```

---

# 4. La mission de FlatCMS

## H2

```text
Rendre la gestion de contenu plus simple à comprendre
```

## Texte

```text
FlatCMS part d’un constat : de nombreux sites éditoriaux n’ont pas besoin
d’une infrastructure complexe pour gérer leurs pages, leurs articles,
leurs médias et leur navigation.
```

```text
Le projet cherche à proposer un CMS dont les responsabilités restent
visibles : le cœur orchestre, les modules portent les fonctions, les
services isolent les traitements, les thèmes produisent le rendu et les
fichiers JSON conservent les données.
```

## Mission

```text
Fournir un CMS PHP natif, modulaire et documenté permettant de créer des
sites éditoriaux modernes sans dépendre d’un serveur SQL pour le stockage
principal des contenus.
```

## Conséquences concrètes

- réduire les services nécessaires à l’installation ;
- conserver une arborescence explicite ;
- faciliter la sauvegarde des contenus ;
- distinguer le Core des composants optionnels ;
- favoriser des modules indépendants ;
- documenter les contrats techniques ;
- rendre les limites du modèle visibles ;
- permettre une évolution progressive du projet.

---

# 5. Pourquoi le projet a été créé

## H2

```text
Une réponse à la complexité croissante des CMS
```

## Texte

```text
FlatCMS a été créé pour explorer une autre manière de construire un CMS :
un runtime PHP natif, un stockage par fichiers, une architecture HMVC et
une séparation forte entre les fonctionnalités.
```

Le projet cherche notamment à éviter :

- une dépendance systématique à MySQL ou MariaDB ;
- une accumulation de couches difficiles à comprendre ;
- un cœur modifié pour chaque nouvelle fonction ;
- des thèmes contenant toute la logique métier ;
- des intégrations externes dispersées dans le code ;
- une documentation séparée de la réalité du produit ;
- des promesses marketing non vérifiables.

## Problème traité

```text
Créer un site éditorial ne devrait pas obliger chaque utilisateur à
administrer une infrastructure plus complexe que son besoin réel.
```

## Positionnement

FlatCMS ne prétend pas remplacer :

- toutes les bases de données ;
- tous les CMS généralistes ;
- toutes les plateformes e-commerce ;
- tous les frameworks PHP ;
- toutes les solutions headless.

Il propose une option différente pour les projets compatibles avec un
modèle flat-file.

---

# 6. Les principes techniques

## H2

```text
Des choix techniques cohérents avec la mission
```

## H3 — PHP natif

```text
Le cœur de FlatCMS est développé en PHP natif afin de conserver une base
maîtrisée et lisible sans dépendre d’un framework applicatif lourd.
```

## H3 — Architecture HMVC

```text
Les fonctionnalités sont regroupées en modules afin de limiter le
couplage et de rendre chaque périmètre plus facile à localiser.
```

## H3 — Autoloading PSR-4

```text
Les classes utilisent des namespaces et des chemins organisés selon les
conventions PSR-4 du projet.
```

## H3 — Stockage JSON

```text
Les contenus et configurations principales sont enregistrés dans des
fichiers structurés plutôt que dans un serveur SQL.
```

## H3 — Services

```text
Les traitements transversaux, comme les licences, les données structurées,
les mises à jour ou l’intelligence artificielle, sont isolés dans des
services dédiés.
```

## H3 — Document root public

```text
La configuration recommandée expose uniquement le dossier public/ afin de
séparer les ressources web du code, des configurations et des données.
```

## CTA

```text
Explorer l’architecture de FlatCMS
```

Destination :

```text
/fr-FR/architecture/
```

---

# 7. Les valeurs du projet

## H2

```text
Les principes qui guident FlatCMS
```

## Simplicité

```text
Réduire la complexité inutile sans cacher les responsabilités réelles.
```

## Lisibilité

```text
Organiser le code et les données de manière prévisible et documentée.
```

## Modularité

```text
Ajouter une fonction dans un module ou un service plutôt que modifier
systématiquement le cœur.
```

## Transparence

```text
Distinguer ce qui est stable, optionnel, premium, expérimental ou prévu.
```

## Contrôle humain

```text
Utiliser l’automatisation et l’intelligence artificielle comme assistance,
pas comme autorité autonome sur le contenu ou le système.
```

## Portabilité

```text
Limiter les dépendances d’infrastructure lorsque le besoin ne les exige
pas.
```

## Sécurité pragmatique

```text
Réduire la surface d’exposition, appliquer le moindre privilège et ne
jamais présenter la sécurité comme absolue.
```

## Documentation

```text
Documenter les décisions, les contrats, les limites et les procédures de
restauration.
```

---

# 8. L’auteur du projet

## H2

```text
Alain BROYE
```

## Présentation courte

```text
FlatCMS est créé, développé et maintenu par Alain BROYE, auteur du projet,
titulaire des droits de première partie et responsable de la marque
FlatCMS.
```

## Rôle

- architecture du CMS ;
- développement du Core ;
- définition des modules ;
- conception de l’expérience d’administration ;
- stratégie documentaire ;
- modèle de licences ;
- identité de marque ;
- roadmap ;
- validation des contributions ;
- développement de l’écosystème premium.

## Biographie publique proposée

> À valider avant publication.

```text
Alain BROYE est ingénieur en sciences et technologies industrielles,
spécialisé dans les systèmes techniques du bâtiment, la supervision et
les environnements numériques. Il développe FlatCMS comme un projet
indépendant centré sur la simplicité, le Clean Code et la maîtrise de
l’architecture.
```

## Version plus sobre si les informations de formation ne sont pas publiées

```text
Alain BROYE est le créateur et mainteneur principal de FlatCMS. Il conçoit
le projet autour d’une architecture PHP native, d’un stockage JSON et de
modules indépendants, avec une attention particulière portée au Clean
Code, à la documentation et à l’évolution agent-ready du CMS.
```

## Informations à confirmer avant publication

- intitulé professionnel public ;
- diplôme et établissement à mentionner ;
- spécialisation publique ;
- ville ou pays à afficher ;
- photographie ;
- profil LinkedIn officiel ;
- profil GitHub officiel ;
- adresse de contact publique ;
- date de début du projet.

---

# 9. Une distribution française et internationale

## H2

```text
Un projet né en France, conçu pour plusieurs langues
```

## Texte

```text
FlatCMS est développé en France et utilise le français comme locale
éditoriale source du projet officiel.
```

Le CMS prévoit également les locales :

```text
en-US
de-DE
es-ES
it-IT
pt-PT
```

## Objectif

- rendre la documentation accessible ;
- publier les pages produit dans plusieurs langues ;
- maintenir des métadonnées locales ;
- relier les traductions ;
- préparer un écosystème international ;
- conserver une terminologie cohérente.

## Prudence

```text
La disponibilité d’une locale dans le CMS ne signifie pas que toute la
documentation est immédiatement traduite et maintenue dans cette langue.
```

---

# 10. LTS Core

## H2

```text
Une ligne stable séparée des expérimentations
```

## Texte

```text
FlatCMS LTS Core représente la ligne stable open source du projet.
```

Son objectif est de conserver :

- un runtime stable ;
- des fonctions éditoriales essentielles ;
- un installateur ;
- une architecture documentée ;
- des contrats prévisibles ;
- un cycle de maintenance contrôlé.

## Séparation

Les éléments suivants peuvent rester dans des lignes distinctes :

- fonctions expérimentales ;
- outils d’authoring instables ;
- composants commerciaux ;
- intégrations en développement ;
- prototypes d’agents ;
- outils de génération de release.

## Principe

```text
Une fonctionnalité n’entre pas dans le LTS Core uniquement parce que son
code existe. Elle doit être stabilisée, testée, documentée et compatible
avec le contrat de la distribution.
```

---

# 11. Modèle open source et commercial

## H2

```text
Un cœur open source et des composants premium distincts
```

## LTS Core

```text
Le code de première partie du LTS Core est placé sous GNU AGPL v3 ou
ultérieure, sauf indication différente dans l’en-tête d’un fichier.
```

## Composants premium

```text
Certains composants, notamment PagesBuilder, MenuBuilder et
FooterBuilder, peuvent être distribués sous une licence commerciale
distincte.
```

## Objectif du modèle

Le modèle cherche à :

- préserver un cœur accessible ;
- financer le développement ;
- maintenir la documentation ;
- proposer des outils avancés ;
- financer le support ;
- conserver une gouvernance indépendante.

## Principe de transparence

Chaque composant doit indiquer :

- son statut ;
- sa licence ;
- sa version ;
- son prix éventuel ;
- sa compatibilité ;
- ses conditions de support.

## CTA

```text
Comprendre les licences FlatCMS
```

Destination :

```text
/fr-FR/licences/
```

---

# 12. Les Builders premium

## H2

```text
Des outils visuels séparés du cœur
```

## PagesBuilder

```text
Construction de pages à partir de sections et de widgets.
```

## MenuBuilder

```text
Construction de menus avancés et de méga-menus.
```

## FooterBuilder

```text
Construction d’un footer modulaire.
```

## Règle produit

```text
Une licence Builder ne doit jamais conditionner le rendu public déjà
construit.
```

Si un abonnement expire :

- les pages restent affichées ;
- les menus restent affichés ;
- les footers restent affichés ;
- les données restent conservées ;
- les mises à jour et services commerciaux peuvent être suspendus.

## CTA

```text
Voir les tarifs des Builders
```

Destination :

```text
/fr-FR/tarifs/
```

---

# 13. Gouvernance actuelle

## H2

```text
Une gouvernance maintenue par l’auteur du projet
```

## Situation actuelle

```text
FlatCMS est actuellement dirigé et maintenu par Alain BROYE.
```

Les décisions principales portent sur :

- architecture ;
- périmètre du LTS Core ;
- roadmap ;
- sécurité ;
- compatibilité ;
- licences ;
- marque ;
- contributions ;
- releases ;
- composants premium.

## Avantages

- vision cohérente ;
- décisions rapides ;
- responsabilité identifiable ;
- architecture unifiée ;
- contrôle des releases.

## Risques reconnus

- dépendance à un mainteneur principal ;
- charge de support ;
- rythme des contributions ;
- validation centralisée ;
- continuité du projet.

## Évolution possible

La gouvernance pourra évoluer avec :

- mainteneurs de modules ;
- relecteurs ;
- responsables de locales ;
- comité de sécurité ;
- processus de proposition ;
- politique de release ;
- roadmap publique ;
- règles de succession ou de continuité.

---

# 14. Contributions

## H2

```text
Contribuer au code, à la documentation et aux traductions
```

## Contributions possibles

- code ;
- correction de bug ;
- test ;
- documentation ;
- traduction ;
- capture ;
- exemple ;
- thème ;
- module ;
- signalement de sécurité ;
- retour d’expérience.

## Accord de contribution

```text
Les contributions au projet sont encadrées par le Contributor License
Agreement de FlatCMS.
```

Le CLA permet au projet :

- d’intégrer la contribution ;
- de la modifier ;
- de la redistribuer ;
- de la proposer dans différents modèles de licence ;
- de maintenir la continuité juridique du projet.

## Acceptation

```text
Soumettre une contribution ne garantit pas son intégration, sa
publication ou son maintien.
```

## Critères

- utilité ;
- qualité ;
- tests ;
- sécurité ;
- compatibilité ;
- documentation ;
- licence ;
- cohérence architecturale ;
- maintenance future.

## CTA

```text
Contribuer à FlatCMS
```

Destination :

```text
/fr-FR/contribuer/
```

---

# 15. Marque et identité

## H2

```text
Distinguer le code libre de la marque FlatCMS
```

## Texte

```text
L’accès au code source ne donne pas automatiquement le droit de présenter
une version modifiée, un service, un thème ou un module comme un produit
officiel FlatCMS.
```

## Éléments de marque

- nom FlatCMS ;
- logos ;
- icônes ;
- signatures visuelles ;
- noms de produits ;
- identité graphique.

## Usages nominaux

Une référence véridique peut indiquer :

```text
Compatible avec FlatCMS
Construit avec FlatCMS
Service utilisant FlatCMS
```

Elle ne doit pas laisser croire à :

- une certification ;
- un partenariat ;
- une approbation ;
- une distribution officielle ;
- un support officiel.

## CTA

```text
Consulter la politique de marque
```

Destination :

```text
/fr-FR/licences/#marque
```

---

# 16. Agent-ready

## H2

```text
Préparer les intégrations IA sans rendre le CMS dépendant de l’IA
```

## Texte

```text
FlatCMS est conçu pour isoler les fournisseurs d’intelligence
artificielle derrière des services et des contrats contrôlés.
```

Cette approche permet d’envisager :

- assistance éditoriale ;
- traduction ;
- métadonnées ;
- génération de structures ;
- médias ;
- support ;
- agents spécialisés.

## Principe

```text
L’IA doit rester optionnelle, réversible et soumise aux permissions.
```

Une panne ou désactivation de l’IA ne doit pas empêcher :

- le frontend ;
- l’administration ;
- l’édition manuelle ;
- la publication ;
- l’accès aux contenus.

## CTA

```text
Découvrir l’approche agent-ready
```

Destination :

```text
/fr-FR/agent-ready/
```

---

# 17. Documentation et transparence

## H2

```text
Documenter avant de promettre
```

## Engagements éditoriaux

FlatCMS doit :

- identifier la version décrite ;
- distinguer le stable du prévu ;
- publier les prérequis ;
- expliquer les limites ;
- citer les fichiers réels ;
- conserver un changelog ;
- publier les licences ;
- documenter les migrations ;
- éviter les benchmarks non reproductibles ;
- corriger les erreurs publiques.

## Problèmes du site actuel à corriger

Le futur site doit éviter les slogans absolus tels que :

```text
le plus rapide au monde
vitesse de la lumière
le temps de réponse le plus rapide du marché
```

sans protocole de benchmark public, reproductible et indépendant.

## Formulation recommandée

```text
FlatCMS vise une architecture légère et une dépendance réduite à
l’infrastructure. Les performances réelles dépendent du serveur, du thème,
des médias, du cache et du projet déployé.
```

---

# 18. Roadmap

## H2

```text
Faire évoluer FlatCMS par étapes
```

## Priorité actuelle

```text
Stabiliser FlatCMS v1.0.0 LTS Core.
```

## Axes de travail

- stabilité ;
- tests ;
- documentation ;
- installation multi-environnements ;
- thèmes ;
- modules ;
- multilingue ;
- données structurées ;
- builders ;
- licences ;
- sécurité ;
- intelligence artificielle ;
- marketplace ;
- support.

## Règle de roadmap

```text
Une roadmap exprime une intention, pas une garantie de date ou de
livraison.
```

## États

```text
À l’étude
Planifié
En développement
En test
Disponible
Reporté
Abandonné
```

## CTA

```text
Consulter la roadmap publique
```

Destination :

```text
/fr-FR/roadmap/
```

---

# 19. Chronologie proposée

## H2

```text
Les principales étapes du projet
```

> Les dates doivent être confirmées à partir du dépôt, des releases et des
> archives avant publication.

## Origine

```text
Début de la conception du CMS et de son architecture flat-file.
```

Date :

```text
À confirmer
```

## Structuration du Core

```text
Organisation du runtime PHP, des modules HMVC, du stockage JSON et des
thèmes.
```

Date :

```text
À confirmer
```

## Documentation et six locales

```text
Mise en place du wiki, des contenus multilingues et de la stratégie
documentaire.
```

Date :

```text
À confirmer
```

## LTS Core v1.0.0

```text
Première version stable publique annoncée le 31 mai 2026.
```

## Futur site officiel

```text
Regroupement du site produit, du blog et de la documentation sous
flat-cms.fr.
```

Date :

```text
Projet engagé en juin 2026
```

---

# 20. Relations avec la communauté

## H2

```text
Construire un projet utile avant de construire une audience
```

## Canaux possibles

- GitHub ;
- documentation ;
- blog ;
- LinkedIn ;
- YouTube ;
- formulaire de contact ;
- rapports de bugs ;
- demandes de fonctionnalités ;
- contributions de traduction.

## Principes

- répondre avec transparence ;
- demander des informations reproductibles ;
- distinguer support et développement sur mesure ;
- ne pas annoncer une disponibilité non confirmée ;
- reconnaître les contributions ;
- protéger les signalements de sécurité ;
- publier les décisions importantes.

---

# 21. Indépendance du projet

## H2

```text
Un projet développé de manière indépendante
```

## Texte

```text
FlatCMS est développé indépendamment des grands éditeurs de CMS et des
fournisseurs d’intelligence artificielle.
```

L’utilisation d’un standard, d’une API ou d’une bibliothèque ne signifie
pas :

- partenariat ;
- certification ;
- approbation ;
- financement ;
- exclusivité.

## Exemples

- PHP ;
- PHP-FIG ;
- Schema.org ;
- OpenAI ;
- Apache ;
- Nginx ;
- GitHub.

## Règle

```text
Les intégrations doivent être décrites comme des compatibilités ou des
utilisations techniques, sauf partenariat formel publié.
```

---

# 22. Ce que FlatCMS n’est pas

## H2

```text
Clarifier le périmètre du projet
```

FlatCMS n’est pas :

- une base de données ;
- un framework généraliste ;
- une plateforme e-commerce complète ;
- un service SaaS obligatoire ;
- un agent autonome ;
- une garantie SEO ;
- un remplacement universel de WordPress ;
- une solution adaptée à tous les volumes d’écriture ;
- un produit officiel de PHP, OpenAI, Apache, Nginx ou GitHub.

## Pourquoi cette section ?

```text
Définir ce que le projet ne cherche pas à être aide les utilisateurs à
choisir une solution adaptée à leur besoin réel.
```

---

# 23. Questions fréquentes éditoriales

> Ces réponses ne déclenchent pas automatiquement un balisage `FAQPage`.

## H2

```text
Questions fréquentes sur le projet
```

### H3 — Qui développe FlatCMS ?

```text
FlatCMS est créé et maintenu principalement par Alain BROYE.
```

### H3 — FlatCMS est-il un projet français ?

```text
Oui. Le projet est développé en France et utilise le français comme
locale éditoriale source de son site officiel.
```

### H3 — FlatCMS est-il open source ?

```text
Le LTS Core est distribué sous GNU AGPL v3 ou ultérieure, sauf mention
différente dans l’en-tête d’un fichier. Certains composants premium
utilisent une licence commerciale distincte.
```

### H3 — Pourquoi FlatCMS utilise-t-il des fichiers JSON ?

```text
Ce choix permet de gérer les contenus sans serveur SQL tout en conservant
une structure de données explicite. Il implique aussi des limites qui
sont documentées.
```

### H3 — FlatCMS remplace-t-il WordPress ?

```text
FlatCMS propose une architecture différente pour certains projets. Il ne
cherche pas à remplacer toutes les plateformes ni leur écosystème.
```

### H3 — Le projet accepte-t-il les contributions ?

```text
Oui, sous réserve des règles du projet, des tests, de la cohérence
architecturale et du Contributor License Agreement.
```

### H3 — Les Builders font-ils partie du Core ?

```text
Non. PagesBuilder, MenuBuilder et FooterBuilder sont des composants
premium distincts du LTS Core.
```

### H3 — FlatCMS dépend-il d’OpenAI ?

```text
Non. Les fournisseurs IA doivent être isolés derrière une couche de
services et rester optionnels.
```

### H3 — Comment suivre le développement ?

```text
Consultez la roadmap, les notes de version, le dépôt GitHub et le blog
officiel.
```

---

# 24. CTA final

## H2

```text
Découvrir, tester et contribuer
```

## Texte

```text
FlatCMS se construit autour d’un cœur stable, d’une documentation
publique et d’une architecture conçue pour évoluer sans masquer ses
limites.
```

## CTA principal

```text
Télécharger FlatCMS
```

Destination :

```text
/fr-FR/telechargement/
```

## CTA secondaire

```text
Tester la démo
```

Destination :

```text
https://demo.flat-cms.fr/
```

## Lien tertiaire

```text
Contribuer au projet
```

Destination :

```text
/fr-FR/contribuer/
```

---

# 25. Maillage interne attendu

| Section | Destination |
|---|---|
| Hero | Roadmap, GitHub, contribution |
| Mission | Pourquoi FlatCMS |
| Principes | Architecture |
| Auteur | Profil public ou contact |
| LTS Core | Téléchargement |
| Licences | Licences |
| Builders | Tarifs |
| Contributions | Guide de contribution |
| Marque | Politique de marque |
| Agent-ready | Page Agent-ready |
| Documentation | Hub documentation |
| Roadmap | Roadmap publique |
| CTA final | Téléchargement, démo, contribution |

---

# 26. Médias à produire

## Image Open Graph

Concept :

```text
Portrait éditorial sobre du projet FlatCMS : logo, architecture, code,
documentation et auteur réunis dans un univers indigo et bleu nuit.
```

## Portrait de l’auteur

À utiliser uniquement après validation :

- photo professionnelle ;
- droits confirmés ;
- texte alternatif ;
- cadrage cohérent ;
- absence d’informations privées en arrière-plan.

## Diagramme de mission

```text
Simplicité
→ PHP natif
→ modules
→ JSON
→ documentation
→ évolution agent-ready
```

## Chronologie

- origine ;
- architecture ;
- wiki ;
- v1.0.0 ;
- futur site officiel ;
- prochaines versions.

## Logos

- FlatCMS ;
- GitHub comme lien externe ;
- aucune marque tierce utilisée comme partenaire sans autorisation.

---

# 27. Textes alternatifs suggérés

## Portrait

```text
Alain BROYE, créateur et mainteneur de FlatCMS
```

## Mission

```text
Principes de FlatCMS : simplicité, modularité, stockage JSON,
documentation et architecture agent-ready
```

## Chronologie

```text
Principales étapes du développement de FlatCMS jusqu’à la version 1.0.0
```

Les textes finaux doivent correspondre aux médias réellement publiés.

---

# 28. Données structurées attendues

```text
AboutPage
Organization
Person
ProfilePage si une page auteur complète existe
BreadcrumbList
ImageObject
```

## Identifiants

```text
https://flat-cms.fr/#organization
https://flat-cms.fr/#software
https://flat-cms.fr/fr-FR/a-propos/#webpage
https://flat-cms.fr/fr-FR/a-propos/#breadcrumb
https://flat-cms.fr/fr-FR/a-propos/#primaryimage
https://flat-cms.fr/fr-FR/auteurs/alain-broye/#person
```

## Propriétés à confirmer

- nom légal de l’organisation ou de l’éditeur ;
- fondateur ;
- date de fondation ;
- adresse publique ;
- profils `sameAs` ;
- logo ;
- contact ;
- statut juridique ;
- zone desservie.

## Prudence

Ne pas créer une organisation fictive distincte si FlatCMS est juridiquement
édité en nom propre.

La donnée structurée doit refléter la situation légale réelle.

---

# 29. Composants du thème suggérés

```text
HeroAbout
MissionStatement
TechnicalPrinciples
ValuesGrid
FounderProfile
OpenSourceCommercialModel
GovernanceSection
ContributionSection
TrademarkNotice
ProjectTimeline
RoadmapPreview
CommunityChannels
FaqAccordion
CallToActionBanner
```

---

# 30. Éléments à confirmer avant publication

- date de création de FlatCMS ;
- date du premier commit ;
- date de la première version publique ;
- date définitive de v1.0.0 ;
- statut juridique de l’éditeur ;
- nom commercial éventuel ;
- adresse publique ;
- identité de l’hébergeur ;
- biographie publique d’Alain BROYE ;
- diplôme et établissement ;
- spécialisation ;
- photo ;
- profil LinkedIn ;
- profil GitHub ;
- politique de gouvernance ;
- mainteneurs futurs ;
- processus de décision ;
- processus de contribution ;
- statut de la roadmap ;
- liste des canaux officiels ;
- URL des notes de version ;
- liste des partenaires réels, s’il en existe.

---

# 31. Checklist éditoriale

- [ ] L’auteur est clairement identifié.
- [ ] La biographie est validée.
- [ ] Les informations privées sont exclues.
- [ ] L’origine du projet est datée avec des preuves.
- [ ] La mission est compréhensible.
- [ ] Les valeurs correspondent au code et aux documents.
- [ ] Le Core et les composants premium sont distingués.
- [ ] La gouvernance actuelle est décrite honnêtement.
- [ ] Les risques d’un mainteneur principal sont reconnus.
- [ ] Le CLA est résumé fidèlement.
- [ ] La marque est distinguée de la licence du code.
- [ ] Agent-ready n’est pas présenté comme autonome.
- [ ] La roadmap n’est pas une promesse contractuelle.
- [ ] Les slogans absolus sont évités.
- [ ] Les profils externes sont officiels.
- [ ] Les données structurées reflètent la situation légale.

---

# 32. Checklist d’intégration

- [ ] URL correcte.
- [ ] Canonique auto-référencée.
- [ ] `<html lang="fr-FR">`.
- [ ] Groupe `hreflang`.
- [ ] Title.
- [ ] Meta description.
- [ ] Open Graph.
- [ ] H1 unique.
- [ ] Portrait autorisé.
- [ ] Chronologie responsive.
- [ ] Liens officiels.
- [ ] Liens HTML explorables.
- [ ] Images responsive.
- [ ] Textes alternatifs.
- [ ] JSON-LD.
- [ ] Sitemap.
- [ ] Directive robots.
- [ ] Test mobile.
- [ ] Test clavier.
- [ ] Test des liens.
- [ ] Test HTTP `200`.
- [ ] Validation juridique et biographique.

---

# 33. Sources internes

- `README.md`
- `VERSION`
- `LICENSING.md`
- `COMMERCIAL_LICENSE.md`
- `CLA.md`
- `TRADEMARK.md`
- `SITE_ARCHITECTURE.md`
- `CONTENT_STYLE_GUIDE.md`
- historique Git ;
- releases ;
- roadmap ;
- profils officiels ;
- informations validées par Alain BROYE.

---

# 34. Références externes

- Site officiel FlatCMS  
  https://flat-cms.fr/

- Dépôt GitHub officiel  
  https://github.com/ABroye/FlatCMS

- PHP-FIG — PSR-4  
  https://www.php-fig.org/psr/psr-4/

- GNU AGPL v3  
  https://www.gnu.org/licenses/agpl-3.0.html

Les références externes confirment les standards et licences utilisés.
L’histoire et la gouvernance du projet doivent être confirmées par ses
archives et documents officiels.

---

# 35. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction complète de la page À propos | ChatGPT / Alain BROYE |

---

# 36. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer CONTACT_CONTENT.md
```

Ce document contiendra la rédaction complète de la page :

```text
/fr-FR/contact/
```

Il organisera les demandes générales, commerciales, techniques, de
partenariat, de presse, de licence et de sécurité autour d’un formulaire
clair et protégé.
