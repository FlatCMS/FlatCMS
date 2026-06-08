# PREPRODUCTION_TEST_PLAN — Plan de recette avant mise en production de FlatCMS

> **Plan de validation fonctionnelle, technique, éditoriale et juridique**
>
> Projet : FlatCMS  
> Site : `https://flat-cms.fr`  
> Thème : `flatcms`  
> Date : 8 juin 2026  
> Documents parents : `LAUNCH_CHECKLIST.md`, `THEME_SPECIFICATION.md`, `DESIGN_SYSTEM.md`, `NAVIGATION_SPECIFICATION.md`, `COMPONENT_LIBRARY.md`, `HOMEPAGE_WIREFRAME.md`, `MEDIA_PLAN.md`  
> Statut : plan de préproduction de référence pour Codex et la validation humaine  
> Portée : site principal, documentation, blog, téléchargement, démo, Builders, formulaires, licences et services associés

---

# 1. Objectif

Ce document définit la recette complète du futur site officiel FlatCMS
avant sa mise en production.

La préproduction doit permettre de vérifier :

- la conformité des contenus ;
- la cohérence du thème ;
- la navigation ;
- les six locales ;
- les performances ;
- l’accessibilité ;
- le SEO ;
- les données structurées ;
- les formulaires ;
- la sécurité ;
- les redirections ;
- les téléchargements ;
- les licences Builders ;
- les sauvegardes ;
- la résilience ;
- le monitoring ;
- les obligations juridiques ;
- la capacité de retour arrière.

La mise en production ne doit être autorisée que lorsque les critères
bloquants sont levés.

---

# 2. Principe de décision

## Go

Le site peut être publié si :

- aucun défaut critique n’est ouvert ;
- aucun défaut majeur bloquant le parcours principal n’est ouvert ;
- les pages P0 sont validées ;
- les redirections sont testées ;
- les formulaires fonctionnent ;
- le frontend reste stable sans les services optionnels ;
- les sauvegardes et la restauration sont validées ;
- les mentions juridiques publiées ne contiennent aucun placeholder ;
- les tests de licences Builders sont conformes ;
- le monitoring est actif.

## No-Go

La publication est interdite si l’un des points suivants est constaté :

- erreur HTTP 500 sur une page publique ;
- frontend cassé après expiration d’une licence ;
- données sensibles exposées ;
- téléchargement officiel non vérifiable ;
- formulaire envoyant des secrets dans les logs ;
- mauvaise locale ou boucle de redirection ;
- page P0 manquante ;
- erreur de statut 404 ;
- certificat HTTPS invalide ;
- sauvegarde non restaurable ;
- mention juridique incomplète publiée ;
- paiement ou abonnement non testé ;
- route d’installation encore accessible sans contrôle ;
- dépendance critique à un service externe indisponible.

---

# 3. Environnements

## Développement local

Usages :

- développement ;
- tests unitaires ;
- tests de composants ;
- tests Builders ;
- validation rapide.

## Intégration

Usages :

- assemblage du thème ;
- tests automatisés ;
- vérification des migrations ;
- intégration des médias ;
- tests des locales.

## Préproduction

La préproduction doit reproduire autant que possible :

- version PHP ;
- serveur web ;
- configuration Nginx ou Apache ;
- HTTPS ;
- cache ;
- CDN ;
- variables d’environnement ;
- tâches planifiées ;
- système de licences ;
- e-mail ;
- analytics ;
- politique de cookies ;
- stockage ;
- permissions.

## Production

La production n’est pas un environnement de test.

Aucune correction non validée ne doit y être appliquée directement sans
procédure de retour arrière.

---

# 4. Données de préproduction

## Règle

Utiliser uniquement :

- contenus officiels validés ;
- comptes de test ;
- données fictives ;
- domaines de test ;
- moyens de paiement sandbox ;
- clés API de test ;
- adresses e-mail dédiées.

## Interdictions

Ne pas utiliser :

- mot de passe réel ;
- carte bancaire réelle ;
- données client ;
- clés production dans un environnement non sécurisé ;
- données personnelles inutiles ;
- sauvegarde de production non anonymisée.

---

# 5. Rôles de validation

## Responsable projet

- valide le périmètre ;
- arbitre les écarts ;
- décide du Go/No-Go.

## Développeur / Codex

- exécute les tests techniques ;
- corrige ;
- produit les rapports ;
- fournit les preuves.

## Validation éditoriale

- relit les contenus ;
- vérifie les locales ;
- valide les CTA ;
- contrôle les médias.

## Validation juridique

- mentions légales ;
- confidentialité ;
- CGV ;
- cookies ;
- licences ;
- médiation ;
- résiliation.

## Validation sécurité

- exposition ;
- secrets ;
- permissions ;
- formulaires ;
- téléchargements ;
- logs ;
- headers.

Une même personne peut cumuler plusieurs rôles, mais les validations
doivent rester traçables.

---

# 6. Niveaux de sévérité

## Critique — P0

Exemples :

- fuite de secret ;
- perte de données ;
- site inaccessible ;
- erreur 500 générale ;
- paiement incohérent ;
- licence cassant le frontend ;
- téléchargement compromis ;
- faille exploitable.

Décision :

```text
No-Go
```

## Majeur — P1

Exemples :

- parcours principal bloqué ;
- locale importante incomplète ;
- formulaire inutilisable ;
- navigation mobile cassée ;
- redirection essentielle absente ;
- accessibilité bloquante.

Décision :

```text
No-Go sauf dérogation formelle exceptionnelle
```

## Modéré — P2

Exemples :

- détail visuel ;
- contenu secondaire incomplet ;
- incohérence non bloquante ;
- média non prioritaire absent.

Décision :

```text
Correction avant lancement si possible ou ticket daté
```

## Mineur — P3

Exemples :

- microcopy ;
- espacement ;
- amélioration esthétique ;
- optimisation secondaire.

Décision :

```text
Peut être reporté
```

---

# 7. Préconditions de recette

- [ ] Version candidate figée.
- [ ] Tag ou identifiant de build.
- [ ] Changelog disponible.
- [ ] Configuration documentée.
- [ ] Variables d’environnement chargées.
- [ ] Base de données non requise ou non présente selon architecture.
- [ ] Stockage JSON initialisé.
- [ ] Thème `flatcms` activé.
- [ ] Pages P0 importées.
- [ ] Médias P0 disponibles.
- [ ] Redirections importées.
- [ ] Certificat HTTPS valide.
- [ ] E-mail de test configuré.
- [ ] Paiement sandbox configuré si concerné.
- [ ] Service de licence sandbox configuré.
- [ ] Sauvegarde initiale créée.
- [ ] Monitoring de test actif.

---

# 8. Tests de disponibilité

## Pages principales

Vérifier un statut `200` sur :

- accueil ;
- pourquoi FlatCMS ;
- fonctionnalités ;
- architecture ;
- documentation ;
- installation ;
- téléchargement ;
- licences ;
- tarifs ;
- agent-ready ;
- à propos ;
- contact ;
- mentions légales ;
- confidentialité.

## Sous-domaines

- démo ;
- autres sous-domaines réellement conservés.

## Résultats attendus

- aucun timeout ;
- aucun `5xx` ;
- aucune boucle ;
- aucun contenu vide ;
- aucune erreur PHP affichée.

---

# 9. Tests du thème `flatcms`

## Mode sombre

- [ ] Fond conforme.
- [ ] Texte lisible.
- [ ] Cartes cohérentes.
- [ ] Code lisible.
- [ ] Images adaptées.
- [ ] Logo correct.
- [ ] Focus visible.

## Mode clair

- [ ] Aucun simple inversement cassé.
- [ ] Contrastes validés.
- [ ] Halos maîtrisés.
- [ ] Panneaux de code adaptés.
- [ ] Logo adapté.
- [ ] Illustrations visibles.

## Persistance

- [ ] Choix sauvegardé.
- [ ] Mode système pris en compte.
- [ ] Aucun flash excessif.
- [ ] Fonctionnement sans cookie non nécessaire si stockage local retenu.

---

# 10. Tests responsive

## Largeurs minimales

```text
320
375
480
768
1024
1280
1440
```

## Vérifications

- [ ] Aucun scroll horizontal global.
- [ ] Header utilisable.
- [ ] Menu mobile accessible.
- [ ] CTA visibles.
- [ ] Cartes non écrasées.
- [ ] Code scrollable.
- [ ] Tableaux gérés.
- [ ] Images non déformées.
- [ ] Footer lisible.
- [ ] Formulaires utilisables.
- [ ] Aucun texte essentiel tronqué.
- [ ] Touch targets suffisantes.

## Orientation

- portrait ;
- paysage ;
- rotation sans perte de contexte.

---

# 11. Tests navigateurs

## Desktop

- Safari récent ;
- Chrome récent ;
- Firefox récent ;
- Edge récent.

## Mobile

- Safari iOS ;
- Chrome Android.

## Vérifications

- CSS ;
- JavaScript ;
- sticky header ;
- menus ;
- formulaires ;
- accordéons ;
- thème ;
- vidéos ;
- téléchargement ;
- cookies ;
- focus.

---

# 12. Tests sans JavaScript

La page doit conserver :

- contenu ;
- navigation essentielle ;
- CTA ;
- liens ;
- formulaires avec soumission serveur ;
- FAQ via `<details>` ;
- pages légales ;
- téléchargement ;
- 404.

Les fonctions améliorées peuvent être limitées, mais le site ne doit pas
devenir vide ou inutilisable.

---

# 13. Tests de navigation

## Header

- logo ;
- menus ;
- sous-menus ;
- méga-menu ;
- CTA ;
- langue ;
- thème ;
- mobile.

## Footer

- colonnes ;
- liens légaux ;
- contact ;
- sécurité ;
- téléchargements ;
- licences.

## Fil d’Ariane

- structure ;
- page courante ;
- locale ;
- données structurées.

## Liens

- aucun lien mort ;
- aucune URL de préproduction ;
- aucun `javascript:`;
- aucun lien vers un placeholder ;
- liens externes vérifiés.

---

# 14. Tests multilingues

## Locales

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

## Pour chaque locale

- [ ] Accueil disponible.
- [ ] Navigation traduite.
- [ ] Footer traduit.
- [ ] Pages P0 disponibles ou statut explicite.
- [ ] URL correcte.
- [ ] Canonical correct.
- [ ] `hreflang` correct.
- [ ] Langue du document correcte.
- [ ] Dates localisées.
- [ ] Prix et devise cohérents.
- [ ] Médias localisés si texte intégré.
- [ ] Formulaires traduits.
- [ ] Messages d’erreur traduits.
- [ ] 404 localisée.

## Traduction manquante

Le fallback ne doit pas créer :

- contenu dupliqué sous mauvaise locale ;
- canonical incohérent ;
- lien `hreflang` vers une page inexistante.

---

# 15. Tests éditoriaux

## Contenus

- [ ] H1 unique.
- [ ] Hiérarchie H2/H3.
- [ ] Orthographe.
- [ ] Terminologie.
- [ ] Ton.
- [ ] CTA cohérents.
- [ ] Liens internes.
- [ ] Version exacte.
- [ ] Prix exacts.
- [ ] Licence exacte.
- [ ] Statuts fonctionnels exacts.
- [ ] Aucune promesse non implémentée.
- [ ] Aucune affirmation absolue non démontrée.

## Contenus sensibles

Vérifier spécialement :

- tarifs ;
- licences ;
- expiration ;
- IA ;
- sécurité ;
- comparatifs ;
- mentions légales ;
- confidentialité.

---

# 16. Tests médias

## Global

- [ ] Fichiers présents.
- [ ] Dimensions.
- [ ] Poids.
- [ ] Alt.
- [ ] Légende.
- [ ] Source.
- [ ] Licence.
- [ ] Locale.
- [ ] Mode clair/sombre.
- [ ] Lazy loading.
- [ ] Aucun secret.

## Captures

- aucune donnée personnelle ;
- aucune clé ;
- aucun domaine privé ;
- aucune IP ;
- aucun chemin local ;
- aucune licence complète.

## Manifest

- IDs uniques ;
- fichiers existants ;
- chemins valides ;
- formats cohérents.

---

# 17. Tests accessibilité

Objectif :

```text
WCAG 2.2 AA
```

## Clavier

- [ ] Skip link.
- [ ] Ordre logique.
- [ ] Menu.
- [ ] Sous-menu.
- [ ] Accordéons.
- [ ] Onglets.
- [ ] Dialogues.
- [ ] Formulaires.
- [ ] Boutons.
- [ ] Focus visible.
- [ ] Aucun piège.

## Sémantique

- landmarks ;
- titres ;
- listes ;
- tableaux ;
- formulaires ;
- messages ;
- navigation.

## Visuel

- contrastes ;
- zoom 200 % ;
- reflow ;
- texte espacé ;
- cibles ;
- couleur non exclusive.

## Mouvement

- `prefers-reduced-motion` ;
- pas de flash ;
- pas de mouvement automatique gênant.

## Lecteur d’écran

Tester au minimum :

- header ;
- menu ;
- Hero ;
- FAQ ;
- formulaire ;
- footer ;
- erreurs.

---

# 18. Tests SEO technique

## Statuts

- pages valides : `200` ;
- redirections : `301` ou `308` ;
- absentes : `404` ;
- supprimées définitives : `410` si politique ;
- erreurs serveur : aucun `200`.

## Métadonnées

- title unique ;
- meta description ;
- canonical ;
- robots ;
- Open Graph ;
- Twitter/X si utilisé ;
- langue.

## Indexation

- pages publiques indexables ;
- pages de recherche en `noindex` ;
- admin non indexable ;
- démo selon politique ;
- préproduction bloquée ;
- 404 en `noindex`.

## Sitemaps

- URLs `200` uniquement ;
- canonicals cohérents ;
- locales ;
- dates exactes ;
- aucune 404 ;
- aucune redirection.

---

# 19. Tests robots et crawl

## `robots.txt`

- environnement préproduction bloqué ;
- production conforme à `CRAWL_POLICY.md` ;
- sitemap déclaré ;
- admin et chemins privés adaptés ;
- robots d’entraînement selon politique ;
- aucun blocage involontaire du site public.

## Vérification

- Googlebot ;
- moteurs classiques ;
- moteurs génératifs selon politique ;
- téléchargements ;
- assets CSS/JS accessibles.

---

# 20. Tests données structurées

## Pages

- `WebSite` ;
- `WebPage` ;
- `Organization` ou `Person` ;
- `SoftwareApplication` ;
- `Article` ;
- `BreadcrumbList` ;
- `Product` / `Offer` si réel.

## Contrôles

- JSON valide ;
- IDs cohérents ;
- URLs absolues ;
- données visibles ;
- prix exact ;
- langue ;
- images ;
- auteur ;
- dates ;
- aucune propriété fictive ;
- aucun type inventé.

## Erreurs

Aucune donnée métier sur :

- 404 ;
- erreur 500 ;
- recherche vide ;
- page non publiée.

---

# 21. Tests performances

## Mesures

- TTFB ;
- LCP ;
- INP ;
- CLS ;
- poids total ;
- nombre de requêtes ;
- cache ;
- compression ;
- images ;
- JavaScript ;
- CSS.

## Conditions

Tester :

- mobile moyen ;
- réseau limité ;
- cache froid ;
- cache chaud ;
- mode sombre ;
- mode clair.

## Budgets

Comparer avec :

- `THEME_SPECIFICATION.md` ;
- `HOMEPAGE_WIREFRAME.md` ;
- `MEDIA_PLAN.md`.

## Bloquants

- image Hero énorme ;
- JS non utilisé massif ;
- police bloquante ;
- CLS important ;
- script tiers avant consentement ;
- iframe lourde.

---

# 22. Tests cache et CDN

- [ ] Assets fingerprintés.
- [ ] HTML non figé incorrectement.
- [ ] 404 conservant le statut.
- [ ] Purge testée.
- [ ] Nouvelle page remplaçant une ancienne 404.
- [ ] Headers cohérents.
- [ ] Compression Brotli/Gzip.
- [ ] Aucun contenu privé mis en cache publiquement.
- [ ] Administration exclue du cache.
- [ ] Formulaires exclus.

---

# 23. Tests HTTPS et sécurité transport

- certificat valide ;
- chaîne complète ;
- redirection HTTP vers HTTPS ;
- aucun mixed content ;
- cookies `Secure` ;
- HSTS selon politique ;
- preload uniquement si décision maîtrisée ;
- TLS moderne ;
- liens absolus HTTPS.

---

# 24. Tests en-têtes de sécurité

Vérifier selon politique :

- `Content-Security-Policy` ;
- `X-Content-Type-Options` ;
- `Referrer-Policy` ;
- `Permissions-Policy` ;
- protection de framing ;
- HSTS ;
- cache.

## CSP

- aucun script inline non autorisé ;
- aucun CDN obligatoire ;
- médias autorisés ;
- formulaires ;
- embeds conditionnels ;
- rapport testé si utilisé.

---

# 25. Tests formulaires

## Contact

- champs requis ;
- validation client ;
- validation serveur ;
- CSRF ;
- rate limit ;
- honeypot ;
- e-mail ;
- message de succès ;
- message d’échec ;
- accusé de réception ;
- routage ;
- stockage ;
- suppression.

## Newsletter

Si active :

- consentement ;
- preuve ;
- double opt-in ;
- désinscription ;
- locale ;
- fréquence.

## Données personnelles

- mention courte ;
- lien confidentialité ;
- absence de case précochée ;
- minimisation.

---

# 26. Tests e-mail

- SPF ;
- DKIM ;
- DMARC ;
- expéditeur ;
- Reply-To ;
- texte et HTML ;
- mobile ;
- mode sombre ;
- liens ;
- désinscription ;
- encodage ;
- absence de secret ;
- gestion des rebonds ;
- erreurs SMTP.

## Boîtes de test

Tester au minimum :

- Gmail ;
- Outlook ;
- Apple Mail si possible.

---

# 27. Tests téléchargements

## Package

- version ;
- nom ;
- taille ;
- checksum ;
- contenu ;
- permissions ;
- installateur ;
- absence de secrets ;
- absence de fichiers de développement inutiles.

## Page

- lien fonctionnel ;
- URL officielle ;
- checksum copiable ;
- notes ;
- licence ;
- prérequis ;
- ancienne version si publiée.

## Sécurité

- MIME correct ;
- pas d’exécution ;
- cache ;
- logs ;
- intégrité.

---

# 28. Tests installation

## Environnements minimum

- MAMP ;
- WAMP ;
- Apache ;
- Nginx.

Selon disponibilité :

- Synology ;
- Raspberry Pi ;
- hébergement mutualisé.

## Parcours

- extraction ;
- prérequis ;
- permissions ;
- document root ;
- installateur ;
- compte ;
- frontend ;
- admin ;
- finalisation ;
- protection installateur ;
- sauvegarde.

## Résultat

Aucune étape bloquée sans message compréhensible.

---

# 29. Tests Builders

## PagesBuilder

- créer ;
- modifier ;
- enregistrer ;
- publier ;
- prévisualiser ;
- expirer ;
- renouveler.

## MenuBuilder

- menus ;
- méga-menu ;
- mobile ;
- expiration ;
- fallback.

## FooterBuilder

- colonnes ;
- liens légaux ;
- expiration ;
- fallback.

## Invariant

```text
Le frontend déjà construit reste toujours visible.
```

---

# 30. Tests licences

États :

- local ;
- valide ;
- période de grâce ;
- absente en production ;
- expirée ;
- domaine différent ;
- révoquée ;
- service indisponible.

## Vérifications

- rendu public ;
- éditeur ;
- sauvegarde ;
- mises à jour ;
- support ;
- bandeau ;
- cache ;
- logs ;
- reprise.

## Bloquant critique

Toute suppression ou altération du frontend est un P0.

---

# 31. Tests système de licence indisponible

- couper l’API ;
- simuler timeout ;
- simuler réponse invalide ;
- simuler DNS ;
- vider cache partiellement ;
- vérifier dernier état connu ;
- vérifier backoff ;
- vérifier aucun appel frontend ;
- vérifier aucun `500`.

---

# 32. Tests paiement et abonnement

Uniquement si la vente est activée.

## Sandbox

- achat Solo ;
- achat Duo ;
- achat Suite ;
- taxes ;
- facture ;
- activation ;
- e-mail ;
- renouvellement ;
- échec ;
- période de grâce ;
- résiliation ;
- remboursement si politique ;
- changement de domaine ;
- upgrade.

## Contrôles

- montant exact ;
- devise ;
- HT/TTC ;
- fréquence ;
- consentement ;
- CGV ;
- informations avant paiement ;
- aucun double débit.

---

# 33. Tests compte client

- création ;
- vérification e-mail ;
- connexion ;
- mot de passe oublié ;
- changement de mot de passe ;
- 2FA si actif ;
- profil ;
- licences ;
- factures ;
- résiliation ;
- suppression ;
- export ;
- sécurité session.

---

# 34. Tests confidentialité et cookies

## Avant consentement

- aucun traceur non nécessaire ;
- aucun embed tiers chargé ;
- aucun analytics soumis au consentement.

## Bandeau

- accepter ;
- refuser ;
- personnaliser ;
- choix équivalents ;
- lien permanent ;
- retrait ;
- langue.

## Conservation

- preuve ;
- durée ;
- renouvellement ;
- suppression.

## Politique

Correspondance exacte entre :

- texte ;
- code ;
- prestataires ;
- cookies.

---

# 35. Tests analytics

- uniquement après consentement si requis ;
- aucune donnée personnelle inutile ;
- IP selon politique ;
- événements validés ;
- pas de double comptage ;
- exclusions admin ;
- exclusions préproduction ;
- consentement respecté ;
- désactivation testée.

---

# 36. Tests IA

Si une fonction IA est activée :

- clé côté serveur ;
- clé masquée ;
- test connexion ;
- timeout ;
- quota ;
- permissions ;
- brouillon par défaut ;
- validation ;
- erreurs ;
- panne sans impact ;
- logs filtrés ;
- coûts ;
- données personnelles ;
- information utilisateur.

## Frontend

Aucun appel IA ne doit être nécessaire pour rendre une page publique.

---

# 37. Tests recherche

- index ;
- résultats ;
- aucune fuite de brouillon ;
- locales ;
- pertinence ;
- zéro résultat ;
- pagination ;
- `noindex` ;
- échappement ;
- rate limit ;
- caractères spéciaux ;
- requête longue.

---

# 38. Tests 404 et erreurs

## 404

- statut réel ;
- locale ;
- liens ;
- noindex ;
- aucun canonical accueil ;
- aucun détail serveur.

## 500

- statut réel ;
- fallback ;
- identifiant d’erreur ;
- aucun détail sensible ;
- log ;
- monitoring.

## Maintenance

- statut adapté ;
- message ;
- Retry-After si pertinent ;
- admin accessible selon politique.

---

# 39. Tests redirections

## Sources

- ancien site ;
- wiki ;
- blog ;
- URLs localisées ;
- téléchargements ;
- pages renommées.

## Vérifications

- statut permanent ;
- destination exacte ;
- aucune chaîne ;
- aucune boucle ;
- paramètres utiles ;
- locale ;
- canonical ;
- sitemap ;
- liens internes mis à jour.

## Règle

Pas de redirection générale vers l’accueil.

---

# 40. Tests sauvegarde

## Contenu

- code ;
- JSON ;
- médias ;
- configuration ;
- secrets ;
- règles serveur ;
- licences ;
- logs nécessaires.

## Vérifications

- création ;
- téléchargement sécurisé ;
- stockage ;
- chiffrement si pertinent ;
- rotation ;
- suppression ;
- absence d’exposition publique.

---

# 41. Test de restauration

Procédure obligatoire :

1. créer une sauvegarde ;
2. modifier ou supprimer des contenus de test ;
3. restaurer dans un environnement isolé ;
4. vérifier pages ;
5. vérifier médias ;
6. vérifier comptes ;
7. vérifier licences ;
8. vérifier configuration ;
9. documenter la durée.

## Invariant

Une sauvegarde non restaurée en test n’est pas considérée comme validée.

---

# 42. Tests mise à jour

- sauvegarde préalable ;
- compatibilité PHP ;
- migration ;
- cache ;
- modules ;
- thèmes ;
- Builders ;
- rollback ;
- données ;
- logs ;
- frontend.

## Retour arrière

La procédure doit être testée, pas seulement écrite.

---

# 43. Monitoring

## Disponibilité

Surveiller :

- accueil ;
- documentation ;
- téléchargement ;
- démo ;
- API licence ;
- e-mail si possible.

## Métriques

- HTTP ;
- latence ;
- erreurs ;
- CPU ;
- mémoire ;
- stockage ;
- certificats ;
- tâches cron ;
- sauvegardes.

## Alertes

- 5xx ;
- téléchargement indisponible ;
- certificat proche expiration ;
- espace disque ;
- tâche échouée ;
- licence API indisponible ;
- e-mail en échec.

---

# 44. Logs

## Vérifier

- rotation ;
- permissions ;
- absence de secrets ;
- dates ;
- niveaux ;
- corrélation ;
- accès ;
- conservation ;
- téléchargement sécurisé.

## Tests

- erreur 404 ;
- erreur 500 ;
- formulaire ;
- login ;
- licence ;
- paiement ;
- sauvegarde ;
- IA.

---

# 45. Tâches planifiées

- sauvegarde ;
- nettoyage ;
- publication planifiée ;
- réinitialisation démo ;
- expiration licences ;
- e-mails ;
- indexation recherche ;
- purge cache ;
- rétention logs.

## Vérifications

- fuseau ;
- exécution ;
- idempotence ;
- verrouillage ;
- logs ;
- alerte ;
- reprise après échec.

---

# 46. Tests de sécurité applicative

## Authentification

- brute force ;
- session fixation ;
- déconnexion ;
- reset ;
- 2FA ;
- rôles.

## Autorisation

- accès direct ;
- changement d’ID ;
- routes admin ;
- médias privés ;
- actions Builders ;
- licences.

## Entrées

- XSS ;
- HTML ;
- URLs ;
- fichiers ;
- entêtes e-mail ;
- JSON ;
- slugs ;
- recherche.

## CSRF

- formulaires ;
- actions admin ;
- licence ;
- compte ;
- paiement.

## Upload

- extension ;
- MIME ;
- taille ;
- nom ;
- stockage ;
- exécution ;
- SVG.

---

# 47. Tests fichiers sensibles

Doivent être inaccessibles publiquement :

```text
.env
.env.local
config/
data/
storage/
app/
resources/
vendor/
backups/
logs/
composer.json
nginx.conf
```

Selon le contrat réel du projet.

## Vérification

Tester via HTTP et CDN.

Résultat attendu :

```text
403 ou 404
```

Jamais le contenu du fichier.

---

# 48. Tests permissions système

- lecture du code ;
- écriture uniquement sur dossiers requis ;
- aucun `777` global ;
- compte PHP ;
- groupe ;
- ACL ;
- uploads ;
- cache ;
- sauvegardes ;
- logs ;
- secrets.

---

# 49. Tests juridiques

## Mentions légales

- aucun placeholder ;
- structure juridique exacte ;
- hébergeur ;
- directeur ;
- contact ;
- date.

## Confidentialité

- responsable ;
- traitements réels ;
- prestataires ;
- durées ;
- droits ;
- CNIL ;
- cookies ;
- IA.

## CGV

Si vente :

- prix ;
- taxes ;
- durée ;
- renouvellement ;
- résiliation ;
- remboursement ;
- rétractation ;
- médiation ;
- support ;
- licence.

## Footer

Tous les liens fonctionnent.

---

# 50. Tests licences logicielles

- `LICENSE` ;
- `LICENSING.md` ;
- `COMMERCIAL_LICENSE.md` ;
- `TRADEMARK.md` ;
- `CLA.md` ;
- en-têtes SPDX ;
- dépendances tierces ;
- notices ;
- aucun premium dans le Core par erreur.

---

# 51. Tests GitHub et release

- tag ;
- version ;
- notes ;
- assets ;
- checksum ;
- source ;
- package officiel ;
- changelog ;
- licence ;
- aucune archive incorrecte ;
- lien site ↔ release.

---

# 52. Tests de la démo

- comptes limités ;
- données fictives ;
- réinitialisation ;
- bannière ;
- noindex selon politique ;
- aucun secret ;
- aucune donnée persistante ;
- rôles ;
- uploads limités ;
- logs ;
- sécurité.

---

# 53. Tests de charge raisonnables

Sans prétendre à un benchmark universel :

- home ;
- documentation ;
- téléchargement ;
- recherche ;
- formulaire ;
- licence API.

## Objectif

Identifier :

- erreurs ;
- saturation ;
- fuites ;
- cache ;
- temps de réponse ;
- limites.

Ne pas publier un slogan de performance à partir d’un test interne non
reproductible.

---

# 54. Tests de résilience

Simuler :

- panne e-mail ;
- panne IA ;
- panne licence ;
- panne analytics ;
- CDN indisponible ;
- média manquant ;
- erreur cache ;
- log non inscriptible ;
- tâche cron échouée.

## Invariant

Le site public et les contenus essentiels doivent rester accessibles
lorsqu’un service optionnel est indisponible.

---

# 55. Tests de retour arrière

## Déploiement

- sauvegarde ;
- ancienne release ;
- configuration ;
- cache ;
- fichiers ;
- symlink si utilisé.

## Critère

Le rollback doit pouvoir être exécuté dans un délai défini et documenté.

---

# 56. Rapport de recette

## En-tête

```text
Version testée
Build
Date
Environnement
Testeur
Résultat
```

## Cas

```text
ID
Section
Précondition
Étapes
Résultat attendu
Résultat obtenu
Statut
Sévérité
Preuve
Ticket
```

## Statuts

```text
PASS
FAIL
BLOCKED
NOT_APPLICABLE
DEFERRED
```

---

# 57. Preuves

Les preuves peuvent inclure :

- capture ;
- vidéo ;
- sortie `curl` ;
- rapport Lighthouse ;
- rapport accessibilité ;
- log filtré ;
- e-mail ;
- checksum ;
- export JSON ;
- ticket.

Ne jamais joindre une preuve contenant un secret non masqué.

---

# 58. Commandes utiles

## Statut HTTP

```bash
curl -I https://preprod.flat-cms.fr/fr-FR/
```

## 404

```bash
curl -sS -o /dev/null -w "%{http_code}\n" \
  https://preprod.flat-cms.fr/fr-FR/url-inexistante
```

## Redirection

```bash
curl -I https://preprod.flat-cms.fr/ancienne-url
```

## Headers

```bash
curl -sSI https://preprod.flat-cms.fr/fr-FR/
```

## Checksum

```bash
shasum -a 256 FlatCMS-1.0.0.zip
```

---

# 59. Automatisation recommandée

## CI

- lint PHP ;
- tests unitaires ;
- tests intégration ;
- validation JSON ;
- liens ;
- HTML ;
- CSS ;
- JavaScript ;
- manifest médias ;
- secrets ;
- licences ;
- package.

## E2E

- Playwright ou équivalent ;
- locales ;
- mobile ;
- formulaires ;
- navigation ;
- thème ;
- Builders ;
- 404.

## Sécurité

- scan dépendances ;
- recherche secrets ;
- contrôle fichiers sensibles ;
- entêtes ;
- CSP.

---

# 60. Rapport demandé à Codex

Codex doit produire :

## Synthèse

- nombre de tests ;
- passés ;
- échoués ;
- bloqués ;
- non applicables ;
- P0 ;
- P1 ;
- recommandation Go/No-Go.

## Écarts

```text
ID
Fichier
Route
Comportement
Risque
Sévérité
Correction
Test
```

## Confirmations critiques

- [ ] Aucun P0 ouvert.
- [ ] Aucun P1 bloquant.
- [ ] Frontend Builders intact après expiration.
- [ ] 404 réelle.
- [ ] Sauvegarde restaurée.
- [ ] Redirections validées.
- [ ] Formulaires sécurisés.
- [ ] Données sensibles protégées.
- [ ] Six locales validées.
- [ ] Pages juridiques complètes.
- [ ] Téléchargement vérifiable.
- [ ] Monitoring actif.
- [ ] Rollback testé.

---

# 61. Checklist Go-Live finale

## Contenus

- [ ] Pages P0.
- [ ] Locales.
- [ ] Médias.
- [ ] CTA.
- [ ] Liens.
- [ ] Prix.
- [ ] Versions.

## Technique

- [ ] HTTPS.
- [ ] Cache.
- [ ] CDN.
- [ ] Cron.
- [ ] Logs.
- [ ] Sauvegardes.
- [ ] Monitoring.
- [ ] Rollback.

## SEO

- [ ] Canonicals.
- [ ] Hreflang.
- [ ] Sitemaps.
- [ ] Robots.
- [ ] Structured data.
- [ ] Redirections.
- [ ] 404.

## Sécurité

- [ ] Secrets.
- [ ] Permissions.
- [ ] CSP.
- [ ] Auth.
- [ ] CSRF.
- [ ] Uploads.
- [ ] Fichiers sensibles.

## Commercial

- [ ] Prix.
- [ ] Taxes.
- [ ] Paiement.
- [ ] Factures.
- [ ] Licences.
- [ ] Résiliation.
- [ ] Support.

## Juridique

- [ ] Structure juridique.
- [ ] Mentions légales.
- [ ] Confidentialité.
- [ ] Cookies.
- [ ] CGV.
- [ ] Médiation si applicable.

---

# 62. Fenêtre de lancement

Plan recommandé :

1. gel des contenus ;
2. sauvegarde ;
3. déploiement ;
4. purge cache ;
5. tests fumée ;
6. ouverture progressive ;
7. surveillance ;
8. validation finale.

## Tests fumée après déploiement

- accueil ;
- navigation ;
- téléchargement ;
- contact ;
- login ;
- tarif ;
- 404 ;
- locale ;
- licence ;
- monitoring.

---

# 63. Surveillance post-lancement

## Première heure

- erreurs ;
- logs ;
- formulaires ;
- téléchargements ;
- cache ;
- redirections ;
- certificats.

## Premier jour

- Search Console ;
- analytics ;
- 404 ;
- e-mails ;
- licences ;
- paiement ;
- performance.

## Première semaine

- indexation ;
- hreflang ;
- backlinks ;
- contenus ;
- demandes support ;
- erreurs répétées ;
- sauvegardes.

---

# 64. Critères de clôture de la préproduction

La phase est clôturée si :

1. le rapport est complet ;
2. les preuves sont archivées ;
3. les P0 sont fermés ;
4. les P1 sont fermés ou formellement acceptés ;
5. le Go est signé ;
6. le rollback est prêt ;
7. les responsabilités de surveillance sont attribuées ;
8. la version candidate devient release.

---

# 65. Sources internes

- `LAUNCH_CHECKLIST.md`
- `THEME_SPECIFICATION.md`
- `DESIGN_SYSTEM.md`
- `NAVIGATION_SPECIFICATION.md`
- `COMPONENT_LIBRARY.md`
- `HOMEPAGE_WIREFRAME.md`
- `MEDIA_PLAN.md`
- `404_CONTENT.md`
- `PRIVACY_CONTENT.md`
- `LEGAL_NOTICE_CONTENT.md`
- `BUILDER_LICENSE_ENFORCEMENT.md`
- `REDIRECTS.md`
- `STRUCTURED_DATA.md`
- `CRAWL_POLICY.md`
- `MULTILINGUAL.md`

---

# 66. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction du plan de préproduction | ChatGPT / Alain BROYE |

---

# 67. Prochaine action

Après ajout dans le Drive :

```text
Créer SECURITY_BASELINE.md
```

Ce document définira le socle de sécurité du futur site et de sa chaîne
de déploiement :

- configuration serveur ;
- permissions ;
- secrets ;
- headers ;
- authentification ;
- logs ;
- sauvegardes ;
- mises à jour ;
- dépendances ;
- formulaires ;
- uploads ;
- licences ;
- IA ;
- réponse aux incidents.
