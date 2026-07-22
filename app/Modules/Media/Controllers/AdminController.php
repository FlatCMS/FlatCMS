<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Media\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Core\ModuleManager;
use App\Modules\Media\Models\MediaModel;
use App\Modules\Media\Services\MediaAiIndexService;
use App\Modules\Trash\Services\TrashService;
use App\Services\AI\Exceptions\AiConfigurationException;
use App\Services\AI\Exceptions\AiProviderException;
use Throwable;

class AdminController extends BaseController
{
    private MediaModel $mediaModel;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Media');
        $this->mediaModel = new MediaModel();
    }

    /**
     * Index - Vue des dossiers avec onglets
     */
    public function index(): void
    {
        if (!$this->authorize('media.view')) {
            return;
        }

        $stats = $this->mediaModel->getStats();
        $foldersConfig = $this->mediaModel->getAllFoldersConfig();
        $uploadDirectories = $this->mediaModel->scanUploadDirectories();
        
        $this->render('Media/Views/admin/index', [
            'pageTitle' => __('title', 'Media'),
            'stats' => $stats,
            'foldersConfig' => $foldersConfig,
            'uploadDirectories' => $uploadDirectories,
            'directoryTree' => $this->mediaModel->getDirectoryTree(),
            'totalFiles' => $this->mediaModel->getTotalFileCount(),
            'publicUrl' => url(''),
            'aiAgentEnabled' => $this->isAiIndexAvailable(),
        ], 'admin.main');
    }

    /**
     * Vue d'un dossier spécifique
     */
    public function folder(string $name): void
    {
        if (!$this->authorize('media.view')) {
            return;
        }

        $validFolders = array_keys(MediaModel::FOLDERS);
        
        if (!in_array($name, $validFolders)) {
            $this->session->flash('error', __('invalid_folder', 'Media'));
            $this->redirect(url('/admin/media'));
            return;
        }

        $files = $this->mediaModel->scanFolder($name);
        $stats = $this->mediaModel->getStats();
        $folderConfig = $this->mediaModel->getFolderConfig($name);
        $foldersConfig = $this->mediaModel->getAllFoldersConfig();
        
        $this->render('Media/Views/admin/folder', [
            'pageTitle' => __($name, 'Media'),
            'folder' => $name,
            'files' => $files,
            'stats' => $stats,
            'folderConfig' => $folderConfig,
            'foldersConfig' => $foldersConfig,
            'publicUrl' => url(''),
            'aiAgentEnabled' => $this->isAiIndexAvailable(),
        ], 'admin.main');
    }

    /**
     * Upload de fichier(s)
     */
    public function upload(): void
    {
        if (!$this->authorize('media.upload')) {
            return;
        }

        // Vérification CSRF manuelle pour AJAX
        $token = $this->request->input('_token') ?? $this->request->header('X-CSRF-TOKEN');
        
        if (!$token || !$this->session->verifyToken($token)) {
            if ($this->request->isAjax()) {
                json_error(__('csrf_error', 'Core'));
            }
            $this->session->flash('error', __('csrf_error', 'Core'));
            $this->back();
            return;
        }

        $folder = trim((string) $this->request->input('folder', ''));
        if ($folder === '' || !isset(MediaModel::FOLDERS[$folder])) {
            if ($this->request->isAjax()) {
                json_error(__('media_root_upload_forbidden', 'Media'));
            }
            $this->session->flash('error', __('media_root_upload_forbidden', 'Media'));
            $this->redirect(url('/admin/media'));
            return;
        }

        $context = (string) $this->request->input('media_context', '');
        $userId = $this->session->get('user_id', 1);
        
        // Gestion upload multiple
        $files = $_FILES['files'] ?? $_FILES['file'] ?? null;
        
        if (!$files) {
            if ($this->request->isAjax()) {
                json_error(__('no_file', 'Media'));
            }
            $this->session->flash('error', __('no_file', 'Media'));
            $this->redirect(url('/admin/media/folder/' . $folder));
            return;
        }

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        // Normaliser pour upload multiple
        if (is_array($files['name'])) {
            $fileCount = count($files['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                $result = $this->mediaModel->upload($file, $folder, $userId, $context);
                $results[] = [
                    'name' => $file['name'],
                    'success' => $result['success'],
                    'error' => $result['error'] ?? null,
                    'media' => $result['media'] ?? null
                ];

                if (!empty($result['success']) && !empty($result['media'])) {
                    hook_run('media.uploaded', $result['media']);
                }
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }
        } else {
            // Upload simple
            $result = $this->mediaModel->upload($files, $folder, $userId, $context);
            $results[] = [
                'name' => $files['name'],
                'success' => $result['success'],
                'error' => $result['error'] ?? null,
                'media' => $result['media'] ?? null
            ];

            if (!empty($result['success']) && !empty($result['media'])) {
                hook_run('media.uploaded', $result['media']);
            }
            
            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        if ($this->request->isAjax()) {
            json_response([
                'success' => $errorCount === 0,
                'message' => sprintf(__('upload_result', 'Media'), $successCount, $errorCount),
                'results' => $results,
                'successCount' => $successCount,
                'errorCount' => $errorCount
            ]);
        }

        if ($successCount > 0) {
            $this->session->flash('success', sprintf(__('upload_success_count', 'Media'), $successCount));
        }
        if ($errorCount > 0) {
            $this->session->flash('error', sprintf(__('upload_error_count', 'Media'), $errorCount));
        }

        $this->redirect(url('/admin/media/folder/' . $folder));
    }

    public function contextualize(): void
    {
        if (!$this->authorize('media.upload')) {
            return;
        }

        $token = $this->request->input('_token') ?? $this->request->header('X-CSRF-TOKEN');
        if (!$token || !$this->session->verifyToken($token)) {
            json_error(__('csrf_error', 'Core'));
        }

        $path = trim((string) $this->request->input('path', ''));
        if ($path === '') {
            json_error(__('media_not_found', 'Media'));
        }

        $folder = trim((string) $this->request->input('folder', 'images'));
        $context = (string) $this->request->input('media_context', '');
        $userId = $this->session->get('user_id', 1);
        $result = $this->mediaModel->contextualize($path, $folder, $userId, $context);

        if (empty($result['success']) || empty($result['media']) || !is_array($result['media'])) {
            $error = (string) ($result['error'] ?? 'media_not_found');
            $messageKey = in_array($error, ['invalid_folder', 'media_not_found'], true) ? $error : 'upload_invalid';
            json_error(__($messageKey, 'Media'));
        }

        if (!empty($result['created'])) {
            hook_run('media.uploaded', $result['media']);
        }

        json_response([
            'success' => true,
            'media' => $result['media'],
        ]);
    }

    public function createDirectory(): void
    {
        if (!$this->authorize('media.upload')) {
            return;
        }

        $token = $this->request->input('_token') ?? $this->request->header('X-CSRF-TOKEN');
        if (!$token || !$this->session->verifyToken($token)) {
            json_error(__('csrf_error', 'Core'));
        }

        $folder = trim((string) $this->request->input('folder', ''));
        if ($folder === '' || !isset(MediaModel::FOLDERS[$folder])) {
            json_error(__('media_root_directory_forbidden', 'Media'));
        }

        $context = trim((string) $this->request->input('context', ''));
        $result = $this->mediaModel->createDirectory($folder, $context);

        if (empty($result['success'])) {
            $error = (string) ($result['error'] ?? 'directory_create_error');
            $messageKey = in_array($error, ['invalid_folder', 'directory_invalid', 'directory_create_error'], true)
                ? $error
                : 'directory_create_error';
            json_error(__($messageKey, 'Media'));
        }

        json_response([
            'success' => true,
            'message' => __('directory_created', 'Media'),
            'directory' => $result['directory'] ?? null,
        ]);
    }

    /**
     * Suppression d'un média
     */
    public function delete(int $id): void
    {
        if (!$this->authorize('media.delete')) {
            return;
        }

        $token = $this->request->input('_token') ?? $this->request->header('X-CSRF-TOKEN');
        
        if (!$token || !$this->session->verifyToken($token)) {
            if ($this->request->isAjax()) {
                json_error(__('csrf_error', 'Core'));
            }
            $this->session->flash('error', __('csrf_error', 'Core'));
            $this->back();
            return;
        }

        $media = $this->mediaModel->find($id);

        if (!is_array($media)) {
            if ($this->request->isAjax()) {
                json_error(__('media_not_found', 'Media'));
            }
            $this->session->flash('error', __('media_not_found', 'Media'));
            $this->redirect(url('/admin/media'));
            return;
        }

        $folder = $media['folder'] ?? 'images';
        $trash = new TrashService();
        $archived = $trash->archiveMedia($media, $this->resolveDeletedBy());
        if (is_array($archived)) {
            hook_run('media.deleted', $media);
            if ($this->request->isAjax()) {
                json_success(__('delete_success', 'Media'));
            }
            $this->session->flash('success', __('delete_success', 'Media'));
        } else {
            if ($this->request->isAjax()) {
                json_error(__('delete_error', 'Media'));
            }
            $this->session->flash('error', __('delete_error', 'Media'));
        }

        $this->redirect(url('/admin/media/folder/' . $folder));
    }

    /**
     * Suppression par chemin (pour fichiers non indexés)
     */
    public function deletePath(): void
    {
        if (!$this->authorize('media.delete')) {
            return;
        }

        $token = $this->request->input('_token') ?? $this->request->header('X-CSRF-TOKEN');
        
        if (!$token || !$this->session->verifyToken($token)) {
            if ($this->request->isAjax()) {
                json_error(__('csrf_error', 'Core'));
            }
            $this->session->flash('error', __('csrf_error', 'Core'));
            $this->back();
            return;
        }

        $path = $this->request->input('path', '');
        $folder = explode('/', $path)[0] ?? 'images';
        if (!isset(MediaModel::FOLDERS[$folder])) {
            $folder = 'images';
        }

        $media = $this->mediaModel->findByPath($path);
        if (!is_array($media)) {
            // Not in repository — try directory deletion
            $deleted = $this->mediaModel->deleteDirectory($path);
            if ($deleted) {
                if ($this->request->isAjax()) {
                    json_success(__('delete_success', 'Media'));
                }
                $this->session->flash('success', __('delete_success', 'Media'));
                $this->redirect(url('/admin/media/folder/' . $folder));
                return;
            }
            if ($this->request->isAjax()) {
                json_error(__('media_not_found', 'Media'));
            }
            $this->session->flash('error', __('media_not_found', 'Media'));
            $this->redirect(url('/admin/media/folder/' . $folder));
            return;
        }

        $trash = new TrashService();
        $archived = $trash->archiveMedia($media, $this->resolveDeletedBy());
        if (!is_array($archived)) {
            if ($this->request->isAjax()) {
                json_error(__('delete_error', 'Media'));
            }
            $this->session->flash('error', __('delete_error', 'Media'));
            $this->redirect(url('/admin/media/folder/' . $folder));
            return;
        }

        hook_run('media.deleted', $media);

        if ($this->request->isAjax()) {
            json_success(__('delete_success', 'Media'));
        }
        
        $this->session->flash('success', __('delete_success', 'Media'));
        $this->redirect(url('/admin/media/folder/' . $folder));
    }

    public function batchDelete(): void
    {
        if (!$this->authorize('media.delete')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $folder = trim((string) $this->request->input('folder', 'images'));
        $paths = $this->normalizeBatchPaths($this->request->input('paths', []));
        if ($paths === []) {
            $this->session->flash('error', __('media_batch_no_selection', 'Media'));
            $this->redirect(url('/admin/media/folder/' . $folder));
            return;
        }

        $trash = new TrashService();
        $deletedBy = $this->resolveDeletedBy();
        $successCount = 0;
        $errorCount = 0;

        foreach ($paths as $path) {
            $media = $this->mediaModel->findByPath($path);
            if (!is_array($media)) {
                $errorCount++;
                continue;
            }

            $archived = $trash->archiveMedia($media, $deletedBy);
            if (!is_array($archived)) {
                $errorCount++;
                continue;
            }

            hook_run('media.deleted', $media);
            $successCount++;
        }

        if ($successCount > 0) {
            $this->session->flash('success', __('media_batch_delete_success', 'Media', ['count' => (string) $successCount]));
        }

        if ($errorCount > 0) {
            $this->session->flash('error', __('media_batch_delete_error', 'Media', ['count' => (string) $errorCount]));
        }

        $this->redirect(url('/admin/media/folder/' . $folder));
    }

    private function resolveDeletedBy(): string
    {
        $user = auth();
        if (!is_array($user)) {
            return '';
        }

        return trim((string) ($user['name'] ?? $user['email'] ?? ''));
    }

    /**
     * @param mixed $rawPaths
     * @return array<int, string>
     */
    private function normalizeBatchPaths(mixed $rawPaths): array
    {
        if (!is_array($rawPaths)) {
            return [];
        }

        $paths = [];
        foreach ($rawPaths as $rawPath) {
            if (!is_string($rawPath)) {
                continue;
            }

            $path = trim(str_replace('\\', '/', $rawPath), '/');
            if ($path === '') {
                continue;
            }

            $paths[$path] = $path;
        }

        return array_values($paths);
    }

    /**
     * Synchronisation des fichiers
     */
    public function sync(): void
    {
        if (!$this->authorize('media.delete')) {
            return;
        }

        $token = $this->request->input('_token') ?? $this->request->header('X-CSRF-TOKEN');
        
        if (!$token || !$this->session->verifyToken($token)) {
            if ($this->request->isAjax()) {
                json_error(__('csrf_error', 'Core'));
            }
            $this->session->flash('error', __('csrf_error', 'Core'));
            $this->back();
            return;
        }

        $result = $this->mediaModel->sync();
        hook_run('media.synced', $result);
        
        $message = sprintf(__('sync_result', 'Media'), $result['added'], $result['removed']);

        if ($this->request->isAjax()) {
            json_response([
                'success' => true,
                'message' => $message,
                'added' => $result['added'],
                'removed' => $result['removed']
            ]);
        }

        $this->session->flash('success', $message);
        $this->redirect(url('/admin/media'));
    }

    /**
     * Indexation IA des medias.
     */
    public function aiIndex(): void
    {
        if (!$this->authorize('media.upload')) {
            return;
        }

        if (!$this->isAiIndexAvailable()) {
            if ($this->request->isAjax()) {
                json_error(__('media_ai_module_disabled', 'Media'));
            }

            $this->session->flash('error', __('media_ai_module_disabled', 'Media'));
            $this->redirect(url('/admin/media'));
            return;
        }

        $token = $this->request->input('_token') ?? $this->request->header('X-CSRF-TOKEN');
        if (!$token || !$this->session->verifyToken($token)) {
            if ($this->request->isAjax()) {
                json_error(__('csrf_error', 'Core'));
            }
            $this->session->flash('error', __('csrf_error', 'Core'));
            $this->back();
            return;
        }

        $folder = trim((string) $this->request->input('folder', ''));
        $paths = $this->normalizeBatchPaths($this->request->input('paths', []));

        try {
            $service = new MediaAiIndexService($this->mediaModel);
            $result = $service->index($folder !== '' ? $folder : null, $paths);
        } catch (AiConfigurationException) {
            if ($this->request->isAjax()) {
                json_error(__('media_ai_not_configured', 'Media'));
            }
            $this->session->flash('error', __('media_ai_not_configured', 'Media'));
            $this->redirect(url('/admin/media'));
            return;
        } catch (AiProviderException $exception) {
            $message = trim($exception->getMessage());
            if ($message === '') {
                $message = __('media_ai_index_failed', 'Media');
            }

            if ($this->request->isAjax()) {
                json_response([
                    'success' => false,
                    'message' => $message,
                ]);
            }

            $this->session->flash('error', $message);
            $this->redirect(url('/admin/media'));
            return;
        } catch (Throwable $exception) {
            if ($this->request->isAjax()) {
                json_response([
                    'success' => false,
                    'message' => __('media_ai_index_failed', 'Media'),
                ]);
            }

            $this->session->flash('error', __('media_ai_index_failed', 'Media'));
            $this->redirect(url('/admin/media'));
            return;
        }

        $indexed = (int) ($result['indexed'] ?? 0);
        $skipped = (int) ($result['skipped'] ?? 0);
        $failed = (int) ($result['failed'] ?? 0);
        $message = ($indexed + $skipped + $failed) > 0
            ? __('media_ai_index_result', 'Media', [
                'indexed' => (string) $indexed,
                'skipped' => (string) $skipped,
                'failed' => (string) $failed,
            ])
            : __('media_ai_nothing_to_index', 'Media');

        if ($this->request->isAjax()) {
            json_response([
                'success' => true,
                'message' => $message,
                'indexed' => $indexed,
                'skipped' => $skipped,
                'failed' => $failed,
                'completed_paths' => $result['completed_paths'] ?? [],
                'failed_paths' => $result['failed_paths'] ?? [],
                'results' => $result['results'] ?? [],
            ]);
        }

        $this->session->flash('success', $message);
        $redirect = $folder !== '' ? url('/admin/media/folder/' . $folder) : url('/admin/media');
        $this->redirect($redirect);
    }

    private function isAiIndexAvailable(): bool
    {
        $manager = new ModuleManager([
            BASE_PATH . '/app/Modules',
            BASE_PATH . '/app/Extensions',
        ], BASE_PATH . '/data/modules.json');

        return $manager->isEnabled('AiAgent');
    }

    /**
     * API - Liste des fichiers d'un dossier (AJAX)
     */
    public function apiFiles(): void
    {
        if (!$this->authorize('media.view')) {
            return;
        }

        $folder = $this->request->input('folder', 'images');
        $context = trim((string) $this->request->input('context', ''));
        $files = $this->mediaModel->scanFolder($folder, $context);
        
        json_response([
            'success' => true,
            'folder' => $folder,
            'context' => $context,
            'files' => $files,
            'count' => count($files)
        ]);
    }

    /**
     * API - Liste des images uniquement (AJAX)
     */
    public function apiImages(): void
    {
        if (!$this->authorize('media.view')) {
            return;
        }

        $includeAvatars = (bool) $this->request->input('include_avatars', false);
        $context = trim((string) $this->request->input('context', ''));
        $images = $this->mediaModel->getImages($includeAvatars, $context);
        
        json_response([
            'success' => true,
            'folder' => 'images',
            'context' => $context,
            'files' => $images,
            'count' => count($images)
        ]);
    }

    public function apiDirectories(): void
    {
        if (!$this->authorize('media.view')) {
            return;
        }

        $folder = trim((string) $this->request->input('folder', 'images'));
        $context = trim((string) $this->request->input('context', ''));
        $allDirectories = $this->mediaModel->listDirectories($folder);

        $contextDepth = $context === '' ? 0 : count(explode('/', $context));
        $directories = array_values(array_filter($allDirectories, static function (array $dir) use ($contextDepth): bool {
            $depth = (int) ($dir['depth'] ?? 0);
            return $depth <= $contextDepth + 1;
        }));

        json_response([
            'success' => true,
            'folder' => $folder,
            'context' => $context,
            'directories' => $directories,
            'count' => count($directories),
        ]);
    }

    /**
     * API - Déplacer un fichier ou dossier
     */
    public function move(): void
    {
        if (!$this->authorize('media.edit')) {
            return;
        }

        $token = $this->request->input('_token') ?? $this->request->header('X-CSRF-TOKEN');
        if (!$token || !$this->session->verifyToken($token)) {
            json_error(__('csrf_error', 'Core'));
            return;
        }

        $folder = trim((string) $this->request->input('folder', 'images'));
        $context = trim((string) $this->request->input('context', ''));
        $itemName = trim((string) $this->request->input('item', ''));
        $targetContext = trim((string) $this->request->input('target', ''));
        $type = trim((string) $this->request->input('type', 'file'));

        if ($itemName === '') {
            json_error(__('media_move_invalid', 'Media'));
            return;
        }

        $result = $this->mediaModel->move($folder, $context, $itemName, $targetContext, $type);

        if ($result['success'] ?? false) {
            json_response(['success' => true, 'type' => $result['type'] ?? 'file']);
        } else {
            $errorKey = $result['error'] ?? 'move_failed';
            $errorMap = [
                'source_not_found' => __('media_move_not_found', 'Media'),
                'target_exists' => __('media_move_target_exists', 'Media'),
                'mkdir_failed' => __('media_move_mkdir_failed', 'Media'),
                'rename_failed' => __('media_move_failed', 'Media'),
                'source_not_directory' => __('media_move_invalid', 'Media'),
                'invalid_folder' => __('media_move_invalid', 'Media'),
                'invalid_name' => __('media_move_invalid', 'Media'),
                'invalid_type' => __('media_move_invalid', 'Media'),
            ];
            json_error($errorMap[$errorKey] ?? __('media_move_failed', 'Media'));
        }
    }

    /**
     * API - Détails d'un média (AJAX)
     */
    public function details(int $id): void
    {
        if (!$this->authorize('media.view')) {
            return;
        }

        $media = $this->mediaModel->find($id);
        
        if (!$media) {
            json_error(__('media_not_found', 'Media'));
        }
        
        json_response([
            'success' => true,
            'media' => $media
        ]);
    }

    /**
     * API - Statistiques (AJAX)
     */
    public function apiStats(): void
    {
        if (!$this->authorize('media.view')) {
            return;
        }

        $stats = $this->mediaModel->getStats();
        
        json_response([
            'success' => true,
            'stats' => $stats
        ]);
    }
}
