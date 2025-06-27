/**
 * @file Category.php
 * @brief Model for handling category data and database operations.
 *
 * Provides static methods for CRUD operations on categories.
 *
 * @author Your Name
 * @date 2024
 */
<?php
/**
 * Class Category
 * Handles category data and database operations.
 *
 * @package App\Models
 */
require_once __DIR__ . '/../../app/models/Database.php';

class Category {
    /**
     * Get the database connection instance.
     *
     * @return SQLite3 Database connection
     */
    protected static function db() {
        return Database::getConnection();
    }

    /**
     * Get all categories.
     *
     * @example $categories = Category::all();
     * @return array[] List of all categories
     */
    public static function all() {
        $db = self::db();
        $res = $db->query('SELECT * FROM category ORDER BY id ASC');
        $categories = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $categories[] = $row;
        }
        return $categories;
    }

    /**
     * Find a category by its ID.
     *
     * @example $cat = Category::find(1);
     * @param int $id Category ID
     * @return array|null Category data or null if not found
     */
    public static function find($id) {
        $db = self::db();
        $stmt = $db->prepare('SELECT * FROM category WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Create a new category.
     *
     * @example $id = Category::create(['name' => 'Test', 'favicon' => 'favicon.png']);
     * @param array $data Category data (name, favicon)
     * @return int ID of the created category
     * @throws Exception If the insert fails
     */
    public static function create($data) {
        $db = self::db();
        $stmt = $db->prepare('INSERT INTO category (name, favicon) VALUES (?, ?)');
        $stmt->bindValue(1, $data['name']);
        $stmt->bindValue(2, $data['favicon'] ?? null);
        $stmt->execute();
        return $db->lastInsertRowID();
    }

    /**
     * Update an existing category.
     *
     * @example Category::update(1, ['name' => 'New', 'favicon' => 'favicon2.png']);
     * @param int $id Category ID
     * @param array $data Category data (name, favicon)
     * @return void
     * @throws Exception If the update fails
     */
    public static function update($id, $data) {
        $db = self::db();
        $stmt = $db->prepare('UPDATE category SET name = ?, favicon = COALESCE(?, favicon) WHERE id = ?');
        $stmt->bindValue(1, $data['name']);
        $stmt->bindValue(2, $data['favicon'] ?? null);
        $stmt->bindValue(3, $id, SQLITE3_INTEGER);
        $stmt->execute();
    }

    /**
     * Delete a category by its ID.
     *
     * @example Category::delete(1);
     * @param int $id Category ID
     * @return void
     * @throws Exception If the delete fails
     */
    public static function delete($id) {
        $db = self::db();
        $stmt = $db->prepare('DELETE FROM category WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->execute();
    }

} 