# Changelog

## 2.0.0

- consolide le Core HMVC et ses services CLI autour des mêmes contrats applicatifs ;
- garantit la portabilité par copier-coller, sans symlink externe ni cache canonique ;
- généralise les écritures JSON atomiques, verrouillées et récupérables ;
- sécurise les mises à jour Core avec capsule de récupération, rollback et finalisation vérifiée ;
- rend les sauvegardes, restaurations et suppressions de dossiers transactionnelles ;
- introduit SiteBackup V3 : archives portables entre installations FlatCMS identiques, composants ajoutés et documentation inclus, secrets chiffrés, contrôle du Core de référence, publication d'assets atomique et rollback vérifié ;
- ajoute la réinitialisation des données du site en conservant le premier Super Admin et les sauvegardes, ainsi qu'une réinitialisation usine complète ;
- isole les composants optionnels afin que leur absence ou désactivation ne bloque plus le Core ;
- unifie les primitives d'administration, IconPicker, Media, éditeurs, modales, toasts et interfaces responsives ;
- ajoute le diagnostic d'installation, la publication contrôlée des assets et les contrôles SEO déterministes ;
- renforce les contrats de packaging, de licences, d'i18n et de compatibilité Apache/Nginx.

## 1.1.7

- fiabilise CKEditor et conserve les alignements sans écraser le HTML source ;
- unifie les onglets de traduction sur mobile et ajoute la localisation SEO ;
- complète les e-mails Contact et raccorde les consentements Newsletter ;
- impose l'activation locale des licences aux composants premium avant exécution ;
- distingue les composants catalogués mais protégés des téléchargements publics ;
- corrige l'affichage responsive des vues d'administration et des thèmes frontend.

## 1.1.6

- première version distribuée avec UpdateManager et le catalogue Addons officiel.
