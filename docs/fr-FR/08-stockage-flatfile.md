# Stockage FlatFile (JSON)

## Principe

FlatCMS utilise un **ORM JSON** (`App\Core\FlatFile`) pour stocker toutes les données dans des fichiers JSON plats — sans aucune base de données.

**Fichier** : `app/Core/FlatFile.php`

## Structure de stockage

```
data/
├── modules.json              # État des modules
├── settings.json             # Paramètres du site
├── installed.lock            # Marqueur d'installation
├── .index/                   # Index centralisés (auto-générés)
│   ├── posts.json
│   ├── pages.json
│   └── ...
├── .integrity/               # Checksums d'intégrité (auto-générés)
│   └── checksums.json
├── languages/                # Languages actifs
│   ├── fr-FR.json
│   └── en-US.json
├── users/                    # Utilisateurs
│   └── {id}.json
├── core/
│   ├── categories/
│   │   └── {id}.json
│   ├── media/
│   │   └── {id}.json
│   ├── pages/
│   │   └── {id}.json
│   ├── posts/
│   │   └── {id}.json
│   └── icons/
│       └── {id}.json
├── menus/
│   └── menus.json            # Menus (fichier unique)
└── footer/
    └── footer.json           # Footer (fichier unique)
```

## Règles de stockage

| Entité | Emplacement | Format |
|---|---|---|
| Utilisateurs | `data/users/{id}.json` | Un fichier par utilisateur |
| Pages | `data/core/pages/{id}.json` | Un fichier par page |
| Articles | `data/core/posts/{id}.json` | Un fichier par article |
| Catégories | `data/core/categories/{id}.json` | Un fichier par catégorie |
| Médias | `data/core/media/{id}.json` | Un fichier par média |
| Menus | `data/menus/menus.json` | Un seul fichier pour tous les menus |
| Footer | `data/footer/footer.json` | Un seul fichier |
| Paramètres | `data/settings.json` | Un seul fichier |
| Modules | `data/modules.json` | Un seul fichier |

## API de l'ORM

### Lecture

```php
use App\Core\FlatFile;

// Instancier pour une entité
$ff = FlatFile::for('posts');

// Récupérer toutes les entités
$posts = $ff->all();

// Trouver par ID
$post = $ff->find($id);

// Recherche par champ
$page = $ff->findBy('status', 'published');

// Recherche avec conditions where
$posts = $ff->where('category_id', $categoryId);

// Recherche textuelle
$results = $ff->search('mon terme', ['title', 'content']);

// Vérifier l'existence
$exists = $ff->exists($id);

// Compter (via l'index)
$count = $ff->count();
```

### Lecture avec vérification intégrité

```php
// Lecture + vérification SHA-256 en une seule opération
// Retourne null si le fichier est corrompu ou absent
$post = $ff->findVerified($id);

// Vérification unitaire détaillée
$result = $ff->integrityCheck($id);
// ['status' => 'valid', 'entity' => 'posts', 'id' => '...']
// ['status' => 'corrupted', 'entity' => 'posts', 'id' => '...']
// ['status' => 'missing', 'entity' => 'posts', 'id' => '...']

// Vérification bulk de toutes les entités du FlatFile
$results = $ff->allIntegrityCheck();
// ['total' => 150, 'valid' => 148, 'corrupted' => 1, 'missing' => 1, 'details' => [...]]
```

```bash
# Vérifier une entité via CLI
php flatcms integrity:verify posts
```

### Écriture

```php
// Créer (index + checksum mis à jour automatiquement)
$post = $ff->create([
    'title' => 'Mon article',
    'content' => 'Contenu...',
    'status' => 'published',
]);

// Mettre à jour
$ff->update($id, [
    'title' => 'Titre modifié',
]);

// Supprimer
$ff->delete($id);
```

### Pagination

```php
// Pagination via l'index (pas de lecture de tous les fichiers)
$results = $ff->paginate($page, $perPage);

// Retourne :
// [
//     'data' => [...],
//     'current_page' => 1,
//     'per_page' => 15,
//     'total' => 120,
//     'total_pages' => 8,
//     'has_more' => true
// ]
```

## File locking (verrouillage)

Chaque opération est protégée par un système de verrouillage fichier :

| Opération | Verrou | Mécanisme |
|---|---|---|
| **Lecture** | `LOCK_SH` (partagé) | Multiple lectures simultanées autorisées |
| **Écriture** | `LOCK_EX` (exclusif) + fichier `.lock` | Un seul écriture à la fois |
| **Suppression** | `LOCK_EX` (exclusif) + fichier `.lock` | Suppression atomique |

### Écriture atomique

Toute écriture suit le schéma :
1. Écriture dans un fichier temporaire `.tmp.{pid}`
2. `rename()` atomique vers le fichier final
3. Nettoyage du fichier `.lock`

Cela garantit qu'aucun lecteur ne voit un fichier partiellement écrit.

## Index centralisé

**Classe** : `App\Core\IndexManager`

Un index par entité est maintenu dans `data/.index/{entity}.json`. Il contient les métadonnées (id, title, slug, status, dates) sans charger le fichier complet.

| Avantage | Impact |
|---|---|
| `count()` instantané | Pas de `glob()` à chaque appel |
| `paginate()` optimisé | Tri et offset sur l'index, lecture sélective des fichiers |
| Rebuild automatique | L'index se reconstruit si le fichier est absent |

```bash
# Reconstruire tous les index
php flatcms index:rebuild

# Reconstruire un index spécifique
php flatcms index:rebuild posts
```

## Checksums d'intégrité

**Classe** : `App\Core\IntegrityManager`

Un hash SHA-256 est enregistré pour chaque fichier JSON dans `data/.integrity/checksums.json`. Les opérations CRUD (create, update, delete) mettent à jour automatiquement les checksums.

```bash
# Vérifier l'intégrité de toutes les données
php flatcms integrity:check

# Enregistrer les checksums pour toutes les entités
php flatcms integrity:record

# Vérifier une entité spécifique
php flatcms integrity:verify posts
```

### Sortie de `integrity:check`

```
Integrity check:
  Total:      150
  Valid:      148
  Corrupted:  1
  Missing:    1

Details:
  [corrupted] posts/20260101_abc12345
  [missing] pages/20260102_def67890
```

## Cache

**Classe** : `App\Core\CacheManager`

Cache à deux niveaux :
1. **Mémoire statique** — persiste pendant la requête courante
2. **Fichier JSON** — persiste entre les requêtes (dans `storage/cache/data/`)

```php
$cache = CacheManager::instance();

$cache->set('key', $value, 3600);     // TTL 1h
$value = $cache->get('key', $default);
$cache->has('key');                     // bool
$cache->forget('key');
$cache->remember('key', fn() => compute(), 3600);
$cache->cleanup();                      // supprime les fichiers expirés
$cache->autoCleanup();                  // cleanup si > 1h depuis dernière exécution
$cache->clear();                        // vide tout
```

```bash
# Vider le cache
php flatcms cache:clear

# Nettoyer les fichiers expirés
php flatcms cache:cleanup
```

L'auto-nettoyage (`autoCleanup()`) est appelé automatiquement au démarrage de chaque requête, mais ne s'exécute qu'une fois par heure maximum via un fichier horodatage.

## Paramètres du site

La fonction helper `settings()` lit les paramètres depuis `data/settings.json` :

```php
$siteName = settings('site_name');
$locale = settings('default_locale');
$theme = settings('frontend_theme');
```

Avec rétrocompatibilité pour les anciens chemins de stockage.

## Identifiants

Les entités utilisent des **identifiants uniques** (format `YYYYMMDD_HHMMSS_XXXXXXXX`) comme nom de fichier. Le fichier `{id}.json` contient toutes les données de l'entité.

## Intégrité des données

| Mécanisme | Protection |
|---|---|
| Écriture atomique | Fichier temporaire + rename |
| File locking | Concurrency reader/writer |
| Checksums SHA-256 | Détection de corruption |
| Index centralisé | Performance et cohérence |

## Avantages

| Avantage | Description |
|---|---|
| **Simplicité** | Pas de serveur de base de données à maintenir |
| **Portabilité** | Copier/coller du dossier `data/` = backup complet |
| **Performance** | Index + cache = lectures optimisées |
| **Débogage** | Fichiers JSON lisibles par un humain |
| **Déploiement** | Pas de migration de schéma |
| **Intégrité** | Checksums SHA-256 sur chaque fichier |
| **Concurrence** | File locking reader/writer |

## Limites

| Limite | Impact |
|---|---|
| **Pas de transactions** | Pas d'atomicité multi-fichiers |
| **Pas de relations** | Pas de jointures SQL |
| **Scalabilité** | Performance dégradée au-delà de quelques milliers d'entités |
| **Recherche** | Recherche textuelle basique (substr, pas full-text) |

## Fichiers de données versionnés

Le fichier `data/modules.json` est **versionné dans git**. Les fichiers `.index/` et `.integrity/` sont auto-générés. Tous les autres fichiers de données sont dans `.gitignore` sauf indication contraire.
