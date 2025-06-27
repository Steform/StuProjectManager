<?php
/**
 * @file form.php
 * @brief Project creation/edit form view.
 */
$editing = isset($project) && !empty($project['id']);
$formAction = $editing ? '?controller=project&action=update&id=' . $project['id'] : '?controller=project&action=create';
if (!isset($categoryList)) $categoryList = [];
?>
<h2><?= $editing ? 'Edit Project' : 'Create Project' ?></h2>
<?php if (isset($error) && $error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post" action="<?= $formAction ?>" enctype="multipart/form-data" class="mb-4 needs-validation" id="project-form" novalidate>
    <div class="row">
        <div class="col-5"><label for="name" class="form-label">Project Name</label></div>
        <div class="col-7">
            <input type="text" name="name" id="name" class="form-control" required minlength="2" maxlength="100" pattern="[A-Za-z0-9 _\-]+" value="<?= htmlspecialchars($project['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="invalid-feedback">Please enter a project name (2-100 characters, letters, numbers, spaces, - or _).</div>
        </div>
        <div class="col-5"><label for="link" class="form-label">Project Link</label></div>
        <div class="col-7">
            <input type="url" name="link" id="link" class="form-control" required maxlength="255" pattern="https?://.+" value="<?= htmlspecialchars($project['link'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="invalid-feedback">Please enter a valid URL (starting with http:// or https://).</div>
        </div>
        <div class="col-5"><label for="description" class="form-label">Description</label></div>
        <div class="col-7">
            <textarea name="description" id="description" class="form-control" rows="3" maxlength="500"><?= htmlspecialchars($project['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="invalid-feedback">Description must be less than 500 characters.</div>
        </div>
        <div class="col-5"><label for="category_id" class="form-label">Category</label></div>
        <div class="col-7">
            <select name="category_id" id="category_id" class="form-control" required>
                <?php foreach ($categoryList as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= (isset($project['category_id']) && $project['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Please select a category.</div>
        </div>
        <div class="col-5"><label for="favicon" class="form-label">Favicon (Image file)</label></div>
        <div class="col-7">
            <input type="file" name="favicon" id="favicon" class="form-control" accept="image/png,image/jpeg,image/gif,image/x-icon,image/svg+xml,image/vnd.microsoft.icon">
            <?php if (!empty($project['favicon'])): ?>
                <div class="mt-2"><img src="<?= htmlspecialchars($project['favicon'], ENT_QUOTES, 'UTF-8') ?>" alt="Favicon" style="width:24px;height:24px;vertical-align:middle;"> Current favicon</div>
            <?php endif; ?>
            <div class="invalid-feedback">Please select a valid image file (png, jpg, jpeg, gif, ico, svg).</div>
        </div>
        <div class="col-12"><button type="submit" class="btn btn-primary">Save</button></div>
    </div>
</form>
<script>
(function () {
    'use strict';
    var form = document.getElementById('project-form');
    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
})();
</script> 