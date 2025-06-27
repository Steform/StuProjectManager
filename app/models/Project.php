<?php
/**
 * Class Project
 * Handles project data and database operations.
 *
 * @package App\Models
 */
require_once __DIR__ . '/../../app/models/Database.php';

class Project {
    /**
     * Get the database connection instance.
     *
     * @return SQLite3 Database connection
     */
    protected static function db() {
        return Database::getConnection();
    }

    /**
     * Get all projects.
     *
     * @example $projects = Project::all();
     * @return array[] List of all projects
     */
    public static function all() {
        $db = self::db();
        $res = $db->query('SELECT * FROM projects ORDER BY id ASC');
        $projects = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $projects[] = $row;
        }
        return $projects;
    }

    /**
     * Find a project by its ID.
     *
     * @example $project = Project::find(1);
     * @param int $id Project ID
     * @return array|null Project data or null if not found
     */
    public static function find($id) {
        $db = self::db();
        $stmt = $db->prepare('SELECT * FROM projects WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Create a new project.
     *
     * @example $id = Project::create(['name' => 'Test', 'link' => 'https://...', 'description' => '...', 'favicon' => null, 'category_id' => 1]);
     * @param array $data Project data (name, link, description, favicon, category_id)
     * @return int ID of the created project
     * @throws Exception If the insert fails
     */
    public static function create($data) {
        $db = self::db();
        $stmt = $db->prepare('INSERT INTO projects (name, link, description, favicon, category_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->bindValue(1, $data['name']);
        $stmt->bindValue(2, $data['link']);
        $stmt->bindValue(3, $data['description']);
        $stmt->bindValue(4, $data['favicon'] ?? null);
        $stmt->bindValue(5, $data['category_id'], SQLITE3_INTEGER);
        $stmt->execute();
        return $db->lastInsertRowID();
    }

    /**
     * Update an existing project.
     *
     * @example Project::update(1, ['name' => 'New', 'link' => 'https://...', 'description' => '...', 'favicon' => null, 'category_id' => 2]);
     * @param int $id Project ID
     * @param array $data Project data (name, link, description, favicon, category_id)
     * @return void
     * @throws Exception If the update fails
     */
    public static function update($id, $data) {
        $db = self::db();
        $stmt = $db->prepare('UPDATE projects SET name = ?, link = ?, description = ?, favicon = COALESCE(?, favicon), category_id = ? WHERE id = ?');
        $stmt->bindValue(1, $data['name']);
        $stmt->bindValue(2, $data['link']);
        $stmt->bindValue(3, $data['description']);
        $stmt->bindValue(4, $data['favicon'] ?? null);
        $stmt->bindValue(5, $data['category_id'], SQLITE3_INTEGER);
        $stmt->bindValue(6, $id, SQLITE3_INTEGER);
        $stmt->execute();
    }

    /**
     * Delete a project by its ID.
     *
     * @example Project::delete(1);
     * @param int $id Project ID
     * @return void
     * @throws Exception If the delete fails
     */
    public static function delete($id) {
        $db = self::db();
        $stmt = $db->prepare('DELETE FROM projects WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->execute();
    }
} 