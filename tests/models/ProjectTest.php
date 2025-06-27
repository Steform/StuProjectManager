<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/Project.php';
require_once __DIR__ . '/../../app/models/Category.php';
require_once __DIR__ . '/../../app/models/Database.php';

class ProjectTest extends TestCase
{
    protected $dbFile;
    protected $project;
    protected $category;

    protected function setUp(): void
    {
        // Utilise une base de test temporaire pour ne pas polluer la vraie base
        $this->dbFile = __DIR__ . '/test.sqlite';
        if (file_exists($this->dbFile)) {
            unlink($this->dbFile);
        }
        $GLOBALS['DB_FILE'] = $this->dbFile;
        $this->category = new Category();
        $this->project = new Project();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbFile)) {
            unlink($this->dbFile);
        }
    }

    public function testCreateAndGetProject()
    {
        // Crée une catégorie pour lier le projet
        $catName = 'Test Category';
        $catFavicon = 'favicon.png';
        $catId = $this->category->create(['name' => $catName, 'favicon' => $catFavicon]);

        $name = 'Test Project';
        $description = 'A test project';
        $link = 'https://example.com';
        $categoryId = $catId;
        $id = $this->project->create([
            'name' => $name,
            'link' => $link,
            'description' => $description,
            'favicon' => null,
            'category_id' => $categoryId
        ]);

        $proj = $this->project->find($id);
        $this->assertEquals($name, $proj['name']);
        $this->assertEquals($description, $proj['description']);
        $this->assertEquals($link, $proj['link']);
        $this->assertEquals($categoryId, $proj['category_id']);

        // Suppression du projet
        $this->project->delete($id);
        $projAfterDelete = $this->project->find($id);
        $this->assertNull($projAfterDelete);
        // Suppression de la catégorie
        $this->category->delete($categoryId);
        $catAfterDelete = $this->category->find($categoryId);
        $this->assertNull($catAfterDelete);
    }

    public function testCannotDeleteCategoryWithLinkedProject()
    {
        // Crée une catégorie
        $catId = $this->category->create(['name' => 'Cat with Project', 'favicon' => null]);
        // Crée un projet lié à cette catégorie
        $projId = $this->project->create([
            'name' => 'Proj linked',
            'link' => 'https://example.com',
            'description' => 'desc',
            'favicon' => null,
            'category_id' => $catId
        ]);
        // Tente de supprimer la catégorie (doit échouer ou ne rien faire)
        try {
            $this->category->delete($catId);
        } catch (\Exception $e) {
            // OK, une exception est attendue
        }
        // La catégorie doit toujours exister
        $cat = $this->category->find($catId);
        $this->assertNotNull($cat);
        // Supprime le projet
        $this->project->delete($projId);
        // Maintenant la suppression de la catégorie doit fonctionner
        $this->category->delete($catId);
        $catAfterDelete = $this->category->find($catId);
        $this->assertNull($catAfterDelete);
    }

    public function testCreateUpdateAndGetProject()
    {
        // Crée une catégorie pour lier le projet
        $catName = 'Test Category';
        $catFavicon = 'favicon.png';
        $catId = $this->category->create(['name' => $catName, 'favicon' => $catFavicon]);

        $name = 'Test Project';
        $description = 'A test project';
        $link = 'https://example.com';
        $categoryId = $catId;
        $id = $this->project->create([
            'name' => $name,
            'link' => $link,
            'description' => $description,
            'favicon' => null,
            'category_id' => $categoryId
        ]);

        // Update juste après création
        $newName = 'Updated Project';
        $newDescription = 'Updated description';
        $newLink = 'https://updated.com';
        $newFavicon = 'updated.png';
        $this->project->update($id, [
            'name' => $newName,
            'link' => $newLink,
            'description' => $newDescription,
            'favicon' => $newFavicon,
            'category_id' => $categoryId
        ]);
        $proj = $this->project->find($id);
        $this->assertEquals($newName, $proj['name']);
        $this->assertEquals($newDescription, $proj['description']);
        $this->assertEquals($newLink, $proj['link']);
        $this->assertEquals($newFavicon, $proj['favicon']);
        $this->assertEquals($categoryId, $proj['category_id']);

        // Suppression du projet
        $this->project->delete($id);
        $projAfterDelete = $this->project->find($id);
        $this->assertNull($projAfterDelete);
        // Suppression de la catégorie
        $this->category->delete($categoryId);
        $catAfterDelete = $this->category->find($categoryId);
        $this->assertNull($catAfterDelete);
    }
} 