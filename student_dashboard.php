<?php
require_once 'config.php';
require_role('student');

$user = get_user();
$student = fetch_one('SELECT s.*, p.name AS program_name FROM students s JOIN programs p ON s.program_id = p.id WHERE s.user_id = ?', [$user['id']]);

if (!$student) {
    set_flash('error', 'Student profile not found. Contact administration.', 'danger');
    redirect('logout.php');
}

$courses = fetch_all('SELECT e.*, c.code, c.title FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.student_id = ?', [$student['id']]);
$grades = fetch_all('SELECT g.grade, c.code, c.title FROM grades g JOIN enrollments e ON g.enrollment_id = e.id JOIN courses c ON e.course_id = c.id WHERE e.student_id = ?', [$student['id']]);
$materials = fetch_all('SELECT m.*, c.title FROM materials m JOIN courses c ON m.course_id = c.id ORDER BY m.uploaded_at DESC LIMIT 5');
$announcements = get_announcements('students');

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS result_pushes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lecturer_id INT NOT NULL,
        student_id INT NOT NULL,
        course_id INT NOT NULL,
        grade VARCHAR(10) NOT NULL,
        recipient ENUM('student', 'admin') NOT NULL,
        note TEXT NULL,
        pushed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_result_pushes_student (student_id),
        INDEX idx_result_pushes_recipient (recipient),
        INDEX idx_result_pushes_lecturer (lecturer_id),
        FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

try {
    $pdo->exec("ALTER TABLE result_pushes ADD COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
} catch (PDOException $e) {
}
try {
    $pdo->exec("ALTER TABLE students ADD COLUMN tuition_total DECIMAL(10,2) NOT NULL DEFAULT 2500.00");
} catch (PDOException $e) {
}
try {
    $pdo->exec("ALTER TABLE students ADD COLUMN tuition_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00");
} catch (PDOException $e) {
}

$student = fetch_one('SELECT s.*, p.name AS program_name FROM students s JOIN programs p ON s.program_id = p.id WHERE s.user_id = ?', [$user['id']]);
$tuitionTotal = floatval($student['tuition_total'] ?? 2500);
$tuitionPaid = floatval($student['tuition_paid'] ?? 0);
$tuitionBalance = max(0, $tuitionTotal - $tuitionPaid);
$tuitionPercent = $tuitionTotal > 0 ? round(($tuitionPaid / $tuitionTotal) * 100) : 0;

$resultUpdates = fetch_all(
    'SELECT rp.*, c.code, c.title, u.name AS lecturer_name
     FROM result_pushes rp
     JOIN courses c ON rp.course_id = c.id
     JOIN lecturers l ON rp.lecturer_id = l.id
     JOIN users u ON l.user_id = u.id
     WHERE rp.student_id = ? AND rp.recipient = "student" AND rp.status = "approved"
     ORDER BY rp.pushed_at DESC
     LIMIT 10',
    [$student['id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Starford International University</title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-dark">
    <nav class="navbar navbar-expand-lg navbar-dark siu-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="student_dashboard.php">
            <img src="uploads/siu.jpeg" alt="SIU Logo" class="me-2 rounded-circle" style="height: 40px; width: 40px;">
            Starford International University
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="student_dashboard.php"><i class="bi bi-info-circle me-1"></i>About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="student_dashboard.php"><i class="bi bi-book me-1"></i>Programs</a></li>
                <li class="nav-item"><a class="nav-link" href="student_dashboard.php"><i class="bi bi-clipboard-check me-1"></i>Admissions</a></li>
                <li class="nav-item"><a class="nav-link" href="student_dashboard.php"><i class="bi bi-newspaper me-1"></i>News</a></li>
                <li class="nav-item"><a class="nav-link" href="library.php"><i class="bi bi-book-half me-1"></i>Library</a></li>
            </ul>
        </div>
    </div>
</nav>
<nav class="navbar navbar-expand-lg navbar-dark siu-navbar-secondary">
    <div class="container">
        <a class="navbar-brand siu-campus-brand" href="student_dashboard.php">STUDENT DASHBOARD</a>
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
                    <p class="text-muted">Student dashboard for course registration, grades, materials and announcements.</p>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-secondary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Program</h5>
                            <p class="card-text"><?php echo sanitize($student['program_name'] ?? 'Not assigned'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-secondary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Registration No.</h5>
                            <p class="card-text"><?php echo sanitize($student['registration_number'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-secondary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Academic Year</h5>
                            <p class="card-text"><?php echo sanitize($student['enrollment_year'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Enrolled Courses</h5>
                    <?php if (empty($courses)): ?>
                        <p>No courses currently registered. Contact your administrator to enroll.</p>
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
                    <h5 class="card-title">Results & Grades</h5>
                    <?php if (empty($grades)): ?>
                        <p>No grades are yet available.</p>
                    <?php else: ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $row): ?>
                                    <tr>
                                        <td><?php echo sanitize($row['code'] . ' - ' . $row['title']); ?></td>
                                        <td><?php echo sanitize($row['grade']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Finance: Tuition Fee</h5>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded bg-light border h-100">
                                <small class="text-muted d-block">Total Tuition</small>
                                <strong>$<?php echo sanitize(number_format($tuitionTotal, 2)); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded bg-light border h-100">
                                <small class="text-muted d-block">Paid</small>
                                <strong class="text-success">$<?php echo sanitize(number_format($tuitionPaid, 2)); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded bg-light border h-100">
                                <small class="text-muted d-block">Balance</small>
                                <strong class="text-danger">$<?php echo sanitize(number_format($tuitionBalance, 2)); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="progress mb-2" role="progressbar" aria-label="Tuition payment progress" aria-valuenow="<?php echo sanitize($tuitionPercent); ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-success" style="width: <?php echo sanitize($tuitionPercent); ?>%"><?php echo sanitize($tuitionPercent); ?>%</div>
                    </div>
                    <small class="text-muted">Contact finance office for payment plans and receipt support.</small>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Learning Materials</h5>
                    <?php if (empty($materials)): ?>
                        <p>No materials uploaded yet.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($materials as $material): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo sanitize($material['title'] . ' (' . $material['title'] . ')'); ?></span>
                                    <a href="<?php echo sanitize($material['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Results from Lecturers</h5>
                    <?php if (empty($resultUpdates)): ?>
                        <p>No pushed results yet.</p>
                    <?php else: ?>
                        <?php foreach ($resultUpdates as $update): ?>
                            <div class="mb-3 pb-2 border-bottom">
                                <h6 class="mb-1"><?php echo sanitize($update['code'] . ' - ' . $update['title']); ?></h6>
                                <div class="small text-muted">By: <?php echo sanitize($update['lecturer_name']); ?></div>
                                <div><strong>Grade:</strong> <?php echo sanitize($update['grade']); ?></div>
                                <?php if (!empty($update['note'])): ?>
                                    <div class="small text-muted"><?php echo sanitize($update['note']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Announcements</h5>
                    <?php if (empty($announcements)): ?>
                        <p>No announcements at this time.</p>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="mb-3">
                                <h6><?php echo sanitize($announcement['title']); ?></h6>
                                <p class="mb-0 text-muted small"><?php echo sanitize(substr($announcement['message'], 0, 100)); ?>...</p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Support</h5>
                    <p>If you need help with registration, timetables or results, contact the student services office.</p>
                    <p><strong>Email:</strong> studentservices@starford.edu</p>
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
