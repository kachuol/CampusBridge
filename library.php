<?php
require_once 'config.php';
require_login();

if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'student') {
    set_flash('auth', 'You do not have permission to access the library catalogue.', 'danger');
    redirect('dashboard.php');
}

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS library_books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(180) NULL,
        isbn VARCHAR(40) NULL,
        category VARCHAR(120) NULL,
        available_copies INT NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_library_books_title (title)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['user_role'] === 'admin') {
    if (isset($_POST['add_book'])) {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $availableCopies = filter_var($_POST['available_copies'] ?? null, FILTER_VALIDATE_INT);

        if ($title === '' || $availableCopies === false || $availableCopies < 0) {
            set_flash('error', 'Enter a book title and a valid non-negative copy count.', 'danger');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO library_books (title, author, isbn, category, available_copies) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $title,
                $author !== '' ? $author : null,
                $isbn !== '' ? $isbn : null,
                $category !== '' ? $category : null,
                $availableCopies,
            ]);
            set_flash('success', 'Book added to the library catalogue.', 'success');
            redirect('library.php');
        }
    }

    if (isset($_POST['delete_book'])) {
        $bookId = filter_var($_POST['book_id'] ?? null, FILTER_VALIDATE_INT);
        if ($bookId === false || $bookId < 1) {
            set_flash('error', 'Select a valid catalogue entry.', 'danger');
        } else {
            $stmt = $pdo->prepare('DELETE FROM library_books WHERE id = ?');
            $stmt->execute([$bookId]);
            set_flash('success', 'Book removed from the library catalogue.', 'success');
            redirect('library.php');
        }
    }
}

$books = fetch_all('SELECT * FROM library_books ORDER BY title, author');
$isAdmin = $_SESSION['user_role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Catalogue - Starford International University</title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-dark">
<nav class="navbar navbar-expand-lg navbar-dark siu-navbar-secondary">
    <div class="container">
        <a class="navbar-brand siu-campus-brand" href="<?php echo $isAdmin ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>">SIU LIBRARY</a>
        <div class="ms-auto d-flex gap-2">
            <a class="btn btn-outline-light btn-sm" href="<?php echo $isAdmin ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>">Dashboard</a>
            <a class="btn btn-danger btn-sm" href="logout.php">Logout</a>
        </div>
    </div>
</nav>
<main class="container py-5">
    <?php display_flash(); ?>
    <div class="row g-4">
        <?php if ($isAdmin): ?>
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="h4 mb-3">Add a catalogue item</h1>
                        <form method="post" action="library.php">
                            <div class="mb-3">
                                <label for="book-title" class="form-label">Title</label>
                                <input id="book-title" name="title" class="form-control" maxlength="255" required>
                            </div>
                            <div class="mb-3">
                                <label for="book-author" class="form-label">Author</label>
                                <input id="book-author" name="author" class="form-control" maxlength="180">
                            </div>
                            <div class="mb-3">
                                <label for="book-isbn" class="form-label">ISBN</label>
                                <input id="book-isbn" name="isbn" class="form-control" maxlength="40">
                            </div>
                            <div class="mb-3">
                                <label for="book-category" class="form-label">Category</label>
                                <input id="book-category" name="category" class="form-control" maxlength="120">
                            </div>
                            <div class="mb-3">
                                <label for="book-copies" class="form-label">Available copies</label>
                                <input id="book-copies" type="number" name="available_copies" class="form-control" min="0" value="1" required>
                            </div>
                            <button type="submit" name="add_book" class="btn btn-primary w-100">Add Book</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="<?php echo $isAdmin ? 'col-lg-8' : 'col-12'; ?>">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h3 mb-3">Library Catalogue</h1>
                    <?php if (empty($books)): ?>
                        <p class="mb-0">There are no books in the catalogue yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Category</th>
                                        <th>ISBN</th>
                                        <th>Available copies</th>
                                        <?php if ($isAdmin): ?><th>Action</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td><?php echo sanitize($book['title']); ?></td>
                                        <td><?php echo sanitize($book['author'] ?: '—'); ?></td>
                                        <td><?php echo sanitize($book['category'] ?: '—'); ?></td>
                                        <td><?php echo sanitize($book['isbn'] ?: '—'); ?></td>
                                        <td><?php echo sanitize($book['available_copies']); ?></td>
                                        <?php if ($isAdmin): ?>
                                            <td>
                                                <form method="post" action="library.php" onsubmit="return confirm('Remove this book from the catalogue?');">
                                                    <input type="hidden" name="book_id" value="<?php echo sanitize($book['id']); ?>">
                                                    <button type="submit" name="delete_book" class="btn btn-sm btn-outline-danger">Remove</button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
