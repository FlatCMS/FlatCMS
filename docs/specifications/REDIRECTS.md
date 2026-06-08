# REDIRECTS — Plan de migration des URLs du futur site officiel FlatCMS

> **Document directeur de migration**
>
> Projet : FlatCMS  
> Domaine cible : `https://flat-cms.fr`  
> Date de création : 8 juin 2026  
> Documents parents : `SEO.md`, `SITE_ARCHITECTURE.md`, `CONTENT_MATRIX.md`, `DOCUMENTATION_MAP.md`, `KEYWORDS.md`  
> Statut : stratégie initiale et registre opérationnel à compléter avant mise en production

---

## 1. Objet du document

Ce document définit la stratégie de migration des URLs actuelles de FlatCMS vers l’architecture définitive du futur site officiel.

La migration concerne principalement :

- la landing page actuelle de `flat-cms.fr` ;
- le contenu de `wiki.flat-cms.fr` ;
- les anciennes URLs dynamiques de documentation ;
- le blog actuellement hébergé avec le wiki ;
- les médias et fichiers téléchargeables ;
- les anciennes variantes de langue ;
- les anciennes pages devenues obsolètes ;
- les URLs dupliquées ou concurrentes ;
- les futures modifications de slugs.

L’objectif est de préserver autant que possible :

- les visiteurs ;
- les liens internes ;
- les liens externes ;
- les signaux d’indexation ;
- les positions acquises ;
- les favoris ;
- les références présentes dans les articles, vidéos et réseaux sociaux.

---

## 2. Principes fondamentaux

## 2.1 Une ancienne URL doit avoir une destination logique

Chaque ancienne URL doit être associée à :

- une page nouvelle équivalente ;
- une page consolidée couvrant réellement le même sujet ;
- ou un code `404`/`410` lorsqu’aucun contenu pertinent ne la remplace.

Exemple correct :

```text
Ancienne documentation d’installation
→ Nouvelle documentation d’installation
```

Exemple incorrect :

```text
Toutes les anciennes pages
→ Accueil
```

Une redirection vers une destination non pertinente peut désorienter les visiteurs et être interprétée comme une erreur « soft 404 ».

---

## 2.2 Redirections permanentes côté serveur

Les déplacements définitifs doivent utiliser en priorité :

```text
301 Moved Permanently
```

ou :

```text
308 Permanent Redirect
```

Ces redirections doivent être effectuées côté serveur.

Les redirections JavaScript ou les balises `meta refresh` ne doivent être utilisées qu’en dernier recours.

---

## 2.3 Destination finale directe

Une ancienne URL doit rediriger directement vers sa destination finale.

À éviter :

```text
ancienne URL
→ URL intermédiaire
→ ancienne locale
→ URL finale
```

À privilégier :

```text
ancienne URL
→ URL finale
```

Objectif :

- aucune boucle ;
- aucune chaîne ;
- une seule redirection lorsque possible ;
- trois sauts maximum dans les rares cas impossibles à simplifier.

---

## 2.4 Conservation longue durée

Les redirections doivent être conservées :

- au minimum un an ;
- idéalement plusieurs années ;
- définitivement pour les URLs ayant reçu des liens externes importants.

Le fichier de correspondance doit rester archivé même lorsque les règles sont intégrées au serveur ou au CMS.

---

## 2.5 Une migration préparée avant le lancement

La totalité des étapes suivantes doit être prête avant la mise en ligne :

```text
Inventaire
→ Classification
→ Correspondance
→ Création des nouvelles pages
→ Tests
→ Configuration des redirections
→ Mise à jour des liens
→ Publication
→ Contrôle
→ Surveillance
```

---

# 3. Périmètre de migration

## 3.1 Domaine principal actuel

```text
https://flat-cms.fr/
```

La page actuelle pourra être remplacée par la nouvelle page d’accueil sans redirection si l’URL reste identique.

Les anciennes URLs secondaires du domaine principal doivent être inventoriées.

---

## 3.2 Wiki actuel

```text
https://wiki.flat-cms.fr/
```

Contenus concernés :

- documentation ;
- articles de blog ;
- pages d’architecture ;
- tutoriels ;
- médias ;
- catégories ;
- pages locales ;
- anciennes URLs à paramètres.

Destinations futures :

```text
https://flat-cms.fr/{locale}/documentation/
https://flat-cms.fr/{locale}/blog/
https://flat-cms.fr/{locale}/architecture/
```

---

## 3.3 Démonstration

```text
https://demo.flat-cms.fr/
```

La démonstration reste sur son sous-domaine.

Aucune redirection générale vers le domaine principal n’est prévue.

Seules les anciennes URLs de présentation de la démo, si elles existent ailleurs, devront pointer vers :

```text
https://demo.flat-cms.fr/
```

Les contenus fictifs de la démo ne doivent pas être migrés vers le blog officiel.

---

## 3.4 Ressources et téléchargements

À inventorier :

- images ;
- vidéos ;
- PDF ;
- archives ZIP ;
- fichiers de version ;
- images Open Graph ;
- captures de documentation ;
- logos.

Les ressources recevant du trafic ou des liens externes doivent également être redirigées lorsque leur emplacement change.

---

# 4. Architecture cible

## 4.1 Site institutionnel

```text
https://flat-cms.fr/fr-FR/
https://flat-cms.fr/en-US/
https://flat-cms.fr/de-DE/
https://flat-cms.fr/es-ES/
https://flat-cms.fr/it-IT/
https://flat-cms.fr/pt-PT/
```

## 4.2 Documentation

```text
https://flat-cms.fr/{locale}/documentation/
```

## 4.3 Blog

```text
https://flat-cms.fr/{locale}/blog/
```

## 4.4 Architecture

```text
https://flat-cms.fr/{locale}/architecture/
```

## 4.5 Comparatifs

```text
https://flat-cms.fr/{locale}/comparatifs/
```

## 4.6 Téléchargements

```text
https://flat-cms.fr/{locale}/telechargement/
```

---

# 5. Registre de correspondance

Le tableau suivant constitue le format obligatoire du registre.

| ID | Ancienne URL | Nouvelle URL | Locale | Type | Action | HTTP | Priorité | Statut | Test |
|---:|---|---|---|---|---|---:|---|---|---|
| 1 | À compléter | À compléter | fr-FR | Documentation | Rediriger | 301 | P0 | À analyser | Non |

## Colonnes complémentaires recommandées

| Champ | Description |
|---|---|
| Titre actuel | Titre visible ou title existant |
| Sujet | Thématique principale |
| Trafic | Niveau ou nombre de visites |
| Backlinks | Nombre ou importance des liens externes |
| Indexée | Oui / Non / Inconnu |
| Décision | Conserver, réécrire, fusionner, supprimer |
| Justification | Motif de la décision |
| Responsable | Personne validant la correspondance |
| Date de test | Dernière vérification |
| Notes | Cas particulier |

---

# 6. Types d’action

## 6.1 Conserver l’URL

Applicable lorsque l’URL définitive reste strictement identique.

```text
200 OK
```

Actions :

- remplacer ou enrichir le contenu ;
- conserver la canonique ;
- mettre à jour les liens internes ;
- ne pas ajouter de redirection inutile.

---

## 6.2 Rediriger vers une URL équivalente

Applicable lorsqu’une page change d’emplacement ou de slug.

```text
301 ou 308
```

Exemple :

```text
https://wiki.flat-cms.fr/fr-FR/documentation/installation
→
https://flat-cms.fr/fr-FR/documentation/installation/
```

---

## 6.3 Fusionner plusieurs pages

Applicable lorsque plusieurs anciennes pages couvrent le même sujet.

Exemple :

```text
ancienne-installation-apache
ancienne-installation-mamp
ancien-guide-installation
→
nouvelle page principale ou guides séparés pertinents
```

Conditions :

- la nouvelle page doit reprendre les informations utiles ;
- chaque ancienne URL doit pointer vers la destination la plus proche ;
- la fusion doit être documentée.

---

## 6.4 Scinder une ancienne page

Une ancienne page très longue peut être divisée en plusieurs nouvelles pages.

Dans ce cas :

- l’ancienne URL pointe vers la nouvelle page principale ;
- les nouvelles pages secondaires sont reliées depuis la page principale ;
- les ancres internes et titres doivent guider l’utilisateur.

Exemple :

```text
ancienne page « Installation complète »
→
/documentation/installation/
```

Puis :

```text
/documentation/deploiement/apache/
/documentation/deploiement/nginx/
/documentation/deploiement/mamp/
/documentation/deploiement/wamp/
```

---

## 6.5 Supprimer sans remplacement

Applicable lorsque le contenu :

- est faux ;
- est périmé ;
- est dangereux ;
- n’a aucun équivalent utile ;
- n’a pas de trafic ni de liens significatifs.

Réponse :

```text
404 Not Found
```

ou :

```text
410 Gone
```

Le code `410` peut être utilisé lorsqu’une suppression définitive est volontaire et connue.

---

## 6.6 Archiver

Les anciennes documentations de versions encore utiles peuvent être conservées sous :

```text
/fr-FR/documentation/versions/1.0/
```

Elles doivent être clairement identifiées comme archives.

---

# 7. Exemples de correspondance initiale

Ces exemples servent de modèle. Ils doivent être confirmés à partir de l’inventaire réel.

| Ancienne URL | Nouvelle URL | Action |
|---|---|---|
| `https://wiki.flat-cms.fr/fr-FR/` | `https://flat-cms.fr/fr-FR/documentation/` | 301 |
| `https://wiki.flat-cms.fr/en-US/` | `https://flat-cms.fr/en-US/documentation/` | 301 |
| `https://wiki.flat-cms.fr/de-DE/` | `https://flat-cms.fr/de-DE/dokumentation/` | 301 |
| `https://wiki.flat-cms.fr/es-ES/` | `https://flat-cms.fr/es-ES/documentacion/` | 301 |
| `https://wiki.flat-cms.fr/it-IT/` | `https://flat-cms.fr/it-IT/documentazione/` | 301 |
| `https://wiki.flat-cms.fr/pt-PT/` | `https://flat-cms.fr/pt-PT/documentacao/` | 301 |
| ancienne page d’installation | `/fr-FR/documentation/installation/` | 301 |
| ancienne page HMVC | `/fr-FR/architecture/hmvc/` | 301 |
| ancienne page PSR-4 | `/fr-FR/architecture/psr-4/` | 301 |
| ancien article agent-ready | `/fr-FR/blog/pourquoi-flatcms-a-ete-concu-agent-ready/` | 301 |
| ancien article GIO | `/fr-FR/blog/gio-generative-indexing-optimization/` | 301 |

Les slugs localisés définitifs doivent être validés avant génération des règles.

---

# 8. Anciennes URLs dynamiques

Certaines anciennes pages peuvent utiliser une structure semblable à :

```text
/index.php?doc=getting-started%2FINSTALLATION&p=wiki
```

Ces URLs doivent être associées individuellement à une URL propre.

Exemple :

```text
https://flat-cms.fr/index.php?doc=getting-started%2FINSTALLATION&p=wiki
→
https://flat-cms.fr/fr-FR/documentation/installation/
```

## Attention aux paramètres

Une règle générique ne doit pas supprimer aveuglément tous les paramètres si certains déterminent réellement le contenu.

Méthode :

1. extraire toutes les variantes ;
2. décoder les paramètres ;
3. associer le contenu ;
4. générer une règle exacte ou contrôlée ;
5. tester les caractères encodés ;
6. vérifier l’absence de collision.

---

# 9. Migration des langues

## 9.1 Conservation de la locale

Une ancienne URL française doit pointer vers une nouvelle URL française.

Correct :

```text
/fr-FR/ancienne-page
→
/fr-FR/nouvelle-page
```

Incorrect :

```text
/fr-FR/ancienne-page
→
/en-US/new-page
```

sauf absence explicite et temporaire de traduction.

## 9.2 Traduction non disponible

Si une ancienne page traduite n’a pas encore de nouvelle traduction :

- éviter de rediriger silencieusement vers une autre langue ;
- conserver temporairement l’ancienne page si elle reste correcte ;
- ou proposer une page locale expliquant que la traduction est en cours ;
- ne pas créer une redirection définitive incorrecte.

## 9.3 Hreflang

Toutes les annotations doivent être mises à jour vers les nouvelles URLs.

Elles doivent être :

- absolues ;
- réciproques ;
- cohérentes avec les canonicals ;
- limitées aux pages réellement disponibles.

---

# 10. Canonicals

Chaque nouvelle page doit avoir une canonique auto-référencée.

Exemple :

```html
<link rel="canonical"
      href="https://flat-cms.fr/fr-FR/documentation/installation/">
```

À éviter :

```html
<link rel="canonical"
      href="https://wiki.flat-cms.fr/fr-FR/documentation/installation">
```

après migration.

## Redirection et canonique

La redirection est le signal principal pour une URL déplacée.

La canonique ne remplace pas une redirection lorsqu’une ancienne page ne doit plus être accessible.

---

# 11. Liens internes

Après migration, tous les liens internes doivent pointer directement vers les nouvelles URLs.

À éviter :

```text
nouvelle page
→ ancien wiki
→ redirection
→ nouvelle page
```

À obtenir :

```text
nouvelle page
→ nouvelle page
```

Contrôles :

- navigation ;
- footer ;
- contenus ;
- sommaires ;
- fils d’Ariane ;
- images ;
- fichiers ;
- données structurées ;
- Open Graph ;
- sitemaps ;
- flux éventuels.

---

# 12. Sitemaps

## 12.1 Ancien sitemap

L’ancien sitemap peut rester accessible temporairement pendant la migration, mais il ne doit pas continuer indéfiniment à promouvoir des URLs remplacées.

## 12.2 Nouveau sitemap

Le nouveau sitemap doit contenir uniquement :

- des URLs canoniques ;
- des pages indexables ;
- des réponses `200 OK` ;
- les URLs finales ;
- les pages de la bonne locale.

Ne pas inclure :

- redirections ;
- erreurs ;
- pages `noindex` ;
- paramètres inutiles ;
- contenus fictifs de la démo.

## 12.3 Sitemaps par locale

```text
/sitemaps/sitemap-fr-FR.xml
/sitemaps/sitemap-en-US.xml
/sitemaps/sitemap-de-DE.xml
/sitemaps/sitemap-es-ES.xml
/sitemaps/sitemap-it-IT.xml
/sitemaps/sitemap-pt-PT.xml
```

---

# 13. Médias

## 13.1 Inventaire

Inclure :

- logo ;
- captures ;
- couvertures d’articles ;
- diagrammes ;
- vidéos ;
- fichiers WebP, PNG, JPEG, SVG ;
- fichiers téléchargeables.

## 13.2 Correspondance

| Ancien média | Nouveau média | Utilisé par | Redirection |
|---|---|---|---|
| À compléter | À compléter | Page/article | 301 |

## 13.3 Bonnes pratiques

- conserver le fichier s’il est toujours valide ;
- éviter de renommer sans raison ;
- rediriger les médias recevant des liens ;
- mettre à jour les URLs dans le contenu ;
- préserver les textes alternatifs ;
- remplacer les anciens médias inexacts.

---

# 14. Implémentation Apache

Exemple conceptuel à adapter après validation des URLs :

```apache
RewriteEngine On

Redirect 301 /ancienne-page https://flat-cms.fr/fr-FR/nouvelle-page/
```

Pour les anciennes URLs complexes à paramètres, une logique `RewriteCond`/`RewriteRule` ou une table de redirection gérée par FlatCMS pourra être préférable.

## Règles

- placer les redirections spécifiques avant les règles génériques ;
- échapper correctement les caractères ;
- tester les paramètres ;
- éviter les motifs trop larges ;
- ne pas interférer avec le routeur FlatCMS.

---

# 15. Implémentation Nginx

Exemple conceptuel :

```nginx
location = /ancienne-page {
    return 301 https://flat-cms.fr/fr-FR/nouvelle-page/;
}
```

Ou :

```nginx
rewrite ^/ancienne-page$ https://flat-cms.fr/fr-FR/nouvelle-page/ permanent;
```

## Règles

- préférer `location =` pour les cas exacts ;
- tester l’ordre des blocs ;
- éviter les regex inutiles ;
- pointer directement vers la destination finale ;
- valider avec `nginx -t` ;
- recharger seulement après validation.

---

# 16. Implémentation dans FlatCMS

Une couche de redirections intégrée peut compléter le serveur.

## Cas d’usage

- anciennes URLs dynamiques ;
- redirections administrables ;
- règles par locale ;
- suivi des 404 ;
- redirections créées après publication ;
- migration de slugs.

## Stockage possible

```text
data/core/redirects/
```

ou :

```text
data/core/redirects.json
```

L’emplacement réel devra respecter l’architecture validée de FlatCMS.

## Structure conceptuelle

```json
{
  "source": "/ancienne-url",
  "target": "/fr-FR/nouvelle-url/",
  "status": 301,
  "enabled": true,
  "created_at": "2026-06-08T00:00:00+02:00",
  "reason": "Migration wiki"
}
```

## Contrôles obligatoires

- source unique ;
- destination valide ;
- pas de boucle ;
- pas de chaîne ;
- statut autorisé ;
- journalisation ;
- possibilité de désactivation ;
- contrôle des permissions ;
- protection contre les redirections externes malveillantes.

---

# 17. Codes HTTP

| Code | Usage |
|---:|---|
| `200` | Page finale valide |
| `301` | Déplacement permanent |
| `308` | Déplacement permanent avec méthode préservée |
| `302` | Déplacement temporaire |
| `307` | Temporaire avec méthode préservée |
| `404` | Ressource absente |
| `410` | Ressource supprimée définitivement |

## Choix recommandé

Pour la migration documentaire :

```text
301
```

Le code `308` peut être utilisé lorsque sa sémantique et la compatibilité attendue sont adaptées.

---

# 18. Pages supprimées

## Utiliser `404`

Lorsque :

- l’URL est inconnue ;
- la ressource n’a jamais existé ;
- la suppression n’est pas explicitement définitive.

## Utiliser `410`

Lorsque :

- le contenu a volontairement été supprimé ;
- aucune alternative n’existe ;
- le retrait est définitif.

## Page 404 utile

Elle doit proposer :

- recherche ;
- documentation ;
- accueil ;
- téléchargement ;
- pages populaires ;
- signalement d’un lien cassé.

Elle doit réellement retourner `404`, et non `200`.

---

# 19. Prévention des erreurs

## 19.1 Boucles

Exemple :

```text
A → B → A
```

Détection automatique obligatoire.

## 19.2 Chaînes

Exemple :

```text
A → B → C → D
```

Réécrire :

```text
A → D
B → D
C → D
```

## 19.3 Destination en erreur

Aucune redirection ne doit pointer vers :

- `404` ;
- `410` ;
- `500` ;
- une autre redirection inutile ;
- une page bloquée ;
- une page `noindex` sans justification.

## 19.4 Mélange HTTP/HTTPS

Toutes les destinations finales doivent utiliser :

```text
https://
```

## 19.5 Slash final

Choisir une convention unique :

```text
/fr-FR/documentation/installation/
```

Toutes les variantes doivent converger vers cette forme.

---

# 20. Normalisation des URLs

## 20.1 HTTPS

```text
http://flat-cms.fr/
→
https://flat-cms.fr/
```

## 20.2 Hôte canonique

Choisir :

```text
https://flat-cms.fr/
```

et rediriger :

```text
https://www.flat-cms.fr/
→
https://flat-cms.fr/
```

si le sous-domaine `www` est configuré.

## 20.3 Casse

Préférer les slugs en minuscules.

```text
/Documentation/Installation
→
/fr-FR/documentation/installation/
```

## 20.4 Index

```text
/index.php
→
/
```

uniquement lorsque la page correspond réellement à la racine.

## 20.5 Paramètres inutiles

Supprimer ou rediriger les paramètres de suivi uniquement s’ils n’affectent pas le contenu.

---

# 21. Inventaire des anciennes URLs

Sources à utiliser :

1. sitemap actuel ;
2. export du CMS ;
3. Search Console ;
4. Bing Webmaster Tools ;
5. journaux serveur ;
6. analytics ;
7. liens internes ;
8. backlinks ;
9. recherche `site:` ;
10. fichiers et médias ;
11. historique du dépôt ;
12. anciennes publications sociales.

## Priorisation

### P0

- trafic important ;
- backlinks ;
- pages indexées ;
- pages de conversion ;
- documentation critique.

### P1

- pages utiles sans trafic important ;
- tutoriels ;
- articles ;
- catégories pertinentes.

### P2

- anciennes pages secondaires ;
- tags ;
- archives ;
- médias peu utilisés.

---

# 22. Tableau opérationnel initial

À compléter avant développement des règles.

| ID | Source | Destination | Type | Locale | Décision | Priorité | Responsable | Statut |
|---:|---|---|---|---|---|---|---|---|
| 001 | Accueil `flat-cms.fr` | `/fr-FR/` ou racine internationale | Institutionnel | x-default/fr-FR | À décider | P0 | Alain | À cadrer |
| 002 | Accueil `wiki.flat-cms.fr` | `/fr-FR/documentation/` | Documentation | fr-FR | 301 | P0 | Alain | À confirmer |
| 003 | Ancienne installation | `/fr-FR/documentation/installation/` | Guide | fr-FR | 301 | P0 | Alain | À inventorier |
| 004 | Ancienne architecture HMVC | `/fr-FR/architecture/hmvc/` | Explication | fr-FR | 301 | P0 | Alain | À inventorier |
| 005 | Ancienne documentation PSR-4 | `/fr-FR/architecture/psr-4/` | Explication | fr-FR | 301 | P1 | Alain | À inventorier |
| 006 | Blog fr-FR | `/fr-FR/blog/` | Blog | fr-FR | 301 | P0 | Alain | À inventorier |
| 007 | Blog en-US | `/en-US/blog/` | Blog | en-US | 301 | P1 | Alain | À inventorier |
| 008 | Blog de-DE | `/de-DE/blog/` | Blog | de-DE | 301 | P1 | Alain | À inventorier |
| 009 | Blog es-ES | `/es-ES/blog/` | Blog | es-ES | 301 | P1 | Alain | À inventorier |
| 010 | Blog it-IT | `/it-IT/blog/` | Blog | it-IT | 301 | P1 | Alain | À inventorier |
| 011 | Blog pt-PT | `/pt-PT/blog/` | Blog | pt-PT | 301 | P1 | Alain | À inventorier |

---

# 23. Préproduction

## Environnement

Les nouvelles pages doivent être testées sur un environnement non public ou protégé.

## Attention

Si la préproduction utilise :

```text
noindex
```

ou un blocage global, ces protections doivent être retirées lors du lancement.

## Tests avant migration

- toutes les nouvelles URLs répondent `200` ;
- canonicals correctes ;
- `hreflang` corrects ;
- données structurées valides ;
- liens internes à jour ;
- images chargées ;
- formulaires opérationnels ;
- aucun blocage robots involontaire ;
- sitemaps générés ;
- règles de redirection testées hors production.

---

# 24. Procédure de lancement

## Étape 1 — Gel de contenu

- définir une date de gel ;
- éviter les modifications concurrentes ;
- exporter les dernières URLs.

## Étape 2 — Sauvegarde

- site actuel ;
- wiki ;
- données ;
- médias ;
- configurations ;
- règles serveur ;
- sitemaps.

## Étape 3 — Publication

- publier les nouvelles pages ;
- activer les redirections ;
- mettre à jour les canonicals ;
- mettre à jour les `hreflang` ;
- publier les sitemaps ;
- vérifier `robots.txt`.

## Étape 4 — Contrôle immédiat

Tester :

- 20 URLs P0 ;
- 20 URLs P1 ;
- toutes les locales ;
- médias importants ;
- accueil ;
- documentation ;
- blog ;
- téléchargement ;
- erreurs 404.

## Étape 5 — Search Console

- vérifier les propriétés ;
- soumettre les nouveaux sitemaps ;
- inspecter les pages principales ;
- utiliser le changement d’adresse seulement si le cas correspond réellement à un changement de domaine ou d’hôte pris en charge ;
- surveiller l’indexation.

---

# 25. Tests automatisés

## Format du fichier de test

```csv
source,expected_status,expected_location,final_status
https://wiki.flat-cms.fr/ancienne,301,https://flat-cms.fr/fr-FR/nouvelle/,200
```

## Contrôles

Pour chaque URL :

1. statut initial ;
2. en-tête `Location` ;
3. nombre de sauts ;
4. statut final ;
5. canonique finale ;
6. indexabilité ;
7. locale ;
8. contenu attendu.

## Critères

```text
1 redirection idéale
destination HTTPS
statut final 200
aucune boucle
canonique finale correcte
```

---

# 26. Surveillance après lancement

## Quotidien — première semaine

- erreurs serveur ;
- 404 ;
- boucles ;
- pages indisponibles ;
- formulaires ;
- logs de crawl ;
- sitemaps.

## Hebdomadaire — premier mois

- indexation ;
- trafic ;
- positions ;
- anciennes URLs encore visitées ;
- erreurs Search Console ;
- couverture par locale.

## Mensuel — première année

- anciennes URLs toujours actives ;
- backlinks à mettre à jour ;
- chaînes apparues ;
- pages orphelines ;
- cannibalisation ;
- redirections inutilisées ;
- erreurs 404.

---

# 27. Mise à jour des backlinks

Priorité aux liens externes importants :

- GitHub ;
- LinkedIn ;
- YouTube ;
- profils officiels ;
- articles partenaires ;
- annuaires ;
- pages de téléchargement ;
- documentation tierce.

Même si une redirection existe, il est préférable de faire mettre à jour les liens vers l’URL finale.

---

# 28. Journal des changements

| Date | Source | Destination | Action | Auteur | Motif |
|---|---|---|---|---|---|
| À compléter | | | | | |

Ce journal doit être conservé avec le registre.

---

# 29. Critères de validation

La migration est prête lorsque :

- [ ] toutes les URLs importantes sont inventoriées ;
- [ ] chaque URL a une décision ;
- [ ] les destinations existent ;
- [ ] les redirections sont directes ;
- [ ] aucune boucle n’est détectée ;
- [ ] les chaînes sont éliminées ;
- [ ] les liens internes sont mis à jour ;
- [ ] les canonicals utilisent les nouvelles URLs ;
- [ ] les `hreflang` utilisent les nouvelles URLs ;
- [ ] les sitemaps contiennent les URLs finales ;
- [ ] les médias importants sont couverts ;
- [ ] les erreurs volontaires retournent `404` ou `410` ;
- [ ] la page 404 retourne bien `404` ;
- [ ] les six locales sont contrôlées ;
- [ ] les règles Apache et Nginx sont testées ;
- [ ] une sauvegarde et un retour arrière sont prêts ;
- [ ] la surveillance post-lancement est organisée.

---

# 30. Décisions à prendre

## 30.1 Racine du domaine

Choisir définitivement entre :

### Option A

```text
https://flat-cms.fr/
```

sert directement le français.

### Option B

```text
https://flat-cms.fr/
```

sert une page internationale `x-default`, puis :

```text
https://flat-cms.fr/fr-FR/
```

sert la version française.

La décision doit être prise avant de générer les règles finales.

## 30.2 Slugs localisés

Choisir si les répertoires sont traduits :

```text
/de-DE/dokumentation/
/es-ES/documentacion/
```

ou conservés de manière uniforme :

```text
/de-DE/documentation/
/es-ES/documentation/
```

Cette décision doit rester stable sur le long terme.

## 30.3 Gestion dans FlatCMS

Décider si les redirections seront :

- uniquement serveur ;
- uniquement module FlatCMS ;
- hybrides.

Recommandation :

```text
Serveur pour les règles globales
FlatCMS pour les redirections éditoriales
```

---

# 31. Références officielles

- Google Search Central — Déplacer un site avec changement d’URLs  
  https://developers.google.com/search/docs/crawling-indexing/site-move-with-url-changes

- Google Search Central — Redirections et Google Search  
  https://developers.google.com/search/docs/crawling-indexing/301-redirects

- Google Search Central — Définir une URL canonique  
  https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls

- Google Search Central — Créer et soumettre un sitemap  
  https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap

---

# 32. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Création de la stratégie de redirections et migration | ChatGPT / Alain BROYE |

---

# 33. Prochaine action

Créer :

```text
STRUCTURED_DATA.md
```

Ce document définira :

- les entités Schema.org ;
- les types par modèle de page ;
- les identifiants `@id` ;
- l’entité Organization FlatCMS ;
- le logiciel FlatCMS ;
- les articles ;
- la documentation ;
- les fils d’Ariane ;
- les vidéos ;
- les offres premium ;
- la validation JSON-LD ;
- les règles de cohérence SEO/GEO/GIO.
