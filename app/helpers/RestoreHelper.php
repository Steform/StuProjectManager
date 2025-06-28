<?php
/**
 * @file RestoreHelper.php
 * @brief Helper for restoring the database and favicons from an uploaded zip file.
 *
 * This helper provides a static method to restore all projects, categories, and favicons from a backup zip archive.
 * It handles database migration, backup of existing data, and extraction of all necessary files.
 *
 * @author Stunivers
 * @date 2025-06-28
 */
class RestoreHelper {
    public static function restoreFromUpload() {
        // 0. S'assure que la base et les tables existent (migration)
        require_once __DIR__ . '/../models/Database.php';
        $db = \Database::getConnection();
        $dbFile = __DIR__ . '/../../projects.db';
        $faviconDir = __DIR__ . '/../../public/favicon';
        $now = date('Y-m-d-H-i-s');
        // 1. Sauvegarde l'existant
        if (file_exists($dbFile)) {
            rename($dbFile, __DIR__ . "/../../projects-$now.db");
            // Patch : reset la connexion statique pour forcer la migration sur la nouvelle base
            require_once __DIR__ . '/../models/Database.php';
            \Database::resetConnection();
        }
        if (is_dir($faviconDir)) {
            rename($faviconDir, __DIR__ . "/../../public/favicon-$now");
        }
        // 2. Vérifie l'upload
        if (!isset($_FILES['restore_zip']) || $_FILES['restore_zip']['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('No zip file uploaded or upload error.');
        }
        $zipPath = $_FILES['restore_zip']['tmp_name'];
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('Could not open uploaded zip.');
        }
        // 3. Extraction temporaire
        $tmpDir = sys_get_temp_dir() . '/stu-restore-' . uniqid();
        mkdir($tmpDir);
        $zip->extractTo($tmpDir);
        $zip->close();
        // 4. Vérifie présence des fichiers nécessaires
        if (!file_exists("$tmpDir/categories.json") || !file_exists("$tmpDir/projects.json")) {
            throw new \Exception('Missing categories.json or projects.json in zip.');
        }
        // 5. Restaure la base
        require_once __DIR__ . '/../models/Database.php';
        $db = \Database::getConnection();
        // Vide les tables
        $db->exec('DELETE FROM category');
        $db->exec('DELETE FROM projects');
        // Restaure les catégories
        $categories = json_decode(file_get_contents("$tmpDir/categories.json"), true);
        foreach ($categories as $cat) {
            $fields = array_keys($cat);
            $placeholders = implode(',', array_fill(0, count($fields), '?'));
            $stmt = $db->prepare('INSERT INTO category (' . implode(',', $fields) . ') VALUES (' . $placeholders . ')');
            foreach ($fields as $i => $f) {
                $stmt->bindValue($i+1, $cat[$f]);
            }
            $stmt->execute();
        }
        // Restaure les projets
        $projects = json_decode(file_get_contents("$tmpDir/projects.json"), true);
        foreach ($projects as $proj) {
            $fields = array_keys($proj);
            $placeholders = implode(',', array_fill(0, count($fields), '?'));
            $stmt = $db->prepare('INSERT INTO projects (' . implode(',', $fields) . ') VALUES (' . $placeholders . ')');
            foreach ($fields as $i => $f) {
                $stmt->bindValue($i+1, $proj[$f]);
            }
            $stmt->execute();
        }
        // 6. Restaure les favicons
        mkdir($faviconDir, 0755, true);
        chmod($faviconDir, 0777); // droits max sur le dossier favicons
        $faviconsSrc = "$tmpDir/favicons";
        if (is_dir($faviconsSrc)) {
            foreach (scandir($faviconsSrc) as $file) {
                if ($file === '.' || $file === '..') continue;
                copy("$faviconsSrc/$file", "$faviconDir/$file");
                chmod("$faviconDir/$file", 0666); // droits max sur chaque favicon
            }
        }
        // 7. Nettoyage
        foreach (scandir($tmpDir) as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = "$tmpDir/$file";
                if (is_dir($path)) {
                    foreach (scandir($path) as $f2) {
                        if ($f2 !== '.' && $f2 !== '..') unlink("$path/$f2");
                    }
                    rmdir($path);
                } else {
                    unlink($path);
                }
            }
        }
        rmdir($tmpDir);
    }
} 