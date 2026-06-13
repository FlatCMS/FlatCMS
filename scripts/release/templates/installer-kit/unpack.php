<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

const BATCH_SIZE = 300;
const MIN_PHP_VERSION_ID = 80100;

$zipPath = __DIR__ . '/flatcms.zip';

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

/**
 * @return array<int, string>
 */
function supported_langs(): array
{
    return ['fr', 'en', 'es', 'de', 'it', 'pt'];
}

function normalize_lang(?string $value): string
{
    $lang = strtolower(trim((string) $value));
    if ($lang === '') {
        return '';
    }
    $lang = substr($lang, 0, 2);

    return in_array($lang, supported_langs(), true) ? $lang : '';
}

function browser_lang(): string
{
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    if ($accept !== '') {
        $first = explode(',', $accept)[0] ?? '';
        $lang = normalize_lang($first);
        if ($lang !== '') {
            return $lang;
        }
    }

    return 'fr';
}

function resolve_lang(): string
{
    $fromRequest = normalize_lang((string) ($_REQUEST['lang'] ?? ''));
    if ($fromRequest !== '') {
        $_SESSION['flatcms_unpack_lang'] = $fromRequest;
        return $fromRequest;
    }

    $fromSession = normalize_lang((string) ($_SESSION['flatcms_unpack_lang'] ?? ''));
    if ($fromSession !== '') {
        return $fromSession;
    }

    $fallback = browser_lang();
    $_SESSION['flatcms_unpack_lang'] = $fallback;

    return $fallback;
}

function tr(string $lang, string $key, array $replace = []): string
{
    static $messages = [
        'fr' => [
            'page_title' => 'FlatCMS - Extraction',
            'chip_label' => 'Déploiement FlatCMS',
            'lang_label' => 'Langue',
            'heading' => 'Extraction du package FlatCMS',
            'subtitle' => 'Lancez l’extraction avec barre de progression, puis enchaînez automatiquement vers l’installateur.',
            'already_extracted' => 'FlatCMS semble déjà extrait dans ce dossier.',
            'continue_install' => 'Continuer l’installation',
            'start_extraction' => 'Lancer l’extraction',
            'refresh' => 'Actualiser',
            'status_ready' => 'Prêt pour extraction',
            'status_initializing' => 'Initialisation…',
            'status_running' => 'Extraction en cours…',
            'status_done' => 'Extraction terminée',
            'status_error' => 'Erreur',
            'check_php' => 'Version PHP >= 8.1',
            'check_php_detail' => 'Version détectée : {version}',
            'check_zip' => 'Extension ZipArchive',
            'check_archive' => 'Archive flatcms.zip',
            'check_write' => 'Dossier cible accessible en écriture',
            'detail_available' => 'Disponible',
            'detail_unavailable' => 'Non disponible',
            'archive_present' => 'Archive détectée',
            'archive_missing' => 'Archive absente',
            'write_ok' => 'Écriture autorisée',
            'write_ko' => 'Écriture refusée',
            'source_label' => 'Source',
            'batch_label' => 'Taille batch',
            'files_label' => 'fichiers',
            'cleanup_notice' => 'À la fin de l’extraction, index.html, unpack.php et flatcms.zip sont supprimés automatiquement.',
            'csrf_invalid' => 'Jeton de sécurité invalide.',
            'precheck_invalid' => 'Pré-contrôles invalides. Corrigez les erreurs puis recommencez.',
            'json_running' => 'Extraction en cours…',
            'json_done' => 'Extraction terminée. Redirection vers l’installateur…',
            'json_parse_error' => 'Réponse serveur invalide.',
            'json_interrupted' => 'Extraction interrompue.',
            'json_unknown_error' => 'Erreur inconnue.',
            'zip_default' => 'Erreur inconnue pendant la lecture de l’archive.',
            'zip_exists' => 'Le fichier existe déjà.',
            'zip_incons' => 'Archive ZIP invalide ou corrompue.',
            'zip_inval' => 'Argument ZIP invalide.',
            'zip_memory' => 'Mémoire insuffisante lors de l’ouverture ZIP.',
            'zip_noent' => 'Impossible de trouver flatcms.zip.',
            'zip_nozip' => 'Le fichier flatcms.zip est invalide.',
            'zip_open' => 'Impossible d’ouvrir flatcms.zip.',
            'zip_read' => 'Erreur de lecture ZIP.',
            'zip_seek' => 'Erreur de positionnement ZIP.',
            'invalid_zip_entry' => 'Entrée ZIP non autorisée : {entry}',
            'extract_entry_error' => 'Erreur extraction : {entry}',
            'flatten_root_missing' => 'Impossible de localiser le dossier flatcms extrait.',
            'flatten_create_dir_fail' => 'Impossible de créer le dossier cible.',
            'flatten_target_exists' => 'Un fichier existe déjà : {entry}',
            'flatten_move_fail' => 'Impossible de déplacer le fichier : {entry}',
        ],
        'en' => [
            'page_title' => 'FlatCMS - Extraction',
            'chip_label' => 'FlatCMS Deployment',
            'lang_label' => 'Language',
            'heading' => 'Extract FlatCMS package',
            'subtitle' => 'Start extraction with a progress bar, then continue automatically to the installer.',
            'already_extracted' => 'FlatCMS already seems extracted in this directory.',
            'continue_install' => 'Continue installation',
            'start_extraction' => 'Start extraction',
            'refresh' => 'Refresh',
            'status_ready' => 'Ready to extract',
            'status_initializing' => 'Initializing…',
            'status_running' => 'Extraction in progress…',
            'status_done' => 'Extraction completed',
            'status_error' => 'Error',
            'check_php' => 'PHP version >= 8.1',
            'check_php_detail' => 'Detected version: {version}',
            'check_zip' => 'ZipArchive extension',
            'check_archive' => 'flatcms.zip archive',
            'check_write' => 'Target directory is writable',
            'detail_available' => 'Available',
            'detail_unavailable' => 'Not available',
            'archive_present' => 'Archive found',
            'archive_missing' => 'Archive missing',
            'write_ok' => 'Write access granted',
            'write_ko' => 'Write access denied',
            'source_label' => 'Source',
            'batch_label' => 'Batch size',
            'files_label' => 'files',
            'cleanup_notice' => 'At the end of extraction, index.html, unpack.php and flatcms.zip are removed automatically.',
            'csrf_invalid' => 'Invalid security token.',
            'precheck_invalid' => 'Pre-checks failed. Fix errors and try again.',
            'json_running' => 'Extraction in progress…',
            'json_done' => 'Extraction completed. Redirecting to installer…',
            'json_parse_error' => 'Invalid server response.',
            'json_interrupted' => 'Extraction interrupted.',
            'json_unknown_error' => 'Unknown error.',
            'zip_default' => 'Unknown error while reading archive.',
            'zip_exists' => 'File already exists.',
            'zip_incons' => 'ZIP archive is invalid or corrupted.',
            'zip_inval' => 'Invalid ZIP argument.',
            'zip_memory' => 'Not enough memory while opening ZIP.',
            'zip_noent' => 'Cannot find flatcms.zip.',
            'zip_nozip' => 'flatcms.zip is invalid.',
            'zip_open' => 'Cannot open flatcms.zip.',
            'zip_read' => 'ZIP read error.',
            'zip_seek' => 'ZIP seek error.',
            'invalid_zip_entry' => 'Unauthorized ZIP entry: {entry}',
            'extract_entry_error' => 'Extraction error: {entry}',
            'flatten_root_missing' => 'Cannot locate extracted flatcms directory.',
            'flatten_create_dir_fail' => 'Cannot create target directory.',
            'flatten_target_exists' => 'A file already exists: {entry}',
            'flatten_move_fail' => 'Cannot move file: {entry}',
        ],
        'es' => [
            'page_title' => 'FlatCMS - Extracción',
            'chip_label' => 'Despliegue FlatCMS',
            'lang_label' => 'Idioma',
            'heading' => 'Extraer paquete FlatCMS',
            'subtitle' => 'Inicia la extracción con barra de progreso y continúa automáticamente al instalador.',
            'already_extracted' => 'FlatCMS ya parece extraído en este directorio.',
            'continue_install' => 'Continuar instalación',
            'start_extraction' => 'Iniciar extracción',
            'refresh' => 'Actualizar',
            'status_ready' => 'Listo para extraer',
            'status_initializing' => 'Inicializando…',
            'status_running' => 'Extracción en curso…',
            'status_done' => 'Extracción finalizada',
            'status_error' => 'Error',
            'check_php' => 'Versión PHP >= 8.1',
            'check_php_detail' => 'Versión detectada: {version}',
            'check_zip' => 'Extensión ZipArchive',
            'check_archive' => 'Archivo flatcms.zip',
            'check_write' => 'Directorio de destino con escritura',
            'detail_available' => 'Disponible',
            'detail_unavailable' => 'No disponible',
            'archive_present' => 'Archivo detectado',
            'archive_missing' => 'Archivo ausente',
            'write_ok' => 'Escritura autorizada',
            'write_ko' => 'Escritura denegada',
            'source_label' => 'Origen',
            'batch_label' => 'Tamaño de lote',
            'files_label' => 'archivos',
            'cleanup_notice' => 'Al finalizar la extracción, index.html, unpack.php y flatcms.zip se eliminan automáticamente.',
            'csrf_invalid' => 'Token de seguridad inválido.',
            'precheck_invalid' => 'Prechecks inválidos. Corrige los errores e inténtalo de nuevo.',
            'json_running' => 'Extracción en curso…',
            'json_done' => 'Extracción finalizada. Redirigiendo al instalador…',
            'json_parse_error' => 'Respuesta del servidor inválida.',
            'json_interrupted' => 'Extracción interrumpida.',
            'json_unknown_error' => 'Error desconocido.',
            'zip_default' => 'Error desconocido al leer el archivo.',
            'zip_exists' => 'El archivo ya existe.',
            'zip_incons' => 'Archivo ZIP inválido o corrupto.',
            'zip_inval' => 'Argumento ZIP inválido.',
            'zip_memory' => 'Memoria insuficiente al abrir ZIP.',
            'zip_noent' => 'No se encontró flatcms.zip.',
            'zip_nozip' => 'flatcms.zip es inválido.',
            'zip_open' => 'No se puede abrir flatcms.zip.',
            'zip_read' => 'Error de lectura ZIP.',
            'zip_seek' => 'Error de posicionamiento ZIP.',
            'invalid_zip_entry' => 'Entrada ZIP no autorizada: {entry}',
            'extract_entry_error' => 'Error de extracción: {entry}',
            'flatten_root_missing' => 'No se puede localizar la carpeta flatcms extraída.',
            'flatten_create_dir_fail' => 'No se puede crear la carpeta de destino.',
            'flatten_target_exists' => 'Ya existe un archivo: {entry}',
            'flatten_move_fail' => 'No se puede mover el archivo: {entry}',
        ],
        'de' => [
            'page_title' => 'FlatCMS - Extraktion',
            'chip_label' => 'FlatCMS Bereitstellung',
            'lang_label' => 'Sprache',
            'heading' => 'FlatCMS-Paket extrahieren',
            'subtitle' => 'Starten Sie die Extraktion mit Fortschrittsbalken und wechseln Sie automatisch zum Installer.',
            'already_extracted' => 'FlatCMS scheint in diesem Verzeichnis bereits extrahiert zu sein.',
            'continue_install' => 'Installation fortsetzen',
            'start_extraction' => 'Extraktion starten',
            'refresh' => 'Aktualisieren',
            'status_ready' => 'Bereit zur Extraktion',
            'status_initializing' => 'Initialisierung…',
            'status_running' => 'Extraktion läuft…',
            'status_done' => 'Extraktion abgeschlossen',
            'status_error' => 'Fehler',
            'check_php' => 'PHP-Version >= 8.1',
            'check_php_detail' => 'Erkannte Version: {version}',
            'check_zip' => 'ZipArchive-Erweiterung',
            'check_archive' => 'flatcms.zip-Archiv',
            'check_write' => 'Zielordner ist beschreibbar',
            'detail_available' => 'Verfügbar',
            'detail_unavailable' => 'Nicht verfügbar',
            'archive_present' => 'Archiv gefunden',
            'archive_missing' => 'Archiv fehlt',
            'write_ok' => 'Schreibzugriff erlaubt',
            'write_ko' => 'Schreibzugriff verweigert',
            'source_label' => 'Quelle',
            'batch_label' => 'Batch-Größe',
            'files_label' => 'Dateien',
            'cleanup_notice' => 'Nach der Extraktion werden index.html, unpack.php und flatcms.zip automatisch gelöscht.',
            'csrf_invalid' => 'Ungültiges Sicherheitstoken.',
            'precheck_invalid' => 'Vorabprüfungen fehlgeschlagen. Bitte korrigieren und erneut versuchen.',
            'json_running' => 'Extraktion läuft…',
            'json_done' => 'Extraktion abgeschlossen. Weiterleitung zum Installer…',
            'json_parse_error' => 'Ungültige Serverantwort.',
            'json_interrupted' => 'Extraktion unterbrochen.',
            'json_unknown_error' => 'Unbekannter Fehler.',
            'zip_default' => 'Unbekannter Fehler beim Lesen des Archivs.',
            'zip_exists' => 'Datei existiert bereits.',
            'zip_incons' => 'ZIP-Archiv ist ungültig oder beschädigt.',
            'zip_inval' => 'Ungültiges ZIP-Argument.',
            'zip_memory' => 'Zu wenig Speicher beim Öffnen von ZIP.',
            'zip_noent' => 'flatcms.zip wurde nicht gefunden.',
            'zip_nozip' => 'flatcms.zip ist ungültig.',
            'zip_open' => 'flatcms.zip kann nicht geöffnet werden.',
            'zip_read' => 'ZIP-Lesefehler.',
            'zip_seek' => 'ZIP-Positionierungsfehler.',
            'invalid_zip_entry' => 'Nicht erlaubter ZIP-Eintrag: {entry}',
            'extract_entry_error' => 'Extraktionsfehler: {entry}',
            'flatten_root_missing' => 'Extrahiertes flatcms-Verzeichnis konnte nicht gefunden werden.',
            'flatten_create_dir_fail' => 'Zielverzeichnis kann nicht erstellt werden.',
            'flatten_target_exists' => 'Datei existiert bereits: {entry}',
            'flatten_move_fail' => 'Datei kann nicht verschoben werden: {entry}',
        ],
        'it' => [
            'page_title' => 'FlatCMS - Estrazione',
            'chip_label' => 'Distribuzione FlatCMS',
            'lang_label' => 'Lingua',
            'heading' => 'Estrai pacchetto FlatCMS',
            'subtitle' => 'Avvia l’estrazione con barra di avanzamento e continua automaticamente all’installer.',
            'already_extracted' => 'FlatCMS sembra già estratto in questa cartella.',
            'continue_install' => 'Continua installazione',
            'start_extraction' => 'Avvia estrazione',
            'refresh' => 'Aggiorna',
            'status_ready' => 'Pronto per estrazione',
            'status_initializing' => 'Inizializzazione…',
            'status_running' => 'Estrazione in corso…',
            'status_done' => 'Estrazione completata',
            'status_error' => 'Errore',
            'check_php' => 'Versione PHP >= 8.1',
            'check_php_detail' => 'Versione rilevata: {version}',
            'check_zip' => 'Estensione ZipArchive',
            'check_archive' => 'Archivio flatcms.zip',
            'check_write' => 'Cartella di destinazione scrivibile',
            'detail_available' => 'Disponibile',
            'detail_unavailable' => 'Non disponibile',
            'archive_present' => 'Archivio rilevato',
            'archive_missing' => 'Archivio assente',
            'write_ok' => 'Scrittura autorizzata',
            'write_ko' => 'Scrittura negata',
            'source_label' => 'Sorgente',
            'batch_label' => 'Dimensione batch',
            'files_label' => 'file',
            'cleanup_notice' => 'Al termine dell’estrazione, index.html, unpack.php e flatcms.zip vengono eliminati automaticamente.',
            'csrf_invalid' => 'Token di sicurezza non valido.',
            'precheck_invalid' => 'Precheck non validi. Correggi gli errori e riprova.',
            'json_running' => 'Estrazione in corso…',
            'json_done' => 'Estrazione completata. Reindirizzamento all’installer…',
            'json_parse_error' => 'Risposta server non valida.',
            'json_interrupted' => 'Estrazione interrotta.',
            'json_unknown_error' => 'Errore sconosciuto.',
            'zip_default' => 'Errore sconosciuto durante la lettura dell’archivio.',
            'zip_exists' => 'Il file esiste già.',
            'zip_incons' => 'Archivio ZIP non valido o corrotto.',
            'zip_inval' => 'Argomento ZIP non valido.',
            'zip_memory' => 'Memoria insufficiente durante apertura ZIP.',
            'zip_noent' => 'Impossibile trovare flatcms.zip.',
            'zip_nozip' => 'flatcms.zip non valido.',
            'zip_open' => 'Impossibile aprire flatcms.zip.',
            'zip_read' => 'Errore lettura ZIP.',
            'zip_seek' => 'Errore posizionamento ZIP.',
            'invalid_zip_entry' => 'Voce ZIP non autorizzata: {entry}',
            'extract_entry_error' => 'Errore estrazione: {entry}',
            'flatten_root_missing' => 'Impossibile localizzare la cartella flatcms estratta.',
            'flatten_create_dir_fail' => 'Impossibile creare la cartella di destinazione.',
            'flatten_target_exists' => 'Un file esiste già: {entry}',
            'flatten_move_fail' => 'Impossibile spostare il file: {entry}',
        ],
        'pt' => [
            'page_title' => 'FlatCMS - Extração',
            'chip_label' => 'Implantação FlatCMS',
            'lang_label' => 'Idioma',
            'heading' => 'Extrair pacote FlatCMS',
            'subtitle' => 'Inicie a extração com barra de progresso e continue automaticamente para o instalador.',
            'already_extracted' => 'O FlatCMS já parece extraído neste diretório.',
            'continue_install' => 'Continuar instalação',
            'start_extraction' => 'Iniciar extração',
            'refresh' => 'Atualizar',
            'status_ready' => 'Pronto para extrair',
            'status_initializing' => 'Inicializando…',
            'status_running' => 'Extração em andamento…',
            'status_done' => 'Extração concluída',
            'status_error' => 'Erro',
            'check_php' => 'Versão PHP >= 8.1',
            'check_php_detail' => 'Versão detectada: {version}',
            'check_zip' => 'Extensão ZipArchive',
            'check_archive' => 'Arquivo flatcms.zip',
            'check_write' => 'Diretório de destino gravável',
            'detail_available' => 'Disponível',
            'detail_unavailable' => 'Indisponível',
            'archive_present' => 'Arquivo detectado',
            'archive_missing' => 'Arquivo ausente',
            'write_ok' => 'Gravação autorizada',
            'write_ko' => 'Gravação negada',
            'source_label' => 'Origem',
            'batch_label' => 'Tamanho do lote',
            'files_label' => 'arquivos',
            'cleanup_notice' => 'Ao final da extração, index.html, unpack.php e flatcms.zip são removidos automaticamente.',
            'csrf_invalid' => 'Token de segurança inválido.',
            'precheck_invalid' => 'Pré-verificações inválidas. Corrija os erros e tente novamente.',
            'json_running' => 'Extração em andamento…',
            'json_done' => 'Extração concluída. Redirecionando para o instalador…',
            'json_parse_error' => 'Resposta do servidor inválida.',
            'json_interrupted' => 'Extração interrompida.',
            'json_unknown_error' => 'Erro desconhecido.',
            'zip_default' => 'Erro desconhecido ao ler o arquivo.',
            'zip_exists' => 'O arquivo já existe.',
            'zip_incons' => 'Arquivo ZIP inválido ou corrompido.',
            'zip_inval' => 'Argumento ZIP inválido.',
            'zip_memory' => 'Memória insuficiente ao abrir ZIP.',
            'zip_noent' => 'Não foi possível encontrar flatcms.zip.',
            'zip_nozip' => 'flatcms.zip é inválido.',
            'zip_open' => 'Não foi possível abrir flatcms.zip.',
            'zip_read' => 'Erro de leitura ZIP.',
            'zip_seek' => 'Erro de posicionamento ZIP.',
            'invalid_zip_entry' => 'Entrada ZIP não autorizada: {entry}',
            'extract_entry_error' => 'Erro de extração: {entry}',
            'flatten_root_missing' => 'Não foi possível localizar a pasta flatcms extraída.',
            'flatten_create_dir_fail' => 'Não foi possível criar o diretório de destino.',
            'flatten_target_exists' => 'Um arquivo já existe: {entry}',
            'flatten_move_fail' => 'Não foi possível mover o arquivo: {entry}',
        ],
    ];

    $lang = normalize_lang($lang);
    if ($lang === '' || !isset($messages[$lang])) {
        $lang = 'fr';
    }
    $dictionary = $messages[$lang];
    $text = $dictionary[$key] ?? ($messages['en'][$key] ?? $key);

    if ($replace !== []) {
        $text = strtr($text, $replace);
    }

    return $text;
}

$lang = resolve_lang();

/**
 * @param array<string, mixed> $payload
 */
function json_response(array $payload): void
{
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function next_install_href(string $lang): string
{
    $query = http_build_query([
        'step' => 1,
        'lang' => $lang,
    ]);

    return 'index.php?' . $query;
}

function runtime_extracted(): bool
{
    return !is_file(__DIR__ . '/flatcms.zip')
        && is_file(__DIR__ . '/index.php')
        && is_file(__DIR__ . '/public/index.php');
}

function zip_error_message(int $errorCode, string $lang): string
{
    $errors = [
        ZipArchive::ER_EXISTS => 'zip_exists',
        ZipArchive::ER_INCONS => 'zip_incons',
        ZipArchive::ER_INVAL => 'zip_inval',
        ZipArchive::ER_MEMORY => 'zip_memory',
        ZipArchive::ER_NOENT => 'zip_noent',
        ZipArchive::ER_NOZIP => 'zip_nozip',
        ZipArchive::ER_OPEN => 'zip_open',
        ZipArchive::ER_READ => 'zip_read',
        ZipArchive::ER_SEEK => 'zip_seek',
    ];

    $key = $errors[$errorCode] ?? 'zip_default';

    return tr($lang, $key);
}

/**
 * @return array{label:string, ok:bool, detail:string, required:bool}
 */
function build_check(string $label, bool $ok, string $detail, bool $required = true): array
{
    return [
        'label' => $label,
        'ok' => $ok,
        'detail' => $detail,
        'required' => $required,
    ];
}

/**
 * @return array<int, array{label:string, ok:bool, detail:string, required:bool}>
 */
function preflight_checks(string $zipPath, string $lang): array
{
    $hasZip = class_exists(ZipArchive::class);
    $hasArchive = is_file($zipPath);
    $writable = is_writable(__DIR__);

    return [
        build_check(
            tr($lang, 'check_php'),
            PHP_VERSION_ID >= MIN_PHP_VERSION_ID,
            tr($lang, 'check_php_detail', ['{version}' => PHP_VERSION])
        ),
        build_check(
            tr($lang, 'check_zip'),
            $hasZip,
            $hasZip ? tr($lang, 'detail_available') : tr($lang, 'detail_unavailable')
        ),
        build_check(
            tr($lang, 'check_archive'),
            $hasArchive,
            $hasArchive ? tr($lang, 'archive_present') : tr($lang, 'archive_missing')
        ),
        build_check(
            tr($lang, 'check_write'),
            $writable,
            $writable ? tr($lang, 'write_ok') : tr($lang, 'write_ko')
        ),
    ];
}

function has_blocking_check_error(array $checks): bool
{
    foreach ($checks as $check) {
        if (($check['required'] ?? false) && !($check['ok'] ?? false)) {
            return true;
        }
    }

    return false;
}

function is_safe_zip_entry(string $entry): bool
{
    $entry = str_replace('\\', '/', $entry);
    if ($entry === '' || str_starts_with($entry, '/')) {
        return false;
    }
    if (preg_match('/^[A-Za-z]:\//', $entry) === 1) {
        return false;
    }
    if (str_contains($entry, '../') || str_contains($entry, '/..') || str_starts_with($entry, '..')) {
        return false;
    }

    return true;
}

/**
 * @throws RuntimeException
 */
function flatten_legacy_root(string $lang): void
{
    $legacyRoot = __DIR__ . '/flatcms';
    if (!is_dir($legacyRoot)) {
        return;
    }

    $legacyRootReal = realpath($legacyRoot);
    if ($legacyRootReal === false) {
        throw new RuntimeException(tr($lang, 'flatten_root_missing'));
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($legacyRootReal, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $sourcePath = $item->getPathname();
        $relative = ltrim(str_replace('\\', '/', substr($sourcePath, strlen($legacyRootReal))), '/');
        if ($relative === '') {
            continue;
        }

        $targetPath = __DIR__ . '/' . $relative;

        if ($item->isDir()) {
            if (!is_dir($targetPath) && !@mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                throw new RuntimeException(tr($lang, 'flatten_create_dir_fail'));
            }
            @rmdir($sourcePath);
            continue;
        }

        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException(tr($lang, 'flatten_create_dir_fail'));
        }

        if (file_exists($targetPath)) {
            throw new RuntimeException(tr($lang, 'flatten_target_exists', ['{entry}' => $relative]));
        }

        if (!@rename($sourcePath, $targetPath)) {
            if (!@copy($sourcePath, $targetPath) || !@unlink($sourcePath)) {
                throw new RuntimeException(tr($lang, 'flatten_move_fail', ['{entry}' => $relative]));
            }
        }
    }

    @rmdir($legacyRootReal);
}

function schedule_cleanup_after_success(): void
{
    @unlink(__DIR__ . '/index.html');
    @unlink(__DIR__ . '/flatcms.zip');

    $self = __FILE__;
    register_shutdown_function(static function () use ($self): void {
        @unlink($self);
    });
}

if (!isset($_SESSION['flatcms_unpack_token']) || !is_string($_SESSION['flatcms_unpack_token'])) {
    try {
        $_SESSION['flatcms_unpack_token'] = bin2hex(random_bytes(24));
    } catch (Throwable) {
        $_SESSION['flatcms_unpack_token'] = sha1((string) mt_rand() . microtime(true));
    }
}
$csrfToken = (string) $_SESSION['flatcms_unpack_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'init') {
    $checks = preflight_checks($zipPath, $lang);
    $blocking = has_blocking_check_error($checks);
    $alreadyExtracted = runtime_extracted();

    json_response([
        'success' => !$blocking,
        'can_extract' => !$blocking,
        'already_extracted' => $alreadyExtracted,
        'token' => $csrfToken,
        'next' => next_install_href($lang),
        'checks' => $checks,
        'message' => $blocking ? tr($lang, 'precheck_invalid') : tr($lang, 'status_ready'),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'extract_batch') {
    $token = (string) ($_POST['token'] ?? '');
    if ($token === '' || !hash_equals($csrfToken, $token)) {
        json_response([
            'success' => false,
            'error' => tr($lang, 'csrf_invalid'),
            'done' => false,
        ]);
    }

    $checks = preflight_checks($zipPath, $lang);
    if (has_blocking_check_error($checks)) {
        json_response([
            'success' => false,
            'error' => tr($lang, 'precheck_invalid'),
            'done' => false,
        ]);
    }

    $start = max(0, (int) ($_POST['start'] ?? 0));
    $zip = new ZipArchive();
    $openStatus = $zip->open($zipPath);
    if ($openStatus !== true) {
        json_response([
            'success' => false,
            'error' => zip_error_message((int) $openStatus, $lang),
            'done' => false,
        ]);
    }

    $numFiles = (int) $zip->numFiles;
    $lastId = min($numFiles, $start + BATCH_SIZE);

    try {
        for ($id = $start; $id < $lastId; ++$id) {
            $entry = (string) $zip->getNameIndex($id);
            if ($entry === '') {
                continue;
            }
            if (!is_safe_zip_entry($entry)) {
                throw new RuntimeException(tr($lang, 'invalid_zip_entry', ['{entry}' => $entry]));
            }
            if (!$zip->extractTo(__DIR__, [$entry])) {
                throw new RuntimeException(tr($lang, 'extract_entry_error', ['{entry}' => $entry]));
            }
        }
    } catch (Throwable $e) {
        $zip->close();
        json_response([
            'success' => false,
            'error' => $e->getMessage(),
            'done' => false,
            'num_files' => $numFiles,
            'last_id' => $lastId,
        ]);
    }

    $zip->close();

    if ($lastId >= $numFiles) {
        try {
            flatten_legacy_root($lang);
        } catch (Throwable $e) {
            json_response([
                'success' => false,
                'error' => $e->getMessage(),
                'done' => false,
            ]);
        }

        @chmod('admin/index.php', 0644);
        @chmod('index.php', 0644);

        schedule_cleanup_after_success();

        json_response([
            'success' => true,
            'done' => true,
            'progress' => 100,
            'num_files' => $numFiles,
            'last_id' => $lastId,
            'next' => next_install_href($lang),
            'message' => tr($lang, 'json_done'),
        ]);
    }

    json_response([
        'success' => true,
        'done' => false,
        'progress' => (int) round(($lastId / max(1, $numFiles)) * 100),
        'num_files' => $numFiles,
        'last_id' => $lastId,
        'next_start' => $lastId,
        'message' => tr($lang, 'json_running'),
    ]);
}

$checks = preflight_checks($zipPath, $lang);
$hasBlockingError = has_blocking_check_error($checks);
$alreadyExtracted = runtime_extracted();
$nextHref = next_install_href($lang);
$autorun = !$hasBlockingError && !$alreadyExtracted && (string) ($_GET['autorun'] ?? '') === '1';
$clientConfig = [
    'canRun' => !$hasBlockingError,
    'autoRun' => $autorun,
    'token' => $csrfToken,
    'lang' => $lang,
    'nextHref' => $nextHref,
    'statusInit' => tr($lang, 'status_initializing'),
    'statusRun' => tr($lang, 'status_running'),
    'statusDone' => tr($lang, 'status_done'),
    'statusError' => tr($lang, 'status_error'),
    'parseError' => tr($lang, 'json_parse_error'),
    'interruptedError' => tr($lang, 'json_interrupted'),
    'unknownError' => tr($lang, 'json_unknown_error'),
];
$clientConfigJson = json_encode($clientConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!is_string($clientConfigJson)) {
    $clientConfigJson = '{}';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(tr($lang, 'page_title'), ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="robots" content="noindex,nofollow">
    <style>
:root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --success: #10b981;
    --danger: #ef4444;
    --bg-a: #0f172a;
    --bg-b: #1e293b;
    --text: #f8fafc;
    --muted: #94a3b8;
    --line: #334155;
    --card: rgba(15, 23, 42, 0.84);
    --shadow: 0 28px 70px rgba(15, 23, 42, 0.5);
}
* { box-sizing: border-box; }
html, body {
    margin: 0;
    min-height: 100%;
    font-family: "Inter", "Segoe UI", Roboto, Arial, sans-serif;
    color: var(--text);
    background:
        radial-gradient(940px 620px at 10% 8%, rgba(99, 102, 241, 0.2), transparent 56%),
        radial-gradient(760px 540px at 88% 86%, rgba(129, 140, 248, 0.15), transparent 58%),
        linear-gradient(148deg, var(--bg-a), var(--bg-b));
}
.unpack-wrap { min-height: 100vh; display: grid; place-items: center; padding: 22px; }
.unpack-card {
    width: min(920px, 100%);
    border: 1px solid color-mix(in srgb, var(--line) 78%, transparent);
    border-radius: 24px;
    background: var(--card);
    box-shadow: var(--shadow);
    backdrop-filter: blur(14px);
    overflow: hidden;
}
.unpack-head {
    padding: 28px 30px 18px;
    border-bottom: 1px solid color-mix(in srgb, var(--line) 76%, transparent);
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.4), rgba(15, 23, 42, 0.06));
}
.unpack-head-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.unpack-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #cbd5e1;
    border: 1px solid color-mix(in srgb, var(--line) 68%, #64748b 32%);
    border-radius: 999px;
    padding: 6px 12px;
    background: rgba(15, 23, 42, 0.48);
}
.unpack-chip::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: var(--success);
    box-shadow: 0 0 0 0 rgba(16, 185, 129, .65);
    animation: pulse 1.8s infinite;
}
.unpack-lang {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #cbd5e1;
    font-size: 12px;
    letter-spacing: .03em;
    text-transform: uppercase;
}
.unpack-lang select {
    border: 1px solid rgba(148, 163, 184, .35);
    border-radius: 9px;
    background: rgba(30, 41, 59, .92);
    color: #f8fafc;
    font-size: 13px;
    padding: 6px 9px;
    min-width: 74px;
    cursor: pointer;
}
.unpack-head h1 {
    margin: 0 0 8px;
    font-size: clamp(28px, 4vw, 40px);
    line-height: 1.08;
    letter-spacing: -.02em;
}
.unpack-head p {
    margin: 0;
    color: var(--muted);
    font-size: 16px;
    line-height: 1.58;
}
.unpack-body {
    display: grid;
    gap: 18px;
    padding: 24px 30px 30px;
}
.check-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
.check-item {
    display: grid;
    grid-template-columns: 24px 1fr;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px solid color-mix(in srgb, var(--line) 70%, transparent);
    background: rgba(15, 23, 42, 0.35);
}
.check-dot { width: 14px; height: 14px; margin-top: 4px; border-radius: 999px; }
.check-dot.ok { background: var(--success); box-shadow: 0 0 0 0 rgba(16, 185, 129, .65); animation: pulse 1.7s infinite; }
.check-dot.ko { background: var(--danger); }
.check-title { margin: 0 0 2px 0; font-weight: 700; color: var(--text); font-size: 14px; }
.check-detail { margin: 0; color: var(--muted); font-size: 13px; }
.progress-wrap {
    border: 1px solid color-mix(in srgb, var(--line) 70%, transparent);
    border-radius: 12px;
    padding: 12px;
    background: rgba(15, 23, 42, 0.32);
}
.progress-meta {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 13px;
    color: #cbd5e1;
}
.progress-bar { width: 100%; height: 12px; border-radius: 999px; background: rgba(148, 163, 184, 0.2); overflow: hidden; }
.progress-current {
    width: 0%;
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #818cf8, var(--primary-dark));
    transition: width 220ms ease;
}
.actions { display: flex; flex-wrap: wrap; gap: 12px; }
.is-hidden { display: none !important; }
.btn {
    appearance: none;
    border: 0;
    border-radius: 12px;
    padding: 12px 18px;
    font-size: 15px;
    font-weight: 600;
    line-height: 1;
    text-decoration: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
}
.btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }
.btn-primary { color: #fff; background: linear-gradient(145deg, #818cf8, var(--primary-dark)); box-shadow: 0 14px 28px rgba(79, 70, 229, .34); }
.btn-primary:hover { transform: translateY(-1px); background: linear-gradient(145deg, #8f97fb, #4338ca); }
.btn-ghost {
    color: #cbd5e1;
    background: rgba(15, 23, 42, .56);
    border: 1px solid color-mix(in srgb, var(--line) 70%, #64748b 30%);
}
.btn-ghost:hover { transform: translateY(-1px); background: rgba(30, 41, 59, .82); }
.alert {
    border-radius: 12px;
    padding: 12px 14px;
    font-size: 14px;
    line-height: 1.5;
    border: 1px solid transparent;
    display: none;
}
.alert.show { display: block; }
.alert.error { color: #fecaca; background: rgba(239, 68, 68, .15); border-color: rgba(239, 68, 68, .4); }
.alert.success { color: #bbf7d0; background: rgba(16, 185, 129, .15); border-color: rgba(16, 185, 129, .42); }
.meta {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
    font-size: 12px;
    line-height: 1.6;
    color: var(--muted);
}
.note {
    margin: 0;
    border: 1px dashed color-mix(in srgb, var(--line) 70%, #64748b 30%);
    border-radius: 12px;
    padding: 11px 13px;
    font-size: 13px;
    line-height: 1.55;
    color: var(--muted);
    background: rgba(15, 23, 42, .34);
}
@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, .65); }
    70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
@media (max-width: 760px) {
    .unpack-head,
    .unpack-body {
        padding-left: 18px;
        padding-right: 18px;
    }
    .unpack-head-top {
        flex-direction: column;
        align-items: flex-start;
    }
    .actions { flex-direction: column; }
    .btn { width: 100%; }
}
    </style>
</head>
<body>
<main class="unpack-wrap">
    <section class="unpack-card">
        <header class="unpack-head">
            <div class="unpack-head-top">
                <div class="unpack-chip"><?= htmlspecialchars(tr($lang, 'chip_label'), ENT_QUOTES, 'UTF-8') ?></div>
                <label class="unpack-lang">
                    <span><?= htmlspecialchars(tr($lang, 'lang_label'), ENT_QUOTES, 'UTF-8') ?></span>
                    <select id="langSwitch" aria-label="<?= htmlspecialchars(tr($lang, 'lang_label'), ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach (supported_langs() as $langItem): ?>
                            <option value="<?= htmlspecialchars($langItem, ENT_QUOTES, 'UTF-8') ?>" <?= $langItem === $lang ? 'selected' : '' ?>><?= strtoupper($langItem) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <h1><?= htmlspecialchars(tr($lang, 'heading'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars(tr($lang, 'subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
        </header>

        <div class="unpack-body">
            <?php if ($alreadyExtracted): ?>
                <div class="alert success show"><?= htmlspecialchars(tr($lang, 'already_extracted'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="actions">
                    <a class="btn btn-primary" href="<?= htmlspecialchars($nextHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(tr($lang, 'continue_install'), ENT_QUOTES, 'UTF-8') ?></a>
                    <a class="btn btn-ghost" href="./?lang=<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(tr($lang, 'refresh'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            <?php else: ?>
                <ul class="check-list">
                    <?php foreach ($checks as $check): ?>
                        <li class="check-item">
                            <div class="check-dot <?= $check['ok'] ? 'ok' : 'ko' ?>"></div>
                            <div>
                                <p class="check-title"><?= htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="check-detail"><?= htmlspecialchars($check['detail'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="progress-wrap">
                    <div class="progress-meta">
                        <span id="progressText">0%</span>
                        <span id="statusText"><?= htmlspecialchars(tr($lang, 'status_ready'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="progress-bar" aria-hidden="true">
                        <div id="progressCurrent" class="progress-current"></div>
                    </div>
                </div>

                <div id="alertError" class="alert error"></div>
                <div id="alertSuccess" class="alert success"></div>

                <div class="actions">
                    <button id="startBtn" class="btn btn-primary" <?= $hasBlockingError ? 'disabled' : '' ?>><?= htmlspecialchars(tr($lang, 'start_extraction'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a class="btn btn-ghost" href="./?lang=<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(tr($lang, 'refresh'), ENT_QUOTES, 'UTF-8') ?></a>
                    <a id="continueBtn" class="btn btn-primary is-hidden" href="<?= htmlspecialchars($nextHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(tr($lang, 'continue_install'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>

                <p class="note"><?= htmlspecialchars(tr($lang, 'cleanup_notice'), ENT_QUOTES, 'UTF-8') ?></p>

                <div class="meta">
                    <?= htmlspecialchars(tr($lang, 'source_label'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($zipPath, ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars(tr($lang, 'batch_label'), ENT_QUOTES, 'UTF-8') ?>: <?= (int) BATCH_SIZE ?> <?= htmlspecialchars(tr($lang, 'files_label'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script type="application/json" id="unpack-config"><?= htmlspecialchars($clientConfigJson, ENT_QUOTES, 'UTF-8') ?></script>
<script>
(() => {
    'use strict';

    const configNode = document.getElementById('unpack-config');
    let config = {};

    if (configNode) {
        try {
            config = JSON.parse(configNode.textContent || '{}');
        } catch (error) {
            config = {};
        }
    }

    const canRun = config.canRun === true;
    const autoRun = config.autoRun === true;
    const token = typeof config.token === 'string' ? config.token : '';
    const lang = typeof config.lang === 'string' ? config.lang : 'fr';
    const nextHref = typeof config.nextHref === 'string' ? config.nextHref : './index.php?step=1';
    const statusInit = typeof config.statusInit === 'string' ? config.statusInit : '';
    const statusRun = typeof config.statusRun === 'string' ? config.statusRun : '';
    const statusDone = typeof config.statusDone === 'string' ? config.statusDone : '';
    const statusError = typeof config.statusError === 'string' ? config.statusError : '';
    const parseError = typeof config.parseError === 'string' ? config.parseError : '';
    const interruptedError = typeof config.interruptedError === 'string' ? config.interruptedError : '';
    const unknownError = typeof config.unknownError === 'string' ? config.unknownError : '';

    const langSwitch = document.getElementById('langSwitch');
    if (langSwitch) {
        langSwitch.addEventListener('change', () => {
            const nextLang = String(langSwitch.value || 'fr').toLowerCase().slice(0, 2);
            const url = new URL(window.location.href);
            url.searchParams.set('lang', nextLang);
            url.searchParams.delete('autorun');
            window.location.href = url.toString();
        });
    }

    const startBtn = document.getElementById('startBtn');
    const continueBtn = document.getElementById('continueBtn');
    const progressCurrent = document.getElementById('progressCurrent');
    const progressText = document.getElementById('progressText');
    const statusText = document.getElementById('statusText');
    const alertError = document.getElementById('alertError');
    const alertSuccess = document.getElementById('alertSuccess');

    if (!startBtn || !progressCurrent || !progressText || !statusText || !alertError || !alertSuccess) {
        return;
    }

    let running = false;

    function setProgress(percent) {
        const safe = Math.max(0, Math.min(100, Number(percent) || 0));
        progressCurrent.style.width = safe + '%';
        progressText.textContent = safe + '%';
    }

    function showError(message) {
        alertError.textContent = message;
        alertError.classList.add('show');
        alertSuccess.classList.remove('show');
        statusText.textContent = statusError;
    }

    function showSuccess(message) {
        alertSuccess.textContent = message;
        alertSuccess.classList.add('show');
        alertError.classList.remove('show');
    }

    async function extractBatch(start) {
        const form = new URLSearchParams();
        form.append('action', 'extract_batch');
        form.append('start', String(start));
        form.append('token', token);
        form.append('lang', lang);

        const response = await fetch('unpack.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: form.toString()
        });

        let data = null;
        try {
            data = await response.json();
        } catch (error) {
            throw new Error(parseError);
        }

        if (!data || data.success !== true) {
            throw new Error(data && data.error ? data.error : interruptedError);
        }

        if (typeof data.progress !== 'undefined') {
            setProgress(data.progress);
        }

        if (data.message) {
            statusText.textContent = data.message;
        } else {
            statusText.textContent = statusRun;
        }

        if (data.done === true) {
            setProgress(100);
            statusText.textContent = statusDone;
            showSuccess(data.message || statusDone);
            if (continueBtn) {
                continueBtn.classList.remove('is-hidden');
            }
            startBtn.disabled = true;
            setTimeout(() => {
                window.location.href = data.next || nextHref;
            }, 1200);
            return;
        }

        const nextStart = Number(data.next_start || 0);
        await extractBatch(nextStart);
    }

    async function runExtraction() {
        if (!canRun || running) {
            return;
        }

        running = true;
        startBtn.disabled = true;
        alertError.classList.remove('show');
        alertSuccess.classList.remove('show');
        statusText.textContent = statusInit;

        try {
            await extractBatch(0);
        } catch (error) {
            showError(error instanceof Error ? error.message : unknownError);
            startBtn.disabled = false;
            running = false;
        }
    }

    startBtn.addEventListener('click', runExtraction);

    if (autoRun && canRun) {
        setTimeout(runExtraction, 160);
    }
})();
</script>
</body>
</html>
