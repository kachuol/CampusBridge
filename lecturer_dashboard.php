<?php
require_once 'config.php';
require_role('lecturer');

$user = get_user();
$lecturer = fetch_one('SELECT * FROM lecturers WHERE user_id = ?', [$user['id']]);

if (!$lecturer) {
    set_flash('error', 'Lecturer profile not found. Contact administration.', 'danger');
    redirect('logout.php');
}

$courses = fetch_all('SELECT * FROM courses WHERE lecturer_id = ?', [$lecturer['id']]);
$materials = fetch_all('SELECT m.*, c.title AS course_title FROM materials m JOIN courses c ON m.course_id = c.id WHERE m.lecturer_id = ? ORDER BY m.uploaded_at DESC', [$lecturer['id']]);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS result_pushes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lecturer_id INT NOT NULL,
        student_id INT NOT NULL,
        course_id INT NOT NULL,
        grade VARCHAR(10) NOT NULL,
        recipient ENUM('student', 'admin') NOT NULL DEFAULT 'student',
        note TEXT NULL,
        result_file_path VARCHAR(255) NULL,
        status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        verified_by INT NULL,
        verified_at DATETIME NULL,
        pushed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_result_pushes_student (student_id),
        INDEX idx_result_pushes_recipient (recipient),
        INDEX idx_result_pushes_lecturer (lecturer_id),
        INDEX idx_result_pushes_status (status),
        FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

try {
    $pdo->exec("ALTER TABLE result_pushes ADD COLUMN result_file_path VARCHAR(255) NULL");
} catch (PDOException $e) {
}
try {
    $pdo->exec("ALTER TABLE result_pushes ADD COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
} catch (PDOException $e) {
}
try {
    $pdo->exec("ALTER TABLE result_pushes ADD COLUMN verified_by INT NULL");
} catch (PDOException $e) {
}
try {
    $pdo->exec("ALTER TABLE result_pushes ADD COLUMN verified_at DATETIME NULL");
} catch (PDOException $e) {
}

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS result_sheet_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lecturer_id INT NOT NULL,
        course_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        note TEXT NULL,
        status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        verified_by INT NULL,
        verified_at DATETIME NULL,
        submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_result_sheets_status (status),
        INDEX idx_result_sheets_lecturer (lecturer_id),
        FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$courseEnrollments = fetch_all(
    'SELECT e.student_id, e.course_id, u.name AS student_name, s.registration_number, c.code, c.title
     FROM enrollments e
     JOIN students s ON e.student_id = s.id
     JOIN users u ON s.user_id = u.id
     JOIN courses c ON e.course_id = c.id
     WHERE c.lecturer_id = ?
     ORDER BY c.code, u.name',
    [$lecturer['id']]
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['push_result'])) {
    $studentId = intval($_POST['student_id'] ?? 0);
    $courseId = intval($_POST['course_id'] ?? 0);
    $grade = strtoupper(sanitize($_POST['grade'] ?? ''));
    $note = sanitize($_POST['note'] ?? '');

    $isOwnedEnrollment = fetch_one(
        'SELECT e.id
         FROM enrollments e
         JOIN courses c ON e.course_id = c.id
         WHERE e.student_id = ? AND e.course_id = ? AND c.lecturer_id = ?',
        [$studentId, $courseId, $lecturer['id']]
    );

    if (!$studentId || !$courseId || empty($grade) || !$isOwnedEnrollment) {
        set_flash('error', 'Please choose a valid student-course record and grade.', 'danger');
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO result_pushes (lecturer_id, student_id, course_id, grade, recipient, note, status, pushed_at)
             VALUES (?, ?, ?, ?, "student", ?, "pending", NOW())'
        );
        $stmt->execute([$lecturer['id'], $studentId, $courseId, $grade, $note ?: null]);
        set_flash('success', 'Result submitted to admin for verification before student release.', 'success');
        redirect('lecturer_dashboard.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_result_sheet'])) {
    $courseId = intval($_POST['course_id'] ?? 0);
    $sheetNote = sanitize($_POST['sheet_note'] ?? '');
    $sheetPath = upload_file('result_sheet_file');
    $isOwnedCourse = fetch_one('SELECT id FROM courses WHERE id = ? AND lecturer_id = ?', [$courseId, $lecturer['id']]);

    if (!$courseId || !$isOwnedCourse || !$sheetPath) {
        set_flash('error', 'Please select your course and upload a valid Excel/CSV result sheet.', 'danger');
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO result_sheet_submissions (lecturer_id, course_id, file_path, note, status, submitted_at)
             VALUES (?, ?, ?, ?, "pending", NOW())'
        );
        $stmt->execute([$lecturer['id'], $courseId, $sheetPath, $sheetNote ?: null]);
        set_flash('success', 'Result sheet posted to administrators for verification.', 'success');
        redirect('lecturer_dashboard.php');
    }
}

$recentResultPushes = fetch_all(
    'SELECT rp.*, c.code, c.title, u.name AS student_name
     FROM result_pushes rp
     JOIN courses c ON rp.course_id = c.id
     JOIN students s ON rp.student_id = s.id
     JOIN users u ON s.user_id = u.id
     WHERE rp.lecturer_id = ? AND rp.student_id > 0
     ORDER BY rp.pushed_at DESC
     LIMIT 10',
    [$lecturer['id']]
);

$recentResultSheets = fetch_all(
    'SELECT rs.*, c.code, c.title
     FROM result_sheet_submissions rs
     JOIN courses c ON rs.course_id = c.id
     WHERE rs.lecturer_id = ?
     ORDER BY rs.submitted_at DESC
     LIMIT 6',
    [$lecturer['id']]
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_material'])) {
    $title = sanitize($_POST['title'] ?? '');
    $course_id = intval($_POST['course_id'] ?? 0);
    $filePath = upload_file('material_file');

    if (!empty($title) && $course_id && $filePath) {
        $stmt = $pdo->prepare('INSERT INTO materials (lecturer_id, course_id, title, file_path, uploaded_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$lecturer['id'], $course_id, $title, $filePath]);
        set_flash('success', 'Material uploaded successfully.', 'success');
        redirect('lecturer_dashboard.php');
    } else {
        set_flash('error', 'Please provide a title, select a course, and upload a file.', 'danger');
    }
}

$announcements = get_announcements('lecturers');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Portal - Starford International University</title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-dark">
    <nav class="navbar navbar-expand-lg navbar-dark siu-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="lecturer_dashboard.php">
            <img src="uploads/siu.jpeg" alt="SIU Logo" class="me-2 rounded-circle" style="height: 40px; width: 40px;">
            Starford International University
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#about"><i class="bi bi-info-circle me-1"></i>About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="#program"><i class="bi bi-book me-1"></i>Programs</a></li>
                <li class="nav-item"><a class="nav-link" href="#admission"><i class="bi bi-clipboard-check me-1"></i>Admissions</a></li>
                <li class="nav-item"><a class="nav-link" href="#news"><i class="bi bi-newspaper me-1"></i>News</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact"><i class="bi bi-envelope me-1"></i>Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a></li>
                <li class="nav-item"><a class="nav-link" href="register.php"><i class="bi bi-pencil-square me-1"></i>Apply</a></li>
            </ul>
        </div>
    </div>
</nav>
<nav class="navbar navbar-expand-lg navbar-dark siu-navbar-secondary">
    <div class="container">
        <a class="navbar-brand siu-campus-brand" href="lecturer_dashboard.php">LECTURER DASHBOARD</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="btn btn-danger btn-sm ms-lg-2" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h3>Welcome, <?php echo sanitize($user['name']); ?></h3>
                    <p class="text-muted">Lecturer portal for course materials, student lists, and announcements.</p>
                    <?php display_flash(); ?>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Assigned Courses</h5>
                    <?php if (empty($courses)): ?>
                        <p>No courses assigned. Please contact administration.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($courses as $course): ?>
                                <li class="list-group-item"><?php echo sanitize($course['code'] . ' - ' . $course['title']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Upload Course Material</h5>
                    <form method="post" action="lecturer_dashboard.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Material Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Course</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Select course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"><?php echo sanitize($course['code'] . ' - ' . $course['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload File</label>
                            <input type="file" name="material_file" class="form-control" required>
                        </div>
                        <button type="submit" name="upload_material" class="btn btn-primary">Upload Material</button>
                    </form>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Push Student Results</h5>
                    <form method="post" action="lecturer_dashboard.php">
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">Select student</option>
                                <?php foreach ($courseEnrollments as $row): ?>
                                    <option value="<?php echo sanitize($row['student_id']); ?>"><?php echo sanitize($row['student_name'] . ' (' . $row['registration_number'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Course</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Select course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo sanitize($course['id']); ?>"><?php echo sanitize($course['code'] . ' - ' . $course['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Grade</label>
                                <input type="text" name="grade" class="form-control" placeholder="e.g A, B+, 74%" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Push To</label>
                                <select name="recipient" class="form-select" required>
                                    <option value="student">Student</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 mb-3">
                            <label class="form-label">Note (Optional)</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Optional remark"></textarea>
                        </div>
                        <button type="submit" name="push_result" class="btn btn-primary">Push Result</button>
                    </form>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Upload Excel Results to Admin</h5>
                    <form method="post" action="lecturer_dashboard.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Course</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Select course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo sanitize($course['id']); ?>"><?php echo sanitize($course['code'] . ' - ' . $course['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Excel/CSV File</label>
                            <input type="file" name="result_sheet_file" class="form-control" required>
                            <small class="text-muted">Accepted: .xls, .xlsx, .csv</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note (Optional)</label>
                            <textarea name="sheet_note" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" name="upload_result_sheet" class="btn btn-outline-primary">Send Sheet for Admin Verification</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Your Profile</h5>
                    <p><strong>Email:</strong> <?php echo sanitize($user['email']); ?></p>
                    <p><strong>Department:</strong> <?php echo sanitize($lecturer['department_id'] ?? 'N/A'); ?></p>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Recent Materials</h5>
                    <?php if (empty($materials)): ?>
                        <p>No uploads yet.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($materials as $material): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo sanitize($material['title']); ?>
                                    <a href="<?php echo sanitize($material['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h5>Announcements</h5>
                    <?php if (empty($announcements)): ?>
                        <p>No announcements yet.</p>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="mb-3">
                                <h6><?php echo sanitize($announcement['title']); ?></h6>
                                <p class="mb-0 small text-muted"><?php echo sanitize(substr($announcement['message'], 0, 100)); ?>...</p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h5>Recent Result Pushes</h5>
                    <?php if (empty($recentResultPushes)): ?>
                        <p>No results pushed yet.</p>
                    <?php else: ?>
                        <?php foreach ($recentResultPushes as $push): ?>
                            <div class="mb-3 pb-2 border-bottom">
                                <strong><?php echo sanitize($push['student_name']); ?></strong>
                                <div class="small text-muted"><?php echo sanitize($push['code'] . ' - ' . $push['title']); ?></div>
                                <div class="small">Grade: <?php echo sanitize($push['grade']); ?> | To: <?php echo sanitize(ucfirst($push['recipient'])); ?> | Status: <?php echo sanitize(ucfirst($push['status'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h5>Recent Result Sheets</h5>
                    <?php if (empty($recentResultSheets)): ?>
                        <p>No result sheets uploaded yet.</p>
                    <?php else: ?>
                        <?php foreach ($recentResultSheets as $sheet): ?>
                            <div class="mb-3 pb-2 border-bottom">
                                <strong><?php echo sanitize($sheet['code'] . ' - ' . $sheet['title']); ?></strong>
                                <div class="small text-muted">Status: <?php echo sanitize(ucfirst($sheet['status'])); ?></div>
                                <a href="<?php echo sanitize($sheet['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary mt-1">View Sheet</a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Bar -->
        <hr class="my-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Starford International University. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="#" class="text-light text-decoration-none me-3">Privacy Policy</a>
                <a href="#" class="text-light text-decoration-none me-3">Terms of Service</a>
                <a href="#" class="text-light text-decoration-none">Accessibility</a>
            </div>
        </div>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
