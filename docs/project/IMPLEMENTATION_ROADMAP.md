# IMPLEMENTATION_ROADMAP — Site officiel FlatCMS

## Objectif

Découper la construction du futur site officiel en lots courts, vérifiables et adaptés à Codex.

## Sprints

### Sprint 01 — Fondations du thème `flatcms`

- arborescence ;
- manifeste ;
- layouts minimaux ;
- design tokens ;
- composants de base ;
- chargement CSS/JS ;
- tests de démarrage.

### Sprint 02 — Navigation et layouts

- header desktop ;
- menu mobile ;
- sélecteur de langue ;
- mode clair/sombre ;
- breadcrumbs ;
- footer ;
- layouts internes.

### Sprint 03 — Homepage native

- Hero ;
- preuves rapides ;
- panneau technique ;
- fonctionnalités ;
- architecture ;
- Builders ;
- FAQ ;
- CTA final.

### Sprint 04 — Pages P0

- page standard ;
- documentation ;
- article ;
- tarifs ;
- pages légales ;
- contact ;
- 404.

### Sprint 05 — Médias P0

- logo ;
- favicon ;
- icônes ;
- diagrammes ;
- captures Builders ;
- images Open Graph prioritaires.

### Sprint 06 — SEO et migration

- canonicals ;
- hreflang ;
- sitemap ;
- robots.txt ;
- données structurées ;
- redirections P0 ;
- migration du wiki et du blog.

### Sprint 07 — Sécurité et préproduction

- document root `public/` ;
- fichiers sensibles ;
- headers ;
- formulaires ;
- sauvegarde et restauration ;
- licences Builders ;
- recette P0.

### Sprint 08 — Mise en ligne

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
