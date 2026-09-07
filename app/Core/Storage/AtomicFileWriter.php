<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Core\Storage;

final class AtomicFileWriter
{
    private StoragePathGuard $paths;
    private FileLockManager $locks;
    private int $directoryMode;
    private int $fileMode;

    public function __construct(
        string $root,
        FileLockManager $locks,
        int $directoryMode = 0755,
        int $fileMode = 0644
    ) {
        $this->paths = new StoragePathGuard($root, $directoryMode);
        $this->locks = $locks;
        $this->directoryMode = $directoryMode;
        $this->fileMode = $fileMode;
    }

    public function root(): string
    {
        return $this->paths->root();
    }

    public function resolvePath(string $path): string
    {
        return $this->paths->resolve($path);
    }

    public function relativePath(string $path): string
    {
        return $this->paths->relative($path);
    }

    public function ensureDirectory(string $path): string
    {
        return $this->paths->ensureDirectory($path, $this->directoryMode);
    }

    public function synchronized(string $path, callable $operation): mixed
    {
        $target = $this->paths->resolve($path);
        $scope = 'canonical-file:' . $this->paths->relative($target);

        return $this->locks->synchronized($scope, $operation);
    }

    public function write(string $path, string $contents, ?callable $validator = null): void
    {
        $this->synchronized($path, function () use ($path, $contents, $validator): void {
            $target = $this->paths->resolve($path);
            $this->paths->ensureDirectory(dirname($target), $this->directoryMode);
            $target = $this->paths->resolve($target);

            if (is_dir($target)) {
                throw new StorageException('Storage target is a directory: ' . $target);
            }

            $directory = dirname($target);
            $suffix = bin2hex(random_bytes(8));
            $temporary = $directory . '/.' . basename($target) . '.' . $suffix . '.tmp';
            $backup = $directory . '/.' . basename($target) . '.' . $suffix . '.bak';
            $hadTarget = is_file($target);
            $originalHash = $hadTarget ? hash_file('sha256', $target) : null;
            $mode = $hadTarget ? (fileperms($target) & 0777) : $this->fileMode;
            $backupCreated = false;
            $committed = false;

            try {
                $this->writeCompleteFile($temporary, $contents, $mode);

                if ($validator !== null) {
                    $validator($temporary, $contents);
                }

                if ($hadTarget) {
                    $this->copyCompleteFile($target, $backup, $mode);
                    $backupCreated = true;
                    if (!is_file($target) || hash_file('sha256', $target) !== $originalHash) {
                        throw new StorageException('Storage target changed outside its lock: ' . $target);
                    }
                } elseif (file_exists($target) || is_link($target)) {
                    throw new StorageException('Storage target appeared outside its lock: ' . $target);
                }

                if (!rename($temporary, $target)) {
                    throw new StorageException('Unable to atomically replace storage target: ' . $target);
                }

                $committed = true;
                $writtenHash = hash('sha256', $contents);
                if (!is_file($target) || hash_file('sha256', $target) !== $writtenHash) {
                    $this->restorePreviousGeneration($target, $backup, $backupCreated);
                    $backupCreated = false;
                    throw new StorageException('Storage verification failed after replacement: ' . $target);
                }

                if ($backupCreated && !unlink($backup)) {
                    throw new StorageException('Unable to remove storage recovery file: ' . $backup);
                }
                $backupCreated = false;
            } catch (StorageException $exception) {
                throw $exception;
            } catch (\Throwable $exception) {
                throw new StorageException('Atomic storage write failed for ' . $target . ': ' . $exception->getMessage(), 0, $exception);
            } finally {
                if (is_file($temporary) || is_link($temporary)) {
                    @unlink($temporary);
                }
                if ($backupCreated && !$committed && (is_file($backup) || is_link($backup))) {
                    @unlink($backup);
                }
            }
        });
    }

    /**
     * Replaces a text generation only if it still matches the expected source.
     */
    public function replaceIfContentsMatch(
        string $path,
        string $expectedContents,
        string $contents,
        ?callable $validator = null
    ): bool {
        return $this->synchronized($path, function () use ($path, $expectedContents, $contents, $validator): bool {
            $target = $this->paths->resolve($path);
            if (!is_file($target)) {
                if (file_exists($target) || is_link($target)) {
                    throw new StorageException('Storage replacement target is not a regular file: ' . $target);
                }

                return false;
            }

            $current = file_get_contents($target);
            if (!is_string($current)) {
                throw new StorageException('Unable to read storage replacement target: ' . $target);
            }
            if ($current !== $expectedContents) {
                return false;
            }

            $this->write($target, $contents, $validator);
            return true;
        });
    }

    public function delete(string $path): bool
    {
        return $this->synchronized($path, function () use ($path): bool {
            $target = $this->paths->resolve($path);
            if (!file_exists($target)) {
                return false;
            }
            if (!is_file($target)) {
                throw new StorageException('Storage deletion target is not a regular file: ' . $target);
            }
            if (!unlink($target)) {
                throw new StorageException('Unable to delete storage file: ' . $target);
            }

            return true;
        });
    }

    public function append(string $path, string $contents, ?callable $validator = null): void
    {
        if ($contents === '') {
            throw new StorageException('Storage append payload cannot be empty.');
        }

        $this->synchronized($path, function () use ($path, $contents, $validator): void {
            $target = $this->paths->resolve($path);
            if (file_exists($target) && !is_file($target)) {
                throw new StorageException('Storage append target is not a regular file: ' . $target);
            }

            $existing = '';
            if (is_file($target)) {
                $existing = file_get_contents($target);
                if (!is_string($existing)) {
                    throw new StorageException('Unable to read storage append target: ' . $target);
                }
            }

            // Replacing the complete generation keeps JSONL records recoverable after a crash.
            $this->write($target, $existing . $contents, $validator);
        });
    }

    private function writeCompleteFile(string $path, string $contents, int $mode): void
    {
        $handle = @fopen($path, 'xb');
        if (!is_resource($handle)) {
            throw new StorageException('Unable to create temporary storage file: ' . $path);
        }

        try {
            $length = strlen($contents);
            $offset = 0;
            while ($offset < $length) {
                $written = fwrite($handle, substr($contents, $offset));
                if (!is_int($written) || $written < 1) {
                    throw new StorageException('Short write detected for temporary storage file: ' . $path);
                }
                $offset += $written;
            }

            if (!fflush($handle)) {
                throw new StorageException('Unable to flush temporary storage file: ' . $path);
            }
            if (function_exists('fsync') && !fsync($handle)) {
                throw new StorageException('Unable to synchronize temporary storage file: ' . $path);
            }
        } finally {
            fclose($handle);
        }

        if (!chmod($path, $mode)) {
            throw new StorageException('Unable to set temporary storage file permissions: ' . $path);
        }
    }

    private function copyCompleteFile(string $source, string $destination, int $mode): void
    {
        $contents = file_get_contents($source);
        if (!is_string($contents)) {
            throw new StorageException('Unable to read previous storage generation: ' . $source);
        }

        $this->writeCompleteFile($destination, $contents, $mode);
    }

    private function restorePreviousGeneration(string $target, string $backup, bool $backupCreated): void
    {
        if (!$backupCreated) {
            if ((is_file($target) || is_link($target)) && !unlink($target)) {
                throw new StorageException('Unable to remove invalid storage generation: ' . $target);
            }
            return;
        }

        if (!rename($backup, $target)) {
            throw new StorageException('Unable to restore previous storage generation: ' . $target);
        }
    }
}
