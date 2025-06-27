<?php
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Database.php';

class CategoryController {
    /**
     * Create a new category (from the modal form).
     *
     * Handles validation and favicon upload. Redirects to the project list after creation.
     *
     * @return void
     */
    public function create() {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $faviconPath = null;
            // --- Server-side validation ---
            $name = trim($_POST['cat_name'] ?? '');
            $allowedfileExtensions = array('png', 'jpg', 'jpeg', 'ico', 'gif', 'svg');
            $allowedMimeTypes = [
                'image/png', 'image/jpeg', 'image/gif', 'image/x-icon', 'image/svg+xml', 'image/vnd.microsoft.icon'
            ];
            // Validate category name
            if ($name === '' || strlen($name) < 2 || strlen($name) > 100) {
                $error = 'Category name must be 2-100 characters.';
            }
            // Validate favicon if present
            elseif (isset($_FILES['cat_favicon']) && $_FILES['cat_favicon']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['cat_favicon']['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Error uploading favicon.';
                } else {
                    $fileTmpPath = $_FILES['cat_favicon']['tmp_name'];
                    $fileName = $_FILES['cat_favicon']['name'];
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
            // If no error, create the category
            if (!$error) {
                $data = [
                    'name' => $name,
                    'favicon' => $faviconPath
                ];
                Category::create($data);
                $_SESSION['alertMessage'] = 'Category added successfully!';
                $_SESSION['alertType'] = 'success';
                header('Location: ?controller=project&action=list');
                exit;
            }
        }
        ob_start();
        if (isset($error)) echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
        echo '<h2>Category Form</h2>';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Update an existing category (from the modal form).
     *
     * Handles validation and favicon upload. Redirects to the project list after update.
     *
     * @return void
     */
    public function update() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ?controller=project&action=list');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $faviconPath = null;
            $name = trim($_POST['cat_name'] ?? '');
            $allowedfileExtensions = array('png', 'jpg', 'jpeg', 'ico', 'gif', 'svg');
            $allowedMimeTypes = [
                'image/png', 'image/jpeg', 'image/gif', 'image/x-icon', 'image/svg+xml', 'image/vnd.microsoft.icon'
            ];
            // Validation
            if ($name === '' || strlen($name) < 2 || strlen($name) > 100) {
                $_SESSION['alertMessage'] = 'Category name must be 2-100 characters.';
                $_SESSION['alertType'] = 'danger';
            } elseif (isset($_FILES['cat_favicon']) && $_FILES['cat_favicon']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['cat_favicon']['error'] !== UPLOAD_ERR_OK) {
                    $_SESSION['alertMessage'] = 'Error uploading favicon.';
                    $_SESSION['alertType'] = 'danger';
                } else {
                    $fileTmpPath = $_FILES['cat_favicon']['tmp_name'];
                    $fileName = $_FILES['cat_favicon']['name'];
                    $fileNameCmps = explode('.', $fileName);
                    $fileExtension = strtolower(end($fileNameCmps));
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $fileTmpPath);
                    finfo_close($finfo);
                    if (!in_array($fileExtension, $allowedfileExtensions) || !in_array($mimeType, $allowedMimeTypes)) {
                        $_SESSION['alertMessage'] = 'Invalid favicon file type.';
                        $_SESSION['alertType'] = 'danger';
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
                            $_SESSION['alertMessage'] = 'Failed to save favicon file.';
                            $_SESSION['alertType'] = 'danger';
                        }
                    }
                }
            }
            // Si pas d'erreur, on update
            if (!isset($_SESSION['alertMessage'])) {
                $data = [
                    'name' => $name,
                    'favicon' => $faviconPath ?? null
                ];
                Category::update($id, $data);
                $_SESSION['alertMessage'] = 'Category updated successfully!';
                $_SESSION['alertType'] = 'success';
            }
        }
        header('Location: ?controller=project&action=list');
        exit;
    }

    /**
     * Delete a category by its ID.
     *
     * Prevents deletion if the category is assigned to any project. Redirects to the project list after deletion.
     *
     * @return void
     */
    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            // Check if the category is assigned to any project
            $db = Database::getConnection();
            $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM projects WHERE category_id = ?');
            $stmt->bindValue(1, $id, SQLITE3_INTEGER);
            $res = $stmt->execute();
            $row = $res->fetchArray(SQLITE3_ASSOC);
            if ($row && $row['cnt'] > 0) {
                $_SESSION['alertMessage'] = 'Cannot delete this category: it is assigned to one or more projects.';
                $_SESSION['alertType'] = 'danger';
            } else {
                Category::delete($id);
                $_SESSION['alertMessage'] = 'Category deleted successfully!';
                $_SESSION['alertType'] = 'warning';
            }
        }
        header('Location: ?controller=project&action=list');
        exit;
    }
} 