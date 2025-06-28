<?php
/**
 * @file Database.php
 * @brief Database connection and migration logic for the application (SQLite).
 *
 * Handles connection, schema migration, and integrity setup.
 *
 * @author Stunivers
 * @date 2025-06-28
 */
/**
 * Class Database
 * Handles the SQLite database connection and minimal setup.
 *
 * @package App\Models
 */
class Database {
    private static $db = null;
    private static $dbFile = __DIR__ . '/../../projects.db';

    /**
     * Get the singleton SQLite3 connection instance.
     *
     * @return SQLite3
     */
    public static function getConnection() {
        if (self::$db === null) {
            self::$db = new SQLite3(self::$dbFile);
            self::$db->enableExceptions(true);
            self::$db->exec('PRAGMA foreign_keys = ON;');
            if (file_exists(self::$dbFile)) {
                chmod(self::$dbFile, 0666);
            }
            self::migrate(); // on garde ce nom
        }
        return self::$db;
    }

    public static function resetConnection() {
        self::$db = null;
    }

    private static function logMigration($message) {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/migration.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . $message . "\n", FILE_APPEND);
    }

    /**
     * Perform basic setup of required tables and default values.
     *
     * @return void
     */
    private static function migrate() {
        $db = self::$db;
        self::logMigration('--- Migration started ---');

        // 1. Ensure 'category' table exists
        $db->exec('CREATE TABLE IF NOT EXISTS category (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            favicon TEXT
        )');
        self::logMigration('Ensured category table exists.');

        // 2. Ensure 'projects' table exists
        $db->exec('CREATE TABLE IF NOT EXISTS projects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            link TEXT NOT NULL,
            description TEXT,
            favicon TEXT,
            category_id INTEGER,
            FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE RESTRICT
        )');
        self::logMigration('Ensured projects table exists.');

        // 3. Insert default category if not present
        $defaultCat = $db->querySingle("SELECT id FROM category WHERE name = 'Dev Env'");
        if (!$defaultCat) {
            $db->exec("INSERT INTO category (name) VALUES ('Dev Env')");
            $defaultCat = $db->lastInsertRowID();
            self::logMigration('Created default category: Dev Env');
        }

        // 4. Assign default category to uncategorized projects
        $db->exec("UPDATE projects SET category_id = $defaultCat WHERE category_id IS NULL OR category_id = '' OR category_id NOT IN (SELECT id FROM category)");
        self::logMigration('Assigned default category to uncategorized projects.');

        self::logMigration('--- Migration finished ---');
    }
}
 