# 404_CONTENT — Page introuvable FlatCMS

> **Contenu éditorial et spécification technique prêts à intégrer**
>
> Projet : FlatCMS  
> Page : Erreur 404 `fr-FR`  
> URL de référence éditoriale : `https://flat-cms.fr/fr-FR/404/`  
> Réponse attendue pour une URL inexistante : `HTTP 404 Not Found`  
> Date de création : 8 juin 2026  
> Brief parent : `PAGE_BRIEFS.md` — `P0-404-FR`  
> Documents associés : `REDIRECTS.md`, `SITE_ARCHITECTURE.md`, `MULTILINGUAL.md`, `CONTENT_STYLE_GUIDE.md`, `LAUNCH_CHECKLIST.md`  
> Statut : première version rédactionnelle et technique à valider contre le routeur et les thèmes FlatCMS

---

# 1. Objectif

La page 404 doit aider une personne qui atteint une URL inexistante à
retrouver rapidement un contenu utile.

Elle doit :

- renvoyer un véritable statut HTTP `404 Not Found` ;
- expliquer simplement que la ressource n’a pas été trouvée ;
- conserver l’identité visuelle du site ;
- proposer une navigation utile ;
- fonctionner dans les six locales ;
- être accessible ;
- ne pas être indexée comme une page normale ;
- ne pas masquer les erreurs de routage ;
- permettre le suivi des liens cassés ;
- rester légère et résiliente.

Elle ne doit pas :

- rediriger automatiquement vers l’accueil ;
- répondre avec `200 OK` ;
- afficher une erreur technique brute ;
- révéler un chemin serveur ;
- afficher une stack trace ;
- proposer des liens inventés ;
- suggérer qu’une page existe si elle a été supprimée ;
- utiliser un ton humoristique qui masque l’information essentielle ;
- dépendre d’un service tiers pour fonctionner.

---

# 2. Métadonnées

## Balise `<title>`

```text
Page introuvable | FlatCMS
```

## Meta description

```text
La page demandée est introuvable. Revenez à l’accueil, consultez la
documentation ou recherchez un contenu FlatCMS.
```

## Directive robots

```text
noindex, follow
```

## En-tête HTTP alternatif

```text
X-Robots-Tag: noindex
```

L’en-tête peut être utilisé en complément, mais il ne remplace pas le
statut HTTP `404`.

## Canonique

La page d’erreur ne doit pas définir comme canonique :

- l’URL inexistante ;
- l’accueil ;
- `/fr-FR/404/` ;

sauf décision technique explicitement validée.

Recommandation :

```text
Aucune balise canonical sur les réponses 404 dynamiques.
```

## Open Graph

Les métadonnées Open Graph ne sont pas prioritaires sur une réponse 404.

Si le thème en génère globalement, elles ne doivent pas faire croire que
l’URL inexistante constitue un contenu partageable officiel.

---

# 3. Principe HTTP

## H2

```text
Une page introuvable doit réellement répondre en 404
```

## Réponse correcte

```http
HTTP/1.1 404 Not Found
Content-Type: text/html; charset=UTF-8
```

## Réponse incorrecte

```http
HTTP/1.1 200 OK
```

avec un contenu visuel indiquant :

```text
Page introuvable
```

Ce second comportement constitue une **soft 404** potentielle.

## Invariant

```text
Le template visuel ne doit jamais modifier le code de statut.
```

Le routeur ou le contrôleur doit définir le statut avant le rendu.

---

# 4. Quand utiliser 404, 410 ou une redirection ?

## H2

```text
Choisir la réponse adaptée
```

## `404 Not Found`

Utiliser lorsque :

- l’URL n’existe pas ;
- une faute de frappe est probable ;
- le serveur ne sait pas si la ressource reviendra ;
- aucune destination équivalente n’existe ;
- une route est inconnue ;
- un slug ne correspond à aucun contenu.

## `410 Gone`

Utiliser éventuellement lorsque :

- la ressource a été supprimée volontairement ;
- elle ne reviendra pas ;
- aucune destination équivalente n’existe ;
- cette décision est explicite et maintenue.

## `301 Moved Permanently`

Utiliser lorsque :

- la page a une nouvelle URL définitive ;
- le contenu de destination est réellement équivalent ;
- une migration ou restructuration est connue ;
- l’ancienne URL figure dans `REDIRECTS.md`.

## `308 Permanent Redirect`

Peut être utilisé comme équivalent permanent selon l’architecture et la
méthode HTTP.

## Ne pas rediriger vers l’accueil

Une URL supprimée ne doit pas être redirigée systématiquement vers la
page d’accueil.

Cette pratique :

- désoriente l’utilisateur ;
- masque les liens cassés ;
- empêche de diagnostiquer les erreurs ;
- peut être interprétée comme une soft 404 ;
- transmet un signal de destination non pertinente.

## Règle

```text
Rediriger uniquement vers la meilleure destination réellement équivalente.
Sinon, renvoyer 404 ou 410.
```

---

# 5. Contenu principal de la page

## H1

```text
Cette page est introuvable
```

## Code visuel

```text
Erreur 404
```

## Introduction

```text
L’adresse est peut-être incorrecte, ou la page a été déplacée ou
supprimée.
```

## Complément

```text
Vous pouvez revenir à l’accueil, consulter la documentation ou utiliser
la recherche pour trouver le contenu souhaité.
```

## CTA principal

```text
Revenir à l’accueil
```

Destination :

```text
/fr-FR/
```

## CTA secondaire

```text
Consulter la documentation
```

Destination :

```text
/fr-FR/documentation/
```

## Lien tertiaire

```text
Voir les fonctionnalités
```

Destination :

```text
/fr-FR/fonctionnalites/
```

---

# 6. Version éditoriale complète

## H1

```text
Cette page est introuvable
```

## Texte

```text
L’adresse saisie ne correspond à aucune page disponible sur FlatCMS.
La ressource a peut-être été déplacée, supprimée ou l’URL contient une
erreur.
```

```text
Utilisez les liens ci-dessous pour poursuivre votre navigation.
```

## Actions

```text
Revenir à l’accueil
```

```text
Consulter la documentation
```

```text
Découvrir les fonctionnalités
```

```text
Télécharger FlatCMS
```

```text
Contacter FlatCMS
```

## Texte facultatif

```text
Si vous avez suivi un lien depuis le site, vous pouvez signaler le lien
cassé afin qu’il soit corrigé.
```

CTA facultatif :

```text
Signaler un lien cassé
```

Destination :

```text
/fr-FR/contact/?motif=documentation
```

---

# 7. Recherche interne

## H2

```text
Rechercher sur FlatCMS
```

Une recherche peut être proposée si le moteur interne existe réellement.

## Placeholder

```text
Rechercher une page, un guide ou une fonctionnalité…
```

## Bouton

```text
Rechercher
```

## Règles

- recherche accessible au clavier ;
- label associé ;
- aucun résultat indexable automatiquement ;
- requête échappée ;
- longueur limitée ;
- protection contre les abus ;
- aucun script tiers requis ;
- conservation facultative de la requête selon la politique de confidentialité.

## Si la recherche n’existe pas

Ne pas afficher un faux champ.

Proposer seulement des liens sûrs et stables.

---

# 8. Liens recommandés

## H2

```text
Liens utiles
```

Sélection initiale :

- accueil ;
- pourquoi FlatCMS ;
- fonctionnalités ;
- documentation ;
- installation ;
- téléchargement ;
- tarifs ;
- contact.

## Règles

- limiter le nombre de liens ;
- choisir des destinations stables ;
- ne pas afficher une liste complète du sitemap ;
- adapter les liens à la locale ;
- utiliser des libellés descriptifs ;
- vérifier les liens à chaque release.

## Ordre recommandé

1. Accueil
2. Documentation
3. Fonctionnalités
4. Téléchargement
5. Contact

---

# 9. Suggestion de contenu selon l’URL

## H2

```text
Aider sans deviner
```

Le système peut proposer des contenus proches uniquement si la méthode
est fiable.

## Exemples

URL demandée :

```text
/fr-FR/documentation/installaton/
```

Suggestion possible :

```text
Installer FlatCMS
```

URL demandée :

```text
/fr-FR/fonctionalite/
```

Suggestion possible :

```text
Fonctionnalités
```

## Règles

- ne jamais rediriger automatiquement sur une simple similarité ;
- présenter la suggestion comme une proposition ;
- limiter aux contenus publiés ;
- respecter la locale ;
- éviter les rapprochements sur des URLs sensibles ;
- journaliser uniquement les données nécessaires ;
- ne pas exposer les slugs privés ou brouillons.

## Formulation

```text
Cherchiez-vous cette page ?
```

---

# 10. Multilingue

## H2

```text
Une page 404 dans la langue du visiteur
```

## Locales

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

## Résolution de locale

Ordre recommandé :

1. locale présente dans l’URL ;
2. locale de session ou de préférence ;
3. langue de l’interface ;
4. locale par défaut ;
5. `fr-FR`.

## Exemple

Une URL inexistante sous :

```text
/de-DE/
```

doit afficher la page 404 allemande et non la version française.

## URL sans locale

Le système peut :

- utiliser la locale par défaut ;
- ou proposer le sélecteur de langue.

Il ne doit pas créer une boucle de redirection.

## `hreflang`

Les réponses 404 dynamiques ne doivent pas générer un groupe `hreflang`
comme s’il s’agissait de pages éditoriales indexables.

## Sélecteur

Le sélecteur de langue peut rester disponible, mais il doit renvoyer vers
une destination utile, par exemple l’accueil de la locale, et non vers
six variantes inexistantes de la même URL.

---

# 11. Navigation et layout

## H2

```text
Conserver une navigation minimale et fiable
```

## Header

Le header peut conserver :

- logo ;
- lien accueil ;
- documentation ;
- téléchargement ;
- sélecteur de langue.

## Footer

Le footer peut conserver :

- mentions légales ;
- confidentialité ;
- licences ;
- contact.

## Éviter

- méga-menu complexe dépendant de données absentes ;
- carrousel ;
- scripts lourds ;
- widget nécessitant une API ;
- contenus dynamiques critiques ;
- chatbot lancé automatiquement ;
- formulaire complet inutile.

## Résilience

La page doit rester affichable même si :

- un module optionnel est indisponible ;
- le service IA est en panne ;
- la recherche est indisponible ;
- une licence Builder est expirée ;
- un contenu éditorial est corrompu ;
- un service externe ne répond pas.

---

# 12. Accessibilité

## H2

```text
Une erreur compréhensible et navigable
```

## Exigences

- H1 unique ;
- texte explicite ;
- code 404 visible mais non suffisant seul ;
- contraste suffisant ;
- focus visible ;
- CTA utilisables au clavier ;
- ordre logique ;
- liens descriptifs ;
- pas de redirection automatique ;
- pas de compte à rebours ;
- pas de son ;
- illustration décorative avec `alt=""` ;
- illustration informative avec alternative ;
- langue déclarée ;
- zoom sans perte ;
- aucun piège de focus.

## Focus initial

Le focus ne doit pas être déplacé automatiquement sauf nécessité
d’accessibilité clairement testée.

Le titre principal doit être rapidement atteignable.

## Message

Éviter :

```text
Oops!
```

comme unique explication.

Préférer :

```text
Cette page est introuvable
```

---

# 13. Ton éditorial

## H2

```text
Rester clair et rassurant
```

Le ton doit être :

- direct ;
- calme ;
- utile ;
- professionnel ;
- cohérent avec FlatCMS.

## Éviter

- culpabiliser l’utilisateur ;
- humour excessif ;
- jargon ;
- message technique ;
- fausse promesse ;
- texte trop long ;
- animation envahissante.

## Formulation recommandée

```text
L’adresse est peut-être incorrecte, ou la page a été déplacée ou supprimée.
```

## Formulation à éviter

```text
Vous vous êtes trompé !
```

---

# 14. Illustration

## H2

```text
Un visuel léger et non bloquant
```

## Concept proposé

```text
Une structure de fichiers FlatCMS dont un chemin se termine par un
nœud absent, dans l’univers indigo et bleu nuit de la marque.
```

## Variante

```text
Une page JSON stylisée portant le code 404, reliée au logo FlatCMS par
des lignes lumineuses interrompues.
```

## Règles

- SVG ou WebP optimisé ;
- aucune dépendance externe ;
- contenu essentiel en HTML ;
- pas de texte important uniquement dans l’image ;
- poids réduit ;
- dimensions réservées ;
- mode clair et sombre si nécessaire ;
- pas d’animation indispensable.

## Texte alternatif

Si décorative :

```html
alt=""
```

Si informative :

```text
Chemin de fichier interrompu illustrant une page introuvable
```

---

# 15. SEO

## H2

```text
Éviter les soft 404 et les redirections trompeuses
```

## Statut

```text
404
```

## Indexation

Une réponse 404 n’est pas destinée à être indexée.

Le `noindex` peut être conservé comme signal complémentaire, mais le
statut HTTP reste essentiel.

## Sitemap

Aucune URL introuvable ne doit apparaître dans :

- sitemap principal ;
- sitemap localisé ;
- sitemap images ;
- sitemap articles.

L’URL éditoriale `/fr-FR/404/` ne doit pas être ajoutée au sitemap.

## Liens internes

Le site ne doit pas créer intentionnellement de liens vers la page 404.

Les liens cassés doivent être corrigés ou redirigés.

## Canonical

Ne pas canonicaliser une URL introuvable vers l’accueil.

## Données structurées

Ne pas produire de données structurées de type :

- `Article` ;
- `Product` ;
- `SoftwareApplication` ;
- `FAQPage` ;

pour une page 404.

Un `WebPage` générique n’est pas nécessaire et peut être omis.

## Open Graph

La page ne doit pas être pensée pour le partage social.

---

# 16. Cache

## H2

```text
Éviter de figer une erreur trop longtemps
```

Une réponse 404 peut être mise en cache selon HTTP.

Le projet doit toutefois choisir une politique compatible avec :

- créations de pages ;
- migrations ;
- corrections de liens ;
- déploiements ;
- CDN.

## Politique recommandée de départ

```http
Cache-Control: no-cache
```

ou une durée courte explicitement testée.

## CDN

Vérifier que le CDN :

- conserve le code 404 ;
- ne remplace pas la page par son propre template ;
- ne convertit pas en 200 ;
- permet une purge ;
- ne met pas en cache indéfiniment une URL qui sera créée ensuite.

## Ne pas utiliser sans réflexion

```http
Cache-Control: max-age=31536000
```

sur toutes les réponses 404.

---

# 17. Sécurité

## H2

```text
Ne rien révéler sur l’infrastructure
```

La page ne doit pas afficher :

- chemin absolu ;
- classe PHP ;
- namespace ;
- requête SQL ;
- fichier JSON ;
- stack trace ;
- version serveur ;
- version PHP ;
- compte système ;
- variable d’environnement ;
- règle de sécurité ;
- existence d’une ressource privée.

## URL affichée

Il n’est pas nécessaire de réimprimer l’URL complète demandée.

Si elle est affichée :

- l’échapper ;
- limiter sa longueur ;
- ne pas l’interpréter comme HTML ;
- ne pas créer automatiquement un lien actif.

## Cas sensible

Une route privée inexistante ne doit pas révéler si une ressource
similaire existe.

---

# 18. Journalisation

## H2

```text
Suivre les erreurs utiles sans collecter excessivement
```

## Données possibles

- date ;
- chemin demandé ;
- locale ;
- référent interne ;
- agent utilisateur limité ;
- code de réponse ;
- source de navigation ;
- fréquence ;
- identifiant de déploiement.

## Finalités

- détecter les liens internes cassés ;
- vérifier les migrations ;
- repérer une attaque ;
- améliorer la navigation ;
- identifier une URL externe importante.

## Minimisation

- ne pas enregistrer les query strings sensibles ;
- masquer les tokens ;
- tronquer ou pseudonymiser l’IP selon la politique ;
- limiter la durée ;
- restreindre les accès ;
- exclure les robots bruyants des rapports éditoriaux.

## Exemple de chemin à filtrer

```text
/reset-password?token=...
```

Journaliser seulement :

```text
/reset-password
```

---

# 19. Monitoring

## H2

```text
Transformer les 404 utiles en actions
```

## Tableau de suivi

| URL | Nombre | Source | Première occurrence | Dernière occurrence | Action |
|---|---:|---|---|---|---|
| ancienne URL | 42 | lien externe | date | date | redirection |
| faute interne | 12 | menu | date | date | correction |
| scan automatique | 500 | robot | date | date | ignorer ou sécurité |

## Priorisation

1. lien interne cassé ;
2. ancienne URL recevant du trafic ;
3. backlink externe pertinent ;
4. erreur de migration ;
5. faute fréquente ;
6. scan automatisé sans valeur.

## Alertes

Ne pas envoyer une alerte pour chaque 404.

Utiliser :

- seuil ;
- regroupement ;
- rapport quotidien ou hebdomadaire ;
- détection d’anomalie ;
- classification.

---

# 20. Migration du wiki et anciens sous-domaines

## H2

```text
Utiliser les redirections avant la page 404
```

Lors du regroupement du futur site :

- inventorier les anciennes URLs ;
- définir une destination exacte ;
- créer une redirection permanente ;
- mettre à jour les liens internes ;
- vérifier les canonicals ;
- vérifier les `hreflang` ;
- tester le code final ;
- surveiller les 404.

## Règle

Une ancienne page du wiki ne doit pas être envoyée vers la documentation
générale si une page précise équivalente existe.

## Sans équivalent

Si aucun contenu équivalent n’existe :

- laisser 404 ;
- ou utiliser 410 si la suppression est volontaire et définitive ;
- proposer des liens utiles dans la page d’erreur.

---

# 21. Route éditoriale `/404/`

## H2

```text
Ne pas confondre template et URL d’erreur
```

Le projet peut conserver une route technique ou de prévisualisation :

```text
/fr-FR/404/
```

uniquement pour tester le rendu.

## Recommandation

En production, cette route de prévisualisation doit elle aussi répondre :

```text
404
```

et ne pas être indexée.

## Alternative

Prévoir une route d’administration ou un mode preview réservé :

```text
/admin/themes/preview-error/404
```

Cette route doit être protégée et en `noindex`.

## Source de vérité

Le template peut être stocké dans le thème, mais son rendu doit être
appelé par le gestionnaire d’erreurs du CMS.

---

# 22. Architecture technique recommandée

## H2

```text
Séparer la résolution, la réponse et le rendu
```

## Composants conceptuels

```text
NotFoundHandler
ErrorResponseFactory
ErrorPageViewModel
ErrorPageRenderer
BrokenLinkLogger
RedirectResolver
```

## Flux

```text
Request
→ Router
→ aucune route
→ RedirectResolver
→ aucune redirection
→ NotFoundHandler
→ Response 404
→ ErrorPageRenderer
```

## Responsabilités

### `RedirectResolver`

- consulte la table de redirections ;
- valide la destination ;
- évite les boucles ;
- produit `301` ou `308`.

### `NotFoundHandler`

- détermine la locale ;
- prépare les liens ;
- journalise de manière minimale ;
- demande une réponse 404.

### `ErrorResponseFactory`

- fixe le code ;
- fixe les en-têtes ;
- crée le corps.

### `ErrorPageRenderer`

- rend le template ;
- échappe les données ;
- n’écrase pas le statut.

### `BrokenLinkLogger`

- filtre les données sensibles ;
- agrège ;
- applique la conservation.

---

# 23. Exemple PHP conceptuel

```php
<?php

declare(strict_types=1);

$response = new Response(
    body: $view->render('errors/404', $viewModel),
    status: 404,
    headers: [
        'Content-Type' => 'text/html; charset=UTF-8',
        'X-Robots-Tag' => 'noindex',
        'Cache-Control' => 'no-cache',
    ],
);

return $response;
```

## Anti-pattern

```php
echo $view->render('errors/404');
```

sans définir le statut.

## Autre anti-pattern

```php
header('Location: /');
exit;
```

pour toute URL inconnue.

---

# 24. Apache

## H2

```text
Conserver le statut avec une page personnalisée
```

Exemple conceptuel :

```apache
ErrorDocument 404 /index.php
```

ou gestion complète par le front controller.

## Important

Le projet doit vérifier que :

- Apache ne redirige pas vers une URL absolue ;
- la réponse finale reste en 404 ;
- le routeur reconnaît le contexte d’erreur ;
- aucune boucle n’apparaît ;
- les assets de la page existent.

## Préférence

Dans FlatCMS, il est généralement préférable que les URLs dynamiques
inconnues soient gérées par le routeur et son front controller.

---

# 25. Nginx

## H2

```text
Rendre la page d’erreur sans convertir la réponse
```

Exemple conceptuel :

```nginx
error_page 404 /index.php;
```

ou traitement par :

```nginx
try_files $uri $uri/ /index.php?$query_string;
```

puis gestion de la route inexistante dans FlatCMS.

## Important

Tester :

```bash
curl -I https://flat-cms.fr/fr-FR/url-inexistante
```

Résultat attendu :

```text
HTTP/2 404
```

et non :

```text
HTTP/2 200
```

## CDN

Tester également la réponse à travers Cloudflare ou tout proxy public.

---

# 26. APIs et formats non HTML

## H2

```text
Adapter la réponse au contexte
```

Une route API inexistante ne doit pas forcément renvoyer le template HTML.

## JSON

Exemple :

```http
HTTP/1.1 404 Not Found
Content-Type: application/json
```

```json
{
  "error": {
    "code": "not_found",
    "message": "La ressource demandée est introuvable."
  }
}
```

## Règles

- même statut 404 ;
- pas de HTML dans une API JSON ;
- pas de stack trace ;
- identifiant d’erreur facultatif ;
- message localisé si le contrat le prévoit ;
- structure documentée.

## Assets

Un asset absent doit renvoyer 404, sans afficher nécessairement la page
HTML complète.

---

# 27. Page supprimée ou brouillon

## H2

```text
Ne pas exposer un contenu non publié
```

## Brouillon

Un contenu en brouillon doit :

- être accessible uniquement aux personnes autorisées ;
- renvoyer 404 ou 403 selon la politique ;
- ne pas révéler son titre ;
- ne pas être indexé.

## Contenu supprimé

Selon la politique :

- redirection vers un équivalent ;
- `410 Gone` ;
- `404 Not Found`.

## Corbeille

La présence d’un contenu dans la corbeille ne doit pas le rendre public.

---

# 28. Cas d’une locale inexistante

## H2

```text
Distinguer langue inconnue et page inconnue
```

Exemple :

```text
/xx-XX/documentation/
```

Politique possible :

- 404 localisée dans la langue par défaut ;
- aucun fallback silencieux de contenu ;
- proposition des langues disponibles.

## Message

```text
Cette langue n’est pas disponible.
```

avec liens vers :

- français ;
- anglais ;
- allemand ;
- espagnol ;
- italien ;
- portugais.

## Statut

```text
404
```

si la route locale n’existe pas.

---

# 29. Version courte pour mobile

## H1

```text
Page introuvable
```

## Texte

```text
L’adresse est incorrecte ou la page n’est plus disponible.
```

## CTA

```text
Accueil
```

```text
Documentation
```

```text
Recherche
```

La version mobile ne doit pas supprimer les informations essentielles.

---

# 30. Questions fréquentes éditoriales

> Ces questions ne nécessitent pas un balisage `FAQPage`.

## H2

```text
Questions sur les pages introuvables
```

### H3 — Pourquoi cette page s’affiche-t-elle ?

```text
L’adresse ne correspond à aucun contenu publié. Elle peut être incorrecte,
ancienne ou liée à une page supprimée.
```

### H3 — La page a-t-elle été déplacée ?

```text
Lorsqu’une destination équivalente est connue, FlatCMS doit mettre en
place une redirection. Sinon, la réponse reste en 404.
```

### H3 — Puis-je signaler un lien cassé ?

```text
Oui. Utilisez le formulaire de contact en indiquant la page depuis
laquelle vous avez suivi le lien.
```

### H3 — Pourquoi ne suis-je pas redirigé automatiquement vers l’accueil ?

```text
Une redirection automatique masquerait l’erreur et pourrait conduire vers
une page sans rapport avec votre recherche.
```

---

# 31. Données structurées

## H2

```text
Ne pas enrichir une page d’erreur comme un contenu
```

## Recommandation

```text
Aucune donnée structurée métier sur une réponse 404.
```

Ne pas générer :

- `Article` ;
- `BlogPosting` ;
- `SoftwareApplication` ;
- `Product` ;
- `FAQPage` ;
- `BreadcrumbList` trompeur.

## Breadcrumb

Un fil d’Ariane visuel simple peut afficher :

```text
Accueil > Page introuvable
```

mais il n’est pas nécessaire de publier un `BreadcrumbList` JSON-LD sur
une réponse non indexable.

---

# 32. Médias à produire

## Image Open Graph

Aucune image Open Graph spécifique n’est nécessaire.

## Illustration principale

Concept :

```text
Un chemin de fichiers JSON interrompu au niveau d’une ressource absente,
avec le code 404 dans l’identité FlatCMS.
```

## Formats

- SVG ;
- WebP ;
- fond transparent ou adapté au thème ;
- version sombre et claire si nécessaire.

## Poids cible

```text
Inférieur à 100 Ko si possible
```

---

# 33. Composants du thème suggérés

```text
ErrorPageLayout
ErrorCode
ErrorMessage
ErrorActions
SuggestedLinks
SearchForm
BrokenLinkReport
LocaleSelector
```

## Dépendances

La page ne doit pas dépendre de PagesBuilder, MenuBuilder ou
FooterBuilder pour afficher son contenu essentiel.

Elle peut utiliser le thème, mais doit disposer d’un fallback minimal.

---

# 34. Fallback minimal

Si le thème principal ne peut pas être rendu :

```html
<!doctype html>
<html lang="fr-FR">
<head>
  <meta charset="utf-8">
  <meta name="robots" content="noindex, follow">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Page introuvable | FlatCMS</title>
</head>
<body>
  <main>
    <h1>Cette page est introuvable</h1>
    <p>L’adresse est incorrecte ou la page n’est plus disponible.</p>
    <p><a href="/fr-FR/">Revenir à l’accueil</a></p>
  </main>
</body>
</html>
```

La réponse doit toujours rester en `404`.

---

# 35. Tests unitaires obligatoires

```text
testUnknownRouteReturns404
testUnknownRouteDoesNotReturn200
testUnknownRouteDoesNotRedirectHome
testNotFoundRendererPreservesStatus
testNotFoundPageUsesRequestedLocale
testNotFoundPageFallsBackToDefaultLocale
testNotFoundPageEscapesRequestedPath
testNotFoundPageContainsNoStackTrace
testNotFoundPageContainsNoStructuredBusinessData
testNotFoundPageIsNoindex
testKnownRedirectRunsBefore404
testRedirectLoopIsRejected
testDeletedDraftIsNotExposed
testApiUnknownRouteReturnsJson404
testAssetUnknownRouteReturns404
```

---

# 36. Tests d’intégration obligatoires

## Route inexistante

1. demander une URL aléatoire ;
2. vérifier `404` ;
3. vérifier le template ;
4. vérifier les CTA ;
5. vérifier l’absence de canonical vers l’accueil ;
6. vérifier `noindex`.

## Soft 404

1. demander une URL inexistante ;
2. vérifier que le code n’est pas `200` ;
3. vérifier qu’aucune redirection automatique n’a lieu.

## Locale

1. demander une URL inexistante dans les six locales ;
2. vérifier la langue ;
3. vérifier les liens localisés ;
4. vérifier le statut.

## Redirection

1. ajouter une ancienne URL dans la table ;
2. vérifier `301` ;
3. vérifier la destination exacte ;
4. retirer la règle ;
5. vérifier le retour en `404`.

## Cache

1. demander une URL inexistante ;
2. créer ensuite la page ;
3. purger si nécessaire ;
4. vérifier que la page répond en `200`.

---

# 37. Tests end-to-end

## Scénario A — Faute de frappe

```text
Étant donné une URL inconnue
Quand un visiteur l’ouvre
Alors le serveur répond 404
Et la page explique l’erreur
Et des liens utiles sont disponibles
```

## Scénario B — Ancienne URL

```text
Étant donné une ancienne URL avec destination équivalente
Quand elle est ouverte
Alors le serveur répond par une redirection permanente
Et le visiteur arrive sur la destination exacte
```

## Scénario C — Page supprimée sans équivalent

```text
Étant donné une page supprimée sans remplacement
Quand son URL est ouverte
Alors le serveur répond 404 ou 410 selon la politique
Et il ne redirige pas vers l’accueil
```

## Scénario D — API

```text
Étant donné une route API inconnue
Quand elle est appelée
Alors la réponse est JSON
Et le statut est 404
Et aucune page HTML n’est renvoyée
```

---

# 38. Commandes de vérification

## En-têtes

```bash
curl -I https://flat-cms.fr/fr-FR/cette-page-n-existe-pas
```

## Corps et statut

```bash
curl -sS -o /tmp/flatcms-404.html -w "%{http_code}\n" \
  https://flat-cms.fr/fr-FR/cette-page-n-existe-pas
```

Résultat attendu :

```text
404
```

## Redirection

```bash
curl -I https://flat-cms.fr/ancienne-url
```

Résultat attendu si une redirection existe :

```text
301
```

ou :

```text
308
```

---

# 39. Audit demandé à Codex

Codex doit vérifier :

- route fallback ;
- gestionnaire d’erreur ;
- objet `Response` ;
- code de statut ;
- templates ;
- Apache ;
- Nginx ;
- CDN ;
- locale ;
- API ;
- assets ;
- redirections ;
- cache ;
- logs ;
- Search Console ;
- sitemap.

## Rapport attendu

```text
Fichier
Ligne
Comportement actuel
Code HTTP actuel
Risque
Correction
Test
```

## Confirmations critiques

- [ ] Toute route HTML inconnue renvoie 404.
- [ ] Aucune route inconnue ne renvoie 200.
- [ ] Aucune redirection générale vers l’accueil.
- [ ] Les redirections exactes sont évaluées avant la 404.
- [ ] Les six locales sont gérées.
- [ ] Les APIs renvoient JSON 404.
- [ ] Les assets absents renvoient 404.
- [ ] Aucun détail serveur n’est exposé.
- [ ] La page fonctionne sans Builder premium.
- [ ] Le CDN conserve le statut.
- [ ] La page n’est pas dans les sitemaps.
- [ ] Les liens internes cassés sont détectés.

---

# 40. Critères d’acceptation

L’implémentation est acceptée uniquement si :

1. une URL inconnue renvoie `404 Not Found` ;
2. le contenu HTML est utile et localisé ;
3. aucune redirection automatique vers l’accueil n’existe ;
4. les redirections permanentes précises sont appliquées avant le fallback ;
5. le template ne transforme pas la réponse en `200` ;
6. la page ne révèle aucun détail technique sensible ;
7. elle reste utilisable sans JavaScript ;
8. elle fonctionne sur mobile et au clavier ;
9. elle ne figure dans aucun sitemap ;
10. les tests automatisés passent.

---

# 41. Checklist éditoriale

- [ ] H1 clair.
- [ ] Code 404 visible.
- [ ] Explication simple.
- [ ] CTA accueil.
- [ ] CTA documentation.
- [ ] Recherche uniquement si fonctionnelle.
- [ ] Liens localisés.
- [ ] Ton professionnel.
- [ ] Aucun jargon.
- [ ] Aucun humour gênant.
- [ ] Aucun faux lien.
- [ ] Signalement de lien cassé.
- [ ] Six locales préparées.
- [ ] Illustration optimisée.
- [ ] Texte alternatif approprié.

---

# 42. Checklist SEO et HTTP

- [ ] Statut HTTP 404.
- [ ] Pas de 200.
- [ ] Pas de redirection accueil.
- [ ] `noindex, follow`.
- [ ] Aucun canonical accueil.
- [ ] Aucune URL 404 dans le sitemap.
- [ ] Pas de données structurées métier.
- [ ] Pas de `hreflang` éditorial.
- [ ] Redirections exactes.
- [ ] 410 selon politique.
- [ ] Cache contrôlé.
- [ ] CDN testé.
- [ ] Search Console surveillée.

---

# 43. Checklist accessibilité

- [ ] H1 unique.
- [ ] Message explicite.
- [ ] Navigation clavier.
- [ ] Focus visible.
- [ ] Liens descriptifs.
- [ ] Contraste suffisant.
- [ ] Pas de redirection automatique.
- [ ] Pas de compte à rebours.
- [ ] Illustration correctement décrite.
- [ ] Fonctionnement sans JavaScript.
- [ ] Langue correcte.
- [ ] Mobile et zoom testés.

---

# 44. Sources internes

- `REDIRECTS.md`
- `SITE_ARCHITECTURE.md`
- `MULTILINGUAL.md`
- `CONTENT_STYLE_GUIDE.md`
- `LAUNCH_CHECKLIST.md`
- routeur FlatCMS ;
- classe `Response` ;
- templates du thème ;
- configuration Apache ;
- configuration Nginx ;
- configuration Cloudflare ;
- Search Console ;
- sitemaps.

---

# 45. Références externes officielles

- Google — How HTTP status codes affect Google’s crawlers  
  https://developers.google.com/crawling/docs/troubleshooting/http-status-codes

- Google Search Central — Redirects and Google Search  
  https://developers.google.com/search/docs/crawling-indexing/301-redirects

- MDN — 404 Not Found  
  https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/404

- RFC 9110 — 404 Not Found  
  https://www.rfc-editor.org/rfc/rfc9110.html#name-404-not-found

Ces références définissent le comportement du statut 404, la différence
avec 410 et l’usage des redirections permanentes.

---

# 46. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction de la page et du contrat technique 404 | ChatGPT / Alain BROYE |

---

# 47. Prochaine action

Après ajout dans le Drive, les quinze pages P0 prévues dans
`PAGE_BRIEFS.md` sont rédigées.

La prochaine phase recommandée est :

```text
Créer THEME_SPECIFICATION.md
```

Ce document décrira le thème officiel du futur site :

- layouts ;
- header ;
- navigation ;
- footer ;
- grille ;
- composants ;
- responsive ;
- accessibilité ;
- performances ;
- modes clair et sombre ;
- intégration des contenus P0.
