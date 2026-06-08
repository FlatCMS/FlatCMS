# CODEX_WORKFLOW — Méthode de travail Codex pour FlatCMS

> Objectif : permettre à Codex de travailler vite, proprement et sans se disperser.

## Principe

Codex traite une issue à la fois.

Chaque issue doit contenir :

- un objectif clair ;
- les documents de référence ;
- le périmètre inclus ;
- le hors-périmètre ;
- les critères d’acceptation ;
- les livrables attendus ;
- les tests ou vérifications à exécuter.

## Règles générales

1. Ne pas modifier le Core si l’issue concerne uniquement le thème.
2. Ne pas modifier les licences si l’issue ne le demande pas explicitement.
3. Ne pas ajouter de dépendance lourde sans justification.
4. Ne pas utiliser de CDN obligatoire pour le thème officiel.
5. Conserver un rendu public fonctionnel sans JavaScript pour le contenu essentiel.
6. Ne jamais masquer ou supprimer le frontend déjà publié à cause d’un état de licence.
7. Documenter tout écart entre la spécification et l’implémentation.
8. Fournir un résumé final exploitable par Alain BROYE.

## Format recommandé d’une réponse Codex

```text
Résumé
- ...

Fichiers modifiés
- ...

Tests réalisés
- ...

Écarts / arbitrages
- ...

Prochaine étape recommandée
- ...
```

## Documents de référence principaux

Les documents détaillés sont conservés dans le Drive projet et peuvent être copiés dans le dépôt si nécessaire.

Priorité pour le thème officiel :

- THEME_SPECIFICATION.md
- DESIGN_SYSTEM.md
- NAVIGATION_SPECIFICATION.md
- COMPONENT_LIBRARY.md
- HOMEPAGE_WIREFRAME.md
- MEDIA_PLAN.md
- PREPRODUCTION_TEST_PLAN.md
- SECURITY_BASELINE.md

## Découpage recommandé

- Sprint 01 : fondations du thème `flatcms`
- Sprint 02 : navigation et layouts
- Sprint 03 : homepage native
- Sprint 04 : pages P0
- Sprint 05 : médias P0
- Sprint 06 : SEO et migration
- Sprint 07 : sécurité et préproduction
- Sprint 08 : mise en ligne

## Invariant stratégique

FlatCMS doit rester simple, lisible, modulaire et maintenable.

La documentation guide Codex ; elle ne l’autorise pas à tout implémenter en une seule passe.
