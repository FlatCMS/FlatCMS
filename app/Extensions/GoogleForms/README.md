# Google Forms — OAuth global FlatCMS

Extension FlatCMS d’intégration Google Forms.

Cette version s’appuie sur le connecteur Google OAuth global du core FlatCMS. Elle permet de connecter un compte Google autorisé, sélectionner un formulaire Google Forms, synchroniser les réponses via l’API Google Forms, puis les consulter dans une interface d’administration FlatCMS premium.

## Version

```text
1.0.0
```

## Fonctionnalités

- Connexion OAuth 2.0 Google via les réglages globaux FlatCMS.
- Statut clair de la configuration OAuth globale et de la connexion Google.
- Liste des formulaires Google Forms disponibles.
- Sélection d’un formulaire actif.
- Synchronisation des réponses via Google Forms API.
- Tableau de bord des réponses dans l’administration FlatCMS.
- Recherche instantanée dans les réponses synchronisées.
- Tableau responsive basé sur les conventions FlatCMS.
- Modal de détail avec toutes les réponses libellées.
- Stockage métier dans `data/extensions/google-forms`.

## Pré-requis

L’administrateur doit configurer Google OAuth dans FlatCMS :

```text
/admin/settings#settings-integrations
```

Variables globales utilisées par le core :

```env
GOOGLE_OAUTH_CLIENT_ID=
GOOGLE_OAUTH_CLIENT_SECRET=
GOOGLE_OAUTH_ENCRYPTION_KEY=
```

L’extension ne stocke pas de Client Secret Google propre au module et n’utilise pas de variables OAuth dédiées de type :

```env
GOOGLE_FORMS_OAUTH_CLIENT_ID=
GOOGLE_FORMS_OAUTH_CLIENT_SECRET=
```

## URI de redirection OAuth

À déclarer dans Google Cloud Console :

```text
/admin/google-forms/oauth/callback
```

Dans FlatCMS, la section Google OAuth affiche l’URI absolue recommandée pour l’installation courante.

## APIs Google nécessaires

- Google Drive API
- Google Forms API

## Scopes demandés

```text
openid
email
profile
https://www.googleapis.com/auth/drive.metadata.readonly
https://www.googleapis.com/auth/forms.body.readonly
https://www.googleapis.com/auth/forms.responses.readonly
```

## Stockage

```text
data/extensions/google-forms/
├── settings/
├── oauth/
├── forms/
└── responses/
```

## Sécurité

- Le module ne demande jamais le mot de passe Google.
- Les secrets OAuth sont centralisés dans le core FlatCMS.
- Les tokens OAuth sont stockés dans `data/extensions/google-forms/oauth`.
- Le chiffrement repose sur la clé globale `GOOGLE_OAUTH_ENCRYPTION_KEY`, gérée par FlatCMS.
- La désactivation du module conserve les données.
- La suppression des données à la désinstallation est prise en charge par le mécanisme core générique.

## Version 1.0.0

Première version stable validée de l’extension GoogleForms.

### Stabilisation fonctionnelle

- Alignement avec le patch core Google OAuth global.
- Utilisation de `GOOGLE_OAUTH_CLIENT_ID`, `GOOGLE_OAUTH_CLIENT_SECRET` et `GOOGLE_OAUTH_ENCRYPTION_KEY`.
- Support de la lecture sécurisée des valeurs `.env.local`, y compris les anciennes valeurs `flatcms-secret:v1:*` pour migration.
- Erreurs Google API plus explicites : statut HTTP, code, message et `error_description` lorsque disponible.

### Interface administration

- Interface principale premium dans `admin/google-forms`.
- Badges d’état OAuth global et connexion Google.
- Bouton de connexion désactivé tant que l’OAuth global n’est pas conforme.
- Carte compte connecté avec nom Google principal et adresse e-mail secondaire.
- Sélection de formulaire alignée et lisible.
- Bloc réponses épuré avec titre indigo unique.
- Tableau principal simplifié : société, contact, e-mail, activité et actions.
- Suppression du badge “Nouveau” dans la colonne société.
- Modal de détail conservant les informations complètes, dont le besoin et le budget.

### Documentation

- Documentation alignée avec l’OAuth global FlatCMS.
- Suppression des consignes obsolètes autour des clés OAuth spécifiques GoogleForms.
