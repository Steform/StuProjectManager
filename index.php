<?php
// Start the session at the beginning of the script to track user sessions across pages
session_start();

/**
 * Class Database
 * Handles the SQLite database connection and table creation.
 */
class Database {
    private $db;

    /**
     * Constructor to establish a database connection.
     *
     * @param string $dbname The name of the SQLite database file (default is 'projets.db').
     */
    public function __construct($dbname = 'projets.db') {
        try {
            // Create a new SQLite3 instance and open the database
            $this->db = new SQLite3($dbname);
            // Create the 'projets' table if it does not exist
            $this->db->exec("CREATE TABLE IF NOT EXISTS projets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nom TEXT NOT NULL,
                lien TEXT NOT NULL,
                description TEXT,
                favicon TEXT
            )");
        } catch (Exception $e) {
            // If there's an error connecting to the database, stop the execution and display the error
            die("Database connection error: " . $e->getMessage());
        }
    }

    /**
     * Get the database connection.
     *
     * @return SQLite3 The database connection instance.
     */
    public function getDB() {
        return $this->db;
    }
}

// Display all errors for debugging during development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Instantiate the Database class and get the database connection
$db = new Database();
$conn = $db->getDB();
$conn->exec("PRAGMA foreign_keys = ON;"); // Enable foreign key support in SQLite
$conn->enableExceptions(true); // Enable exceptions for database errors

/**
 * Validate the given URL to ensure it's a valid format.
 *
 * @param string $url The URL to validate.
 * @return bool Returns true if the URL is valid, false otherwise.
 */
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

// Initialize alert message variables for feedback to the user
$alertMessage = "";
$alertType = "";

// Handle the form submission (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'], $_POST['lien'])) {
    $nom = trim($_POST['nom']); // Get the project name from the form input
    $lien = trim($_POST['lien']); // Get the project link from the form input
    $description = isset($_POST['description']) ? trim($_POST['description']) : null; // Get the optional description

    // Handle the uploaded favicon image
    $faviconPath = null;
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['favicon']['tmp_name']; // Temporary file path
        $fileName = $_FILES['favicon']['name']; // Original file name
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps)); // Get the file extension
        $allowedfileExtensions = array('png', 'jpg', 'jpeg', 'ico', 'gif', 'svg'); // Allowed image extensions
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $uploadFileDir = './favicon/';
            // Create the directory if it doesn't exist
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            // Generate a unique file name to avoid collisions
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;
            // Move the uploaded file to the target directory
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $faviconPath = $dest_path; // Store the path of the uploaded favicon
            }
        }
    }

    // If the form is being used to update an existing project, retain the old favicon if no new one is uploaded
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        if ($faviconPath === null) {
            // Fetch the existing favicon path from the database
            $stmtCheck = $conn->prepare("SELECT favicon FROM projets WHERE id = ?");
            $stmtCheck->bindValue(1, $_POST['id'], SQLITE3_INTEGER);
            $resultCheck = $stmtCheck->execute();
            $row = $resultCheck->fetchArray(SQLITE3_ASSOC);
            $faviconPath = $row ? $row['favicon'] : null;
        }
    }

    // Check if required fields are filled in and validate the URL
    if (!empty($nom) && validate_url($lien)) {
        try {
            // If an ID is provided, update the existing project
            if (isset($_POST['id']) && is_numeric($_POST['id'])) {
                $stmt = $conn->prepare("UPDATE projets SET nom = ?, lien = ?, description = ?, favicon = ? WHERE id = ?");
                $stmt->bindValue(1, $nom);
                $stmt->bindValue(2, $lien);
                $stmt->bindValue(3, $description);
                $stmt->bindValue(4, $faviconPath);
                $stmt->bindValue(5, $_POST['id'], SQLITE3_INTEGER);
                $alertMessage = "Project updated successfully!";
            } else {
                // Insert a new project into the database
                $stmt = $conn->prepare("INSERT INTO projets (nom, lien, description, favicon) VALUES (?, ?, ?, ?)");
                $stmt->bindValue(1, $nom);
                $stmt->bindValue(2, $lien);
                $stmt->bindValue(3, $description);
                $stmt->bindValue(4, $faviconPath);
                $alertMessage = "Project added successfully!";
            }
            $stmt->execute(); // Execute the SQL statement
            $alertType = "success"; // Set the alert type to success for feedback
        } catch (Exception $e) {
            // Catch any database errors and display an error message
            $alertMessage = "Error during the operation: " . $e->getMessage();
            $alertType = "danger"; // Set the alert type to danger
        }
    }
}

// Handle project deletion (GET request)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Delete the project with the provided ID
        $stmt = $conn->prepare("DELETE FROM projets WHERE id = ?");
        $stmt->bindValue(1, $_GET['delete'], SQLITE3_INTEGER);
        $stmt->execute(); // Execute the delete statement
        $alertMessage = "Project deleted successfully!";
        $alertType = "warning"; // Set the alert type to warning for deletion feedback
    } catch (Exception $e) {
        // Catch any errors and display an error message
        $alertMessage = "Error during deletion: " . $e->getMessage();
        $alertType = "danger"; // Set the alert type to danger
    }
}

// Fetch all projects from the database
$result = $conn->query("SELECT * FROM projets ORDER BY nom ASC");
$projets = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $projets[] = $row; // Store each project in an array
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Projets</title>
    <link href="./css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="favicon.png" sizes="32x32">
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12 mt-4">
                <h1>Project Management</h1>
            </div>
        </div>
        <!-- Project Form -->
        <form method="post" enctype="multipart/form-data" class="mb-4" id="project-form">
            <div class="row">
                <input type="hidden" name="id" id="id">
                <div class="col-5">
                    <label for="nom" class="form-label">Project Name</label>
                </div>
                <div class="col-7">
                    <input type="text" name="nom" id="nom" class="form-control" required>
                </div>
                <div class="col-5">
                    <label for="lien" class="form-label">Project Link</label>
                </div>
                <div class="col-7">
                    <input type="url" name="lien" id="lien" class="form-control" required>
                </div>
                <div class="col-5">
                    <label for="description" class="form-label">Description</label>
                </div>
                <div class="col-7">
                    <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-5">
                    <label for="favicon" class="form-label">Favicon (Image file)</label>
                </div>
                <div class="col-7">
                    <input type="file" name="favicon" id="favicon" class="form-control">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <h3>Project List</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 32px;">Favicon</th>
                            <th scope="col">Name</th>
                            <th scope="col">Link</th>
                            <th scope="col" class="text-start">Description</th>
                            <th scope="col" class="text-end" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loop through each project and display it in the table -->
                        <?php foreach ($projets as $projet){ ?>
                            <tr id="row-<?= $projet['id'] ?>">
                                <td>
                                    <?php if (!empty($projet['favicon'])) { ?>
                                        <img src="<?= htmlspecialchars($projet['favicon'], ENT_QUOTES, 'UTF-8') ?>" alt="Favicon" style="width: 32px; height: 32px;">
                                    <?php } else { ?>
                                        <img src="favicon.png" alt="Favicon" style="width: 32px; height: 32px;">
                                    <?php } ?>
                                </td>
                                <td><?= htmlspecialchars($projet['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><a href="<?= htmlspecialchars($projet['lien'], ENT_QUOTES, 'UTF-8') ?>" target="_blank"><?= htmlspecialchars($projet['lien'], ENT_QUOTES, 'UTF-8') ?></a></td>
                                <td><?= htmlspecialchars($projet['description'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <!-- Delete and Edit buttons -->
                                        <a href="?delete=<?= $projet['id'] ?>" class="btn btn-danger" onclick="return confirm('Do you really want to delete this project?')">🗑️</a>
                                        <button class="btn btn-warning" onclick="editRow(
                                            <?= $projet['id'] ?>, 
                                            '<?= addslashes($projet['nom']) ?>', 
                                            '<?= addslashes($projet['lien']) ?>', 
                                            '<?= addslashes($projet['description']) ?>'
                                        )">✏️</button>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        /**
         * Function to populate the project form with existing project data for editing.
         *
         * @param int id The project ID.
         * @param string nom The project name.
         * @param string lien The project link.
         * @param string description The project description.
         */
        function editRow(id, nom, lien, description) {
            document.getElementById('id').value = id;
            document.getElementById('nom').value = nom;
            document.getElementById('lien').value = lien;
            document.getElementById('description').value = description;
        }
    </script>
</body>
</html>
