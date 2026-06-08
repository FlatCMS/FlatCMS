# INDEX — Dossier directeur du futur site officiel FlatCMS

> **Point d’entrée du projet**
>
> Projet : FlatCMS  
> Domaine cible : `https://flat-cms.fr`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Fuseau de référence : `Europe/Paris`  
> Responsable du projet : Alain BROYE  
> Statut : socle stratégique initial constitué

---

## 1. Objet de ce dossier

Ce dossier centralise les documents directeurs nécessaires à la conception, à la rédaction, au développement, à la migration et au lancement du futur site officiel de FlatCMS.

Il doit permettre de construire un site :

- cohérent avec l’architecture réelle de FlatCMS v1.0.0 ;
- solide pour le référencement naturel ;
- lisible par les moteurs classiques et génératifs ;
- structuré pour les six locales natives ;
- techniquement sécurisé ;
- accessible ;
- maintenable ;
- documenté avant sa mise en production.

Ce fichier constitue le point d’entrée du dossier.

Toute personne intervenant sur le futur site doit commencer par lire ce document, puis consulter les documents correspondant à sa mission.

---

## 2. Objectif du futur site

Le futur site officiel réunira sous `flat-cms.fr` :

```text
le site produit
les pages institutionnelles
le blog officiel
la documentation
les comparatifs
les téléchargements
les licences
les tarifs
la roadmap
les contenus multilingues
```

La démonstration restera séparée :

```text
https://demo.flat-cms.fr/
```

Le code source et les contributions resteront associés au dépôt GitHub officiel.

---

## 3. Principes déjà actés

1. `flat-cms.fr` devient le domaine officiel central.
2. Le blog et la documentation rejoignent le domaine principal.
3. `demo.flat-cms.fr` reste un environnement fonctionnel séparé.
4. Le code réel de FlatCMS v1.0.0 constitue la source de vérité technique.
5. L’arborescence publique explique l’architecture sans reproduire mécaniquement les dossiers PHP.
6. Chaque fonctionnalité indique son statut : Core, optionnelle, premium, expérimentale ou prévue.
7. Le français `fr-FR` constitue la source éditoriale initiale.
8. Les cinq autres locales sont `en-US`, `de-DE`, `es-ES`, `it-IT` et `pt-PT`.
9. Chaque locale utilise une URL distincte.
10. Les anciennes URLs sont migrées page par page avec des redirections permanentes pertinentes.
11. Les données structurées décrivent uniquement le contenu visible.
12. Les robots de recherche et les robots d’entraînement sont gérés séparément.
13. Toute affirmation importante doit pouvoir être prouvée.
14. Aucun classement, résultat enrichi ou citation par une IA n’est garanti.
15. Le lancement est soumis à une checklist Go / No-Go.

---

# 4. Documents directeurs

## 4.1 Stratégie SEO et roadmap

### [SEO.md](SEO.md)

**Rôle :** document stratégique principal.

Il définit :

- l’analyse SEO initiale ;
- le positionnement officiel ;
- l’architecture cible du domaine ;
- les mots-clés prioritaires ;
- les titres SEO ;
- l’organisation du blog et de la documentation ;
- la stratégie multilingue ;
- la gestion de la démo ;
- les données structurées ;
- les indicateurs ;
- la roadmap générale.

**À lire en premier** pour comprendre la vision globale.

**Public principal :**

- direction du projet ;
- SEO ;
- contenu ;
- architecture ;
- développement.

---

## 4.2 Architecture du futur site

### [SITE_ARCHITECTURE.md](SITE_ARCHITECTURE.md)

**Rôle :** traduire l’architecture réelle de FlatCMS en architecture publique.

Il définit :

- les sources de vérité ;
- l’arborescence technique réelle ;
- les composants du Core ;
- les modules ;
- les services ;
- le stockage JSON ;
- les thèmes ;
- l’arborescence éditoriale ;
- les menus et méga-menus ;
- le footer ;
- les modèles de page ;
- les principes SEO, GEO et GIO.

**À utiliser avant :**

- la création du thème ;
- la création des menus ;
- le développement des templates ;
- la rédaction des pages.

---

## 4.3 Matrice éditoriale

### [CONTENT_MATRIX.md](CONTENT_MATRIX.md)

**Rôle :** transformer l’architecture en plan de production page par page.

Il associe à chaque page :

- priorité ;
- audience ;
- intention ;
- mot-clé principal ;
- expressions secondaires ;
- URL ;
- title ;
- meta description ;
- H1 ;
- structure H2/H3 ;
- CTA ;
- liens internes ;
- données structurées ;
- preuve attendue ;
- statut de rédaction ;
- statut multilingue.

**À utiliser pour :**

- créer les briefs ;
- répartir les tâches ;
- suivre la production ;
- éviter les doublons ;
- contrôler la couverture du site.

---

## 4.4 Cartographie documentaire

### [DOCUMENTATION_MAP.md](DOCUMENTATION_MAP.md)

**Rôle :** organiser la future documentation officielle.

Il distingue :

- tutoriels ;
- guides pratiques ;
- référence ;
- explications ;
- dépannage.

Il définit également :

- les parcours utilisateurs ;
- les catégories ;
- l’ordre de lecture ;
- le versionnement ;
- les modèles de pages ;
- les lacunes documentaires ;
- la migration du wiki.

**À utiliser avant :**

- la migration de la documentation ;
- la création du menu documentaire ;
- la rédaction des guides ;
- le développement de la recherche documentaire.

---

## 4.5 Stratégie de mots-clés

### [KEYWORDS.md](KEYWORDS.md)

**Rôle :** définir les clusters de visibilité SEO, GEO et GIO.

Il contient :

- requêtes de marque ;
- CMS sans base de données ;
- CMS flat-file ;
- PHP natif ;
- HMVC ;
- PSR-4 ;
- stockage JSON ;
- installation ;
- fonctionnalités ;
- multilingue ;
- sécurité ;
- performances ;
- licences ;
- comparatifs ;
- agent-ready ;
- GEO et GIO ;
- dépannage ;
- cas d’usage ;
- risques de cannibalisation.

**À utiliser pour :**

- choisir la page cible d’une requête ;
- préparer les briefs ;
- organiser le maillage interne ;
- contrôler les doublons éditoriaux ;
- suivre Search Console.

---

## 4.6 Migration et redirections

### [REDIRECTS.md](REDIRECTS.md)

**Rôle :** préparer la migration des URLs actuelles.

Il définit :

- les règles `301` et `308` ;
- la correspondance page par page ;
- les suppressions `404` et `410` ;
- les anciennes URLs dynamiques ;
- la migration des locales ;
- les canonicals ;
- les sitemaps ;
- les médias ;
- les règles Apache et Nginx ;
- les tests automatisés ;
- la surveillance après lancement.

**À compléter avec :**

- toutes les anciennes URLs ;
- leur trafic ;
- leurs backlinks ;
- leur destination ;
- leur statut de test.

---

## 4.7 Données structurées

### [STRUCTURED_DATA.md](STRUCTURED_DATA.md)

**Rôle :** définir le graphe JSON-LD du futur site.

Il couvre :

- `Organization` ;
- `WebSite` ;
- `SoftwareApplication` ;
- `WebPage` ;
- `TechArticle` ;
- `BlogPosting` ;
- `BreadcrumbList` ;
- `ImageObject` ;
- `VideoObject` ;
- `Person` ;
- `ProfilePage` ;
- `Product` ;
- `Offer`.

Il s’appuie sur :

```text
StructuredDataManager
SchemaGraphBuilder
SiteSchemaProvider
PageSchemaProvider
PostSchemaProvider
```

**À utiliser pour :**

- adapter le service StructuredData ;
- créer les providers ;
- définir les `@id` ;
- tester les templates ;
- valider les données structurées.

---

## 4.8 Politique de crawl

### [CRAWL_POLICY.md](CRAWL_POLICY.md)

**Rôle :** définir l’accès des robots au site.

Il couvre :

- moteurs de recherche classiques ;
- robots de recherche générative ;
- fetchers déclenchés par un utilisateur ;
- robots d’entraînement ;
- `robots.txt` ;
- `noindex` ;
- `X-Robots-Tag` ;
- préproduction ;
- démo ;
- médias ;
- WAF ;
- logs ;
- contrôle de charge.

**Décision initiale :**

```text
recherche classique autorisée
recherche générative autorisée
entraînement bloqué par défaut
```

La politique doit être révisée avant le lancement.

---

## 4.9 Guide éditorial

### [CONTENT_STYLE_GUIDE.md](CONTENT_STYLE_GUIDE.md)

**Rôle :** garantir une voix cohérente sur l’ensemble du site.

Il définit :

- la voix de FlatCMS ;
- le ton selon le contenu ;
- les termes officiels ;
- la sémantique H1/H2/H3 ;
- les titres SEO ;
- les paragraphes ;
- les preuves ;
- les CTA ;
- les liens ;
- les tableaux ;
- le code ;
- les erreurs ;
- les images ;
- les textes alternatifs ;
- les dates ;
- les prix ;
- l’assistance IA ;
- les règles par locale.

**À appliquer à tous les contenus publics.**

---

## 4.10 Architecture multilingue

### [MULTILINGUAL.md](MULTILINGUAL.md)

**Rôle :** définir le fonctionnement des six locales.

Il couvre :

- URLs locales ;
- slugs traduits ;
- racine `x-default` ;
- canonicals locales ;
- `hreflang` ;
- sélecteur de langue ;
- fallbacks ;
- statuts de traduction ;
- glossaire ;
- dates ;
- nombres ;
- devises ;
- médias ;
- sitemaps ;
- workflow IA et relecture humaine.

**À utiliser avant :**

- le développement du modèle de données ;
- le sélecteur de langue ;
- la migration des contenus ;
- les traductions ;
- la génération des sitemaps.

---

## 4.11 Checklist de lancement

### [LAUNCH_CHECKLIST.md](LAUNCH_CHECKLIST.md)

**Rôle :** contrôler la mise en production.

Elle couvre :

- gouvernance ;
- code ;
- DNS ;
- TLS ;
- serveur ;
- PHP ;
- sécurité ;
- sauvegardes ;
- contenus ;
- SEO ;
- redirections ;
- multilingue ;
- données structurées ;
- crawl ;
- accessibilité ;
- performances ;
- formulaires ;
- e-mails ;
- téléchargements ;
- licences ;
- démo ;
- analytics ;
- Search Console ;
- rollback ;
- suivi J+1, J+7 et J+30.

**Le lancement est interdit tant qu’un contrôle bloquant reste ouvert.**

---

# 5. Ordre de lecture recommandé

## Direction du projet

1. [SEO.md](SEO.md)
2. [SITE_ARCHITECTURE.md](SITE_ARCHITECTURE.md)
3. [CONTENT_MATRIX.md](CONTENT_MATRIX.md)
4. [LAUNCH_CHECKLIST.md](LAUNCH_CHECKLIST.md)

## Développeur du site

1. [SITE_ARCHITECTURE.md](SITE_ARCHITECTURE.md)
2. [MULTILINGUAL.md](MULTILINGUAL.md)
3. [STRUCTURED_DATA.md](STRUCTURED_DATA.md)
4. [CRAWL_POLICY.md](CRAWL_POLICY.md)
5. [REDIRECTS.md](REDIRECTS.md)
6. [LAUNCH_CHECKLIST.md](LAUNCH_CHECKLIST.md)

## Rédacteur

1. [CONTENT_MATRIX.md](CONTENT_MATRIX.md)
2. [CONTENT_STYLE_GUIDE.md](CONTENT_STYLE_GUIDE.md)
3. [KEYWORDS.md](KEYWORDS.md)
4. [DOCUMENTATION_MAP.md](DOCUMENTATION_MAP.md)

## Traducteur

1. [MULTILINGUAL.md](MULTILINGUAL.md)
2. [CONTENT_STYLE_GUIDE.md](CONTENT_STYLE_GUIDE.md)
3. [CONTENT_MATRIX.md](CONTENT_MATRIX.md)
4. [KEYWORDS.md](KEYWORDS.md)

## Responsable SEO

1. [SEO.md](SEO.md)
2. [KEYWORDS.md](KEYWORDS.md)
3. [CONTENT_MATRIX.md](CONTENT_MATRIX.md)
4. [REDIRECTS.md](REDIRECTS.md)
5. [STRUCTURED_DATA.md](STRUCTURED_DATA.md)
6. [CRAWL_POLICY.md](CRAWL_POLICY.md)
7. [MULTILINGUAL.md](MULTILINGUAL.md)
8. [LAUNCH_CHECKLIST.md](LAUNCH_CHECKLIST.md)

## Responsable documentation

1. [DOCUMENTATION_MAP.md](DOCUMENTATION_MAP.md)
2. [CONTENT_STYLE_GUIDE.md](CONTENT_STYLE_GUIDE.md)
3. [SITE_ARCHITECTURE.md](SITE_ARCHITECTURE.md)
4. [MULTILINGUAL.md](MULTILINGUAL.md)
5. [REDIRECTS.md](REDIRECTS.md)

---

# 6. Dépendances entre les documents

```text
SEO.md
├── SITE_ARCHITECTURE.md
│   ├── CONTENT_MATRIX.md
│   │   ├── KEYWORDS.md
│   │   └── CONTENT_STYLE_GUIDE.md
│   ├── DOCUMENTATION_MAP.md
│   ├── STRUCTURED_DATA.md
│   ├── CRAWL_POLICY.md
│   └── MULTILINGUAL.md
├── REDIRECTS.md
└── LAUNCH_CHECKLIST.md
```

## Lecture fonctionnelle

```text
Vision
→ Architecture
→ Matrice de contenus
→ Production
→ Migration
→ Validation
→ Lancement
```

---

# 7. Hiérarchie des sources de vérité

En cas de contradiction, utiliser l’ordre suivant.

## 1. Code réel de FlatCMS v1.0.0

```text
app/
config/
data/
public/
resources/
storage/
themes/
```

## 2. Documents juridiques et manifestes officiels

```text
VERSION
flatcms.json
LICENSE
LICENSING.md
COMMERCIAL_LICENSE.md
TRADEMARK.md
CLA.md
```

## 3. Documentation technique testée

Guides validés sur une version identifiée.

## 4. Documents directeurs de ce dossier

Ils décrivent le futur site, mais doivent être corrigés s’ils contredisent le produit réel.

## 5. Contenus marketing

Ils ne doivent jamais modifier ou exagérer la réalité technique.

---

# 8. Règle de gestion des contradictions

Lorsqu’une contradiction est détectée :

1. identifier les deux sources ;
2. vérifier le code réel ;
3. vérifier la version concernée ;
4. enregistrer la décision ;
5. corriger tous les documents affectés ;
6. mettre à jour leur journal ;
7. informer les personnes concernées.

Ne pas laisser deux descriptions concurrentes de FlatCMS coexister publiquement.

---

# 9. Statut actuel du socle documentaire

| Document | Version initiale | Statut |
|---|---:|---|
| `SEO.md` | 1.0 | En place |
| `SITE_ARCHITECTURE.md` | 1.0 | En place |
| `CONTENT_MATRIX.md` | 1.0 | En place |
| `DOCUMENTATION_MAP.md` | 1.0 | En place |
| `KEYWORDS.md` | 1.0 | En place |
| `REDIRECTS.md` | 1.0 | En place |
| `STRUCTURED_DATA.md` | 1.0 | En place |
| `CRAWL_POLICY.md` | 1.0 | En place |
| `CONTENT_STYLE_GUIDE.md` | 1.0 | En place |
| `MULTILINGUAL.md` | 1.0 | En place |
| `LAUNCH_CHECKLIST.md` | 1.0 | En place |
| `INDEX.md` | 1.0 | Présent document |

---

# 10. État du projet

## Phase terminée

```text
Cadrage stratégique initial
```

Livrables :

- stratégie SEO ;
- architecture publique ;
- matrice éditoriale ;
- cartographie documentaire ;
- stratégie de mots-clés ;
- plan de redirections ;
- stratégie JSON-LD ;
- politique de crawl ;
- guide éditorial ;
- architecture multilingue ;
- checklist de lancement ;
- index du dossier.

## Phase suivante

```text
Conception détaillée et production du site
```

---

# 11. Prochaine phase : pages P0

Les pages suivantes doivent être conçues en premier :

1. Accueil
2. Pourquoi FlatCMS
3. Fonctionnalités
4. Architecture
5. Documentation
6. Installation
7. Téléchargement
8. Licences
9. Tarifs
10. Agent-ready
11. À propos
12. Contact
13. Mentions légales
14. Politique de confidentialité
15. Page 404

Pour chaque page, créer un brief dédié.

---

# 12. Format d’un brief de page

Nom recommandé :

```text
pages/fr-FR/home.md
pages/fr-FR/features.md
pages/fr-FR/architecture.md
```

Chaque brief doit contenir :

```text
ID
locale
statut
priorité
audience
intention
URL
mot-clé principal
mots-clés secondaires
title
meta description
H1
promesse principale
structure H2/H3
preuves
CTA
liens internes
médias
données structurées
règles d’indexation
statut de traduction
responsable
```

---

# 13. Arborescence de travail recommandée

```text
https://flat-cms.fr/
├── INDEX.md
├── SEO.md
├── SITE_ARCHITECTURE.md
├── CONTENT_MATRIX.md
├── DOCUMENTATION_MAP.md
├── KEYWORDS.md
├── REDIRECTS.md
├── STRUCTURED_DATA.md
├── CRAWL_POLICY.md
├── CONTENT_STYLE_GUIDE.md
├── MULTILINGUAL.md
├── LAUNCH_CHECKLIST.md
├── pages/
│   ├── fr-FR/
│   ├── en-US/
│   ├── de-DE/
│   ├── es-ES/
│   ├── it-IT/
│   └── pt-PT/
├── documentation/
│   ├── briefs/
│   ├── migration/
│   └── sources/
├── blog/
│   ├── briefs/
│   ├── drafts/
│   └── sources/
├── assets/
│   ├── brand/
│   ├── images/
│   ├── diagrams/
│   ├── screenshots/
│   └── videos/
├── migration/
│   ├── urls/
│   ├── redirects/
│   ├── media/
│   └── reports/
├── theme/
│   ├── wireframes/
│   ├── components/
│   └── specifications/
└── launch/
    ├── backups/
    ├── crawls/
    ├── security/
    ├── performance/
    └── reports/
```

Cette arborescence représente l’espace de préparation, pas nécessairement l’arborescence finale déployée sur le serveur.

---

# 14. Documents à créer pendant la prochaine phase

## Priorité 1

```text
PAGE_BRIEFS.md
THEME_SPECIFICATION.md
COMPONENT_LIBRARY.md
NAVIGATION_SPECIFICATION.md
HOMEPAGE_CONTENT.md
```

## Priorité 2

```text
FEATURES_CONTENT.md
ARCHITECTURE_CONTENT.md
DOWNLOAD_CONTENT.md
LICENSING_CONTENT.md
PRICING_CONTENT.md
AGENT_READY_CONTENT.md
ABOUT_CONTENT.md
CONTACT_CONTENT.md
```

## Priorité 3

```text
WIKI_INVENTORY.md
MIGRATION_MAP.csv
MEDIA_INVENTORY.md
BENCHMARK_PROTOCOL.md
SECURITY_BASELINE.md
```

---

# 15. Recommandation pour la prochaine action

Le prochain fichier à produire devrait être :

```text
PAGE_BRIEFS.md
```

Il regroupera les briefs des pages P0 avant leur rédaction complète.

Cette étape permettra de :

- figer les intentions ;
- éviter les doublons ;
- valider les titles et H1 ;
- prévoir les preuves ;
- concevoir le maillage ;
- préparer le thème ;
- identifier les médias nécessaires ;
- répartir la rédaction.

Après validation des briefs, la première page à rédiger sera :

```text
Accueil fr-FR
```

---

# 16. Règles de maintenance du dossier

## 16.1 Journal des mises à jour

Chaque document doit conserver son propre journal.

Toute modification structurelle doit également être signalée dans ce fichier.

## 16.2 Version

Format recommandé :

```text
1.0
1.1
1.2
2.0
```

- changement mineur : `1.1` ;
- évolution importante compatible : `1.2` ;
- changement de stratégie : `2.0`.

## 16.3 Date

Utiliser une date absolue :

```text
8 juin 2026
```

et non :

```text
aujourd’hui
hier
la semaine prochaine
```

## 16.4 Responsable

Identifier la personne ayant validé une modification importante.

## 16.5 Liens relatifs

Utiliser de préférence :

```markdown
[SEO.md](SEO.md)
```

plutôt qu’un lien Drive absolu.

Les liens relatifs facilitent :

- le déplacement du dossier ;
- l’utilisation dans GitHub ;
- l’export ;
- le versionnement ;
- la portabilité.

---

# 17. Conventions de nommage des fichiers

## Documents directeurs

```text
MAJUSCULES_AVEC_UNDERSCORES.md
```

Exemples :

```text
SITE_ARCHITECTURE.md
CONTENT_MATRIX.md
```

## Briefs et contenus

```text
minuscules-avec-tirets.md
```

Exemples :

```text
home.md
why-flatcms.md
structured-data.md
```

## Inventaires

```text
CSV ou Markdown selon le besoin
```

Exemples :

```text
MIGRATION_MAP.csv
MEDIA_INVENTORY.md
```

---

# 18. Règles de liens internes entre documents

Chaque document doit :

- pointer vers ses documents parents ;
- annoncer le document suivant ;
- utiliser un libellé descriptif ;
- éviter « cliquez ici » ;
- employer un chemin relatif ;
- vérifier les liens après renommage.

Exemple :

```markdown
Consultez la [stratégie de mots-clés FlatCMS](KEYWORDS.md).
```

---

# 19. Utilisation dans Google Drive

Le dossier Google Drive sert à :

- centraliser les fichiers ;
- partager les documents ;
- préparer les contenus ;
- conserver les ressources ;
- collaborer avant intégration.

Précautions :

- éviter les doublons portant le même nom ;
- ne pas remplacer un fichier validé sans sauvegarde ;
- conserver l’historique ;
- limiter les droits d’édition ;
- éviter le partage public en écriture ;
- vérifier la synchronisation avant une modification majeure.

---

# 20. Utilisation future dans Git

Lorsque le dossier passera sous contrôle de version :

- conserver les mêmes noms ;
- utiliser les liens relatifs ;
- réaliser des commits thématiques ;
- faire relire les changements importants ;
- utiliser les pull requests ;
- associer les décisions aux tickets ;
- ne pas stocker de secrets ;
- éviter les fichiers binaires lourds sans stratégie.

---

# 21. Tableau de pilotage global

| Phase | Livrable | Statut | Responsable | Dépendance |
|---|---|---|---|---|
| Stratégie | Documents directeurs | Validé initialement | Alain | Code FlatCMS |
| Briefs | Pages P0 | À faire | Alain | Content Matrix |
| UX | Wireframes | À faire | À définir | Architecture |
| UI | Design system | À faire | À définir | Wireframes |
| Thème | Thème FlatCMS | À faire | À définir | UI |
| Contenus | Pages fr-FR | À faire | Alain | Briefs |
| Documentation | Migration wiki | À faire | Alain | Documentation Map |
| SEO | Redirections finales | À faire | Alain | Inventaire |
| Multilingue | Cinq traductions | À faire | À définir | fr-FR validé |
| Préproduction | Site complet | À faire | À définir | Développement |
| Lancement | Go / No-Go | À faire | Alain | Checklist |

---

# 22. Risques principaux du projet

| Risque | Impact | Réponse |
|---|---|---|
| Documentation incohérente avec le code | Confiance et support | Vérifier chaque affirmation |
| Confusion avec l’ancien FlatCMS | Marque et SEO | Signature distinctive |
| Migration incomplète | Perte SEO | Inventaire et redirections |
| Contenus de démo indexés | Dilution thématique | `noindex` |
| Traductions partielles | Mauvaise UX et SEO | Workflow de validation |
| Promesses non prouvées | Crédibilité | Règle affirmation/preuve |
| Trop de pages simultanées | Retard et qualité | Lots P0/P1/P2 |
| Architecture trop complexe | Maintenance | Modèles réutilisables |
| Sécurité du document root | Exposition de données | `public/` comme racine |
| Dépendance aux outils IA | Erreurs factuelles | Validation humaine |

---

# 23. Définition de « terminé »

Le cadrage est terminé lorsque :

- les documents directeurs sont validés ;
- les décisions ouvertes sont résolues ;
- les pages P0 ont un brief ;
- les responsables sont identifiés ;
- l’arborescence de travail est créée.

La conception est terminée lorsque :

- les wireframes sont validés ;
- les composants sont définis ;
- le thème est spécifié ;
- les contenus P0 sont rédigés.

Le site est prêt lorsque :

- la checklist de lancement est validée ;
- aucune condition bloquante ne subsiste ;
- le rollback est opérationnel ;
- la migration est testée.

---

# 24. Références de méthode

Les documents de ce dossier appliquent notamment :

- les recommandations Google Search Central ;
- l’organisation documentaire Diátaxis ;
- les normes et recommandations W3C ;
- les spécifications PHP-FIG ;
- les recommandations OWASP ;
- Schema.org ;
- les documentations officielles des fournisseurs de robots.

Les sources précises sont référencées dans chaque document concerné.

---

# 25. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Création de l’index directeur du projet | ChatGPT / Alain BROYE |

---

# 26. Prochaine action

Créer :

```text
PAGE_BRIEFS.md
```

Ordre recommandé des briefs :

1. Accueil
2. Pourquoi FlatCMS
3. Fonctionnalités
4. Architecture
5. Documentation
6. Installation
7. Téléchargement
8. Licences
9. Tarifs
10. Agent-ready
11. À propos
12. Contact
13. Mentions légales
14. Confidentialité
15. Page 404

Une fois ces briefs validés, commencer la rédaction du contenu complet de l’accueil `fr-FR`.
