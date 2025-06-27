<?php
/**
 * @file BackupHelper.php
 * @brief Helper for backing up the projects and categories tables as JSON in a downloadable zip file, including favicons.
 *
 * This helper provides a static method to export all projects, categories, and favicons from the database
 * and the favicon directory into a single zip archive for backup purposes.
 *
 * @author Your Name
 * @date 2024
 */
class BackupHelper {
    public static function backupAndDownload() {
        // Connexion DB
        require_once __DIR__ . '/../models/Database.php';
        $db = Database::getConnection();
        // Récupérer les données
        $projects = [];
        $categories = [];
        $projRes = $db->query('SELECT * FROM projects');
        while ($row = $projRes->fetchArray(SQLITE3_ASSOC)) {
            $projects[] = $row;
        }
        $catRes = $db->query('SELECT * FROM category');
        while ($row = $catRes->fetchArray(SQLITE3_ASSOC)) {
            $categories[] = $row;
        }
        // Encodage JSON
        $projectsJson = json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $categoriesJson = json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        // Création du zip temporaire
        $zip = new \ZipArchive();
        $tmpZip = tempnam(sys_get_temp_dir(), 'stu-backup-') . '.zip';
        if ($zip->open($tmpZip, \ZipArchive::CREATE) !== true) {
            http_response_code(500);
            echo 'Could not create zip archive.';
            exit;
        }
        $zip->addFromString('projects.json', $projectsJson);
        $zip->addFromString('categories.json', $categoriesJson);
        // Ajouter tous les favicons
        $faviconDir = __DIR__ . '/../../public/favicon/';
        $files = scandir($faviconDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.gitignore') continue;
            $filePath = $faviconDir . $file;
            if (is_file($filePath)) {
                $zip->addFile($filePath, 'favicons/' . $file);
            }
        }
        $zip->close();
        // Forcer le téléchargement
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="stu-backup-' . date('Ymd-His') . '.zip"');
        header('Content-Length: ' . filesize($tmpZip));
        readfile($tmpZip);
        unlink($tmpZip);
        exit;
    }
} 