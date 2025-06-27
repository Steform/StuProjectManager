<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/Category.php';
require_once __DIR__ . '/../../app/models/Database.php';

class CategoryTest extends TestCase
{
    protected $dbFile;
    protected $category;

    protected function setUp(): void
    {
        // Utilise une base de test temporaire pour ne pas polluer la vraie base
        $this->dbFile = __DIR__ . '/test.sqlite';
        if (file_exists($this->dbFile)) {
            unlink($this->dbFile);
        }
        // Force Database à utiliser ce fichier
        $GLOBALS['DB_FILE'] = $this->dbFile;
        $this->category = new Category();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbFile)) {
            unlink($this->dbFile);
        }
    }

    public function testCreateAndGetCategory()
    {
        $name = 'Test Category';
        $favicon = 'favicon.png';
        $id = $this->category->create(['name' => $name, 'favicon' => $favicon]);

        $cat = $this->category->find($id);
        $this->assertEquals($name, $cat['name']);
        $this->assertEquals($favicon, $cat['favicon']);

        // Delete the category
        $this->category->delete($id);
        $catAfterDelete = $this->category->find($id);
        $this->assertNull($catAfterDelete);
    }

    public function testCreateUpdateAndGetCategory()
    {
        $name = 'Test Category';
        $favicon = 'favicon.png';
        $id = $this->category->create(['name' => $name, 'favicon' => $favicon]);

        // Update juste après création
        $newName = 'Updated Category';
        $newFavicon = 'updated.png';
        $this->category->update($id, ['name' => $newName, 'favicon' => $newFavicon]);
        $cat = $this->category->find($id);
        $this->assertEquals($newName, $cat['name']);
        $this->assertEquals($newFavicon, $cat['favicon']);

        // Delete the category
        $this->category->delete($id);
        $catAfterDelete = $this->category->find($id);
        $this->assertNull($catAfterDelete);
    }
} 