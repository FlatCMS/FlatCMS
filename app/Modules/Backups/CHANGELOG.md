# Backups changelog

## 1.1.0

- Ajoute SiteBackup V3 et son référentiel de portabilité versionné.
- Transporte les composants ajoutés avec leurs sources, assets et fichiers de documentation.
- Chiffre les secrets portables et conserve la clé de restauration dans un fichier séparé.
- Publie les assets dans la transaction de restauration et les réconcilie après rollback.
- Ajoute la réinitialisation des données du site et renforce la réinitialisation usine.
- Protège le Core, les fichiers d'entrée, la configuration et les assets communs contre une restauration incompatible.

## 1.0.2

- Stabilise les sauvegardes complètes et les restaurations transactionnelles.
