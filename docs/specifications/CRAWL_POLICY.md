# CRAWL_POLICY — Politique d’exploration, d’indexation et d’accès automatisé de FlatCMS

> **Document directeur de crawl**
>
> Projet : FlatCMS  
> Domaines concernés : `https://flat-cms.fr` et `https://demo.flat-cms.fr`  
> Date de création : 8 juin 2026  
> Documents parents : `SEO.md`, `SITE_ARCHITECTURE.md`, `CONTENT_MATRIX.md`, `DOCUMENTATION_MAP.md`, `KEYWORDS.md`, `REDIRECTS.md`, `STRUCTURED_DATA.md`  
> Statut : politique initiale à réviser avant mise en production et deux fois par an

---

## 1. Objet du document

Ce document définit la politique de FlatCMS concernant :

- l’exploration par les moteurs de recherche ;
- l’indexation des pages et ressources ;
- les robots de recherche générative ;
- les robots déclenchés par un utilisateur ;
- les robots destinés à l’entraînement des modèles ;
- `robots.txt` ;
- les balises robots ;
- `X-Robots-Tag` ;
- les environnements de production, préproduction et démonstration ;
- la protection des répertoires privés ;
- la limitation de charge ;
- la vérification des robots ;
- les journaux et audits.

Cette politique vise trois objectifs simultanés :

1. rendre le site officiel largement découvrable ;
2. permettre sa citation dans les moteurs classiques et génératifs ;
3. conserver un contrôle explicite sur l’utilisation des contenus pour l’entraînement.

---

## 2. Principes fondamentaux

## 2.1 `robots.txt` n’est pas un mécanisme de sécurité

Le fichier `robots.txt` exprime des préférences d’exploration à des robots coopératifs.

Il ne protège pas :

- les secrets ;
- les données personnelles ;
- les fichiers de configuration ;
- les sauvegardes ;
- l’administration ;
- les contenus premium ;
- les endpoints privés.

Les ressources sensibles doivent être protégées par :

- une architecture de document root correcte ;
- des contrôles d’accès ;
- une authentification ;
- des permissions serveur ;
- des réponses HTTP adaptées ;
- une absence d’exposition publique.

Ne jamais publier un chemin secret dans `robots.txt` en pensant le protéger.

---

## 2.2 Exploration et indexation sont deux opérations différentes

```text
robots.txt
→ contrôle principalement l’exploration

meta robots et X-Robots-Tag
→ contrôlent l’indexation et la présentation
```

Une page bloquée par `robots.txt` peut rester connue et parfois apparaître sous forme d’URL si des liens externes pointent vers elle.

Pour retirer une page de l’index, utiliser :

```html
<meta name="robots" content="noindex">
```

ou :

```http
X-Robots-Tag: noindex
```

La page doit rester accessible au robot afin qu’il puisse lire la directive `noindex`.

---

## 2.3 Les robots coopératifs respectent une préférence, pas une barrière

Le protocole repose sur la coopération des robots.

Pour les accès abusifs ou non conformes, utiliser en complément :

- pare-feu applicatif ;
- limitation de débit ;
- blocage IP ou ASN lorsqu’il est justifié ;
- détection comportementale ;
- authentification ;
- surveillance des logs.

---

## 2.4 Politique distincte par usage

FlatCMS distingue :

| Usage | Politique cible |
|---|---|
| Recherche classique | Autoriser |
| Recherche générative avec citation | Autoriser |
| Accès demandé par un utilisateur | Autoriser lorsque raisonnable |
| Entraînement de modèles | Refuser par défaut, décision révisable |
| Scraping agressif ou non identifié | Limiter ou bloquer |
| Préproduction et espaces privés | Interdire et protéger |

---

# 3. Décision stratégique initiale

## 3.1 Domaine officiel

Pour `flat-cms.fr` :

```text
Autoriser l’indexation classique
Autoriser les robots de recherche générative documentés
Autoriser les récupérations déclenchées par l’utilisateur
Refuser par défaut les robots explicitement dédiés à l’entraînement
```

## 3.2 Démonstration

Pour `demo.flat-cms.fr` :

```text
Indexer uniquement la page d’accueil de présentation
Placer les contenus fictifs en noindex, follow
Exclure les zones d’administration et techniques
Limiter la charge automatisée
```

## 3.3 Justification

Le référencement GEO/GIO ne nécessite pas nécessairement d’autoriser l’entraînement.

Plusieurs fournisseurs distinguent désormais :

- leurs robots de recherche ;
- leurs robots d’entraînement ;
- leurs fetchers déclenchés par un utilisateur.

Cette séparation permet à FlatCMS de rester visible dans les recherches assistées par IA sans accorder automatiquement l’utilisation de l’ensemble du contenu pour l’entraînement futur.

---

# 4. Catégories de robots

## 4.1 Moteurs de recherche classiques

À autoriser :

```text
Googlebot
Googlebot-Image
Googlebot-Video
Bingbot
Applebot
```

Sous réserve :

- du respect des répertoires interdits ;
- de la capacité du serveur ;
- de la vérification de l’identité en cas de règle WAF spécifique.

---

## 4.2 Recherche générative

À autoriser initialement :

```text
OAI-SearchBot
Claude-SearchBot
PerplexityBot
Applebot
Googlebot
```

### OpenAI

- `OAI-SearchBot` sert à faire apparaître les sites dans les fonctions de recherche de ChatGPT.
- Son contrôle est indépendant de `GPTBot`.

### Anthropic

- `Claude-SearchBot` explore le Web pour améliorer les résultats de recherche de Claude.
- Son contrôle est indépendant de `ClaudeBot`.

### Perplexity

- `PerplexityBot` est documenté comme robot de recherche et de liaison vers les sources.
- Une surveillance renforcée des logs reste recommandée.

### Google

Les fonctionnalités IA de Google Search utilisent l’infrastructure et l’index de Google Search.

Le contrôle de Google Search reste lié à `Googlebot`.

---

## 4.3 Accès déclenché par l’utilisateur

Agents documentés :

```text
ChatGPT-User
Claude-User
Perplexity-User
```

Ces agents interviennent lorsqu’un utilisateur demande explicitement l’accès à une page.

### Politique

```text
Autoriser les pages publiques
Refuser les zones privées ou sensibles
Appliquer les mêmes contrôles d’accès que pour un navigateur humain
```

### Limite importante

Certains fournisseurs indiquent que les règles `robots.txt` peuvent ne pas s’appliquer, ou être traitées différemment, pour les récupérations déclenchées par un utilisateur.

La sécurité ne doit donc jamais dépendre de `robots.txt`.

---

## 4.4 Robots d’entraînement

Bloqués par défaut :

```text
GPTBot
ClaudeBot
Google-Extended
Applebot-Extended
```

### Rôles documentés

| Robot | Usage |
|---|---|
| `GPTBot` | Contenus potentiellement utilisés pour l’entraînement des modèles OpenAI |
| `ClaudeBot` | Collecte pouvant contribuer à l’entraînement des modèles Anthropic |
| `Google-Extended` | Contrôle d’utilisation pour Gemini et certains usages de grounding |
| `Applebot-Extended` | Contrôle de l’utilisation des données Applebot pour les modèles Apple |

### Révision possible

Cette décision pourra évoluer si FlatCMS choisit volontairement de contribuer à l’amélioration des modèles.

Tout changement doit être :

- daté ;
- documenté ;
- approuvé ;
- appliqué à chaque sous-domaine concerné.

---

# 5. Politique `robots.txt` de `flat-cms.fr`

## 5.1 Version recommandée initiale

```text
# FlatCMS — robots.txt
# Production policy

User-agent: *
Allow: /
Disallow: /admin/
Disallow: /install/
Disallow: /config/
Disallow: /data/
Disallow: /storage/
Disallow: /tmp/
Disallow: /cache/
Disallow: /*?preview=
Disallow: /*?token=
Disallow: /*?session=
Disallow: /recherche/

# OpenAI search visibility
User-agent: OAI-SearchBot
Allow: /
Disallow: /admin/
Disallow: /install/
Disallow: /config/
Disallow: /data/
Disallow: /storage/

# OpenAI training opt-out
User-agent: GPTBot
Disallow: /

# Anthropic search visibility
User-agent: Claude-SearchBot
Allow: /
Disallow: /admin/
Disallow: /install/
Disallow: /config/
Disallow: /data/
Disallow: /storage/

# Anthropic training opt-out
User-agent: ClaudeBot
Disallow: /

# Perplexity search visibility
User-agent: PerplexityBot
Allow: /
Disallow: /admin/
Disallow: /install/
Disallow: /config/
Disallow: /data/
Disallow: /storage/

# Google Gemini training / grounding control
User-agent: Google-Extended
Disallow: /

# Apple model training control
User-agent: Applebot-Extended
Disallow: /

Sitemap: https://flat-cms.fr/sitemap.xml
```

---

## 5.2 Important : répertoires privés

Les lignes :

```text
Disallow: /config/
Disallow: /data/
Disallow: /storage/
```

sont une défense secondaire.

Dans une installation correcte, ces répertoires ne doivent pas être accessibles depuis le document root public.

Le site officiel doit pointer vers :

```text
public/
```

et non vers la racine du projet.

---

## 5.3 Paramètres

Les règles concernant les paramètres doivent être testées contre le routeur réel.

Ne pas bloquer aveuglément :

```text
/*
```

ou tous les paramètres si certains servent à des pages canoniques nécessaires.

À contrôler :

- prévisualisation ;
- recherche ;
- sessions ;
- jetons ;
- filtres ;
- pagination ;
- tracking.

---

## 5.4 Recherche interne

La recherche interne publique peut rester explorable ou être bloquée.

Recommandation initiale :

```text
Disallow: /recherche/
```

et :

```html
<meta name="robots" content="noindex, follow">
```

La balise `noindex` reste le contrôle principal pour éviter l’indexation.

---

# 6. Politique `robots.txt` de `demo.flat-cms.fr`

## 6.1 Objectif

La démo sert à tester FlatCMS, pas à créer un second corpus éditorial indexé.

## 6.2 Version recommandée

```text
# FlatCMS Demo — robots.txt

User-agent: *
Allow: /
Disallow: /admin/
Disallow: /install/
Disallow: /config/
Disallow: /data/
Disallow: /storage/
Disallow: /login/
Disallow: /logout/
Disallow: /account/
Disallow: /api/
Disallow: /preview/
Disallow: /search/

User-agent: GPTBot
Disallow: /

User-agent: ClaudeBot
Disallow: /

User-agent: Google-Extended
Disallow: /

User-agent: Applebot-Extended
Disallow: /

Sitemap: https://demo.flat-cms.fr/sitemap.xml
```

## 6.3 Sitemap de la démo

Le sitemap de la démo doit idéalement contenir uniquement :

```text
https://demo.flat-cms.fr/
```

Éventuellement :

- une page d’aide ;
- une page expliquant les identifiants ;
- une page de présentation des fonctions testables.

Les contenus fictifs ne doivent pas y figurer.

---

# 7. Directives d’indexation du site officiel

## 7.1 Pages indexables

Directive par défaut :

```html
<meta name="robots" content="index, follow, max-image-preview:large">
```

Cette balise peut être omise si le comportement par défaut suffit, mais une génération explicite peut faciliter l’administration.

Pages concernées :

- accueil ;
- fonctionnalités ;
- architecture ;
- documentation ;
- blog ;
- comparatifs ;
- téléchargement ;
- licences ;
- tarifs ;
- à propos ;
- contact ;
- roadmap.

---

## 7.2 Pages `noindex, follow`

Recommandées pour :

- résultats de recherche interne ;
- prévisualisations ;
- brouillons accessibles par lien ;
- pages de test ;
- filtres sans valeur éditoriale ;
- archives techniques inutiles ;
- contenus fictifs de la démo ;
- pages de confirmation de formulaire ;
- écrans d’authentification publics.

```html
<meta name="robots" content="noindex, follow">
```

---

## 7.3 Pages `noindex, nofollow`

À limiter aux cas où aucun lien de la page ne doit être suivi.

Exemples possibles :

- page de test temporaire ;
- réponse technique isolée ;
- page compromise en attente de suppression.

Dans la majorité des cas éditoriaux :

```text
noindex, follow
```

est préférable.

---

## 7.4 `nosnippet`

Ne pas appliquer globalement.

`nosnippet` empêcherait la génération de certains extraits et pourrait limiter la visibilité dans les résultats classiques ou génératifs.

Utiliser seulement pour un besoin juridique ou éditorial précis.

---

## 7.5 `max-snippet`

Ne pas restreindre par défaut.

Une limite faible peut réduire la capacité des moteurs à présenter un extrait pertinent.

---

## 7.6 `max-image-preview`

Recommandation :

```html
<meta name="robots" content="max-image-preview:large">
```

sur les pages indexables dotées d’images pertinentes.

---

# 8. `X-Robots-Tag`

## 8.1 Cas d’usage

Utiliser pour les ressources non HTML :

- PDF ;
- fichiers texte ;
- exports ;
- documents techniques ;
- images ;
- vidéos ;
- flux ;
- archives.

## 8.2 Documents téléchargeables indexables

Exemple :

```http
X-Robots-Tag: index, follow
```

La directive explicite n’est pas toujours nécessaire.

## 8.3 Documents internes ou duplicatifs

Exemple :

```http
X-Robots-Tag: noindex, nofollow
```

Applicable à :

- exports d’administration ;
- rapports privés ;
- fichiers temporaires ;
- copies PDF d’une page déjà canonique ;
- documents sous licence ne devant pas être indexés.

## 8.4 Archives de téléchargement

Les archives ZIP ne nécessitent généralement pas d’indexation.

Elles doivent néanmoins être accessibles depuis la page de téléchargement.

---

# 9. Politique des images

## 9.1 Images éditoriales

Autoriser :

```text
Googlebot-Image
Applebot
robots de recherche compatibles
```

Les images utiles doivent :

- être accessibles ;
- avoir des URLs stables ;
- être intégrées dans des pages indexables ;
- posséder un texte alternatif ;
- être incluses dans les sitemaps si pertinent.

## 9.2 Images privées ou temporaires

Protéger par contrôle d’accès ou stockage hors document root.

Ne pas compter uniquement sur :

```text
Disallow
```

## 9.3 Démo

Les images fictives peuvent rester explorables afin d’afficher correctement la démo, même lorsque les pages sont `noindex`.

---

# 10. Politique des vidéos

Autoriser l’exploration des vidéos et vignettes officielles.

Inclure lorsque pertinent :

- sitemap vidéo ;
- `VideoObject` ;
- vignette accessible ;
- page de lecture indexable ;
- transcription.

Les vidéos privées ou non publiées doivent être protégées par authentification ou URL signée.

---

# 11. Préproduction

## 11.1 Politique obligatoire

Une préproduction ne doit pas reposer uniquement sur `robots.txt`.

Protection recommandée :

```text
authentification HTTP
restriction réseau
ou contrôle d’accès applicatif
```

Complément :

```text
X-Robots-Tag: noindex, nofollow
```

sur toutes les réponses.

## 11.2 Exemple `robots.txt`

```text
User-agent: *
Disallow: /
```

Cette règle est complémentaire, pas suffisante.

## 11.3 Suppression au lancement

Avant la mise en production, vérifier :

- retrait de l’authentification prévue ;
- retrait du `noindex` ;
- remplacement du `robots.txt` ;
- canonicals de production ;
- sitemaps de production ;
- absence d’URL de préproduction dans le HTML ou JSON-LD.

---

# 12. Administration et API

## 12.1 Administration

Routes concernées :

```text
/admin/
/login/
/logout/
/account/
```

Exigences :

- authentification ;
- protection CSRF ;
- contrôle de rôle ;
- `noindex` ;
- exclusion des sitemaps ;
- aucune donnée sensible dans les réponses publiques.

## 12.2 API

Une API publique documentée peut être explorée selon son objectif.

Une API privée doit utiliser :

- authentification ;
- autorisation ;
- rate limiting ;
- réponses appropriées.

Ne pas essayer de sécuriser une API uniquement avec `robots.txt`.

---

# 13. Fichiers et chemins à ne jamais exposer

Liste indicative :

```text
.env
.env.local
config/
data/
storage/
backups/
logs/
sessions/
cache privé
clés
tokens
exports utilisateurs
fichiers temporaires
artefacts d’installation sensibles
```

Le serveur doit retourner :

```text
403
404
ou aucune route publique
```

selon le cas.

---

# 14. Contrôle de charge

## 14.1 Principes

- cache HTTP ;
- compression ;
- pages statiques ou pré-rendues lorsque pertinent ;
- limitation de débit sur les endpoints coûteux ;
- protection de la recherche interne ;
- pagination ;
- surveillance des crawls répétitifs.

## 14.2 `Crawl-delay`

`Crawl-delay` n’est pas une directive universellement prise en charge.

- Google ne l’utilise pas comme mécanisme standard de contrôle.
- Anthropic indique la prendre en charge pour ses robots.
- son emploi doit rester ciblé.

Exemple éventuel :

```text
User-agent: ClaudeBot
Crawl-delay: 1
```

Dans la politique initiale, `ClaudeBot` est bloqué ; cette directive n’est donc pas nécessaire.

## 14.3 En cas de surcharge

Préférer :

- optimisation ;
- cache ;
- réponse `429 Too Many Requests` ;
- en-tête `Retry-After` ;
- contrôle WAF ;
- ajustement ciblé.

Éviter de bloquer brutalement tous les moteurs sans diagnostic.

---

# 15. Vérification des robots

Un User-Agent peut être usurpé.

Pour les règles WAF ou les autorisations privilégiées, vérifier :

- reverse DNS lorsque le fournisseur le recommande ;
- plages IP officielles ;
- endpoints JSON officiels ;
- cohérence IP + User-Agent.

Exemples de fournisseurs publiant des plages :

- Google ;
- OpenAI ;
- Apple ;
- Anthropic ;
- Perplexity.

Ne pas intégrer statiquement des plages qui évoluent sans mécanisme de mise à jour.

---

# 16. Cloudflare et WAF

Si Cloudflare est utilisé :

- ne pas autoriser un robot uniquement sur le User-Agent ;
- combiner User-Agent et IP vérifiée lorsque possible ;
- journaliser les règles ;
- tester les robots légitimes ;
- éviter les challenges JavaScript sur les robots vérifiés de recherche ;
- conserver une politique distincte pour la démo.

## Politique IA

Les fonctions Cloudflare de contrôle des robots IA peuvent compléter `robots.txt`.

Toute règle Cloudflare doit rester cohérente avec ce document.

---

# 17. Journaux

## 17.1 Données à collecter

- date et heure ;
- IP ;
- User-Agent ;
- URL ;
- statut HTTP ;
- octets ;
- durée ;
- référent ;
- cache hit/miss ;
- règle WAF appliquée.

## 17.2 Conservation

Définir une durée compatible avec :

- diagnostic ;
- sécurité ;
- confidentialité ;
- réglementation ;
- capacité de stockage.

## 17.3 Rapport mensuel

Produire :

| Robot | Requêtes | Pages | Erreurs | Bande passante | Décision |
|---|---:|---:|---:|---:|---|
| Googlebot | | | | | Autoriser |
| Bingbot | | | | | Autoriser |
| OAI-SearchBot | | | | | Autoriser |
| GPTBot | | | | | Bloquer |
| Claude-SearchBot | | | | | Autoriser |
| ClaudeBot | | | | | Bloquer |
| PerplexityBot | | | | | Surveiller |
| Applebot | | | | | Autoriser |

---

# 18. Audit des robots IA

## Fréquence

```text
Deux fois par an
et avant chaque lancement majeur
```

## Contrôles

- noms des agents encore valides ;
- finalités annoncées ;
- nouvelles catégories ;
- changement de politique ;
- plages IP ;
- respect observé ;
- charge ;
- trafic généré ;
- citations ;
- conversions.

## Principe

Ne jamais copier une longue liste de robots IA depuis une source tierce sans vérifier chaque agent sur une documentation officielle.

---

# 19. Politique par environnement

| Environnement | Crawl | Indexation | Protection |
|---|---|---|---|
| Production | Autorisé selon politique | Oui | WAF + architecture |
| Démo accueil | Autorisé | Oui | Limitation |
| Démo contenu fictif | Autorisé pour lire `noindex` | Non | `noindex, follow` |
| Préproduction | Bloqué | Non | Auth + `noindex` |
| Développement local | Non public | Non | Réseau local |
| Administration | Exclue | Non | Authentification |
| API privée | Exclue | Non | Auth + autorisation |

---

# 20. Règles FlatCMS à implémenter

## 20.1 Paramètres SEO par contenu

Chaque page et article doit pouvoir définir :

```text
index
noindex
follow
nofollow
max-image-preview
max-snippet
```

## 20.2 Valeur par défaut

Pour un contenu public publié :

```text
index, follow, max-image-preview:large
```

Pour un brouillon ou une prévisualisation :

```text
noindex, nofollow
```

## 20.3 Modules et routes

Chaque module doit pouvoir déclarer :

- route publique ;
- indexabilité ;
- inclusion sitemap ;
- robots meta ;
- protection requise.

## 20.4 Fichiers

Le serveur ou FlatCMS doit pouvoir envoyer `X-Robots-Tag` pour :

- exports ;
- PDF ;
- archives ;
- fichiers privés servis sous contrôle.

---

# 21. Contrôles automatisés

Avant lancement :

- [ ] `robots.txt` répond `200`
- [ ] type MIME texte
- [ ] syntaxe valide
- [ ] sitemap absolu
- [ ] aucun chemin public essentiel bloqué
- [ ] CSS et JS nécessaires accessibles
- [ ] images importantes accessibles
- [ ] pages `noindex` explorables
- [ ] administration non indexable
- [ ] préproduction protégée
- [ ] robots de recherche IA autorisés
- [ ] robots d’entraînement bloqués selon décision
- [ ] fichiers sensibles inaccessibles
- [ ] sous-domaines possèdent leur propre `robots.txt`

---

# 22. Tests manuels

Tester :

```text
https://flat-cms.fr/robots.txt
https://demo.flat-cms.fr/robots.txt
```

Puis vérifier :

- accueil ;
- page architecture ;
- article ;
- documentation ;
- téléchargement ;
- recherche interne ;
- prévisualisation ;
- démo fictive ;
- admin ;
- fichier PDF ;
- image Open Graph ;
- sitemap.

---

# 23. Search Console et Bing Webmaster Tools

Après lancement :

- soumettre les sitemaps ;
- tester les URLs ;
- vérifier les pages exclues ;
- surveiller « bloquée par robots.txt » ;
- surveiller `noindex` ;
- contrôler les ressources bloquées ;
- vérifier le rendu mobile ;
- examiner les erreurs d’exploration.

---

# 24. Mesure GEO/GIO

Pour mesurer la pertinence de cette politique :

- trafic issu des moteurs génératifs ;
- présence de citations ;
- pages citées ;
- fréquence des robots de recherche IA ;
- erreurs rencontrées ;
- temps de réponse ;
- conversions ;
- qualité des descriptions générées.

Une absence de crawl d’un robot d’entraînement ne doit pas être interprétée comme une perte de visibilité dans la recherche générative lorsque le fournisseur dispose d’un robot de recherche distinct.

---

# 25. Cas Perplexity

Perplexity documente :

```text
PerplexityBot
Perplexity-User
```

et recommande de vérifier ses plages IP officielles.

Toutefois, des controverses publiques ont porté sur des accès attribués à des systèmes ou prestataires ne respectant pas les préférences déclarées.

Politique FlatCMS :

- autoriser `PerplexityBot` pour la visibilité ;
- surveiller les logs ;
- ne pas créer de contournement de sécurité spécifique ;
- bloquer les comportements abusifs ;
- vérifier les IP avant une exemption WAF ;
- réviser la décision selon les observations.

---

# 26. Cas Google-Extended

`Google-Extended` est un jeton de contrôle dans `robots.txt`.

Il ne correspond pas à un User-Agent HTTP séparé.

Le bloquer :

- n’empêche pas l’inclusion dans Google Search ;
- n’est pas un signal de classement Google Search ;
- contrôle certains usages liés à Gemini et au grounding décrits par Google.

Politique initiale :

```text
User-agent: Google-Extended
Disallow: /
```

Cette décision n’affecte pas l’autorisation de `Googlebot`.

---

# 27. Cas Applebot-Extended

`Applebot-Extended` ne crawl pas séparément les pages.

Il sert à indiquer comment Apple peut utiliser les données déjà explorées par `Applebot` pour l’entraînement de ses modèles.

Politique initiale :

```text
User-agent: Applebot
Allow: /

User-agent: Applebot-Extended
Disallow: /
```

Ainsi, FlatCMS reste visible dans Spotlight, Siri et les fonctions de recherche Apple tout en refusant initialement l’entraînement.

---

# 28. Cas OpenAI

Politique initiale :

```text
User-agent: OAI-SearchBot
Allow: /

User-agent: GPTBot
Disallow: /
```

`ChatGPT-User` est un fetcher déclenché par un utilisateur.

Les pages publiques doivent rester accessibles à un navigateur normal ; les pages privées doivent rester protégées indépendamment de son User-Agent.

---

# 29. Cas Anthropic

Politique initiale :

```text
User-agent: Claude-SearchBot
Allow: /

User-agent: ClaudeBot
Disallow: /
```

`Claude-User` peut être autorisé pour les pages publiques.

Anthropic indique que ses trois agents respectent les directives `robots.txt`, mais les protections privées doivent rester applicatives.

---

# 30. Décisions à valider avant production

- [ ] Autoriser ou bloquer définitivement GPTBot
- [ ] Autoriser ou bloquer ClaudeBot
- [ ] Autoriser ou bloquer Google-Extended
- [ ] Autoriser ou bloquer Applebot-Extended
- [ ] Autoriser PerplexityBot avec surveillance
- [ ] Politique CCBot
- [ ] Politique Amazonbot
- [ ] Politique Bytespider
- [ ] Politique Meta-ExternalAgent
- [ ] Politique des archives PDF
- [ ] Politique de la recherche interne
- [ ] Routes exactes du site
- [ ] Routes exactes de la démo
- [ ] Paramètres à exclure

Les agents supplémentaires doivent être ajoutés uniquement après vérification officielle.

---

# 31. Checklist de publication

- [ ] politique approuvée
- [ ] fichier production créé
- [ ] fichier démo créé
- [ ] fichier préproduction créé
- [ ] sous-domaines couverts
- [ ] sitemaps corrects
- [ ] Search Console testée
- [ ] Bing testé
- [ ] OAI-SearchBot autorisé
- [ ] Claude-SearchBot autorisé
- [ ] Applebot autorisé
- [ ] robots d’entraînement selon décision
- [ ] pages de démo `noindex`
- [ ] fichiers sensibles inaccessibles
- [ ] WAF cohérent
- [ ] logs opérationnels
- [ ] audit planifié

---

# 32. Références officielles

- Google — Introduction à `robots.txt`  
  https://developers.google.com/search/docs/crawling-indexing/robots/intro

- Google — Bloquer l’indexation avec `noindex`  
  https://developers.google.com/search/docs/crawling-indexing/block-indexing

- Google — Robots meta et `X-Robots-Tag`  
  https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag

- Google — Robots courants et Google-Extended  
  https://developers.google.com/crawling/docs/crawlers-fetchers/google-common-crawlers

- OpenAI — Overview of OpenAI Crawlers  
  https://developers.openai.com/api/docs/bots

- Anthropic — Web crawlers and blocking controls  
  https://support.claude.com/en/articles/8896518-does-anthropic-crawl-data-from-the-web-and-how-can-site-owners-block-the-crawler

- Perplexity — Perplexity Crawlers  
  https://docs.perplexity.ai/docs/resources/perplexity-crawlers

- Apple — About Applebot  
  https://support.apple.com/en-us/119829

- RFC 9309 — Robots Exclusion Protocol  
  https://www.rfc-editor.org/rfc/rfc9309

---

# 33. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Création de la politique initiale d’exploration et d’indexation | ChatGPT / Alain BROYE |

---

# 34. Prochaine action

Créer :

```text
CONTENT_STYLE_GUIDE.md
```

Ce document définira :

- la voix de FlatCMS ;
- le ton ;
- les termes officiels ;
- les règles de titres ;
- la sémantique H1/H2/H3 ;
- les blocs GEO/GIO ;
- les affirmations et preuves ;
- les conventions de code ;
- les traductions ;
- les textes alternatifs ;
- les CTA ;
- les règles éditoriales pour les six locales.
