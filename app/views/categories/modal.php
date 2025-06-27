<?php
/**
 * @file modal.php
 * @brief Category management modal view.
 */
$content = '<h2>Category Modal</h2>
<form id="category-form" method="post" action="?controller=category&action=create" enctype="multipart/form-data">
    <input type="hidden" name="cat_id" id="cat_id">
    <div class="mb-3">
        <label for="cat_name" class="form-label">Category name</label>
        <input type="text" class="form-control" id="cat_name" name="cat_name" required>
    </div>
    <div class="mb-3">
        <label for="cat_favicon" class="form-label">Favicon</label>
        <input type="file" class="form-control" id="cat_favicon" name="cat_favicon">
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>';
include __DIR__ . '/../layout.php'; 