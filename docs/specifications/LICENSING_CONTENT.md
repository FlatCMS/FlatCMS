# LICENSING_CONTENT — Licences et droits d’utilisation de FlatCMS

> **Contenu éditorial prêt à intégrer**
>
> Projet : FlatCMS  
> Page : Licences `fr-FR`  
> URL cible : `https://flat-cms.fr/fr-FR/licences/`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Brief parent : `PAGE_BRIEFS.md` — `P0-LICENSE-FR`  
> Sources internes principales : `LICENSE`, `LICENSING.md`, `COMMERCIAL_LICENSE.md`, `CLA.md`, `TRADEMARK.md`  
> Statut : première version rédactionnelle à faire relire juridiquement avant publication

---

## 1. Objet de la page

Cette page explique le modèle de licences de FlatCMS et oriente les utilisateurs vers les textes officiels applicables.

Elle doit permettre de comprendre :

- la licence du LTS Core ;
- le statut des composants premium ;
- l’importance des en-têtes SPDX ;
- les obligations générales liées à l’AGPL ;
- les règles de redistribution et de modification ;
- les conditions commerciales ;
- le statut des dépendances tierces ;
- les conditions de contribution ;
- la distinction entre droit d’auteur, licence logicielle et marque ;
- les documents qui prévalent en cas de doute.

Cette page est une présentation pédagogique.

Elle ne remplace pas :

- le texte intégral de la GNU AGPL ;
- un contrat commercial signé ;
- le CLA ;
- la politique de marque ;
- un avis juridique adapté à une situation précise.

---

# 2. Métadonnées SEO

## URL canonique

```text
https://flat-cms.fr/fr-FR/licences/
```

## Balise `<title>`

```text
Licences FlatCMS — Open source, commercial et marque
```

## Meta description

```text
Comprenez les licences de FlatCMS : LTS Core sous GNU AGPL v3 ou
ultérieure, composants premium, contributions et règles de marque.
```

## Directive robots

```text
index, follow, max-image-preview:large
```

## Open Graph

### `og:title`

```text
Licences et droits d’utilisation de FlatCMS
```

### `og:description`

```text
Découvrez le modèle de licences de FlatCMS, les droits associés au
LTS Core, les composants commerciaux, le CLA et la politique de marque.
```

### `og:type`

```text
article
```

### `og:url`

```text
https://flat-cms.fr/fr-FR/licences/
```

### `og:image`

```text
https://flat-cms.fr/assets/images/og/licences-flatcms-fr-FR.webp
```

---

# 3. Avertissement juridique

## H2

```text
Une présentation pédagogique, pas un avis juridique
```

## Texte

```text
Cette page résume les documents de licence de FlatCMS afin d’en faciliter
la compréhension. Seuls les textes officiels applicables au fichier,
au composant ou au contrat concerné déterminent les droits et obligations.
```

```text
Si votre projet implique une redistribution, une modification importante,
un service en ligne, une intégration propriétaire, une revente, une
marque dérivée ou un contrat client, demandez conseil à une personne
qualifiée en droit des logiciels.
```

## Règle de priorité

En cas de contradiction, consulter dans cet ordre :

1. l’en-tête de licence du fichier concerné ;
2. le texte intégral de la licence ou du contrat applicable ;
3. les documents officiels FlatCMS ;
4. cette page de présentation.

---

# 4. Vue d’ensemble

## H1

```text
Licences et droits d’utilisation de FlatCMS
```

## Introduction

```text
FlatCMS utilise un modèle de licences séparées dans sa gamme de produits.
Le LTS Core est destiné à constituer la ligne stable open source, tandis
que certains composants premium peuvent être distribués sous une licence
commerciale distincte.
```

```text
Sauf indication contraire dans l’en-tête d’un fichier de première partie,
le code source FlatCMS est placé sous la GNU Affero General Public
License version 3 ou toute version ultérieure, identifiée par
`AGPL-3.0-or-later`.
```

```text
Les fichiers premium identifiés par
`LicenseRef-FlatCMS-Commercial` ne sont pas placés sous l’AGPL et
nécessitent une autorisation commerciale valide.
```

## CTA principal

```text
Lire la licence du LTS Core
```

Destination :

```text
/les textes officiels/LICENSE
```

## CTA secondaire

```text
Consulter la licence commerciale
```

Destination :

```text
/les textes officiels/COMMERCIAL_LICENSE.md
```

## Lien tertiaire

```text
Voir les tarifs des composants premium
```

Destination :

```text
/fr-FR/tarifs/
```

---

# 5. Le principe déterminant : la licence du fichier

## H2

```text
L’en-tête SPDX du fichier fait foi
```

## Texte

```text
FlatCMS utilise des identifiants SPDX pour indiquer la licence applicable
aux fichiers de première partie.
```

## Code open source

```php
// SPDX-License-Identifier: AGPL-3.0-or-later
```

## Composant commercial

```php
// SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
```

## Règle

```text
Si l’en-tête d’un fichier et une présentation générale semblent
différents, l’en-tête du fichier est prioritaire et le fichier doit être
examiné avec le texte de licence correspondant.
```

## Pourquoi cette règle ?

Un même package peut contenir :

- du code FlatCMS sous AGPL ;
- un composant premium propriétaire ;
- une bibliothèque tierce ;
- un asset soumis à une autre licence ;
- une police ou une icône avec ses propres conditions ;
- un exemple ou un contenu dont les droits diffèrent.

Le nom du dossier ou du produit ne suffit donc pas à déterminer la licence.

---

# 6. Le LTS Core open source

## H2

```text
FlatCMS LTS Core sous GNU AGPL v3 ou ultérieure
```

## Identifiant

```text
SPDX-License-Identifier: AGPL-3.0-or-later
```

## Texte officiel

```text
GNU Affero General Public License, version 3 ou toute version ultérieure
publiée par la Free Software Foundation.
```

## Périmètre annoncé

```text
FlatCMS LTS Core est destiné à distribuer la ligne stable open source du
CMS. Il ne doit pas inclure intentionnellement les répertoires premium
dans son périmètre runtime pris en charge.
```

## Droits généraux accordés

Sous réserve de respecter les conditions de la licence applicable, vous
pouvez notamment :

- utiliser le logiciel ;
- étudier son code source ;
- modifier le logiciel ;
- exécuter une version modifiée ;
- copier le logiciel ;
- redistribuer le logiciel ;
- facturer une copie, une installation, une intégration ou un service ;
- proposer du support ou une garantie dans un contrat séparé.

## Important

```text
« Open source » ne signifie pas « sans conditions ».
```

La GNU AGPL est une licence copyleft qui impose des obligations lors de
certaines redistributions et lors de l’utilisation en réseau d’une
version modifiée.

---

# 7. Utiliser FlatCMS sans le modifier

## H2

```text
Installer et exploiter la version officielle
```

## Cas général

Vous pouvez installer et utiliser une copie non modifiée du LTS Core,
sous réserve de respecter les notices et conditions de la GNU AGPL.

## Exemples

- site personnel ;
- site associatif ;
- site d’entreprise ;
- site client ;
- environnement local ;
- hébergement interne ;
- service public accessible sur Internet.

## Notices

Conservez :

- les mentions de copyright ;
- les identifiants de licence ;
- le fichier `LICENSE` ;
- les notices d’absence de garantie ;
- les licences tierces ;
- les informations nécessaires à l’identification de l’origine.

## Prudence

Une configuration, un thème, un contenu ou un module distinct n’est pas
automatiquement soumis à la même licence uniquement parce qu’il est
utilisé avec FlatCMS.

La qualification juridique d’un composant combiné dépend notamment :

- de son degré d’intégration ;
- des interfaces utilisées ;
- de sa distribution ;
- de sa dépendance au code couvert ;
- des droits accordés par son auteur.

---

# 8. Modifier le LTS Core

## H2

```text
Conserver les libertés attachées aux versions modifiées
```

## Texte

```text
Vous pouvez modifier le code couvert par la GNU AGPL.
```

Lorsqu’une version modifiée est redistribuée, les conditions de la
licence imposent notamment, selon la situation :

- d’indiquer que le logiciel a été modifié ;
- de fournir une date pertinente de modification ;
- de conserver les notices applicables ;
- de placer le travail couvert sous la GNU AGPL ;
- de fournir le code source correspondant ;
- de transmettre une copie de la licence ;
- de ne pas ajouter de restriction incompatible.

## Interaction à distance

```text
Si vous modifiez le programme et que des utilisateurs interagissent avec
cette version à distance par un réseau, la section 13 de la GNU AGPL
impose de leur offrir de manière visible et pratique un accès gratuit au
code source correspondant de la version exécutée.
```

## Mise en œuvre possible

L’interface d’une version modifiée peut afficher un lien tel que :

```text
Code source de cette version
```

Ce lien peut pointer vers :

- une archive source ;
- un dépôt Git ;
- une page de release ;
- un serveur proposant le code correspondant.

## Exigence essentielle

Le code proposé doit correspondre à la version réellement exécutée et
inclure les éléments relevant du « Corresponding Source » au sens de la
licence.

---

# 9. Utilisation privée et développement interne

## H2

```text
Développer une modification sans la distribuer
```

## Texte

La GNU AGPL autorise l’exécution et la modification privées sous réserve
du maintien de la licence.

L’obligation spécifique liée à l’interaction réseau devient pertinente
lorsque des utilisateurs interagissent à distance avec une version
modifiée.

## Exemples à analyser

### Prototype local

```text
Un développement réalisé et utilisé uniquement sur la machine du
développeur n’est pas un service public.
```

### Outil interne

```text
Un service utilisé par des collaborateurs à travers un réseau peut
constituer une interaction à distance avec la version modifiée.
```

### Environnement de test client

```text
Un client ou un testeur accédant à une version modifiée peut être un
utilisateur auquel l’offre de source doit être présentée.
```

## Recommandation

Ne présumez pas qu’un service est exempté uniquement parce qu’il est :

- gratuit ;
- interne ;
- en préproduction ;
- accessible à peu de personnes ;
- hébergé chez un prestataire.

Analysez le cas réel au regard du texte de la licence.

---

# 10. Redistribuer FlatCMS

## H2

```text
Transmettre une copie officielle ou modifiée
```

## Copie non modifiée

Lors de la redistribution du code source original :

- conserver les notices ;
- conserver la licence ;
- conserver les exclusions de garantie ;
- ne pas présenter la copie comme un autre produit officiel ;
- respecter la politique de marque.

## Version modifiée

Lors de la redistribution d’une version modifiée :

- signaler les modifications ;
- conserver la licence applicable ;
- fournir le code source correspondant ;
- ne pas ajouter de restriction incompatible ;
- distinguer la version modifiée de la distribution officielle ;
- adapter la marque conformément à `TRADEMARK.md`.

## Archive binaire ou package installable

Lorsque vous distribuez une forme non source, la GNU AGPL prévoit des
méthodes précises permettant de fournir le code source correspondant.

La méthode choisie doit respecter le texte complet de la licence.

## Tarif

```text
La GNU AGPL permet de demander un prix pour une copie ou un service.
Le paiement ne supprime pas les droits que la licence accorde aux
destinataires sur le code couvert.
```

---

# 11. Héberger FlatCMS pour des clients

## H2

```text
Distinguer hébergement, modification et composants premium
```

## LTS Core officiel non modifié

L’hébergement d’une copie officielle non modifiée doit conserver les
mentions et droits applicables.

## LTS Core modifié

Si les utilisateurs interagissent à distance avec une version modifiée,
l’offre de code source correspondant doit être prévue conformément à la
section 13 de la GNU AGPL.

## Développements client

Un projet client peut contenir :

- le LTS Core sous AGPL ;
- un thème ;
- des contenus ;
- des médias ;
- des modules personnalisés ;
- des bibliothèques tierces ;
- des composants FlatCMS premium.

Chaque élément doit être inventorié avec sa propre licence.

## Composants premium

La licence commerciale FlatCMS peut restreindre notamment :

- la copie ;
- la modification ;
- la distribution ;
- la sous-licence ;
- la revente ;
- l’hébergement pour des tiers ;
- l’intégration dans un autre produit.

Les droits exacts dépendent du contrat commercial applicable.

## Agences

Une agence doit vérifier avant livraison :

- le titulaire de la licence ;
- le nombre de sites autorisés ;
- l’usage client ;
- les droits de transfert ;
- les mises à jour ;
- le support ;
- la maintenance ;
- la redistribution ;
- l’hébergement pour tiers ;
- la marque.

---

# 12. Composants commerciaux

## H2

```text
Une licence distincte pour les composants premium
```

## Identifiant

```text
SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
```

## Statut

```text
Les fichiers portant cet identifiant sont des composants propriétaires
premium et ne sont pas licenciés sous la GNU AGPL.
```

## Droit d’utilisation

```text
L’utilisation d’un composant premium nécessite une licence commerciale
valide accordée par Alain BROYE / FlatCMS.
```

## En l’absence d’un accord écrit différent

La licence commerciale interne indique qu’aucun droit n’est accordé pour :

- copier ;
- modifier ;
- distribuer ;
- sous-licencier ;
- revendre ;
- héberger pour des tiers ;
- intégrer dans un autre produit ;

les composants premium identifiés par le marqueur commercial.

## Contrat applicable

Les éléments suivants dépendent du contrat commercial écrit :

- nombre de sites ;
- durée ;
- renouvellement ;
- mises à jour ;
- support ;
- garantie ;
- déploiement commercial ;
- usage en agence ;
- transfert à un client ;
- environnement de test ;
- remboursement ;
- fin de licence.

## Propriété

```text
Les droits de propriété intellectuelle sur les composants premium restent
détenus par Alain BROYE, sauf accord écrit contraire.
```

## CTA

```text
Consulter les offres premium
```

Destination :

```text
/fr-FR/tarifs/
```

---

# 13. Core et premium dans un même projet

## H2

```text
Respecter chaque licence séparément
```

Un site peut associer :

```text
FlatCMS LTS Core
+ thème
+ modules open source
+ composants premium
+ bibliothèques tierces
+ contenus du propriétaire du site
```

Chaque élément conserve son régime juridique.

## Exemple conceptuel

| Élément | Licence possible |
|---|---|
| LTS Core | `AGPL-3.0-or-later` |
| PagesBuilder | `LicenseRef-FlatCMS-Commercial` |
| Bibliothèque JavaScript | Licence tierce |
| Icône | Licence de l’éditeur de l’icône |
| Thème client | Contrat ou licence du thème |
| Articles et images | Droits du propriétaire du contenu |

## Règle

```text
L’achat d’un composant premium ne transforme pas le LTS Core en logiciel
propriétaire.
```

Réciproquement :

```text
La licence open source du Core ne transforme pas un composant premium en
logiciel libre.
```

---

# 14. Dépendances tierces

## H2

```text
Les bibliothèques tierces conservent leurs licences
```

## Emplacements cités par le projet

```text
app/ThirdParty/**
public/assets/dists/**
vendor/**
```

## Texte

```text
FlatCMS ne relicencie pas les bibliothèques, assets, polices, icônes ou
codes de fournisseurs tiers.
```

## Obligations possibles

Selon la dépendance :

- conserver un fichier de licence ;
- afficher une attribution ;
- fournir le code source ;
- reproduire une notice ;
- respecter une licence de police ;
- ne pas redistribuer certains assets ;
- respecter les conditions d’une API ;
- éviter une incompatibilité de licence.

## Release

Chaque package officiel doit idéalement fournir :

- un inventaire des dépendances ;
- leur version ;
- leur licence ;
- les notices requises ;
- la source ou l’emplacement de la source lorsque nécessaire.

## Bonnes pratiques

- utiliser des dépendances officielles ;
- verrouiller les versions ;
- analyser les licences ;
- éviter les assets sans provenance ;
- documenter les modifications ;
- contrôler les distributions minifiées ;
- conserver les notices dans l’archive.

---

# 15. Thèmes et modules tiers

## H2

```text
Vérifier la licence de chaque extension
```

## Module tiers

Un module tiers peut utiliser sa propre licence si son auteur dispose des
droits nécessaires et si cette licence reste compatible avec son mode
d’intégration à FlatCMS.

## Thème

Un thème peut inclure :

- templates PHP ;
- CSS ;
- JavaScript ;
- images ;
- polices ;
- icônes ;
- contenus de démonstration.

Ces éléments peuvent relever de licences différentes.

## Marketplace

Avant installation ou publication, vérifier :

- auteur ;
- version ;
- compatibilité ;
- licence ;
- dépendances ;
- droits de redistribution ;
- droits sur les médias ;
- signature ;
- provenance ;
- support.

## Responsabilité

```text
La présence d’un composant dans une marketplace ou un package ne garantit
pas automatiquement que son utilisateur possède tous les droits requis.
```

---

# 16. Contributions et CLA

## H2

```text
Contribuer à FlatCMS
```

## Document applicable

```text
CLA.md
```

## Déclarations du contributeur

En soumettant une contribution, le contributeur déclare notamment :

- qu’il s’agit de son travail original ou qu’il peut le soumettre ;
- qu’il ne viole pas sciemment les droits de tiers ;
- qu’il est juridiquement en mesure d’accorder les droits prévus.

## Droits accordés

Le CLA accorde à Alain BROYE un droit :

- perpétuel ;
- mondial ;
- non exclusif ;
- irrévocable ;
- gratuit ;

permettant notamment d’utiliser, reproduire, modifier, distribuer,
sous-licencier, afficher, exécuter et relicencier la contribution.

## Modèles de licence couverts

Le CLA autorise notamment l’utilisation de la contribution sous :

- GNU AGPL ;
- conditions commerciales ;
- futurs modèles de licence FlatCMS.

## Pourquoi ce CLA ?

Il permet au projet :

- d’intégrer les contributions dans le Core ;
- de maintenir un modèle de licences séparées ;
- de proposer des distributions commerciales ;
- de faire évoluer la stratégie de licence ;
- de protéger la continuité juridique du projet.

## Acceptation

```text
FlatCMS n’est pas obligé d’accepter, fusionner, publier, maintenir ou
supporter une contribution.
```

## Contribution importante

```text
Une contribution substantielle ou stratégique peut nécessiter un accord
signé séparément avant son acceptation.
```

## Employeur

Un contributeur doit vérifier si :

- son contrat de travail attribue ses créations à son employeur ;
- son employeur doit autoriser la contribution ;
- le code provient d’un projet client ;
- une dépendance tierce est intégrée ;
- un brevet ou une clause de confidentialité est concerné.

---

# 17. Droits moraux et attribution

## H2

```text
Gérer les contributions dans la durée
```

## CLA FlatCMS

Dans la mesure permise par la loi applicable, le contributeur accepte de
ne pas invoquer ses droits moraux d’une manière empêchant l’exercice des
droits accordés au projet.

## Attribution

```text
Une attribution raisonnable peut être conservée, modifiée ou retirée
dans le cadre de la maintenance normale du projet.
```

## Prudence

Le traitement des droits moraux varie selon les juridictions.

Cette section doit être relue juridiquement, notamment au regard du droit
français.

---

# 18. Marque FlatCMS

## H2

```text
La licence du code n’accorde pas la marque
```

## Éléments protégés

La politique de marque couvre notamment :

- le nom `FlatCMS` ;
- les logos ;
- les noms de produits ;
- les icônes ;
- les signatures visuelles ;
- les éléments de marque.

## Utilisations nominatives autorisées

Vous pouvez employer le mot `FlatCMS` de manière véridique pour :

- décrire une compatibilité ;
- indiquer qu’un service utilise FlatCMS ;
- identifier le projet ;
- créer un lien vers le site officiel ;
- rédiger un article ou une comparaison.

## Conditions

L’utilisation ne doit pas laisser croire à :

- un partenariat ;
- une certification ;
- une approbation ;
- un produit officiel ;
- un support officiel ;
- une distribution officielle.

## Utilisations nécessitant une autorisation préalable

Sans permission écrite, la politique interdit notamment :

- d’utiliser `FlatCMS` dans le nom d’un fork ou d’un CMS concurrent ;
- d’enregistrer un domaine ou un compte social prêtant à confusion ;
- d’utiliser le logo comme si le produit était officiel ;
- de présenter un thème, module ou service comme un produit officiel ;
- de conserver la marque sur une distribution modifiée susceptible de
  tromper les utilisateurs.

## Forks

```text
Une version modifiée redistribuée doit retirer ou remplacer les éléments
de marque qui pourraient la faire passer pour la distribution officielle,
sauf autorisation écrite.
```

## Règle fondamentale

```text
La GNU AGPL porte sur les droits d’auteur du logiciel.
La politique de marque régit l’identification commerciale du projet.
```

---

# 19. Peut-on vendre des services autour de FlatCMS ?

## H2

```text
Facturer une expertise, une installation ou un service
```

## LTS Core

La GNU AGPL permet de facturer :

- une copie ;
- une installation ;
- une configuration ;
- un développement ;
- un thème ;
- une intégration ;
- une migration ;
- une formation ;
- un hébergement ;
- une maintenance ;
- un support ;
- une garantie contractuelle.

## Conditions

La facturation ne supprime pas les obligations de licence portant sur le
code couvert.

## Marque

Une entreprise peut indiquer :

```text
Service compatible avec FlatCMS
```

mais ne doit pas prétendre :

```text
Service officiel FlatCMS
Partenaire certifié FlatCMS
Distribution officielle FlatCMS
```

sans autorisation correspondante.

## Composants premium

Les droits de revente, d’hébergement pour tiers, de transfert et
d’intégration dépendent de la licence commerciale achetée.

---

# 20. Peut-on créer un fork ?

## H2

```text
Modifier et redistribuer une version distincte
```

## Code

La GNU AGPL permet de créer et redistribuer une version modifiée sous
réserve de respecter ses conditions.

## Marque

La politique de marque exige que le fork soit clairement distingué de la
distribution officielle.

## À faire

- choisir un nom distinct ;
- retirer les logos officiels non autorisés ;
- indiquer l’origine du code ;
- indiquer les modifications ;
- conserver les notices de licence ;
- fournir le code source correspondant ;
- ne pas utiliser un domaine prêtant à confusion ;
- ne pas annoncer une approbation inexistante.

## Compatibilité

Une version modifiée ne doit pas être présentée comme compatible avec les
services, mises à jour ou composants premium officiels sans validation.

---

# 21. Peut-on créer un module propriétaire ?

## H2

```text
Analyser le mode d’intégration et de distribution
```

## Réponse prudente

La possibilité de distribuer un module sous une licence propriétaire ne
peut pas être décidée uniquement à partir du mot « module ».

Il faut examiner :

- les interfaces utilisées ;
- les classes héritées ;
- les dépendances au Core ;
- la manière dont le module est chargé ;
- la distribution avec ou séparément du Core ;
- les éléments de code AGPL incorporés ;
- les obligations de la GNU AGPL ;
- la juridiction applicable.

## Recommandation

Pour un module propriétaire ou une intégration fermée :

- demander un avis juridique ;
- éviter de copier du code AGPL ;
- utiliser les contrats publics documentés ;
- conserver une séparation technique nette ;
- vérifier la compatibilité avec la distribution ;
- envisager un accord commercial spécifique si nécessaire.

## Composants FlatCMS premium

Les composants premium officiels utilisent leur propre licence
commerciale et ne doivent pas être pris comme précédent juridique pour
un module tiers.

---

# 22. Peut-on modifier le logo ou le nom ?

## H2

```text
Distinguer adaptation visuelle et usage de marque
```

## Site construit avec FlatCMS

Vous pouvez créer votre propre identité visuelle pour le site client.

## Administration et mentions

Les modifications ne doivent pas :

- supprimer des notices juridiquement requises ;
- faire passer une version modifiée pour la version officielle ;
- utiliser un logo FlatCMS modifié comme nouvelle marque officielle ;
- créer une confusion sur l’origine du logiciel.

## Fork

Un fork doit adopter une identité distincte si la marque officielle peut
induire les utilisateurs en erreur.

## Référence nominative

Une mention telle que :

```text
Propulsé par FlatCMS
```

peut être autorisée comme référence véridique, sous réserve de ne pas
suggérer une approbation particulière.

---

# 23. Support, mises à jour et garantie

## H2

```text
La licence logicielle n’est pas un contrat de support
```

## LTS Core

La GNU AGPL fournit le logiciel sans garantie, dans les limites permises
par la loi applicable.

## Support

Un support peut être :

- communautaire ;
- fourni par un intégrateur ;
- proposé par FlatCMS ;
- inclus dans une offre commerciale ;
- couvert par un contrat séparé.

## Mises à jour

L’accès aux mises à jour peut dépendre :

- du canal de distribution ;
- de la version ;
- du contrat commercial ;
- du maintien du composant ;
- de la compatibilité technique.

## Premium

Pour les composants commerciaux, le support, la maintenance, la garantie,
les mises à jour et les droits de déploiement commercial sont définis
par l’accord applicable.

## Règle

```text
Ne pas promettre une durée de support ou de mise à jour sans engagement
écrit et publié.
```

---

# 24. Absence de garantie

## H2

```text
Utiliser et tester le logiciel dans son environnement
```

La GNU AGPL prévoit une absence de garantie dans les limites permises par
le droit applicable.

L’utilisateur doit notamment :

- tester la version ;
- vérifier la compatibilité ;
- sauvegarder les données ;
- protéger le serveur ;
- contrôler les modules ;
- vérifier les mises à jour ;
- planifier un retour arrière ;
- respecter les lois applicables à son site.

## Contrat séparé

Une garantie ou une responsabilité supplémentaire ne s’applique que si
elle est prévue dans un accord écrit.

---

# 25. Conformité d’un package officiel

## H2

```text
Vérifier les licences avant chaque release
```

Chaque archive de distribution doit être contrôlée.

## Checklist

- [ ] `LICENSE` présent ;
- [ ] `LICENSING.md` présent ;
- [ ] `COMMERCIAL_LICENSE.md` présent si nécessaire ;
- [ ] `TRADEMARK.md` présent ;
- [ ] `CLA.md` présent dans le dépôt de contribution ;
- [ ] en-têtes SPDX cohérents ;
- [ ] aucun code premium introduit par erreur dans le Core ;
- [ ] dépendances tierces inventoriées ;
- [ ] notices tierces conservées ;
- [ ] sources correspondantes disponibles lorsque requises ;
- [ ] médias autorisés ;
- [ ] polices autorisées ;
- [ ] archive et checksum identifiés ;
- [ ] version correcte ;
- [ ] aucune clé ou donnée privée ;
- [ ] marque officielle utilisée uniquement sur la distribution officielle.

---

# 26. Documents officiels

## H2

```text
Consulter les textes applicables
```

## Licence du Core

### `LICENSE`

```text
Texte intégral de la GNU Affero General Public License.
```

Lien :

```text
https://flat-cms.fr/legal/LICENSE
```

ou emplacement final à définir.

## Politique de licences

### `LICENSING.md`

```text
Règle par défaut, périmètre du LTS Core, dépendances tierces, marque et
contributions.
```

## Licence commerciale

### `COMMERCIAL_LICENSE.md`

```text
Règles applicables aux fichiers identifiés par
LicenseRef-FlatCMS-Commercial.
```

## Accord de contribution

### `CLA.md`

```text
Droits et déclarations associés aux contributions.
```

## Politique de marque

### `TRADEMARK.md`

```text
Usages autorisés et interdits du nom et des éléments de marque FlatCMS.
```

## Règle

```text
Les URLs finales doivent fournir une version datée ou versionnée des
documents afin de préserver les conditions applicables à une release.
```

---

# 27. Tableau récapitulatif

## H2

```text
Quel document consulter ?
```

| Situation | Document principal |
|---|---|
| Utiliser le LTS Core | `LICENSE` et `LICENSING.md` |
| Modifier le Core | `LICENSE` |
| Héberger une version modifiée | `LICENSE`, notamment section 13 |
| Redistribuer FlatCMS | `LICENSE` et `TRADEMARK.md` |
| Utiliser un builder premium | Contrat commercial et `COMMERCIAL_LICENSE.md` |
| Créer une agence ou une offre client | Contrat commercial si premium, `LICENSE` pour le Core |
| Contribuer au projet | `CLA.md` |
| Utiliser le nom ou le logo | `TRADEMARK.md` |
| Distribuer une dépendance tierce | Licence de cette dépendance |
| Créer un fork | `LICENSE` et `TRADEMARK.md` |
| Créer un module propriétaire | Analyse juridique et contrats applicables |

---

# 28. Questions fréquentes éditoriales

> Ces réponses simplifient des principes généraux. Elles ne remplacent
> pas l’analyse des textes officiels.

## H2

```text
Questions fréquentes sur les licences
```

### H3 — FlatCMS LTS Core est-il open source ?

```text
Oui. Sauf en-tête contraire, le code de première partie du LTS Core est
licencié sous GNU AGPL version 3 ou ultérieure.
```

### H3 — Puis-je utiliser FlatCMS pour un site commercial ?

```text
Oui, la GNU AGPL n’interdit pas les usages commerciaux. Vous devez
respecter ses conditions et vérifier séparément la licence des composants
premium et tiers utilisés.
```

### H3 — Puis-je facturer une installation FlatCMS ?

```text
Oui. Vous pouvez facturer une installation, une intégration, un
hébergement, une formation ou un support. La facturation ne supprime pas
les droits et obligations attachés au code sous AGPL.
```

### H3 — Dois-je publier les modifications de mon site ?

```text
Si vous modifiez le programme couvert et que des utilisateurs
interagissent avec cette version à distance, la GNU AGPL impose de leur
offrir un accès au code source correspondant de la version modifiée.
L’analyse dépend du code modifié et de la manière dont il est utilisé.
```

### H3 — Mes articles et images deviennent-ils AGPL ?

```text
Le contenu produit ou géré par le CMS n’est pas automatiquement couvert
par l’AGPL uniquement parce qu’il est stocké ou affiché par FlatCMS.
Vous devez toutefois posséder les droits nécessaires sur ce contenu.
```

### H3 — Mon thème devient-il automatiquement AGPL ?

```text
Cela dépend de sa structure, de son intégration, du code qu’il incorpore
et de sa distribution. Une analyse juridique peut être nécessaire pour
un thème distribué sous une licence différente.
```

### H3 — Les builders premium sont-ils AGPL ?

```text
Non lorsqu’ils portent l’identifiant
LicenseRef-FlatCMS-Commercial. Ils suivent leur licence et leur contrat
commercial propres.
```

### H3 — Puis-je redistribuer PagesBuilder avec mon offre ?

```text
Uniquement si votre contrat commercial vous accorde explicitement ce
droit. La licence commerciale par défaut n’accorde pas la distribution,
la revente, la sous-licence ou l’hébergement pour tiers.
```

### H3 — Puis-je créer un fork appelé FlatCMS Pro ?

```text
La politique de marque interdit l’utilisation de FlatCMS dans le nom
d’un fork ou d’un produit concurrent sans permission écrite. Un fork
doit adopter une identité distincte.
```

### H3 — Puis-je utiliser le logo FlatCMS sur mon site ?

```text
Une référence véridique au projet peut être possible, mais le logo ne
doit pas laisser croire que votre service, thème ou distribution est
officiel ou approuvé. Consultez la politique de marque.
```

### H3 — Pourquoi FlatCMS utilise-t-il un CLA ?

```text
Le CLA permet au projet d’intégrer les contributions, de conserver la
continuité juridique et d’utiliser ces contributions sous les modèles
open source et commerciaux de FlatCMS.
```

### H3 — Quelle licence prévaut si deux documents semblent se contredire ?

```text
Commencez par l’en-tête du fichier concerné, puis consultez le texte
intégral de la licence ou du contrat correspondant. Signalez toute
incohérence au projet.
```

---

# 29. CTA final

## H2

```text
Identifier la licence avant d’utiliser ou de distribuer un composant
```

## Texte

```text
Vérifiez l’en-tête du fichier, lisez le document applicable et
conservez les notices nécessaires dans votre projet et vos distributions.
```

## CTA principal

```text
Lire les textes officiels
```

Destination :

```text
/fr-FR/licences/#documents-officiels
```

## CTA secondaire

```text
Voir les composants premium
```

Destination :

```text
/fr-FR/tarifs/
```

## Lien tertiaire

```text
Poser une question commerciale
```

Destination :

```text
/fr-FR/contact/?motif=licence
```

---

# 30. Maillage interne attendu

| Section | Destination |
|---|---|
| Hero | Textes officiels, tarifs |
| LTS Core | Téléchargement |
| Version modifiée | Documentation développeur |
| Redistribution | Guide de distribution |
| Hébergement client | Page agence ou tarifs |
| Premium | Tarifs et pages builders |
| Dépendances | Référence des dépendances |
| Contributions | Guide de contribution |
| Marque | Page politique de marque |
| Fork | Guide de contribution ou développement |
| Support | Contact |
| Documents | Versions HTML des textes |
| CTA final | Textes, tarifs, contact |

---

# 31. Médias à produire

## Image Open Graph

Concept :

```text
FlatCMS au centre de trois branches distinctes :
LTS Core open source
Composants premium
Marque et contributions
```

## Diagramme principal

```text
Fichier
→ lire l’en-tête SPDX
→ AGPL / Commercial / Tiers
→ consulter le texte applicable
```

## Illustration du modèle

```text
FlatCMS LTS Core
AGPL-3.0-or-later

PagesBuilder / MenuBuilder / FooterBuilder
LicenseRef-FlatCMS-Commercial

Bibliothèques
Licences tierces
```

## Règle

Ne pas utiliser un logo GNU ou SPDX d’une manière laissant croire à une
validation particulière du projet sans respecter leurs règles d’usage.

---

# 32. Textes alternatifs suggérés

## Modèle de licences

```text
Modèle de licences de FlatCMS séparant le LTS Core open source, les
composants premium et les dépendances tierces
```

## En-têtes SPDX

```text
Choix du document de licence à partir de l’identifiant SPDX d’un fichier
FlatCMS
```

## Marque

```text
Distinction entre licence du code FlatCMS et droits sur le nom et le logo
```

Les alternatives finales doivent correspondre aux illustrations produites.

---

# 33. Données structurées attendues

```text
WebPage
BreadcrumbList
ImageObject
```

## Identifiants

```text
https://flat-cms.fr/fr-FR/licences/#webpage
https://flat-cms.fr/fr-FR/licences/#breadcrumb
https://flat-cms.fr/fr-FR/licences/#primaryimage
```

## Relations

```text
about
→ https://flat-cms.fr/#software

isPartOf
→ https://flat-cms.fr/#website

publisher
→ https://flat-cms.fr/#organization
```

## Licence du logiciel

L’entité `SoftwareApplication` globale peut inclure une propriété
`license` pointant vers la page officielle ou le texte applicable au
LTS Core.

## Prudence

Ne pas encoder les composants premium comme s’ils étaient couverts par la
licence du Core.

---

# 34. Composants du thème suggérés

```text
HeroLicensing
LegalNotice
LicenseModelDiagram
SpdxDecisionFlow
OpenSourceRights
NetworkInteractionNotice
CommercialLicenseCard
ThirdPartyLicenses
ContributionCla
TrademarkRules
LicenseDecisionTable
OfficialDocuments
FaqAccordion
CallToActionBanner
```

---

# 35. Éléments à faire valider juridiquement

- formulation publique du modèle séparé ;
- portée exacte de l’AGPL sur les modules et thèmes ;
- explication de la section 13 ;
- usage interne et utilisateurs distants ;
- qualification du « Corresponding Source » ;
- interaction entre Core et composants premium ;
- clauses commerciales par défaut ;
- hébergement pour tiers ;
- droits d’agence ;
- transfert des licences ;
- règles de fork ;
- politique de marque ;
- droits moraux du CLA ;
- compatibilité du CLA avec le droit français ;
- identité juridique du concédant ;
- limitation de responsabilité ;
- politique de support ;
- contenu des mentions légales ;
- traduction des textes juridiques.

---

# 36. Checklist éditoriale

- [ ] Le modèle est décrit comme séparé par fichier.
- [ ] `AGPL-3.0-or-later` est écrit correctement.
- [ ] `LicenseRef-FlatCMS-Commercial` est écrit correctement.
- [ ] L’en-tête du fichier est présenté comme prioritaire.
- [ ] Les droits et obligations ne sont pas simplifiés de manière trompeuse.
- [ ] La section réseau est expliquée avec prudence.
- [ ] Le prix d’un service n’est pas confondu avec la licence du code.
- [ ] Les composants premium restent distincts.
- [ ] Les dépendances tierces restent distinctes.
- [ ] La marque est séparée du copyright.
- [ ] Le CLA est résumé fidèlement.
- [ ] L’absence de garantie est mentionnée.
- [ ] Les textes officiels sont liés.
- [ ] Les documents sont versionnés.
- [ ] Une validation juridique est requise avant publication.
- [ ] Aucun avis juridique personnalisé n’est donné.

---

# 37. Checklist d’intégration

- [ ] URL correcte.
- [ ] Canonique auto-référencée.
- [ ] `<html lang="fr-FR">`.
- [ ] Groupe `hreflang`.
- [ ] Title.
- [ ] Meta description.
- [ ] Open Graph.
- [ ] H1 unique.
- [ ] Avertissement juridique visible.
- [ ] Liens vers les textes officiels.
- [ ] Tableaux responsive.
- [ ] Blocs de code accessibles.
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
- [ ] Validation juridique enregistrée.

---

# 38. Sources internes

- `LICENSE`
- `LICENSING.md`
- `COMMERCIAL_LICENSE.md`
- `CLA.md`
- `TRADEMARK.md`
- `README.md`
- en-têtes SPDX des fichiers ;
- manifestes des composants ;
- contrats commerciaux applicables ;
- inventaire des dépendances tierces.

---

# 39. Références externes

- GNU Affero General Public License v3  
  https://www.gnu.org/licenses/agpl-3.0.html

- GNU — Why the Affero GPL  
  https://www.gnu.org/licenses/why-affero-gpl.html

- SPDX — AGPL-3.0-or-later  
  https://spdx.org/licenses/AGPL-3.0-or-later.html

- SPDX — License List  
  https://spdx.org/licenses/

- Open Source Initiative — Frequently Answered Questions  
  https://opensource.org/faq

Les références externes permettent de consulter la licence officielle et
l’identifiant SPDX. Les conditions spécifiques à FlatCMS restent définies
par les fichiers et accords du projet.

---

# 40. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction complète de la page Licences | ChatGPT / Alain BROYE |

---

# 41. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer PRICING_CONTENT.md
```

Ce document contiendra la rédaction complète de la page :

```text
/fr-FR/tarifs/
```

Il distinguera le LTS Core gratuit des builders premium et précisera pour
chaque offre le prix, la fiscalité, le nombre de sites, les mises à jour,
le support et les droits commerciaux.
