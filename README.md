# 🛠️ StuProjectManager v0.2

## 📦 Requirements & Installation

- **PHP 8.0 or higher** (with SQLite3 extension enabled)
- **Composer** (for dependency management)

Clone the repository:

```bash
git clone https://github.com/Steform/StuProjectManager.git
cd StuProjectManager
composer install
```

Start a local PHP server (the document root must be `public/`):

```bash
php -S localhost:8000 -t public
```

Then open [http://localhost:8000](http://localhost:8000) in your browser.

- **Database**: The SQLite file (`projects.db`) is created automatically.
- **Favicon folder**: Ensure the `./public/favicon/` directory is writable (`chmod 755 public/favicon`).

## 🚀 Features
- Add, edit, and delete projects
- Organize projects by category
- Store data in an SQLite database
- Upload and manage project and category favicons
- Simple, accessible Bootstrap-based UI

## 📚 Documentation (Doxygen)
This project uses [Doxygen](https://www.doxygen.nl/) to generate developer documentation from PHP docblocks.

**The documentation is not versioned in the repository. You must generate it locally.**

### Generate the documentation
```bash
doxygen
```
The documentation will be generated in the `docs/` directory.

### View the documentation
Open `docs/html/index.html` in your web browser.

You will find class, method, and file documentation for the whole PHP codebase.

## 🧪 Automated Tests

This project uses [PHPUnit](https://phpunit.de/) for automated unit testing of the models.

### Run the tests

First, install dependencies (if not already done):
```bash
composer install
```

Then, run all tests:
```bash
vendor/bin/phpunit tests/
```

You should see output indicating the number of tests and assertions.

### Test structure
- All tests are located in the `tests/` directory (e.g., `tests/models/CategoryTest.php`, `tests/models/ProjectTest.php`).
- Tests use a temporary SQLite database to avoid interfering with your real data.

## ⚠️ Database integrity

- The application enforces referential integrity using SQLite foreign key constraints.
- A project must always be linked to an existing category (via `category_id`).
- You **cannot delete a category** if there are still projects linked to it (the database will reject the operation).
- This is enforced by a `FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE RESTRICT` constraint in the `projects` table.
- Automated tests verify this behavior: attempting to delete a category with linked projects will fail, and only succeeds after all related projects are deleted.

## 💡 Example usage

![Screenshot](docs/capture.png)

- Add a new project with a name, link, description, category, and favicon.
- Filter projects by category using the tabs.
- Manage categories (add, edit, delete) via the modal dialog accessible from the main project page.
- Delete or edit existing projects from the list.

### Editing workflow

- **Edit a project:**
  - Click the ✏️ button next to the project you want to edit in the project list.
  - The project form at the top of the page will switch to edit mode, pre-filled with the project data.
  - Make your changes and click "Update". Click "Cancel" to exit edit mode.

- **Edit a category:**
  - Click the "Manage categories" button to open the modal dialog.
  - In the modal, click the ✏️ button next to the category you want to edit.
  - The category form in the modal will switch to edit mode, pre-filled with the category data.
  - Make your changes and click "Update". Click "Cancel" to exit edit mode.

## 🤝 Contributing

Contributions are welcome! To contribute:

1. Fork the repository
2. Create a new branch (`git checkout -b my-feature`)
3. Make your changes and add tests if needed
4. Commit your changes (`git commit -am 'Add new feature'`)
5. Push to your fork (`git push origin my-feature`)
6. Open a Pull Request

Please ensure your code follows the existing style and passes all tests.

---

📜 License
This project is licensed under the GNU General Public License v3.0. See the LICENSE file for details.