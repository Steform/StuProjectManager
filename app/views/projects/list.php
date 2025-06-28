<?php
/**
 * @file list.php
 * @brief Main project management view: form, category tabs, project table, and category modal.
 *
 * This view displays:
 *   - The project creation form
 *   - Bootstrap tabs for category filtering
 *   - The project list table (filtered by category)
 *   - The modal for category management
 *
 * Variables expected:
 *   - $projects: array of all projects
 *   - $categoryList: array of all categories
 */

// Determine the active category
$firstCategoryId = !empty($categoryList) ? $categoryList[0]['id'] : null;
$activeCategoryId = isset($_GET['cat']) ? intval($_GET['cat']) : $firstCategoryId;

// Filter projects by selected category
$filteredProjects = array_filter($projects, function($p) use ($activeCategoryId) {
    return isset($p['category_id']) && $p['category_id'] == $activeCategoryId;
});
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Project Management</h1>
    <div class="btn-group" role="group" aria-label="Project actions">
        <a href="?controller=project&action=backup" class="btn btn-outline-primary" id="backup-btn" type="button">Backup</a>
        <a href="?controller=project&action=restore" class="btn btn-outline-success ms-2" id="restore-btn" type="button">Restore</a>
        <button class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#categoryModal">Manage categories</button>
    </div>
</div>

<!-- Project creation/edit form -->
<?php
$editing = isset($editProject) && !empty($editProject['id']);
$formAction = '?controller=project&action=list' . ($editing ? '&edit_id=' . $editProject['id'] : '');
?>
<form method="post" action="<?= $formAction ?>" enctype="multipart/form-data" class="mb-4 needs-validation" id="project-form" novalidate>
    <input type="hidden" name="project_form" value="1">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editProject['id'] ?>"><?php endif; ?>
    <div class="row">
        <div class="col-5"><label for="name" class="form-label">Project Name</label></div>
        <div class="col-7">
            <input type="text" name="name" id="name" class="form-control" required minlength="2" maxlength="100" pattern="[A-Za-z0-9 _\-]+" value="<?= htmlspecialchars($editing ? $editProject['name'] : '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="invalid-feedback">Please enter a project name (2-100 characters, letters, numbers, spaces, - or _).</div>
        </div>
        <div class="col-5"><label for="link" class="form-label">Project Link</label></div>
        <div class="col-7">
            <input type="url" name="link" id="link" class="form-control" required maxlength="255" pattern="https?://.+" value="<?= htmlspecialchars($editing ? $editProject['link'] : '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="invalid-feedback">Please enter a valid URL (starting with http:// or https://).</div>
        </div>
        <div class="col-5"><label for="description" class="form-label">Description</label></div>
        <div class="col-7">
            <textarea name="description" id="description" class="form-control" rows="3" maxlength="500"><?= htmlspecialchars($editing ? $editProject['description'] : '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="invalid-feedback">Description must be less than 500 characters.</div>
        </div>
        <div class="col-5"><label for="category_id" class="form-label">Category</label></div>
        <div class="col-7">
            <select name="category_id" id="category_id" class="form-control" required>
                <?php foreach ($categoryList as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($editing && isset($editProject['category_id']) && $editProject['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Please select a category.</div>
        </div>
        <div class="col-5"><label for="favicon" class="form-label">Favicon (Image file)</label></div>
        <div class="col-7">
            <input type="file" name="favicon" id="favicon" class="form-control" accept="image/png,image/jpeg,image/gif,image/x-icon,image/svg+xml,image/vnd.microsoft.icon">
            <?php if ($editing && !empty($editProject['favicon'])): ?>
                <div class="mt-2"><img src="<?= htmlspecialchars($editProject['favicon'], ENT_QUOTES, 'UTF-8') ?>" alt="Favicon" style="width:24px;height:24px;vertical-align:middle;"> Current favicon</div>
            <?php endif; ?>
            <div class="invalid-feedback">Please select a valid image file (png, jpg, jpeg, gif, ico, svg).</div>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Save' ?></button>
            <?php if ($editing): ?>
                <a href="?controller=project&action=list" class="btn btn-secondary ms-2">Cancel</a>
            <?php endif; ?>
        </div>
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



<!-- Bootstrap tabs for categories -->
<ul class="nav nav-tabs mb-3" id="categoryTabs" role="tablist">
    <?php foreach ($categoryList as $cat): ?>
        <li class="nav-item" role="presentation">
            <a class="nav-link<?= ($cat['id'] == $activeCategoryId ? ' active' : '') ?>" href="?controller=project&action=list&cat=<?= $cat['id'] ?>" role="tab">
                <?php if (!empty($cat['favicon'])): ?>
                    <img src="<?= htmlspecialchars($cat['favicon'], ENT_QUOTES, 'UTF-8') ?>" alt="Favicon" style="width:20px;height:20px;vertical-align:middle;"> 
                <?php else: ?>
                    <img src="/favicon/favicon.png" alt="Favicon" style="width:20px;height:20px;vertical-align:middle;"> 
                <?php endif; ?>
                <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<!-- Project list table -->
<table class="table table-striped" role="table" aria-label="Project list table">
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
        <?php if (!empty($filteredProjects)): ?>
            <?php foreach ($filteredProjects as $projet): ?>
                <tr id="row-<?= $projet['id'] ?>">
                    <td>
                        <?php if (!empty($projet['favicon'])): ?>
                            <img src="<?= htmlspecialchars($projet['favicon'], ENT_QUOTES, 'UTF-8') ?>" alt="Favicon" style="width: 32px; height: 32px;">
                        <?php else: ?>
                            <img src="/favicon/favicon.png" alt="Favicon" style="width: 32px; height: 32px;">
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($projet['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><a href="<?= htmlspecialchars($projet['link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank"><?= htmlspecialchars($projet['link'], ENT_QUOTES, 'UTF-8') ?></a></td>
                    <td><?= htmlspecialchars($projet['description'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <a href="?controller=project&action=delete&id=<?= $projet['id'] ?>" class="btn btn-danger" aria-label="Delete project <?= htmlspecialchars($projet['name'], ENT_QUOTES, 'UTF-8') ?>" onclick="return confirm('Do you really want to delete this project?')">🗑️</a>
                            <a href="?controller=project&action=list&edit_id=<?= $projet['id'] ?>" class="btn btn-warning" aria-label="Edit project <?= htmlspecialchars($projet['name'], ENT_QUOTES, 'UTF-8') ?>">✏️</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Category management modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="categoryModalLabel">Category management</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="category-form" method="post" action="?controller=category&action=create" enctype="multipart/form-data">
          <input type="hidden" name="id" id="cat_id">
          <div class="mb-3">
            <label for="cat_name" class="form-label">Category name</label>
            <input type="text" class="form-control" id="cat_name" name="cat_name" required>
          </div>
          <div class="mb-3">
            <label for="cat_favicon" class="form-label">Favicon</label>
            <input type="file" class="form-control" id="cat_favicon" name="cat_favicon">
            <div id="current-favicon" class="mt-2" style="display:none;"></div>
          </div>
          <button type="submit" class="btn btn-primary" id="cat-save-btn">Save</button>
          <button type="button" class="btn btn-secondary ms-2" id="cat-cancel-btn" style="display:none;" data-bs-dismiss="modal">Cancel</button>
        </form>
        <hr>
        <h6>Existing categories</h6>
        <ul class="list-group" id="category-list">
          <?php foreach ($categoryList as $cat): ?>
            <li class="list-group-item d-flex align-items-center justify-content-between">
              <?php if (!empty($cat['favicon'])): ?>
                <img src="<?= htmlspecialchars($cat['favicon'], ENT_QUOTES, 'UTF-8') ?>" alt="Favicon" style="width:24px;height:24px;margin-right:8px;">
              <?php else: ?>
                <img src="/favicon/favicon.png" alt="Favicon" style="width:24px;height:24px;margin-right:8px;">
              <?php endif; ?>
              <span><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></span>
              <span>
                <button type="button" class="btn btn-sm btn-warning me-1 edit-category-btn"
                  data-id="<?= $cat['id'] ?>"
                  data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>"
                  data-favicon="<?= htmlspecialchars($cat['favicon'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  >✏️</button>
                <a href="?controller=category&action=delete&id=<?= $cat['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">🗑️</a>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>
<script>
// Bootstrap modale + édition catégorie JS
(function () {
  // Ouvre la modale en mode édition et pré-remplit le formulaire
  document.querySelectorAll('.edit-category-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = this.getAttribute('data-id');
      var name = this.getAttribute('data-name');
      var favicon = this.getAttribute('data-favicon');
      var form = document.getElementById('category-form');
      form.action = '?controller=category&action=update&id=' + id;
      document.getElementById('cat_id').value = id;
      document.getElementById('cat_name').value = name;
      document.getElementById('cat_favicon').value = '';
      var currentFavicon = document.getElementById('current-favicon');
      if (favicon) {
        currentFavicon.innerHTML = '<img src="' + favicon + '" alt="Favicon" style="width:24px;height:24px;vertical-align:middle;"> Current favicon';
        currentFavicon.style.display = '';
      } else {
        currentFavicon.innerHTML = '';
        currentFavicon.style.display = 'none';
      }
      document.getElementById('cat-save-btn').textContent = 'Update';
      document.getElementById('cat-cancel-btn').style.display = '';
      var modal = new bootstrap.Modal(document.getElementById('categoryModal'));
      modal.show();
    });
  });
  // Remet le formulaire en mode ajout quand on ferme la modale ou clique sur Cancel
  var resetCatForm = function() {
    var form = document.getElementById('category-form');
    form.action = '?controller=category&action=create';
    document.getElementById('cat_id').value = '';
    document.getElementById('cat_name').value = '';
    document.getElementById('cat_favicon').value = '';
    document.getElementById('current-favicon').innerHTML = '';
    document.getElementById('current-favicon').style.display = 'none';
    document.getElementById('cat-save-btn').textContent = 'Save';
    document.getElementById('cat-cancel-btn').style.display = 'none';
  };
  document.getElementById('cat-cancel-btn').addEventListener('click', resetCatForm);
  document.getElementById('categoryModal').addEventListener('hidden.bs.modal', resetCatForm);
})();
</script>

<?php include __DIR__ . '/../layout.php'; ?> 