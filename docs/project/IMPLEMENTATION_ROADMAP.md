# IMPLEMENTATION_ROADMAP — Site officiel FlatCMS

## Objectif

Découper la construction du futur site officiel en lots courts, vérifiables et adaptés à Codex.

## Sprints

### Sprint 01 — Fondations du thème `flatcms`

Épic : #1

Tâches initiales :

- #9 Créer l’arborescence du thème `flatcms`
- #10 Créer le manifeste et les métadonnées du thème
- #11 Implémenter les design tokens clair et sombre
- #12 Créer les layouts minimaux du thème
- #13 Créer les composants de base Button, Link et Badge
- #14 Mettre en place le chargement local des assets CSS et JavaScript
- #15 Ajouter les tests de démarrage et le rapport de validation

### Sprint 02 — Navigation et layouts

Épic : #2

- header desktop ;
- menu mobile ;
- sélecteur de langue ;
- mode clair/sombre ;
- breadcrumbs ;
- footer ;
- layouts internes.

### Sprint 03 — Homepage native

Épic : #3

- Hero ;
- preuves rapides ;
- panneau technique ;
- fonctionnalités ;
- architecture ;
- Builders ;
- FAQ ;
- CTA final.

### Sprint 04 — Pages P0

Épic : #4

- page standard ;
- documentation ;
- article ;
- tarifs ;
- pages légales ;
- contact ;
- 404.

### Sprint 05 — Médias P0

Épic : #5

- logo ;
- favicon ;
- icônes ;
- diagrammes ;
- captures Builders ;
- images Open Graph prioritaires.

### Sprint 06 — SEO et migration

Épic : #6

- canonicals ;
- hreflang ;
- sitemap ;
- robots.txt ;
- données structurées ;
- redirections P0 ;
- migration du wiki et du blog.

### Sprint 07 — Sécurité et préproduction

Épic : #7

- document root `public/` ;
- fichiers sensibles ;
- headers ;
- formulaires ;
- sauvegarde et restauration ;
- licences Builders ;
- recette P0.

### Sprint 08 — Mise en ligne

Épic : #8

- gel de release ;
- sauvegarde ;
- déploiement ;
- tests fumée ;
- surveillance ;
- rollback prêt.

## Règles de planification

- une issue = un objectif principal ;
- aucune issue « implémenter tout » ;
- le hors-périmètre doit être explicite ;
- les critères d’acceptation doivent être testables ;
- les tâches P1 et P2 ne bloquent pas la release P0 sauf dépendance réelle ;
- les fonctions premium et expérimentales restent hors du dépôt LTS Core sauf contrat explicite.

## Statuts recommandés dans GitHub Projects

- Backlog
- Ready for Codex
- In progress
- Review
- Testing
- Blocked
- Done
- Deployed
