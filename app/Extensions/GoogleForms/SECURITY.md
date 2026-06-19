# Sécurité — Google Forms OAuth 2.0

- Ne jamais demander le mot de passe Google.
- Utiliser OAuth 2.0 uniquement.
- Stocker le Client Secret Google hors dépôt Git.
- Centraliser les secrets Google OAuth dans les réglages globaux FlatCMS.
- Utiliser `GOOGLE_OAUTH_CLIENT_ID`, `GOOGLE_OAUTH_CLIENT_SECRET` et `GOOGLE_OAUTH_ENCRYPTION_KEY`.
- Ne pas créer de Client Secret propre à GoogleForms.
- Ne plus utiliser `GOOGLE_FORMS_OAUTH_CLIENT_ID` ou `GOOGLE_FORMS_OAUTH_CLIENT_SECRET`.
- Stocker les tokens OAuth dans `data/extensions/google-forms/oauth`.
- Déclarer précisément l’URI de redirection `/admin/google-forms/oauth/callback` dans Google Cloud Console.
