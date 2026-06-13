<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Bootstrap;

final class Autoloader
{
    /**
     * Namespaces PSR-4 enregistrés
     * @var array<string, array<string>>
     */
    private static array $prefixes = [];

    /**
     * Fichiers helpers à charger automatiquement
     * @var array<string>
     */
    private static array $helperFiles = [
        'functions.php',
        'view.php',
        'forms.php',
        'cache.php',
        'validation.php',
        'json.php',
        'files.php'
    ];

    /**
     * Enregistrer l'autoloader PSR-4 et charger les helpers
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'loadClass'], true, true);
        self::loadHelpers();
    }

    /**
     * Ajouter un namespace PSR-4
     * 
     * @param string $prefix   Namespace avec trailing backslash (ex: "App\\")
     * @param string $baseDir  Chemin de base absolu (ex: "/var/www/app/")
     * @return void
     */
    public static function addNamespace(string $prefix, string $baseDir): void
    {
        // Normaliser le prefix avec trailing backslash
        $prefix = trim($prefix, '\\') . '\\';
        
        // Normaliser le chemin avec trailing slash
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Initialiser le tableau si nécessaire
        if (!isset(self::$prefixes[$prefix])) {
            self::$prefixes[$prefix] = [];
        }

        // Ajouter le chemin de base pour ce namespace
        array_push(self::$prefixes[$prefix], $baseDir);
    }

    /**
     * Charger une classe selon la spécification PSR-4
     * 
     * @param string $class Nom complet de la classe (ex: "App\Core\Application")
     * @return void
     */
    private static function loadClass(string $class): void
    {
        // Parcourir chaque namespace enregistré
        foreach (self::$prefixes as $prefix => $baseDirs) {
            $len = strlen($prefix);

            // La classe n'appartient pas à ce namespace
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }

            // Obtenir le nom relatif de la classe
            $relativeClass = substr($class, $len);

            // Parcourir les chemins de base pour ce namespace
            foreach ($baseDirs as $baseDir) {
                // Construire le chemin du fichier
                $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

                // Si le fichier existe, le charger
                if (self::requireFile($file)) {
                    return;
                }
            }
        }
    }

    /**
     * Charger les fichiers helpers globaux
     * 
     * @return void
     */
    private static function loadHelpers(): void
    {
        $helpersDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR;

        foreach (self::$helperFiles as $helperFile) {
            $file = $helpersDir . $helperFile;
            
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Charger un fichier PHP s'il existe
     * 
     * @param string $file Chemin absolu du fichier
     * @return bool True si le fichier a été chargé, false sinon
     */
    private static function requireFile(string $file): bool
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }

        return false;
    }

    /**
     * Obtenir tous les namespaces enregistrés (debug)
     * 
     * @return array<string, array<string>>
     */
    public static function getPrefixes(): array
    {
        return self::$prefixes;
    }
}

// Configuration PSR-4 par défaut
Autoloader::addNamespace('App', dirname(__DIR__));
Autoloader::register();
