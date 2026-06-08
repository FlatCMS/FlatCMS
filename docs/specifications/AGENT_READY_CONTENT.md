# AGENT_READY_CONTENT — FlatCMS, une architecture pensée pour les agents IA

> **Contenu éditorial prêt à intégrer**
>
> Projet : FlatCMS  
> Page : Agent-ready `fr-FR`  
> URL cible : `https://flat-cms.fr/fr-FR/agent-ready/`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Brief parent : `PAGE_BRIEFS.md` — `P0-AGENT-FR`  
> Documents associés : `ARCHITECTURE_CONTENT.md`, `FEATURES_CONTENT.md`, `STRUCTURED_DATA.md`, `CRAWL_POLICY.md`, `CONTENT_STYLE_GUIDE.md`  
> Statut : première version rédactionnelle à valider contre le code réel de `app/Services/AI` et du module `AiAgent`

---

## 1. Objectif de la page

Cette page explique pourquoi FlatCMS est décrit comme **agent-ready**.

Elle doit permettre de comprendre :

- ce que signifie réellement « agent-ready » ;
- pourquoi une architecture de CMS peut faciliter l’intégration d’agents IA ;
- quelles fondations techniques existent déjà dans FlatCMS ;
- quelles fonctions sont disponibles, optionnelles, premium, expérimentales ou prévues ;
- comment les services IA doivent être isolés du cœur du CMS ;
- comment les agents peuvent utiliser des outils contrôlés ;
- pourquoi le contrôle humain reste indispensable ;
- comment protéger les secrets, les contenus et les données personnelles ;
- pourquoi le référencement classique reste la base de la visibilité dans les moteurs génératifs.

La page ne doit pas laisser entendre que :

- toutes les fonctions IA sont déjà disponibles dans le LTS Core ;
- FlatCMS dispose d’une intelligence autonome sans configuration ;
- un agent peut modifier librement le site ;
- l’IA garantit un meilleur classement ou une citation ;
- les réponses d’un modèle sont toujours exactes ;
- OpenAI est le seul fournisseur possible ;
- l’utilisation d’une IA est gratuite ou illimitée.

---

# 2. Métadonnées SEO

## URL canonique

```text
https://flat-cms.fr/fr-FR/agent-ready/
```

## Balise `<title>`

```text
FlatCMS : un CMS conçu pour les agents IA
```

## Meta description

```text
Découvrez comment l’architecture modulaire, le stockage JSON, les
services, les outils contrôlés et le multilingue rendent FlatCMS agent-ready.
```

## Directive robots

```text
index, follow, max-image-preview:large
```

## Open Graph

### `og:title`

```text
FlatCMS, une architecture pensée pour les agents IA
```

### `og:description`

```text
Services IA, stockage JSON, outils contrôlés, données structurées,
multilingue et validation humaine : découvrez l’approche agent-ready de FlatCMS.
```

### `og:type`

```text
article
```

### `og:url`

```text
https://flat-cms.fr/fr-FR/agent-ready/
```

### `og:image`

```text
https://flat-cms.fr/assets/images/og/agent-ready-flatcms-fr-FR.webp
```

---

# 3. Hero

## Sur-titre

```text
Architecture agent-ready
```

## H1

```text
FlatCMS, une architecture pensée pour les agents IA
```

## Introduction

```text
FlatCMS sépare les contenus, les services, les modules, les permissions
et les fournisseurs externes afin de permettre des intégrations IA
contrôlées sans mélanger l’intelligence artificielle avec le cœur stable
du CMS.
```

```text
Le stockage JSON, l’architecture HMVC, les services, les hooks, les
données structurées et le multilingue forment une base exploitable par
des assistants et agents, sous réserve de contrats précis, de permissions
et d’une validation humaine.
```

## Message de transparence

```text
Agent-ready ne signifie pas que toutes les fonctions IA sont incluses
dans FlatCMS LTS Core. Le socle technique, les modules optionnels, les
services premium et les évolutions prévues doivent rester clairement
distingués.
```

## CTA principal

```text
Découvrir l’architecture IA
```

Destination :

```text
/fr-FR/agent-ready/#architecture
```

## CTA secondaire

```text
Explorer les fonctionnalités
```

Destination :

```text
/fr-FR/fonctionnalites/
```

## Lien tertiaire

```text
Consulter l’architecture FlatCMS
```

Destination :

```text
/fr-FR/architecture/
```

---

# 4. Que signifie « agent-ready » ?

## H2

```text
Préparer le CMS à des agents contrôlés
```

## Définition

```text
Un CMS agent-ready expose ses contenus, actions et règles à travers des
contrats structurés qu’un agent peut utiliser sans contourner la sécurité
ou modifier directement les fichiers internes.
```

Une architecture agent-ready doit notamment proposer :

- des données identifiables ;
- des services métier ;
- des outils ou fonctions explicites ;
- des schémas d’entrée et de sortie ;
- des rôles et permissions ;
- des validations ;
- des journaux d’audit ;
- des limites d’usage ;
- des confirmations humaines ;
- une abstraction des fournisseurs ;
- des mécanismes de reprise en cas d’erreur.

## Ce qu’un agent ne doit pas faire

Un agent ne doit pas :

- écrire directement dans un fichier JSON sans validation ;
- contourner un contrôleur ou un service ;
- publier sans permission ;
- utiliser une clé API exposée dans le navigateur ;
- exécuter une action destructive sans confirmation ;
- modifier des contenus hors de son périmètre ;
- inventer un statut, un auteur ou une source ;
- masquer son intervention ;
- supprimer un historique ;
- dépendre d’un fournisseur unique sans abstraction.

## Principe

```text
L’agent demande.
Le service valide.
Le CMS applique.
L’utilisateur contrôle.
```

---

# 5. Pourquoi l’architecture de FlatCMS s’y prête

## H2

```text
Des fondations déjà séparées par responsabilité
```

## Architecture HMVC

```text
Les fonctionnalités sont regroupées en modules. Un agent peut donc être
limité à un périmètre fonctionnel : pages, articles, médias, traductions
ou builders.
```

## Autoloading PSR-4

```text
Les classes et services sont organisés dans des namespaces prévisibles,
ce qui facilite la maintenance et l’ajout de fournisseurs ou d’outils.
```

## Stockage JSON

```text
Les contenus sont représentés dans des structures identifiables. Un agent
peut produire une proposition conforme à un schéma sans accéder
directement au stockage final.
```

## Services

```text
Les traitements partagés peuvent être isolés dans une couche de services
qui contrôle les entrées, les sorties, les erreurs et les effets de bord.
```

## Hooks

```text
Les hooks permettent d’enrichir un workflow sans modifier directement le
cœur stable.
```

## Permissions

```text
Les rôles et contrôles d’accès permettent de limiter les actions
autorisées selon l’utilisateur ou le contexte.
```

## Multilingue

```text
Les contenus, métadonnées et URLs sont organisés par locale, ce qui
permet de construire des workflows de traduction contrôlés.
```

## Données structurées

```text
Le graphe JSON-LD aide à décrire les entités, les pages, les auteurs et
les relations du site dans un format exploitable par les systèmes
externes.
```

---

# 6. Architecture IA proposée

## H2

```text
Une couche de services indépendante des modules
```

## Ancre

```text
#architecture
```

## Organisation conceptuelle

```text
app/Services/AI/
├── Contracts/
│   └── AiProviderInterface.php
├── DTO/
│   ├── AiRequest.php
│   ├── AiMessage.php
│   └── AiUsage.php
├── Providers/
│   └── OpenAiProvider.php
├── Responses/
│   └── AiResponse.php
├── Exceptions/
└── AIManager.php
```

## Module fonctionnel conceptuel

```text
app/Modules/AiAgent/
├── Controllers/
├── Services/
├── Views/
├── Config/
├── Languages/
├── manifest.json
└── ai-agent.json
```

## Principe de flux

```text
Utilisateur
→ module AiAgent
→ service fonctionnel
→ AIManager
→ fournisseur conforme au contrat
→ réponse normalisée
→ validation
→ brouillon ou action autorisée
```

## Séparation

```text
Le module exprime le besoin métier.
AIManager orchestre.
Le provider communique avec le fournisseur.
Le CMS valide et enregistre.
```

---

# 7. Contrat fournisseur

## H2

```text
Ne pas coupler FlatCMS à un seul modèle
```

## Interface conceptuelle

```php
interface AiProviderInterface
{
    public function generate(AiRequest $request): AiResponse;

    public function supports(string $capability): bool;

    public function testConnection(): bool;
}
```

## Responsabilités

Un provider doit gérer :

- authentification ;
- endpoint ;
- modèle ;
- délai d’expiration ;
- format de requête ;
- format de réponse ;
- erreurs ;
- consommation ;
- limitations ;
- compatibilité des outils ;
- capacités multimodales ;
- journalisation contrôlée.

## Bénéfices

- changer de fournisseur ;
- utiliser plusieurs fournisseurs ;
- tester sans modifier les modules ;
- isoler les SDK ;
- normaliser les erreurs ;
- contrôler les coûts ;
- désactiver un fournisseur ;
- prévoir un provider local.

## Règle

```text
Les modules Pages, Posts ou Media ne doivent pas construire directement
des requêtes HTTP vers OpenAI ou un autre fournisseur.
```

---

# 8. AIManager

## H2

```text
Orchestrer les requêtes et les politiques
```

## Responsabilités possibles

- sélectionner le provider ;
- valider la requête ;
- vérifier la permission ;
- appliquer les limites ;
- ajouter le contexte autorisé ;
- exécuter la demande ;
- normaliser la réponse ;
- enregistrer l’usage ;
- masquer les secrets ;
- journaliser l’erreur ;
- déclencher une validation humaine.

## Exemple conceptuel

```php
$response = $aiManager->request(
    capability: 'article_draft',
    request: $request,
    actor: $currentUser
);
```

## À éviter

- choix du modèle dispersé dans les contrôleurs ;
- clé API transmise dans les vues ;
- requête sans timeout ;
- coût non suivi ;
- contenu complet du site envoyé sans nécessité ;
- publication automatique par défaut ;
- erreur fournisseur affichée telle quelle au visiteur.

---

# 9. Module AiAgent

## H2

```text
Une interface fonctionnelle distincte du LTS Core
```

## Statut

```text
Optionnel, premium ou expérimental selon la distribution finale
```

La présence d’un module `AiAgent` dans une arborescence de développement
ne suffit pas à établir :

- qu’il est livré dans le LTS Core ;
- qu’il est activé par défaut ;
- qu’il est gratuit ;
- que toutes ses fonctions sont terminées ;
- qu’il est compatible avec tous les fournisseurs.

## Services fonctionnels envisagés

```text
ChatbotService
ArticleGeneratorService
TranslationService
PageBuilderGeneratorService
MediaGeneratorService
UsageTrackerService
```

Les noms définitifs doivent correspondre au code réel.

## Interface d’administration

Le groupe de paramètres peut couvrir :

- fournisseur ;
- clé API ;
- modèle ;
- URL de base ;
- timeout ;
- tokens maximum ;
- limites par minute ;
- fonctions activées ;
- rôles autorisés ;
- consentement ;
- journalisation ;
- test de connexion.

---

# 10. Capacités par statut

## H2

```text
Distinguer ce qui existe de ce qui est prévu
```

> Ce tableau doit être validé contre le package et les tests avant
> publication.

| Capacité | Statut à confirmer | Publication autorisée |
|---|---|---|
| Contrat de provider | Fondation technique | Oui si présent et testé |
| AIManager | Fondation technique | Oui si présent et testé |
| Provider OpenAI | Optionnel | Oui si opérationnel |
| Test de connexion | Optionnel | Oui si testé |
| Chatbot frontend | Optionnel / premium | Selon distribution |
| Brouillon d’article | Optionnel / premium | Selon distribution |
| Amélioration de texte | Optionnel / premium | Selon distribution |
| Traduction | Optionnel / premium | Selon distribution |
| Métadonnées SEO | Optionnel / premium | Selon distribution |
| Structure de page | Expérimentale / premium | Selon validation |
| Génération d’image | Optionnelle / premium | Selon provider |
| Suivi d’usage | Fondation nécessaire | Oui si fiable |
| Agents autonomes | Prévue | Non comme fonction stable |
| Orchestration multi-agent | Prévue | Non comme fonction stable |
| MCP | Étudié / prévu | Non avant implémentation |
| Publication autonome | Non recommandée par défaut | Non sans contrôle |

---

# 11. Assistant de rédaction

## H2

```text
Produire un brouillon, pas remplacer l’auteur
```

## Cas d’usage

- proposer un plan ;
- générer un premier brouillon ;
- reformuler ;
- raccourcir ;
- développer une section ;
- corriger le ton ;
- suggérer un title ;
- proposer une meta description ;
- extraire un résumé ;
- générer une FAQ éditoriale.

## Workflow recommandé

```text
Demande utilisateur
→ génération
→ aperçu
→ comparaison
→ modification humaine
→ validation
→ enregistrement en brouillon
→ publication manuelle
```

## Interdictions par défaut

- publication automatique ;
- modification silencieuse d’un article publié ;
- remplacement sans historique ;
- création d’un auteur fictif ;
- ajout de sources inexistantes ;
- suppression du contenu original ;
- présentation d’un texte généré comme validé.

## Trace

Conserver si nécessaire :

- utilisateur ;
- date ;
- fonction ;
- provider ;
- modèle ;
- coût ;
- contenu cible ;
- statut de validation.

Ne pas conserver un prompt contenant des secrets ou données sensibles
sans nécessité.

---

# 12. Traduction assistée

## H2

```text
Accélérer la traduction sans supprimer la relecture
```

## Locales natives

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

## Capacités

- traduire un title ;
- traduire une meta description ;
- traduire un résumé ;
- traduire un contenu ;
- préserver les blocs de code ;
- conserver les noms de produits ;
- appliquer le glossaire ;
- détecter les chaînes manquantes ;
- comparer source et traduction.

## Workflow

```text
Source validée
→ brouillon IA
→ contrôle du glossaire
→ relecture linguistique
→ relecture technique
→ validation
→ publication
```

## Ne pas traduire automatiquement sans validation

- licences ;
- mentions légales ;
- confidentialité ;
- sécurité ;
- prix ;
- contrats ;
- commandes ;
- messages d’erreur critiques ;
- noms de classes ;
- chemins ;
- variables ;
- API.

## Statuts

```text
machine_translated
human_review_required
reviewed
approved
published
outdated
```

---

# 13. Génération de pages et Builders

## H2

```text
Transformer une intention en structure contrôlée
```

## Cas d’usage

- proposer des sections ;
- sélectionner des widgets ;
- générer une structure JSON ;
- préparer un Hero ;
- suggérer des CTA ;
- créer une FAQ ;
- proposer un plan de landing page ;
- générer un scénario de démonstration.

## Règle fondamentale

```text
Le modèle ne doit pas écrire directement dans les fichiers du Builder.
```

## Workflow

```text
Prompt
→ sortie structurée
→ validation du schéma
→ contrôle des widgets autorisés
→ aperçu
→ confirmation utilisateur
→ enregistrement
```

## Validation

Vérifier :

- type de widget ;
- propriétés requises ;
- identifiants ;
- ordre ;
- locale ;
- liens ;
- médias ;
- accessibilité ;
- statut de licence ;
- compatibilité du thème.

## Sortie structurée

Les sorties structurées et le function calling permettent de demander au
modèle un format conforme à un schéma, mais le CMS doit toujours valider
la réponse avant de l’utiliser.

---

# 14. Génération de médias

## H2

```text
Créer des ressources sans perdre la maîtrise des droits
```

## Cas d’usage

- image de couverture ;
- illustration ;
- variante de format ;
- texte alternatif suggéré ;
- légende ;
- nom de fichier ;
- métadonnées.

## Contrôles

- droit d’utilisation ;
- contenu sensible ;
- personnes réelles ;
- marques ;
- dimensions ;
- format ;
- poids ;
- locale ;
- texte dans l’image ;
- accessibilité ;
- validation humaine.

## Enregistrement

Un média généré doit être enregistré dans la médiathèque avec :

- provenance ;
- date ;
- provider ;
- modèle si utile ;
- prompt ou résumé contrôlé ;
- droits ;
- auteur de la demande ;
- texte alternatif validé.

## Interdiction

```text
Ne pas présenter une image générée comme une photographie documentaire
réelle sans indication adaptée au contexte.
```

---

# 15. Chatbot frontend

## H2

```text
Un assistant limité au périmètre du site
```

## Nom prévu

```text
Flatty
```

## Position

```text
En bas à droite
```

## Couleur de référence

```text
#4F46E5
```

## Capacités possibles

- répondre à partir de la documentation ;
- orienter vers une page ;
- expliquer une fonction ;
- aider à l’installation ;
- proposer un contact ;
- citer les sources internes utilisées.

## Le chatbot ne doit pas

- prétendre être humain ;
- inventer une fonction ;
- garantir un résultat ;
- révéler une clé ;
- accéder à l’administration ;
- modifier le site ;
- collecter des données inutiles ;
- répondre hors périmètre sans le signaler ;
- fournir une réponse juridique personnalisée ;
- remplacer le support de sécurité.

## Réponse en cas d’incertitude

```text
Je n’ai pas trouvé cette information dans la documentation FlatCMS.
Consultez la page de support ou contactez le projet.
```

## Sources

Le chatbot doit privilégier :

- documentation officielle ;
- version concernée ;
- changelog ;
- licences ;
- FAQ validée ;
- contenus du site.

---

# 16. Outils contrôlés

## H2

```text
Donner à l’agent des actions explicites
```

Le function calling permet à un modèle de demander l’exécution d’une
fonction définie par l’application.

Dans FlatCMS, un outil peut représenter une action comme :

```text
get_page
list_posts
create_draft
translate_content
get_media
suggest_metadata
validate_slug
preview_builder_layout
```

## Exemple conceptuel

```json
{
  "name": "create_article_draft",
  "description": "Crée un brouillon d’article sans le publier.",
  "parameters": {
    "type": "object",
    "properties": {
      "locale": {"type": "string"},
      "title": {"type": "string"},
      "content": {"type": "string"}
    },
    "required": ["locale", "title", "content"]
  }
}
```

## Contrôle

Le CMS reste responsable de :

- valider le schéma ;
- vérifier l’utilisateur ;
- vérifier le rôle ;
- appliquer les limites ;
- refuser l’action ;
- demander une confirmation ;
- journaliser ;
- exécuter ;
- retourner le résultat.

## Règle

```text
Le modèle propose un appel.
L’application décide de l’exécuter.
```

---

# 17. Actions sensibles et confirmation humaine

## H2

```text
Ne jamais confondre assistance et autorité
```

## Actions à confirmation obligatoire

- publier ;
- dépublier ;
- supprimer ;
- restaurer ;
- modifier un utilisateur ;
- changer un rôle ;
- activer un module ;
- désactiver un module ;
- modifier une licence ;
- envoyer un e-mail massif ;
- remplacer plusieurs traductions ;
- écraser un layout ;
- déclencher une mise à jour ;
- effectuer une action externe.

## Exemple

```text
L’agent a préparé la traduction de 24 pages.
Aucune page n’a encore été publiée.

[Comparer]
[Enregistrer en brouillon]
[Valider et publier]
[Annuler]
```

## Double contrôle

Pour certaines actions :

- confirmation de l’utilisateur ;
- permission serveur ;
- token CSRF ;
- limite de portée ;
- journal d’audit.

---

# 18. Rôles et permissions

## H2

```text
Limiter les fonctions IA selon les responsabilités
```

## Matrice conceptuelle

| Fonction | Super Admin | Admin | Éditeur | Démo |
|---|---:|---:|---:|---:|
| Configurer le provider | Oui | Selon permission | Non | Non |
| Tester la connexion | Oui | Selon permission | Non | Non |
| Générer un brouillon | Oui | Oui | Oui | Limité ou non |
| Traduire | Oui | Oui | Oui | Non |
| Générer un média | Oui | Oui | Selon permission | Non |
| Générer un layout | Oui | Oui | Selon licence | Non |
| Publier | Oui | Selon rôle | Selon workflow | Non |
| Consulter les coûts | Oui | Selon permission | Limité | Non |
| Modifier les limites | Oui | Non | Non | Non |

## Principe

```text
L’accès au bouton ne remplace pas le contrôle serveur.
```

Chaque action doit vérifier la permission au moment de l’exécution.

---

# 19. Secrets

## H2

```text
Conserver les clés hors du navigateur et du dépôt
```

## Emplacement prévu

```text
.env.local
```

ou un mécanisme privé équivalent.

## Variables conceptuelles

```env
OPENAI_API_KEY=
OPENAI_API_BASE_URL=
OPENAI_CHAT_MODEL=
OPENAI_TIMEOUT=
OPENAI_MAX_TOKENS=
OPENAI_SLOW_LOG_THRESHOLD_MS=
OPENAI_RATE_LIMIT_PER_MINUTE=
```

## Règles

- ne jamais committer la clé ;
- ne jamais l’afficher en clair ;
- ne jamais l’envoyer au frontend ;
- masquer la valeur dans l’administration ;
- limiter les droits du fichier ;
- renouveler une clé exposée ;
- utiliser une clé propre au projet ;
- séparer développement et production ;
- ne pas partager la clé d’un utilisateur entre plusieurs clients sans politique.

## Test de connexion

Le test doit :

- utiliser un appel minimal ;
- retourner un message compréhensible ;
- ne pas enregistrer la clé dans les logs ;
- appliquer un timeout ;
- distinguer authentification, quota, réseau et modèle inconnu.

---

# 20. Données personnelles et confidentialité

## H2

```text
Envoyer uniquement les données nécessaires
```

## Avant l’appel

Le CMS doit déterminer :

- quelles données sont envoyées ;
- pourquoi ;
- à quel fournisseur ;
- dans quelle région si pertinente ;
- pendant combien de temps ;
- selon quelle base juridique ;
- si un sous-traitant intervient ;
- si l’utilisateur a été informé.

## Ne pas envoyer sans nécessité

- mot de passe ;
- clé API ;
- contenu privé complet ;
- données client ;
- adresse personnelle ;
- données de santé ;
- informations de paiement ;
- journaux complets ;
- pièces jointes confidentielles.

## Minimisation

```text
Envoyer le passage utile, pas l’ensemble du site.
```

## Journalisation

Les logs doivent éviter de conserver :

- prompts sensibles ;
- réponses confidentielles ;
- clés ;
- données personnelles non nécessaires.

---

# 21. Coûts et limites

## H2

```text
Contrôler l’usage avant d’activer une fonction
```

## Paramètres

- modèle ;
- tokens maximum ;
- requêtes par minute ;
- requêtes par utilisateur ;
- budget quotidien ;
- budget mensuel ;
- taille maximale du contenu ;
- nombre de médias ;
- fonctions autorisées ;
- timeout ;
- nouvelle tentative.

## Suivi

Le service `UsageTracker` peut enregistrer :

- date ;
- utilisateur ;
- fonction ;
- provider ;
- modèle ;
- tokens d’entrée ;
- tokens de sortie ;
- coût estimé ;
- durée ;
- statut ;
- erreur.

## Alertes

- 50 % du budget ;
- 80 % ;
- 100 % ;
- anomalie ;
- hausse soudaine ;
- boucle répétitive.

## Interruption

Une limite dépassée doit :

- bloquer la nouvelle requête ;
- expliquer la raison ;
- conserver le site fonctionnel ;
- ne pas affecter les contenus existants ;
- permettre à l’administrateur d’ajuster la politique.

---

# 22. Erreurs et résilience

## H2

```text
L’IA ne doit jamais devenir une dépendance critique du CMS
```

## En cas d’indisponibilité

- frontend actif ;
- administration active ;
- contenus accessibles ;
- édition manuelle disponible ;
- fonction IA désactivée temporairement ;
- message compréhensible ;
- erreur journalisée ;
- nouvelle tentative contrôlée.

## Ne jamais

- provoquer une erreur 500 globale ;
- bloquer la publication manuelle ;
- supprimer un brouillon ;
- masquer un contenu existant ;
- réessayer sans limite ;
- afficher la réponse brute du provider ;
- exposer une stack trace ;
- rendre le site dépendant d’un appel IA pour afficher une page.

## Message utilisateur

```text
Le service d’assistance IA est temporairement indisponible. Vous pouvez
continuer à modifier le contenu manuellement.
```

---

# 23. Validation des sorties

## H2

```text
Traiter toute réponse comme une proposition non fiable
```

## Contrôles

- type ;
- schéma ;
- longueur ;
- locale ;
- HTML autorisé ;
- URLs ;
- slugs ;
- IDs ;
- widgets ;
- permissions ;
- champs obligatoires ;
- caractères interdits ;
- contenu sensible ;
- faits ;
- sources.

## Sortie structurée

Même si le fournisseur garantit une conformité à un schéma, FlatCMS doit
encore vérifier :

- règles métier ;
- droits ;
- cohérence ;
- existence des ressources ;
- compatibilité avec la version ;
- sécurité du contenu.

## HTML

Le contenu généré doit être :

- nettoyé ;
- filtré ;
- échappé selon le contexte ;
- compatible avec SunEditor ou le renderer ;
- débarrassé des scripts non autorisés.

---

# 24. Guardrails

## H2

```text
Encadrer les entrées, les outils et les sorties
```

## Entrées

- limiter la taille ;
- refuser les secrets ;
- détecter les demandes hors périmètre ;
- vérifier la permission ;
- filtrer les pièces jointes ;
- avertir sur les données personnelles.

## Outils

- liste blanche ;
- schéma strict ;
- portée limitée ;
- confirmation ;
- timeout ;
- journalisation ;
- résultat validé.

## Sorties

- modération ;
- schéma ;
- exactitude ;
- liens ;
- données personnelles ;
- contenus interdits ;
- ton ;
- locale.

## Important

Un guardrail ne remplace pas :

- les permissions ;
- la validation serveur ;
- la confirmation ;
- les sauvegardes ;
- les audits ;
- la relecture humaine.

---

# 25. Historique, versioning et restauration

## H2

```text
Ne jamais écraser silencieusement le travail humain
```

## Pour chaque opération IA

Conserver si possible :

- version avant ;
- proposition IA ;
- version après validation ;
- utilisateur ;
- date ;
- fonction ;
- statut ;
- commentaire.

## Actions

- comparer ;
- accepter ;
- rejeter ;
- restaurer ;
- enregistrer en brouillon ;
- publier.

## Règle

```text
Une génération IA ne doit pas remplacer définitivement un contenu publié
sans possibilité de retour arrière.
```

---

# 26. Observabilité et audit

## H2

```text
Comprendre ce que l’agent a tenté et exécuté
```

## Journal d’audit

- utilisateur ;
- rôle ;
- action demandée ;
- outil proposé ;
- outil exécuté ;
- paramètres filtrés ;
- résultat ;
- confirmation ;
- erreur ;
- durée ;
- coût ;
- provider ;
- modèle.

## Ne pas journaliser

- clé complète ;
- mot de passe ;
- données personnelles inutiles ;
- contenu confidentiel complet ;
- jeton de session.

## Tracing

Les runtimes d’agents modernes peuvent proposer des mécanismes de traces
pour visualiser les étapes, les outils et les résultats.

FlatCMS doit conserver une abstraction afin de ne pas dépendre
exclusivement du tracing d’un fournisseur.

---

# 27. OpenAI Agents SDK et Responses API

## H2

```text
Deux niveaux d’intégration possibles
```

## Responses API

Une intégration directe convient lorsque FlatCMS veut :

- contrôler la boucle ;
- gérer lui-même les outils ;
- gérer l’état ;
- exécuter un workflow court ;
- obtenir une réponse structurée ;
- limiter les abstractions.

## Agents SDK

Un runtime d’agents devient pertinent lorsque le module doit gérer :

- plusieurs tours ;
- outils ;
- handoffs ;
- guardrails ;
- sessions ;
- état ;
- orchestration ;
- human-in-the-loop ;
- tracing.

## Point important

```text
Le SDK officiel OpenAI Agents est Python-first.
```

FlatCMS étant un CMS PHP natif, son adoption doit être conçue comme :

- un service séparé ;
- un microservice ;
- un worker ;
- une API interne ;
- ou une intégration future clairement isolée.

Le LTS Core PHP ne doit pas dépendre directement d’un runtime Python pour
afficher ou administrer les fonctions essentielles du CMS.

## Approche progressive recommandée

### Phase 1

```text
API fournisseur directe
+ contrat PHP
+ outils contrôlés
+ sorties structurées
```

### Phase 2

```text
service d’agents séparé
+ orchestration
+ sessions
+ guardrails
+ tracing
```

### Phase 3

```text
agents spécialisés
+ handoffs
+ outils FlatCMS
+ approbations humaines
```

---

# 28. Agents spécialisés envisagés

## H2

```text
Séparer les missions plutôt que créer un agent omnipotent
```

## Agent éditorial

- plan ;
- brouillon ;
- résumé ;
- ton ;
- lisibilité.

## Agent SEO

- title ;
- meta description ;
- maillage suggéré ;
- données structurées ;
- contrôle des doublons.

## Agent traduction

- traduction ;
- glossaire ;
- comparaison ;
- détection d’obsolescence.

## Agent Builder

- structure ;
- widgets ;
- responsive ;
- prévisualisation.

## Agent média

- prompt ;
- génération ;
- métadonnées ;
- texte alternatif.

## Agent support

- documentation ;
- diagnostic ;
- liens ;
- collecte d’environnement.

## Règle

Chaque agent possède :

- une mission ;
- des outils ;
- des permissions ;
- des limites ;
- un format de résultat ;
- un point de validation.

---

# 29. SEO, GEO et GIO

## H2

```text
Les fondamentaux SEO restent la base
```

## Texte

```text
Les fonctions génératives des moteurs de recherche utilisent les pages
indexées et les mêmes fondations techniques que la recherche classique.
```

FlatCMS peut aider à publier :

- contenu textuel accessible ;
- URLs propres ;
- maillage interne ;
- canonicals ;
- `hreflang` ;
- données structurées ;
- auteurs ;
- dates ;
- sources ;
- versions ;
- sitemaps.

## Aucun balisage magique

```text
Aucun fichier ou schéma spécial « IA » ne garantit une citation.
```

## L’agent peut assister

- vérification des métadonnées ;
- détection de contenus incomplets ;
- proposition de liens ;
- contrôle du JSON-LD ;
- traduction ;
- résumé ;
- cohérence terminologique.

## L’agent ne peut pas garantir

- indexation ;
- classement ;
- rich result ;
- visibilité dans une réponse IA ;
- attribution ;
- trafic ;
- conversion.

---

# 30. Crawlers et entraînement

## H2

```text
Séparer recherche, utilisation et entraînement
```

La politique de crawl de FlatCMS distingue :

- robots de recherche classiques ;
- robots de recherche générative ;
- fetchers déclenchés par un utilisateur ;
- robots d’entraînement.

## Principe initial

```text
Recherche classique : autorisée
Recherche générative : autorisée
Entraînement : bloqué par défaut
```

Cette politique doit être implémentée dans `robots.txt` et révisée avant
le lancement.

## Limite

`robots.txt` exprime une directive de crawl à des robots coopératifs.

Il ne constitue pas :

- une protection d’accès ;
- une authentification ;
- une licence ;
- une garantie de non-utilisation.

---

# 31. Cas d’usage concrets

## H2

```text
Ce qu’un utilisateur pourrait faire
```

## Article

```text
Prépare un plan d’article sur l’architecture HMVC de FlatCMS en français,
sans publier.
```

Résultat :

```text
Brouillon
```

## Traduction

```text
Traduis cette page en de-DE en conservant les noms de classes et les
blocs de code.
```

Résultat :

```text
Traduction à relire
```

## SEO

```text
Propose trois titles inférieurs à la limite éditoriale définie et une
meta description pour cette page.
```

Résultat :

```text
Suggestions
```

## Builder

```text
Crée une structure de landing page avec un Hero, une grille de fonctions,
un tableau de tarifs et un CTA final.
```

Résultat :

```text
Aperçu validable
```

## Support

```text
Explique l’erreur Unable to write public/.htaccess à partir de la
documentation officielle.
```

Résultat :

```text
Diagnostic avec liens
```

---

# 32. Ce que FlatCMS ne doit pas promettre

## H2

```text
Une communication responsable
```

Ne pas affirmer :

- « FlatCMS écrit un site complet sans intervention » ;
- « l’IA ne fait jamais d’erreur » ;
- « agent-ready signifie autonome » ;
- « vos contenus sont automatiquement optimisés pour Google » ;
- « l’IA garantit une citation » ;
- « aucun contrôle humain n’est nécessaire » ;
- « toutes les données restent forcément en France » ;
- « les coûts sont fixes » ;
- « tous les modèles sont compatibles » ;
- « l’IA remplace le support » ;
- « OpenAI est inclus gratuitement ».

## Formulation recommandée

```text
FlatCMS fournit une architecture et des outils permettant d’intégrer des
assistants IA de manière contrôlée. Les capacités, coûts, fournisseurs et
conditions dépendent de la configuration et des modules activés.
```

---

# 33. Questions fréquentes éditoriales

> Ces réponses n’impliquent pas automatiquement un balisage `FAQPage`.

## H2

```text
Questions fréquentes sur l’approche agent-ready
```

### H3 — Que signifie agent-ready ?

```text
Cela signifie que l’architecture du CMS est préparée pour exposer des
données et actions structurées à des assistants ou agents contrôlés.
```

### H3 — FlatCMS contient-il déjà un agent complet ?

```text
Le projet prévoit une fondation de services IA et un module AiAgent. Les
fonctions réellement livrées doivent être vérifiées selon la distribution
et la version.
```

### H3 — AiAgent fait-il partie du LTS Core ?

```text
Pas nécessairement. Le README du LTS Core indique que les lignes
expérimentales, commerciales et instables restent hors du dépôt stable.
Le statut d’AiAgent doit être précisé par sa distribution et sa licence.
```

### H3 — FlatCMS dépend-il obligatoirement d’OpenAI ?

```text
Non. La couche de providers doit permettre d’isoler OpenAI et d’autres
fournisseurs derrière un contrat commun.
```

### H3 — Où est stockée la clé API ?

```text
Dans un stockage privé côté serveur, par exemple `.env.local`. Elle ne
doit jamais être envoyée au navigateur ou enregistrée dans le contenu.
```

### H3 — L’IA peut-elle publier automatiquement ?

```text
La publication automatique ne doit pas être activée par défaut. Une
validation humaine et une permission serveur sont requises pour les
actions sensibles.
```

### H3 — L’IA peut-elle traduire les six locales ?

```text
Elle peut produire un brouillon, mais chaque traduction doit être relue,
notamment pour les contenus techniques, juridiques et de sécurité.
```

### H3 — Que se passe-t-il si l’API ne répond plus ?

```text
FlatCMS continue à fonctionner manuellement. Une panne IA ne doit jamais
bloquer le frontend, l’administration ou les contenus existants.
```

### H3 — L’utilisation est-elle gratuite ?

```text
FlatCMS peut fournir l’intégration, mais les appels au fournisseur sont
soumis à ses tarifs, quotas et conditions. Un module premium peut
également nécessiter une licence FlatCMS.
```

### H3 — Agent-ready améliore-t-il automatiquement le référencement ?

```text
Non. L’IA peut assister la production et les contrôles, mais la visibilité
dépend de la qualité, de l’indexabilité, de l’autorité et de la pertinence
des contenus.
```

---

# 34. CTA final

## H2

```text
Ajouter l’IA sans rendre le CMS dépendant de l’IA
```

## Texte

```text
FlatCMS prépare des intégrations modulaires, contrôlées et réversibles :
le site continue de fonctionner même lorsque le fournisseur IA est
désactivé ou indisponible.
```

## CTA principal

```text
Explorer l’architecture
```

Destination :

```text
/fr-FR/architecture/
```

## CTA secondaire

```text
Découvrir les fonctionnalités
```

Destination :

```text
/fr-FR/fonctionnalites/
```

## Lien tertiaire

```text
Consulter la roadmap
```

Destination :

```text
/fr-FR/roadmap/
```

---

# 35. Maillage interne attendu

| Section | Destination |
|---|---|
| Hero | Architecture IA, fonctionnalités |
| Définition | Architecture générale |
| Fondations | HMVC, PSR-4, stockage JSON |
| AIManager | Documentation développeur |
| AiAgent | Fiche module |
| Rédaction | Articles |
| Traduction | Multilingue |
| Builders | PagesBuilder |
| Médias | Médiathèque |
| Chatbot | Documentation chatbot |
| Outils | Documentation function calling |
| Permissions | Utilisateurs et rôles |
| Secrets | Configuration |
| Coûts | Paramètres IA |
| SEO/GEO | Données structurées et blog |
| Crawl | Politique de crawl |
| CTA final | Architecture, fonctionnalités, roadmap |

---

# 36. Médias à produire

## Image Open Graph

Concept :

```text
FlatCMS au centre d’un graphe reliant contenus JSON, modules, outils,
agents spécialisés et validation humaine
```

## Diagramme principal

```text
Utilisateur
→ AiAgent
→ AIManager
→ Provider
→ Modèle
→ Réponse structurée
→ Validation
→ Brouillon / action
```

## Diagramme outils

```text
Agent
→ demande d’outil
→ permission
→ validation
→ confirmation
→ service FlatCMS
→ résultat
```

## Diagramme statuts

```text
LTS Core
Fondation IA
Module optionnel
Premium
Expérimental
Prévu
```

## Diagramme résilience

```text
API disponible
→ assistance IA

API indisponible
→ édition manuelle et site toujours actifs
```

---

# 37. Textes alternatifs suggérés

## Architecture IA

```text
Flux d’une requête IA de l’utilisateur vers le provider puis vers une
validation humaine dans FlatCMS
```

## Outils

```text
Un agent demande une action contrôlée qui est validée avant exécution par
un service FlatCMS
```

## Agents spécialisés

```text
Agents éditorial, traduction, SEO, Builder, média et support autour du CMS
```

## Résilience

```text
FlatCMS continue de fonctionner manuellement lorsque le service IA est
indisponible
```

---

# 38. Données structurées attendues

```text
WebPage
TechArticle
BreadcrumbList
ImageObject
SoftwareApplication
```

## Identifiants

```text
https://flat-cms.fr/fr-FR/agent-ready/#webpage
https://flat-cms.fr/fr-FR/agent-ready/#article
https://flat-cms.fr/fr-FR/agent-ready/#breadcrumb
https://flat-cms.fr/fr-FR/agent-ready/#primaryimage
https://flat-cms.fr/#software
```

## Règle

Ne pas ajouter une propriété ou un type fictif comme :

```text
AgentReadyCMS
AICertified
GEOOptimized
```

Les capacités décrites doivent correspondre au contenu visible et à la
version réelle.

---

# 39. Composants du thème suggérés

```text
HeroAgentReady
AgentReadyDefinition
ArchitectureFlow
FoundationGrid
CapabilityStatusTable
HumanValidationWorkflow
ToolCallingDiagram
SecurityPrinciples
UsageAndCostPanel
AgentSpecialists
SeoGeoGioSection
FaqAccordion
CallToActionBanner
```

---

# 40. Éléments à confirmer avant publication

- arborescence réelle de `app/Services/AI` ;
- noms exacts des contrats ;
- DTO réellement présents ;
- provider OpenAI ;
- format de `AiResponse` ;
- exceptions ;
- présence de `AIManager` ;
- structure du module `AiAgent` ;
- licence du module ;
- fonctions réellement testées ;
- statut du chatbot Flatty ;
- rôles autorisés ;
- paramètres disponibles ;
- stockage de la clé ;
- modèles supportés ;
- base URL personnalisée ;
- suivi d’usage ;
- limites ;
- génération de médias ;
- intégration PagesBuilder ;
- publication ;
- historique ;
- journal d’audit ;
- politique de confidentialité ;
- coûts ;
- support ;
- roadmap Agents SDK ;
- éventuel service Python séparé ;
- compatibilité MCP ;
- politique premium.

---

# 41. Checklist éditoriale

- [ ] Agent-ready est défini sans exagération.
- [ ] Le LTS Core est distingué du module AiAgent.
- [ ] Les fonctions existantes et prévues sont séparées.
- [ ] OpenAI n’est pas présenté comme fournisseur obligatoire.
- [ ] Le SDK Python n’est pas présenté comme une dépendance PHP native.
- [ ] La validation humaine est centrale.
- [ ] Les actions sensibles nécessitent une confirmation.
- [ ] Les secrets restent côté serveur.
- [ ] Les données personnelles sont minimisées.
- [ ] Les coûts et quotas sont mentionnés.
- [ ] Une panne IA ne bloque pas FlatCMS.
- [ ] Les sorties sont validées.
- [ ] Aucun résultat SEO ou IA n’est garanti.
- [ ] Les agents spécialisés ont un périmètre.
- [ ] Les statuts produit sont visibles.
- [ ] Les sources officielles sont liées.

---

# 42. Checklist technique

- [ ] Contrat provider.
- [ ] Provider sélectionnable.
- [ ] AIManager.
- [ ] DTO validés.
- [ ] Réponse normalisée.
- [ ] Timeout.
- [ ] Retry limité.
- [ ] Limitation par minute.
- [ ] Budget.
- [ ] Permissions serveur.
- [ ] Clé masquée.
- [ ] Aucun secret frontend.
- [ ] Outils en liste blanche.
- [ ] Schémas stricts.
- [ ] Confirmation des actions sensibles.
- [ ] Brouillon par défaut.
- [ ] Historique.
- [ ] Restauration.
- [ ] Logs filtrés.
- [ ] Suivi d’usage.
- [ ] Panne sans impact global.
- [ ] Tests du provider.
- [ ] Tests des permissions.
- [ ] Tests de validation.
- [ ] Tests de coûts.
- [ ] Tests de résilience.

---

# 43. Checklist d’intégration SEO et UX

- [ ] URL correcte.
- [ ] Canonique auto-référencée.
- [ ] `<html lang="fr-FR">`.
- [ ] Groupe `hreflang`.
- [ ] Title.
- [ ] Meta description.
- [ ] Open Graph.
- [ ] H1 unique.
- [ ] Statuts accessibles.
- [ ] Diagrammes responsive.
- [ ] Descriptions textuelles.
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

---

# 44. Sources internes

- `README.md`
- `VERSION`
- `ARCHITECTURE_CONTENT.md`
- `FEATURES_CONTENT.md`
- `STRUCTURED_DATA.md`
- `CRAWL_POLICY.md`
- `MULTILINGUAL.md`
- arborescence `app/Services/AI` ;
- module `app/Modules/AiAgent` ;
- configurations d’intégration ;
- tests ;
- manifestes ;
- documents de licence.

---

# 45. Références externes

- OpenAI — Agents SDK  
  https://openai.github.io/openai-agents-python/

- OpenAI API — Agents  
  https://developers.openai.com/api/docs/guides/agents

- OpenAI API — Function calling  
  https://developers.openai.com/api/docs/guides/function-calling

- OpenAI API — Structured outputs  
  https://developers.openai.com/api/docs/guides/structured-outputs

- OpenAI API — Safety best practices  
  https://developers.openai.com/api/docs/guides/safety-best-practices

- Google Search Central — AI features and your website  
  https://developers.google.com/search/docs/appearance/ai-features

Les références OpenAI décrivent les concepts d’agents, d’outils, de
guardrails, de sessions et de tracing. Elles ne définissent pas
l’architecture interne de FlatCMS, qui reste déterminée par son code.

---

# 46. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction complète de la page Agent-ready | ChatGPT / Alain BROYE |

---

# 47. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer ABOUT_CONTENT.md
```

Ce document contiendra la rédaction complète de la page :

```text
/fr-FR/a-propos/
```

Il présentera l’origine du projet, sa mission, son auteur, ses valeurs,
son modèle open source et commercial, sa gouvernance et sa roadmap.
