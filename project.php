<?php
session_start();  // Démarre la session dès le début du script

class Database {
    private $db;

    public function __construct($dbname = 'projets.db') {
        try {
            $this->db = new SQLite3($dbname);
            $this->db->exec("CREATE TABLE IF NOT EXISTS projets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nom TEXT NOT NULL,
                lien TEXT NOT NULL,
                description TEXT,
                favicon TEXT
            )");
        } catch (Exception $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    public function getDB() {
        return $this->db;
    }
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = new Database();
$conn = $db->getDB();
$conn->exec("PRAGMA foreign_keys = ON;");
$conn->enableExceptions(true);

// Validation d'URL
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

// Initialisation des messages d'alerte
$alertMessage = "";
$alertType = "";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'], $_POST['lien'])) {
    $nom = trim($_POST['nom']);
    $lien = trim($_POST['lien']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    
    // Gestion du favicon uploadé
    $faviconPath = null;
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['favicon']['tmp_name'];
        $fileName = $_FILES['favicon']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $allowedfileExtensions = array('png', 'jpg', 'jpeg', 'ico', 'gif', 'svg');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $uploadFileDir = './favicon/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            // Génération d'un nom unique pour éviter les collisions
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $faviconPath = $dest_path;
            }
        }
    }
    
    // Pour la mise à jour, si aucun fichier n'est uploadé, on conserve l'ancien favicon
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        if ($faviconPath === null) {
            $stmtCheck = $conn->prepare("SELECT favicon FROM projets WHERE id = ?");
            $stmtCheck->bindValue(1, $_POST['id'], SQLITE3_INTEGER);
            $resultCheck = $stmtCheck->execute();
            $row = $resultCheck->fetchArray(SQLITE3_ASSOC);
            $faviconPath = $row ? $row['favicon'] : null;
        }
    }
    
    // Vérification des champs obligatoires
    if (!empty($nom) && validate_url($lien)) {
        try {
            if (isset($_POST['id']) && is_numeric($_POST['id'])) {
                $stmt = $conn->prepare("UPDATE projets SET nom = ?, lien = ?, description = ?, favicon = ? WHERE id = ?");
                $stmt->bindValue(1, $nom);
                $stmt->bindValue(2, $lien);
                $stmt->bindValue(3, $description);
                $stmt->bindValue(4, $faviconPath);
                $stmt->bindValue(5, $_POST['id'], SQLITE3_INTEGER);
                $alertMessage = "Projet mis à jour avec succès !";
            } else {
                $stmt = $conn->prepare("INSERT INTO projets (nom, lien, description, favicon) VALUES (?, ?, ?, ?)");
                $stmt->bindValue(1, $nom);
                $stmt->bindValue(2, $lien);
                $stmt->bindValue(3, $description);
                $stmt->bindValue(4, $faviconPath);
                $alertMessage = "Projet ajouté avec succès !";
            }
            $stmt->execute();
            $alertType = "success";
        } catch (Exception $e) {
            $alertMessage = "Erreur lors de l'opération : " . $e->getMessage();
            $alertType = "danger";
        }
    }
}

// Suppression de projet
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM projets WHERE id = ?");
        $stmt->bindValue(1, $_GET['delete'], SQLITE3_INTEGER);
        $stmt->execute();
        $alertMessage = "Projet supprimé avec succès !";
        $alertType = "warning";
    } catch (Exception $e) {
        $alertMessage = "Erreur lors de la suppression : " . $e->getMessage();
        $alertType = "danger";
    }
}

// Récupération des projets
$result = $conn->query("SELECT * FROM projets ORDER BY nom ASC");
$projets = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $projets[] = $row;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Projets</title>
    <link href="./css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12 mt-4">
                <h1>Gestion des Projets</h1>
            </div>
        </div>
        <form method="post" enctype="multipart/form-data" class="mb-4" id="project-form">
            <div class="row">
                <input type="hidden" name="id" id="id">
                <div class="col-5">
                    <label for="nom" class="form-label">Nom du projet</label>
                </div>
                <div class="col-7">
                    <input type="text" name="nom" id="nom" class="form-control" required>
                </div>
                <div class="col-5">
                    <label for="lien" class="form-label">Lien du projet</label>
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
                    <label for="favicon" class="form-label">Favicon (fichier image)</label>
                </div>
                <div class="col-7">
                    <input type="file" name="favicon" id="favicon" class="form-control">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </div>
        </form>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <h3>Liste des projets</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 32px;">Favicon</th>
                            <th scope="col">Nom</th>
                            <th scope="col">Lien</th>
                            <th scope="col" class="text-start">Description</th>
                            <th scope="col" class="text-end" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
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
                                        <a href="?delete=<?= $projet['id'] ?>" class="btn btn-danger" onclick="return confirm('Voulez-vous vraiment supprimer ce projet ?')">🗑️</a>
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
        function editRow(id, nom, lien, description) {
            document.getElementById('id').value = id;
            document.getElementById('nom').value = nom;
            document.getElementById('lien').value = lien;
            document.getElementById('description').value = description;
        }
    </script>
</body>
</html>
