// JS management for categories in the modal
function editCategory(id, name, favicon) {
    document.getElementById('cat_id').value = id;
    document.getElementById('cat_nom').value = name;
    // The file input cannot be pre-filled for security reasons
    // You could display the current favicon next to the field if needed
}

function deleteCategory(id) {
    if (confirm('Do you really want to delete this category?')) {
        window.location.href = '?delete_cat=' + id;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Reset the form when the modal is opened for adding
    var categoryModal = document.getElementById('categoryModal');
    if (categoryModal) {
        categoryModal.addEventListener('show.bs.modal', function () {
            document.getElementById('cat_id').value = '';
            document.getElementById('cat_nom').value = '';
            document.getElementById('cat_favicon').value = '';
        });
    }
}); 