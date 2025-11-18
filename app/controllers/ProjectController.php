<?php
/**
 * @file ProjectController.php
 * @brief Controller for project management (CRUD, backup, restore).
 *
 * Handles all project-related HTTP requests and business logic.
 *
 * @author Stunivers
 * @date 2025-06-28
 */

require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../helpers/FaviconHelper.php';

class ProjectController {
    /**
     * Affiche la liste des projets et des catégories.
     *
     * @return void
     */
    public function list() {
        $projects = Project::all();
        $categoryList = Category::all();
        $editProject = null;
        $error = null;
        // Mode édition inline
        if (isset($_GET['edit_id'])) {
            $editProject = Project::find((int)$_GET['edit_id']);
            if (!$editProject) {
                $_SESSION['alertMessage'] = 'Project not found!';
                $_SESSION['alertType'] = 'danger';
            }
        }
        // Gestion du POST (create ou update inline)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_form'])) {
            $id = $_POST['id'] ?? null;
            $faviconPath = null;
            $name = trim($_POST['name'] ?? '');
            $link = trim($_POST['link'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category_id = $_POST['category_id'] ?? null;
            $allowedfileExtensions = array('png', 'jpg', 'jpeg', 'ico', 'gif', 'svg');
            $allowedMimeTypes = [
                'image/png', 'image/jpeg', 'image/gif', 'image/x-icon', 'image/svg+xml', 'image/vnd.microsoft.icon'
            ];
            // Validation
            if ($name === '' || strlen($name) < 2 || strlen($name) > 100 || !preg_match('/^[A-Za-z0-9 _\-]+$/', $name)) {
                $error = 'Project name must be 2-100 characters (letters, numbers, spaces, - or _).';
            } elseif ($link === '' || strlen($link) > 255 || !preg_match('/^https?:\/\/.+/', $link)) {
                $error = 'Project link must be a valid URL (starting with http:// or https://).';
            } elseif (strlen($description) > 500) {
                $error = 'Description must be less than 500 characters.';
            } elseif (empty($category_id) || !in_array($category_id, array_column($categoryList, 'id'))) {
                $error = 'Please select a valid category.';
            } elseif (isset($_FILES['favicon']) && $_FILES['favicon']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['favicon']['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Error uploading favicon.';
                } else {
                    $fileTmpPath = $_FILES['favicon']['tmp_name'];
                    $fileName = $_FILES['favicon']['name'];
                    $fileNameCmps = explode('.', $fileName);
                    $fileExtension = strtolower(end($fileNameCmps));
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $fileTmpPath);
                    finfo_close($finfo);
                    if (!in_array($fileExtension, $allowedfileExtensions) || !in_array($mimeType, $allowedMimeTypes)) {
                        $error = 'Invalid favicon file type.';
                    } else {
                        $uploadFileDir = __DIR__ . '/../../public/favicon/';
                        if (!is_dir($uploadFileDir)) {
                            mkdir($uploadFileDir, 0755, true);
                        }
                        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                        $dest_path = $uploadFileDir . $newFileName;
                        if (move_uploaded_file($fileTmpPath, $dest_path)) {
                            $faviconPath = '/favicon/' . $newFileName;
                        } else {
                            $error = 'Failed to save favicon file.';
                        }
                    }
                }
            }
            // Si pas d'erreur, on crée ou update
            if (!$error) {
                // If no favicon provided, try to fetch it from the website
                if (!$faviconPath) {
                    if ($id) {
                        // On update, garder l'ancien favicon s'il existe
                        $faviconPath = $editProject['favicon'] ?? null;
                    }
                    // Si toujours pas de favicon, essayer de le récupérer du site
                    if (!$faviconPath) {
                        $faviconPath = FaviconHelper::fetchFavicon($link);
                    }
                }
                $sortOrder = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
                $data = [
                    'name' => $name,
                    'link' => $link,
                    'description' => $description,
                    'favicon' => $faviconPath,
                    'category_id' => $category_id,
                    'sort_order' => $sortOrder
                ];
                if ($id) {
                    Project::update($id, $data);
                    $_SESSION['alertMessage'] = 'Project updated successfully!';
                    $_SESSION['alertType'] = 'success';
                } else {
                    Project::create($data);
                    $_SESSION['alertMessage'] = 'Project added successfully!';
                    $_SESSION['alertType'] = 'success';
                }
                header('Location: ?controller=project&action=list#project-list');
                exit;
            } else {
                // On garde les valeurs saisies pour le formulaire
                $sortOrder = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
                $editProject = [
                    'id' => $id,
                    'name' => $name,
                    'link' => $link,
                    'description' => $description,
                    'favicon' => $faviconPath ?? ($id ? ($editProject['favicon'] ?? null) : null),
                    'category_id' => $category_id,
                    'sort_order' => $sortOrder
                ];
            }
        }
        ob_start();
        include __DIR__ . '/../views/projects/list.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Gère la création d'un projet (formulaire et logique).
     *
     * @return void
     */
    public function create() {
        $categoryList = Category::all();
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $faviconPath = null;
            // --- Server-side validation ---
            $name = trim($_POST['name'] ?? '');
            $link = trim($_POST['link'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category_id = $_POST['category_id'] ?? null;
            $allowedfileExtensions = array('png', 'jpg', 'jpeg', 'ico', 'gif', 'svg');
            $allowedMimeTypes = [
                'image/png', 'image/jpeg', 'image/gif', 'image/x-icon', 'image/svg+xml', 'image/vnd.microsoft.icon'
            ];
            // Validate project name
            if ($name === '' || strlen($name) < 2 || strlen($name) > 100 || !preg_match('/^[A-Za-z0-9 _\-]+$/', $name)) {
                $error = 'Project name must be 2-100 characters (letters, numbers, spaces, - or _).';
            }
            // Validate project link
            elseif ($link === '' || strlen($link) > 255 || !preg_match('/^https?:\/\/.+/', $link)) {
                $error = 'Project link must be a valid URL (starting with http:// or https://).';
            }
            // Validate description
            elseif (strlen($description) > 500) {
                $error = 'Description must be less than 500 characters.';
            }
            // Validate category
            elseif (empty($category_id) || !in_array($category_id, array_column($categoryList, 'id'))) {
                $error = 'Please select a valid category.';
            }
            // Validate favicon if present
            elseif (isset($_FILES['favicon']) && $_FILES['favicon']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['favicon']['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Error uploading favicon.';
                } else {
                    $fileTmpPath = $_FILES['favicon']['tmp_name'];
                    $fileName = $_FILES['favicon']['name'];
                    $fileNameCmps = explode('.', $fileName);
                    $fileExtension = strtolower(end($fileNameCmps));
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $fileTmpPath);
                    finfo_close($finfo);
                    if (!in_array($fileExtension, $allowedfileExtensions) || !in_array($mimeType, $allowedMimeTypes)) {
                        $error = 'Invalid favicon file type.';
                    } else {
                        $uploadFileDir = __DIR__ . '/../../public/favicon/';
                        if (!is_dir($uploadFileDir)) {
                            mkdir($uploadFileDir, 0755, true);
                        }
                        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                        $dest_path = $uploadFileDir . $newFileName;
                        if (move_uploaded_file($fileTmpPath, $dest_path)) {
                            $faviconPath = '/favicon/' . $newFileName;
                        } else {
                            $error = 'Failed to save favicon file.';
                        }
                    }
                }
            }
            // If no error, create the project
            if (!$error) {
                // If no favicon provided, try to fetch it from the website
                if (!$faviconPath) {
                    $faviconPath = FaviconHelper::fetchFavicon($link);
                }
                $sortOrder = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
                $data = [
                    'name' => $name,
                    'link' => $link,
                    'description' => $description,
                    'favicon' => $faviconPath,
                    'category_id' => $category_id,
                    'sort_order' => $sortOrder
                ];
                Project::create($data);
                $_SESSION['alertMessage'] = 'Project added successfully!';
                $_SESSION['alertType'] = 'success';
                header('Location: ?controller=project&action=list#project-list');
                exit;
            }
        }
        ob_start();
        if (isset($error)) echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
        echo '<h2>Project Form</h2>';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Gère la modification d'un projet (formulaire et logique).
     *
     * @return void
     */
    public function update() {
        $id = $_GET['id'] ?? null;
        $categoryList = Category::all();
        $error = null;
        if (!$id) {
            header('Location: ?controller=project&action=list');
            exit;
        }
        $project = Project::find($id);
        if (!$project) {
            $_SESSION['alertMessage'] = 'Project not found!';
            $_SESSION['alertType'] = 'danger';
            header('Location: ?controller=project&action=list');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $faviconPath = $project['favicon'];
            // --- Server-side validation ---
            $name = trim($_POST['name'] ?? '');
            $link = trim($_POST['link'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category_id = $_POST['category_id'] ?? null;
            $allowedfileExtensions = array('png', 'jpg', 'jpeg', 'ico', 'gif', 'svg');
            $allowedMimeTypes = [
                'image/png', 'image/jpeg', 'image/gif', 'image/x-icon', 'image/svg+xml', 'image/vnd.microsoft.icon'
            ];
            // Validate project name
            if ($name === '' || strlen($name) < 2 || strlen($name) > 100 || !preg_match('/^[A-Za-z0-9 _\-]+$/', $name)) {
                $error = 'Project name must be 2-100 characters (letters, numbers, spaces, - or _).';
            }
            // Validate project link
            elseif ($link === '' || strlen($link) > 255 || !preg_match('/^https?:\/\/.+/', $link)) {
                $error = 'Project link must be a valid URL (starting with http:// or https://).';
            }
            // Validate description
            elseif (strlen($description) > 500) {
                $error = 'Description must be less than 500 characters.';
            }
            // Validate category
            elseif (empty($category_id) || !in_array($category_id, array_column($categoryList, 'id'))) {
                $error = 'Please select a valid category.';
            }
            // Validate favicon if present
            elseif (isset($_FILES['favicon']) && $_FILES['favicon']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['favicon']['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Error uploading favicon.';
                } else {
                    $fileTmpPath = $_FILES['favicon']['tmp_name'];
                    $fileName = $_FILES['favicon']['name'];
                    $fileNameCmps = explode('.', $fileName);
                    $fileExtension = strtolower(end($fileNameCmps));
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $fileTmpPath);
                    finfo_close($finfo);
                    if (!in_array($fileExtension, $allowedfileExtensions) || !in_array($mimeType, $allowedMimeTypes)) {
                        $error = 'Invalid favicon file type.';
                    } else {
                        $uploadFileDir = __DIR__ . '/../../public/favicon/';
                        if (!is_dir($uploadFileDir)) {
                            mkdir($uploadFileDir, 0755, true);
                        }
                        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                        $dest_path = $uploadFileDir . $newFileName;
                        if (move_uploaded_file($fileTmpPath, $dest_path)) {
                            $faviconPath = '/favicon/' . $newFileName;
                        } else {
                            $error = 'Failed to save favicon file.';
                        }
                    }
                }
            }
            // If no error, update the project
            if (!$error) {
                // If no new favicon uploaded, try to fetch it from the website (only if no existing favicon)
                $hasNewFavicon = isset($_FILES['favicon']) && $_FILES['favicon']['error'] !== UPLOAD_ERR_NO_FILE;
                if (!$hasNewFavicon && empty($project['favicon'])) {
                    $fetchedFavicon = FaviconHelper::fetchFavicon($link);
                    if ($fetchedFavicon) {
                        $faviconPath = $fetchedFavicon;
                    }
                }
                $sortOrder = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : null;
                $data = [
                    'name' => $name,
                    'link' => $link,
                    'description' => $description,
                    'favicon' => $faviconPath,
                    'category_id' => $category_id
                ];
                if ($sortOrder !== null) {
                    $data['sort_order'] = $sortOrder;
                }
                Project::update($id, $data);
                $_SESSION['alertMessage'] = 'Project updated successfully!';
                $_SESSION['alertType'] = 'success';
                header('Location: ?controller=project&action=list#project-list');
                exit;
            } else {
                // On garde les valeurs saisies pour le formulaire
                $sortOrder = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : ($project['sort_order'] ?? 0);
                $project = array_merge($project, [
                    'name' => $name,
                    'link' => $link,
                    'description' => $description,
                    'category_id' => $category_id,
                    'sort_order' => $sortOrder
                ]);
            }
        }
        ob_start();
        include __DIR__ . '/../views/projects/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Gère la suppression d'un projet.
     *
     * @return void
     */
    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            Project::delete($id);
            $_SESSION['alertMessage'] = 'Project deleted successfully!';
            $_SESSION['alertType'] = 'warning';
        }
        header('Location: ?controller=project&action=list#project-list');
        exit;
    }

    public function backup() {
        require_once __DIR__ . '/../helpers/BackupHelper.php';
        \BackupHelper::backupAndDownload();
    }

    public function restore() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                require_once __DIR__ . '/../helpers/RestoreHelper.php';
                \RestoreHelper::restoreFromUpload();
                $_SESSION['alertMessage'] = 'Restoration completed successfully!';
                $_SESSION['alertType'] = 'success';
            } catch (\Exception $e) {
                $_SESSION['alertMessage'] = 'Restore failed: ' . $e->getMessage();
                $_SESSION['alertType'] = 'danger';
            }
            header('Location: ?controller=project&action=list');
            exit;
        } else {
            // Affiche le formulaire d'upload
            ob_start();
            echo '<h2>Restore backup</h2>';
            echo '<form method="post" enctype="multipart/form-data">';
            echo '<div class="mb-3"><label for="restore_zip" class="form-label">Backup zip file</label>';
            echo '<input type="file" name="restore_zip" id="restore_zip" class="form-control" accept="application/zip" required></div>';
            echo '<button type="submit" class="btn btn-success">Restore</button>';
            echo ' <a href="?controller=project&action=list" class="btn btn-secondary ms-2">Cancel</a>';
            echo '</form>';
            $content = ob_get_clean();
            include __DIR__ . '/../views/layout.php';
        }
    }
} 