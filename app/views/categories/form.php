<?php
/**
 * @file form.php
 * @brief Category creation/edit form view.
 */
$editing = isset($category) && !empty($category['id']);
$formAction = $editing ? '?controller=category&action=update&id=' . $category['id'] : '?controller=category&action=create';
?>
<h2><?= $editing ? 'Edit Category' : 'Create Category' ?></h2>
<?php if (isset($error) && $error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post" action="<?= $formAction ?>" enctype="multipart/form-data" class="mb-4 needs-validation" id="category-form" novalidate>
    <div class="mb-3">
        <label for="cat_name" class="form-label">Category name</label>
        <input type="text" class="form-control" id="cat_name" name="cat_name" required value="<?= htmlspecialchars($category['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <div class="invalid-feedback">Category name is required (2-100 characters).</div>
    </div>
    <div class="mb-3">
        <label for="cat_sort_order" class="form-label">Display order</label>
        <input type="number" class="form-control" id="cat_sort_order" name="cat_sort_order" min="0" value="<?= htmlspecialchars($category['sort_order'] ?? 0, ENT_QUOTES, 'UTF-8') ?>">
        <small class="form-text text-muted">Lower numbers appear first in tabs (0 = first position)</small>
    </div>
    <div class="mb-3">
        <label for="cat_favicon" class="form-label">Favicon</label>
        <input type="file" class="form-control" id="cat_favicon" name="cat_favicon">
        <?php if (!empty($category['favicon'])): ?>
            <div class="mt-2"><img src="<?= htmlspecialchars($category['favicon'], ENT_QUOTES, 'UTF-8') ?>" alt="Favicon" style="width:24px;height:24px;vertical-align:middle;"> Current favicon</div>
        <?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>
<script>
(function () {
    'use strict';
    var form = document.getElementById('category-form');
    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
})();
</script> 