# LAUNCH_CHECKLIST — Checklist de lancement du futur site officiel FlatCMS

> **Document opérationnel de mise en production**
>
> Projet : FlatCMS  
> Domaine cible : `https://flat-cms.fr`  
> Sous-domaine de démonstration : `https://demo.flat-cms.fr`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Fuseau de référence : `Europe/Paris`  
> Documents parents : `SEO.md`, `SITE_ARCHITECTURE.md`, `CONTENT_MATRIX.md`, `DOCUMENTATION_MAP.md`, `KEYWORDS.md`, `REDIRECTS.md`, `STRUCTURED_DATA.md`, `CRAWL_POLICY.md`, `CONTENT_STYLE_GUIDE.md`, `MULTILINGUAL.md`  
> Statut : checklist initiale à compléter avec les responsables, dates et preuves avant mise en production

---

## 1. Objet du document

Ce document regroupe les contrôles indispensables avant, pendant et après la mise en ligne du futur site officiel FlatCMS.

Il sert à :

- décider si le site est prêt ;
- attribuer les responsabilités ;
- centraliser les preuves de validation ;
- éviter les oublis ;
- préparer un retour arrière ;
- contrôler la migration du wiki ;
- protéger le référencement acquis ;
- vérifier les six locales ;
- valider la sécurité et les performances ;
- organiser la surveillance post-lancement.

Cette checklist ne doit pas être remplie uniquement de mémoire.

Chaque validation importante doit être accompagnée d’une preuve :

```text
capture
URL
rapport
commande
journal
résultat de test
nom du responsable
date
```

---

# 2. Statuts utilisés

```text
À faire
En cours
Bloqué
À corriger
Validé
Non applicable
Reporté
```

## Modèle de suivi

| ID | Contrôle | Responsable | Statut | Date | Preuve | Commentaire |
|---:|---|---|---|---|---|---|
| 001 | Exemple | À définir | À faire | | | |

---

# 3. Niveaux de criticité

## BLOQUANT

Le site ne doit pas être lancé si ce point échoue.

## MAJEUR

Le lancement est fortement déconseillé tant que le point n’est pas corrigé.

## MINEUR

Le point peut être traité après le lancement s’il est documenté et planifié.

## INFORMATION

Contrôle utile sans incidence directe sur la décision de lancement.

---

# 4. Règle Go / No-Go

Le lancement est autorisé uniquement lorsque :

- tous les contrôles **BLOQUANTS** sont validés ;
- aucun incident de sécurité critique n’est ouvert ;
- aucun contenu juridique obligatoire n’est absent ;
- les sauvegardes et le retour arrière sont testés ;
- les nouvelles URLs principales répondent correctement ;
- les redirections P0 sont testées ;
- la préproduction n’est plus en `noindex` par erreur ;
- les fichiers sensibles ne sont pas exposés ;
- les formulaires et e-mails fonctionnent ;
- au moins la locale `fr-FR` est complète ;
- la démo ne pollue pas l’index ;
- les responsables du suivi post-lancement sont disponibles.

---

# 5. Rôles du lancement

| Rôle | Responsabilités | Responsable |
|---|---|---|
| Directeur de lancement | Décision Go / No-Go | Alain BROYE |
| Responsable technique | Déploiement et rollback | À définir |
| Responsable contenu | Validation des pages | Alain BROYE |
| Responsable SEO | Indexation, redirections, Search Console | Alain BROYE |
| Responsable sécurité | Audit et incidents | À définir |
| Responsable infrastructure | DNS, TLS, serveur, sauvegardes | À définir |
| Responsable multilingue | Locales et `hreflang` | À définir |
| Responsable support | Contact et incidents utilisateurs | À définir |

Une même personne peut cumuler plusieurs rôles, mais les responsabilités doivent rester explicites.

---

# 6. Jalons

## T-30 jours

- architecture figée ;
- contenus P0 en rédaction ;
- inventaire des anciennes URLs ;
- environnement de préproduction disponible ;
- plan de sauvegarde défini.

## T-14 jours

- pages P0 intégrées ;
- redirections préparées ;
- sécurité testée ;
- formulaires testés ;
- sitemaps générés ;
- traduction française validée.

## T-7 jours

- gel fonctionnel ;
- crawl complet ;
- tests mobiles ;
- tests de charge ;
- répétition du déploiement ;
- répétition du rollback.

## T-2 jours

- gel éditorial ;
- sauvegarde finale préparée ;
- table des redirections figée ;
- checklist Go / No-Go presque complète ;
- équipes informées.

## Jour J

- sauvegarde ;
- déploiement ;
- activation des redirections ;
- contrôles immédiats ;
- soumission des sitemaps ;
- surveillance renforcée.

## J+1 à J+7

- suivi quotidien ;
- correction des erreurs ;
- indexation ;
- logs ;
- conversions ;
- performances.

## J+30

- audit complet ;
- bilan SEO ;
- bilan technique ;
- priorités de correction.

---

# 7. Gouvernance et périmètre

## BLOQUANT

- [ ] Le périmètre de la version de lancement est écrit et validé.
- [ ] La version FlatCMS utilisée est identifiée.
- [ ] Les fonctionnalités Core sont distinguées des composants premium.
- [ ] Les fonctions expérimentales ou prévues ne sont pas présentées comme disponibles.
- [ ] Les pages P0 sont listées.
- [ ] Les locales incluses au lancement sont listées.
- [ ] Les fonctionnalités explicitement reportées sont documentées.
- [ ] La date et la fenêtre de lancement sont définies.
- [ ] Le responsable Go / No-Go est nommé.
- [ ] Le plan de retour arrière est approuvé.

## MAJEUR

- [ ] Le journal des décisions est à jour.
- [ ] Les documents directeurs sont synchronisés.
- [ ] Aucun conflit connu ne subsiste entre la documentation et le code réel.
- [ ] Les coordonnées des responsables sont disponibles hors du site.

---

# 8. Code source et version

## BLOQUANT

- [ ] Le commit ou tag de production est identifié.
- [ ] Le fichier `VERSION` correspond à la version annoncée.
- [ ] Le package de production provient d’une source contrôlée.
- [ ] Aucun fichier de développement inutile n’est inclus.
- [ ] Aucun secret n’est présent dans le dépôt ou l’archive.
- [ ] La syntaxe PHP est validée.
- [ ] Les fichiers JSON sont valides.
- [ ] Les dépendances tierces sont identifiées.
- [ ] Les licences tierces sont respectées.
- [ ] Le package de production a été testé dans un environnement vierge.

## MAJEUR

- [ ] Le checksum de l’archive est généré.
- [ ] Les notes de version sont prêtes.
- [ ] Le changelog est publié.
- [ ] La procédure de reproduction du package est documentée.
- [ ] Les fichiers inutiles de macOS ou Windows sont supprimés.
- [ ] Les attributs étendus indésirables sont supprimés des archives.

---

# 9. Infrastructure et DNS

## BLOQUANT

- [ ] Le domaine `flat-cms.fr` pointe vers le bon serveur.
- [ ] `www.flat-cms.fr` est configuré ou redirigé.
- [ ] `demo.flat-cms.fr` pointe vers son environnement dédié.
- [ ] Les enregistrements DNS ont une valeur TTL adaptée au lancement.
- [ ] Le certificat TLS est valide.
- [ ] La chaîne du certificat est complète.
- [ ] Le renouvellement automatique du certificat est actif.
- [ ] HTTP redirige vers HTTPS.
- [ ] L’hôte canonique est unique.
- [ ] Le serveur répond avec la bonne configuration pour chaque domaine.
- [ ] Aucun ancien serveur ne sert une copie concurrente sans redirection.

## MAJEUR

- [ ] IPv4 fonctionne.
- [ ] IPv6 fonctionne ou n’est pas publié.
- [ ] Les enregistrements CAA sont vérifiés si utilisés.
- [ ] Le DNSSEC est vérifié si activé.
- [ ] Une surveillance d’expiration du certificat est configurée.
- [ ] Les erreurs DNS sont journalisées.

---

# 10. Serveur web et PHP

## BLOQUANT

- [ ] Le document root pointe vers `public/`.
- [ ] `app/`, `config/`, `data/` et `storage/` ne sont pas accessibles directement.
- [ ] La version PHP est compatible.
- [ ] Les extensions PHP requises sont actives.
- [ ] Les limites PHP sont adaptées.
- [ ] Les erreurs PHP ne sont pas affichées publiquement.
- [ ] Les erreurs sont journalisées.
- [ ] Les permissions d’écriture sont limitées aux dossiers nécessaires.
- [ ] Le propriétaire des fichiers est cohérent avec le serveur.
- [ ] Les URLs propres fonctionnent.
- [ ] La configuration Apache ou Nginx a été validée.
- [ ] La page d’accueil, l’administration et les routes de contenu fonctionnent.

## MAJEUR

- [ ] PHP-FPM est correctement dimensionné.
- [ ] OPcache est actif et configuré.
- [ ] La compression Brotli ou gzip est active.
- [ ] Le cache navigateur est configuré.
- [ ] Les types MIME sont corrects.
- [ ] Les fichiers statiques disposent de cache validators.
- [ ] Les réponses d’erreur ne révèlent pas de chemins internes.
- [ ] Le fuseau horaire PHP est cohérent.

---

# 11. Sécurité

## BLOQUANT

- [ ] Les secrets sont stockés hors du code public.
- [ ] `.env`, `.env.local` et fichiers similaires sont inaccessibles.
- [ ] Les sauvegardes sont inaccessibles depuis le Web.
- [ ] Les journaux sont inaccessibles depuis le Web.
- [ ] L’administration exige une authentification.
- [ ] Les permissions sont vérifiées côté serveur.
- [ ] Les actions sensibles vérifient les rôles.
- [ ] La protection CSRF est active.
- [ ] Les sessions utilisent des cookies sécurisés.
- [ ] Les cookies d’authentification sont `HttpOnly`.
- [ ] Les cookies sensibles sont `Secure`.
- [ ] La politique `SameSite` est définie.
- [ ] Les sorties utilisateur sont échappées.
- [ ] Les téléversements de fichiers sont contrôlés.
- [ ] Les extensions et types MIME des médias sont vérifiés.
- [ ] Les chemins de fichiers sont normalisés.
- [ ] Les redirections externes non contrôlées sont interdites.
- [ ] Les formulaires possèdent une protection anti-abus.
- [ ] Les identifiants de démonstration ne donnent aucun accès à la production.
- [ ] Aucun compte par défaut dangereux ne subsiste.
- [ ] Les vulnérabilités critiques connues sont corrigées.

## MAJEUR

- [ ] Une politique de sécurité des contenus est définie.
- [ ] `X-Content-Type-Options: nosniff` est envoyé.
- [ ] Une politique de framing est définie.
- [ ] `Referrer-Policy` est configurée.
- [ ] `Permissions-Policy` est configurée selon les besoins.
- [ ] HSTS est envisagé après validation complète de HTTPS.
- [ ] Les dépendances sont analysées.
- [ ] Un scan OWASP ZAP ou équivalent est réalisé.
- [ ] Les principales routes sont testées manuellement.
- [ ] Un canal de signalement de vulnérabilité est disponible.
- [ ] Une procédure de réponse à incident existe.

## INFORMATION

- [ ] Un fichier `security.txt` est envisagé sous `/.well-known/security.txt`.

---

# 12. Sauvegardes et retour arrière

## BLOQUANT

- [ ] Une sauvegarde complète du site actuel est créée.
- [ ] Le wiki actuel est sauvegardé.
- [ ] Les médias sont sauvegardés.
- [ ] Les données JSON sont sauvegardées.
- [ ] Les configurations serveur sont sauvegardées.
- [ ] Les règles DNS sont exportées.
- [ ] Les certificats et procédures TLS sont documentés.
- [ ] Les règles de redirection sont sauvegardées.
- [ ] La sauvegarde est stockée sur un emplacement distinct.
- [ ] L’intégrité de la sauvegarde est vérifiée.
- [ ] Une restauration a été testée.
- [ ] Le rollback a été répété.
- [ ] Les critères déclenchant le rollback sont définis.
- [ ] Le temps et les étapes du rollback sont connus.
- [ ] La personne autorisée à déclencher le rollback est nommée.

## MAJEUR

- [ ] Une sauvegarde juste avant déploiement est planifiée.
- [ ] La durée de conservation est définie.
- [ ] Les sauvegardes sont chiffrées si nécessaire.
- [ ] Les accès aux sauvegardes sont limités.
- [ ] Une copie hors site est disponible.

---

# 13. Contenus P0

## BLOQUANT

- [ ] Accueil.
- [ ] Pourquoi FlatCMS.
- [ ] Fonctionnalités.
- [ ] Architecture.
- [ ] Documentation.
- [ ] Installation.
- [ ] Téléchargement.
- [ ] Licences.
- [ ] Tarifs si les offres sont commercialisées.
- [ ] Agent-ready.
- [ ] À propos.
- [ ] Contact.
- [ ] Mentions légales.
- [ ] Politique de confidentialité.
- [ ] Politique de cookies si nécessaire.
- [ ] Page 404.
- [ ] Page 500 ou gestion équivalente.
- [ ] Tous les contenus annoncés dans le menu existent.
- [ ] Aucun texte de remplissage n’est présent.
- [ ] Aucun contenu obsolète ne décrit une ancienne architecture.

## MAJEUR

- [ ] Chaque page a un objectif clair.
- [ ] Chaque affirmation importante possède une preuve.
- [ ] Les statuts Core, Premium, optionnel ou prévu sont explicites.
- [ ] Les auteurs et dates sont affichés lorsque pertinents.
- [ ] Les comparatifs indiquent leur méthodologie.
- [ ] Les tarifs indiquent HT/TTC, périmètre et durée.
- [ ] Les licences correspondent aux documents officiels.

---

# 14. Qualité éditoriale

## BLOQUANT

- [ ] Chaque page possède un H1 visible.
- [ ] La hiérarchie H2/H3 est cohérente.
- [ ] Les titres sont uniques.
- [ ] Les textes ne contiennent pas de promesses non démontrées.
- [ ] Les termes officiels sont respectés.
- [ ] Le nom FlatCMS est écrit correctement.
- [ ] Les noms PagesBuilder, MenuBuilder et FooterBuilder sont cohérents.
- [ ] Les fautes critiques sont corrigées.
- [ ] Les liens externes importants sont valides.
- [ ] Les commandes et exemples de code ont été testés.

## MAJEUR

- [ ] Les paragraphes sont lisibles.
- [ ] Les tableaux sont adaptés au mobile.
- [ ] Les listes sont structurées.
- [ ] Les acronymes sont définis au premier usage.
- [ ] Les dates et versions sont explicites.
- [ ] Les contenus assistés par IA ont été relus.
- [ ] Les sources primaires sont privilégiées.

---

# 15. Navigation et architecture

## BLOQUANT

- [ ] Le menu principal fonctionne.
- [ ] Le menu mobile fonctionne.
- [ ] Les méga-menus sont utilisables au clavier.
- [ ] Le footer est complet.
- [ ] Le sélecteur de langue fonctionne.
- [ ] Le fil d’Ariane est correct.
- [ ] Les liens précédent/suivant fonctionnent dans la documentation.
- [ ] Aucun lien interne P0 n’est cassé.
- [ ] Les pages principales sont accessibles en trois clics maximum.
- [ ] La navigation ne dépend pas uniquement de JavaScript.

## MAJEUR

- [ ] Les ancres de liens sont descriptives.
- [ ] Les pages orphelines sont identifiées.
- [ ] La recherche interne fonctionne.
- [ ] Les résultats de recherche sont en `noindex`.
- [ ] Les catégories vides ne sont pas publiées.

---

# 16. SEO technique

## BLOQUANT

- [ ] Chaque page indexable répond `200 OK`.
- [ ] Les pages supprimées répondent `404` ou `410`.
- [ ] Les pages de redirection répondent `301` ou `308`.
- [ ] Chaque page indexable possède une canonique correcte.
- [ ] Les canonicals utilisent HTTPS.
- [ ] Les canonicals pointent vers des URLs finales.
- [ ] Aucun `noindex` de préproduction ne subsiste.
- [ ] `robots.txt` est accessible.
- [ ] `robots.txt` ne bloque pas les pages publiques.
- [ ] Le sitemap index est accessible.
- [ ] Les sitemaps contiennent uniquement des URLs finales en `200`.
- [ ] Les pages `noindex` ne sont pas dans les sitemaps.
- [ ] Les titres SEO sont uniques.
- [ ] Les meta descriptions P0 sont présentes.
- [ ] Le contenu essentiel est disponible dans le HTML.
- [ ] Les CSS et JavaScript nécessaires au rendu sont explorables.
- [ ] La page 404 retourne réellement le code `404`.
- [ ] Les variantes HTTP et `www` convergent vers l’hôte canonique.

## MAJEUR

- [ ] Les images importantes sont explorables.
- [ ] Les métadonnées Open Graph sont présentes.
- [ ] Les Twitter Cards sont présentes si retenues.
- [ ] Les favicons sont configurés.
- [ ] Le nom de site est cohérent.
- [ ] Les URLs sont descriptives.
- [ ] Les paramètres inutiles ne créent pas de doublons.
- [ ] Les pages paginées sont cohérentes.
- [ ] Les archives inutiles sont en `noindex`.

---

# 17. Redirections et migration

## BLOQUANT

- [ ] L’inventaire des anciennes URLs est complet pour les pages P0.
- [ ] Chaque ancienne URL importante possède une destination pertinente.
- [ ] Les redirections sont permanentes.
- [ ] Les anciennes URLs pointent directement vers la destination finale.
- [ ] Aucune boucle n’existe.
- [ ] Les chaînes sont réduites.
- [ ] Les redirections ne pointent pas vers des erreurs.
- [ ] Les anciennes locales restent dans la même langue.
- [ ] Les anciennes URLs dynamiques sont couvertes.
- [ ] Les médias importants sont couverts.
- [ ] Les liens internes ont été mis à jour.
- [ ] Les canonicals utilisent les nouvelles URLs.
- [ ] Les `hreflang` utilisent les nouvelles URLs.
- [ ] Le sitemap contient les nouvelles URLs.
- [ ] Les règles Apache et Nginx ont été testées.

## MAJEUR

- [ ] Les backlinks importants ont été recensés.
- [ ] Les profils officiels seront mis à jour.
- [ ] GitHub sera mis à jour.
- [ ] LinkedIn et YouTube seront mis à jour.
- [ ] Les redirections seront conservées au moins un an.
- [ ] Un fichier CSV de tests existe.
- [ ] Les anciennes URLs à fort trafic sont testées individuellement.

---

# 18. Multilingue

## BLOQUANT POUR CHAQUE LOCALE PUBLIÉE

- [ ] La page d’accueil locale est complète.
- [ ] Le menu est traduit.
- [ ] Le footer est traduit.
- [ ] Les pages P0 annoncées sont traduites.
- [ ] L’attribut `<html lang>` est correct.
- [ ] Les titles sont traduits.
- [ ] Les meta descriptions sont traduites.
- [ ] Les H1 sont traduits.
- [ ] Les CTA sont traduits.
- [ ] Les textes alternatifs sont traduits.
- [ ] Les liens internes restent dans la locale.
- [ ] Les canonicals sont auto-référencées.
- [ ] Les `hreflang` sont réciproques.
- [ ] La page s’auto-référence dans `hreflang`.
- [ ] Les URLs `hreflang` répondent `200`.
- [ ] Aucune traduction inexistante n’est déclarée.
- [ ] Les données structurées utilisent la bonne locale.
- [ ] Le sitemap local est valide.
- [ ] Le sélecteur pointe vers la page équivalente.
- [ ] Aucun contenu français résiduel n’est visible par erreur.

## MAJEUR

- [ ] Les dates sont localisées.
- [ ] Les nombres sont localisés.
- [ ] Les prix sont présentés correctement.
- [ ] Les captures correspondent à la langue.
- [ ] Les e-mails transactionnels sont traduits.
- [ ] Les messages d’erreur sont traduits.
- [ ] Le glossaire est respecté.

---

# 19. Données structurées

## BLOQUANT

- [ ] Le JSON-LD est valide.
- [ ] L’entité `Organization` est unique.
- [ ] Le logo officiel est utilisé.
- [ ] L’entité `WebSite` est correcte.
- [ ] L’entité `SoftwareApplication` utilise la bonne version.
- [ ] Les `@id` sont stables.
- [ ] Les URLs JSON-LD sont canoniques.
- [ ] Les pages techniques utilisent un type pertinent.
- [ ] Les articles indiquent un auteur réel.
- [ ] Les dates sont exactes.
- [ ] Le fil d’Ariane JSON-LD correspond au fil visible.
- [ ] Aucune note ou avis fictif n’est présent.
- [ ] Les offres correspondent aux prix visibles.
- [ ] Les données structurées correspondent au contenu visible.
- [ ] Le Rich Results Test ne signale pas d’erreur critique.
- [ ] Le Schema Markup Validator ne signale pas d’erreur bloquante.

## MAJEUR

- [ ] Les images structurées sont accessibles.
- [ ] Les vidéos utilisent `VideoObject` lorsque pertinent.
- [ ] Les pages auteur utilisent une entité cohérente.
- [ ] Les six locales possèdent le bon `inLanguage`.
- [ ] Aucun ancien domaine du wiki ne subsiste dans le graphe.

---

# 20. Crawl et indexation

## BLOQUANT

- [ ] `robots.txt` de production correspond à `CRAWL_POLICY.md`.
- [ ] `robots.txt` de la démo est distinct.
- [ ] La préproduction est protégée.
- [ ] La production ne contient plus de `Disallow: /`.
- [ ] OAI-SearchBot suit la politique validée.
- [ ] GPTBot suit la politique validée.
- [ ] Claude-SearchBot suit la politique validée.
- [ ] ClaudeBot suit la politique validée.
- [ ] Google-Extended suit la politique validée.
- [ ] Applebot-Extended suit la politique validée.
- [ ] Les pages de démo fictives utilisent `noindex, follow`.
- [ ] Les pages `noindex` restent explorables.
- [ ] Les fichiers sensibles ne dépendent pas de `robots.txt` pour leur protection.

## MAJEUR

- [ ] Les logs permettent d’identifier les robots.
- [ ] Le WAF ne bloque pas les robots légitimes.
- [ ] Les plages IP sont vérifiées pour les exemptions.
- [ ] Les pages de recherche interne sont en `noindex`.
- [ ] Les PDF utilisent `X-Robots-Tag` lorsque nécessaire.

---

# 21. Accessibilité

## BLOQUANT

- [ ] La navigation fonctionne au clavier.
- [ ] Le focus est visible.
- [ ] Le menu mobile est accessible.
- [ ] Le sélecteur de langue est accessible.
- [ ] Les formulaires possèdent des labels.
- [ ] Les erreurs de formulaire sont annoncées clairement.
- [ ] Les images informatives possèdent un texte alternatif.
- [ ] Les images décoratives utilisent `alt=""`.
- [ ] Les contrastes essentiels sont suffisants.
- [ ] Les titres suivent une hiérarchie logique.
- [ ] Les boutons ont un libellé compréhensible.
- [ ] Les liens sont distinguables.
- [ ] Le zoom navigateur ne casse pas les fonctions principales.

## MAJEUR

- [ ] Les tableaux utilisent une structure sémantique.
- [ ] Les vidéos possèdent des sous-titres.
- [ ] Les transcriptions sont disponibles.
- [ ] Les messages ne reposent pas uniquement sur la couleur.
- [ ] Les animations respectent les préférences de réduction de mouvement.
- [ ] Le site a été contrôlé avec un lecteur d’écran sur les parcours P0.

---

# 22. Responsive et navigateurs

## BLOQUANT

- [ ] Accueil sur mobile.
- [ ] Documentation sur mobile.
- [ ] Navigation sur mobile.
- [ ] Formulaires sur mobile.
- [ ] Tableaux sur mobile.
- [ ] Images responsive.
- [ ] Aucun débordement horizontal critique.
- [ ] Safari macOS.
- [ ] Safari iOS.
- [ ] Chrome.
- [ ] Firefox.
- [ ] Edge.
- [ ] Android Chrome.

## MAJEUR

- [ ] Grandes résolutions.
- [ ] Orientation paysage mobile.
- [ ] Navigation tactile.
- [ ] Impression des pages documentaires.
- [ ] Mode sombre si pris en charge.

---

# 23. Performance

## Cibles de terrain

```text
LCP ≤ 2,5 secondes
INP ≤ 200 millisecondes
CLS ≤ 0,1
```

Les cibles doivent être évaluées au 75e percentile, séparément sur mobile et desktop.

## BLOQUANT

- [ ] Aucun problème de performance empêchant l’utilisation du site.
- [ ] Le serveur répond sans erreurs sous charge normale.
- [ ] Les images principales sont dimensionnées.
- [ ] Les scripts ne bloquent pas la navigation.
- [ ] Les polices ne provoquent pas de décalage majeur.
- [ ] Le cache fonctionne.
- [ ] Les assets inexistants ne génèrent pas de nombreuses erreurs.
- [ ] Les pages P0 ont été testées avec Lighthouse ou PageSpeed Insights.
- [ ] Les temps serveur sont mesurés.
- [ ] La démo ne surcharge pas le site officiel.

## MAJEUR

- [ ] LCP cible atteinte sur les pages principales.
- [ ] INP cible atteinte.
- [ ] CLS cible atteinte.
- [ ] Les images utilisent WebP ou AVIF lorsque pertinent.
- [ ] Le lazy loading est utilisé hors écran.
- [ ] L’image LCP n’est pas lazy-loaded sans justification.
- [ ] Les CSS inutiles sont limités.
- [ ] Les JavaScript tiers sont limités.
- [ ] La compression est active.
- [ ] Le cache HTTP est documenté.

---

# 24. Formulaires et e-mails

## BLOQUANT

- [ ] Formulaire de contact envoyé.
- [ ] Message de confirmation affiché.
- [ ] E-mail reçu.
- [ ] Adresse de réponse correcte.
- [ ] Objet correct.
- [ ] Encodage UTF-8 correct.
- [ ] Protection CSRF.
- [ ] Protection anti-spam.
- [ ] Validation serveur.
- [ ] Consentement et confidentialité.
- [ ] Aucun secret n’est envoyé dans l’e-mail.
- [ ] Les erreurs ne révèlent pas de détails internes.
- [ ] Les liens des e-mails pointent vers la production.
- [ ] SPF, DKIM et DMARC sont vérifiés pour le domaine d’envoi.

## MAJEUR

- [ ] Version texte des e-mails.
- [ ] Affichage mobile.
- [ ] Mode sombre.
- [ ] Traductions des e-mails.
- [ ] Adresse de rebond surveillée.
- [ ] Journal d’envoi conforme à la confidentialité.

---

# 25. Téléchargements et licences

## BLOQUANT

- [ ] Le bouton télécharge la bonne version.
- [ ] L’archive s’ouvre.
- [ ] Le checksum correspond.
- [ ] La version affichée correspond au fichier.
- [ ] La licence est accessible.
- [ ] Les prérequis sont affichés.
- [ ] Les notes de version sont accessibles.
- [ ] Les archives précédentes sont clairement distinguées.
- [ ] Le téléchargement utilise HTTPS.
- [ ] Le fichier ne contient aucun secret.
- [ ] Les produits premium affichent les bonnes conditions.

## MAJEUR

- [ ] Le poids du fichier est affiché.
- [ ] La date de publication est affichée.
- [ ] Les signatures ou mécanismes de vérification sont documentés.
- [ ] Le compteur de téléchargement, s’il existe, est fiable.

---

# 26. Démonstration

## BLOQUANT

- [ ] La page d’accueil de la démo explique son fonctionnement.
- [ ] Les identifiants de test fonctionnent.
- [ ] Les droits du compte de démonstration sont limités.
- [ ] La remise à zéro fonctionne.
- [ ] La remise à zéro ne touche pas la production.
- [ ] Les contenus fictifs sont en `noindex`.
- [ ] La démo possède son propre `robots.txt`.
- [ ] Aucun secret de production n’est partagé.
- [ ] Les formulaires de démonstration ne provoquent pas d’envoi réel non contrôlé.
- [ ] Les téléversements sont limités.
- [ ] Les tâches planifiées de la démo fonctionnent.
- [ ] La démo possède une limite de ressources.
- [ ] Un lien vers le site officiel est visible.
- [ ] Un lien vers le téléchargement est visible.

## MAJEUR

- [ ] Une bannière indique qu’il s’agit d’une démo.
- [ ] La date de prochaine remise à zéro est indiquée.
- [ ] Les données utilisateurs sont supprimées lors du reset.
- [ ] Les erreurs de démo sont surveillées.

---

# 27. Analytics et confidentialité

## BLOQUANT

- [ ] L’outil analytics retenu est configuré.
- [ ] Les pages vues sont enregistrées.
- [ ] Les téléchargements sont mesurés.
- [ ] Les clics vers la démo sont mesurés.
- [ ] Les conversions principales sont définies.
- [ ] Les données sensibles ne sont pas envoyées.
- [ ] Les paramètres d’URL sensibles sont filtrés.
- [ ] Le consentement est géré si nécessaire.
- [ ] La politique de confidentialité décrit les outils utilisés.
- [ ] Les environnements de test sont exclus des statistiques.

## MAJEUR

- [ ] Les locales sont mesurées.
- [ ] Les erreurs 404 sont mesurées.
- [ ] Les sources IA sont identifiables lorsque possible.
- [ ] Les performances réelles sont collectées.
- [ ] Les événements sont documentés.
- [ ] Les accès au tableau analytics sont limités.

---

# 28. Search Console et moteurs

## BLOQUANT

- [ ] La propriété domaine Search Console est vérifiée.
- [ ] Bing Webmaster Tools est configuré.
- [ ] Le sitemap index est soumis.
- [ ] Les pages P0 sont inspectées.
- [ ] La page d’accueil n’est pas bloquée.
- [ ] La documentation n’est pas bloquée.
- [ ] Le blog n’est pas bloqué.
- [ ] Les pages multilingues sont accessibles.
- [ ] Les anciennes propriétés utiles sont conservées.
- [ ] Les tokens de vérification survivent au déploiement.

## MAJEUR

- [ ] Les sitemaps par locale sont soumis.
- [ ] Les actions manuelles sont vérifiées.
- [ ] Les suppressions temporaires anciennes sont vérifiées.
- [ ] Les paramètres de crawl sont laissés à la gestion normale sauf besoin.
- [ ] Le fichier de désaveu est revu si un tel fichier existe.
- [ ] Les pages principales sont demandées en réindexation après lancement si nécessaire.

---

# 29. Réseaux, profils et communications

## MAJEUR

- [ ] README GitHub mis à jour.
- [ ] Lien du wiki remplacé.
- [ ] Lien de téléchargement mis à jour.
- [ ] Profil LinkedIn mis à jour.
- [ ] Chaîne YouTube mise à jour.
- [ ] E-mails et signatures mis à jour.
- [ ] Documentation externe mise à jour.
- [ ] Les anciens liens importants sont corrigés à la source.
- [ ] Le communiqué de lancement est prêt.
- [ ] La page de statut ou d’information est prête.

---

# 30. Tests de préproduction

## BLOQUANT

- [ ] La préproduction est inaccessible aux visiteurs non autorisés.
- [ ] Elle est en `noindex`.
- [ ] Elle n’utilise pas les données sensibles de production.
- [ ] Les formulaires n’envoient pas vers de vrais destinataires sans contrôle.
- [ ] Les paiements éventuels utilisent un environnement de test.
- [ ] Les redirections sont testables.
- [ ] Les sitemaps de production ne référencent pas la préproduction.
- [ ] Les canonicals ne pointent pas vers la préproduction.
- [ ] Les JSON-LD ne pointent pas vers la préproduction.
- [ ] Les e-mails ne contiennent pas les URLs de préproduction.

## MAJEUR

- [ ] Un crawl complet de préproduction a été archivé.
- [ ] Les erreurs du crawl sont classées.
- [ ] Le déploiement a été répété sur un clone.
- [ ] Le rollback a été répété.

---

# 31. Gel avant lancement

## BLOQUANT

- [ ] Date du gel fonctionnel annoncée.
- [ ] Date du gel éditorial annoncée.
- [ ] Les modifications urgentes suivent une procédure contrôlée.
- [ ] La table des redirections est figée.
- [ ] Les contenus P0 sont figés.
- [ ] La version du package est figée.
- [ ] Les checksums sont figés.
- [ ] La sauvegarde finale est planifiée.
- [ ] Les personnes nécessaires sont disponibles le jour J.

---

# 32. Procédure Jour J

## Avant déploiement

- [ ] Confirmer le Go / No-Go.
- [ ] Enregistrer l’heure de début.
- [ ] Effectuer la sauvegarde finale.
- [ ] Vérifier l’intégrité de la sauvegarde.
- [ ] Informer les responsables.
- [ ] Réduire les changements externes.
- [ ] Préparer les commandes de rollback.

## Déploiement

- [ ] Mettre le site en maintenance si nécessaire.
- [ ] Déployer les fichiers.
- [ ] Déployer les données.
- [ ] Appliquer les permissions.
- [ ] Activer la configuration serveur.
- [ ] Vider ou reconstruire les caches.
- [ ] Activer les redirections.
- [ ] Désactiver le `noindex` de préproduction.
- [ ] Publier le `robots.txt` de production.
- [ ] Publier les sitemaps.
- [ ] Sortir du mode maintenance.

## Contrôles immédiats

- [ ] Accueil.
- [ ] Navigation.
- [ ] Documentation.
- [ ] Blog.
- [ ] Téléchargement.
- [ ] Contact.
- [ ] Administration.
- [ ] Connexion.
- [ ] Déconnexion.
- [ ] Médias.
- [ ] Démo.
- [ ] Page 404.
- [ ] HTTPS.
- [ ] Hôte canonique.
- [ ] Redirections P0.
- [ ] `robots.txt`.
- [ ] Sitemap.
- [ ] Canonicals.
- [ ] `hreflang`.
- [ ] JSON-LD.
- [ ] E-mails.
- [ ] Logs.

---

# 33. Critères de rollback

Déclencher le rollback si l’un des événements suivants survient et ne peut être corrigé rapidement et sans risque :

- exposition de données sensibles ;
- faille de sécurité critique ;
- impossibilité de se connecter à l’administration ;
- corruption des données ;
- perte de contenus ;
- indisponibilité prolongée ;
- redirections globalement incorrectes ;
- certificat TLS invalide ;
- téléchargement compromis ;
- erreurs 500 massives ;
- démo affectant la production ;
- absence de sauvegarde exploitable ;
- problème juridique majeur.

La décision appartient au directeur de lancement ou à son remplaçant désigné.

---

# 34. Surveillance J+1 à J+7

## Quotidien

- [ ] disponibilité ;
- [ ] erreurs 5xx ;
- [ ] erreurs 404 ;
- [ ] boucles de redirection ;
- [ ] journaux PHP ;
- [ ] journaux serveur ;
- [ ] formulaires ;
- [ ] e-mails ;
- [ ] téléchargements ;
- [ ] indexation ;
- [ ] Search Console ;
- [ ] Bing Webmaster Tools ;
- [ ] performances ;
- [ ] sécurité ;
- [ ] charge de la démo ;
- [ ] retours utilisateurs.

## Rapport quotidien

| Date | Disponibilité | Erreurs | Indexation | Conversions | Incidents | Actions |
|---|---:|---:|---:|---:|---|---|

---

# 35. Surveillance J+8 à J+30

## Hebdomadaire

- [ ] positions de marque ;
- [ ] requêtes génériques ;
- [ ] pages indexées ;
- [ ] anciennes URLs encore explorées ;
- [ ] redirections utilisées ;
- [ ] backlinks ;
- [ ] Core Web Vitals ;
- [ ] conversions ;
- [ ] locales ;
- [ ] citations IA ;
- [ ] erreurs structurées ;
- [ ] pages orphelines ;
- [ ] cannibalisation.

---

# 36. Audit J+30

## Technique

- [ ] crawl complet ;
- [ ] erreurs HTTP ;
- [ ] redirections ;
- [ ] canonicals ;
- [ ] `hreflang` ;
- [ ] sitemaps ;
- [ ] données structurées ;
- [ ] performance ;
- [ ] sécurité ;
- [ ] accessibilité.

## Éditorial

- [ ] contenus les plus consultés ;
- [ ] pages sans trafic ;
- [ ] requêtes inattendues ;
- [ ] titles à faible CTR ;
- [ ] contenus à enrichir ;
- [ ] traductions obsolètes ;
- [ ] documentation manquante.

## Commercial

- [ ] téléchargements ;
- [ ] essais de démo ;
- [ ] contacts ;
- [ ] ventes premium ;
- [ ] abandons ;
- [ ] retours utilisateurs.

---

# 37. Core Web Vitals

## Cibles officielles retenues

| Mesure | Cible « bonne » |
|---|---:|
| LCP | ≤ 2,5 s |
| INP | ≤ 200 ms |
| CLS | ≤ 0,1 |

Évaluation :

```text
75e percentile
mobile et desktop séparés
données terrain prioritaires
```

## Outils

- PageSpeed Insights ;
- Lighthouse ;
- Chrome User Experience Report ;
- Search Console ;
- mesure RUM éventuelle.

Les scores Lighthouse de laboratoire ne remplacent pas les données terrain.

---

# 38. Registre final Go / No-Go

| Domaine | Bloquants validés | Majeurs ouverts | Décision | Responsable |
|---|---:|---:|---|---|
| Gouvernance | | | | |
| Infrastructure | | | | |
| Sécurité | | | | |
| Sauvegarde | | | | |
| Contenus | | | | |
| SEO | | | | |
| Redirections | | | | |
| Multilingue | | | | |
| Accessibilité | | | | |
| Performance | | | | |
| Formulaires | | | | |
| Démo | | | | |

## Décision finale

```text
[ ] GO
[ ] GO avec réserves documentées
[ ] NO-GO
```

Date :

```text
____________________
```

Responsable :

```text
____________________
```

Signature ou validation :

```text
____________________
```

---

# 39. Preuves à archiver

Créer un dossier de lancement :

```text
launch/
├── backups/
├── crawls/
├── redirects/
├── screenshots/
├── security/
├── performance/
├── structured-data/
├── multilingual/
├── search-console/
├── logs/
└── reports/
```

Archiver :

- sauvegardes ;
- rapports de crawl ;
- résultats Lighthouse ;
- rapports sécurité ;
- tests de redirection ;
- captures Search Console ;
- validation JSON-LD ;
- vérifications `hreflang` ;
- compte rendu Go / No-Go ;
- journal du lancement ;
- bilan J+30.

---

# 40. Références officielles

- Google Search Central — Site moves and migrations  
  https://developers.google.com/search/docs/crawling-indexing/site-move-with-url-changes

- Google Search Central — Build and submit a sitemap  
  https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap

- Google Search Central — Structured data  
  https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data

- web.dev — Web Vitals  
  https://web.dev/articles/vitals

- OWASP — Web Security Testing Guide  
  https://owasp.org/www-project-web-security-testing-guide/

- W3C Web Accessibility Initiative  
  https://www.w3.org/WAI/

---

# 41. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Création de la checklist initiale de lancement | ChatGPT / Alain BROYE |

---

# 42. Prochaine action

Après ajout de ce fichier dans le dossier Drive :

```text
1. vérifier l’ensemble des documents directeurs ;
2. créer un INDEX.md qui les référence ;
3. commencer les briefs détaillés des pages P0 ;
4. définir la structure réelle du futur thème FlatCMS ;
5. préparer le plan de migration des contenus du wiki.
```
