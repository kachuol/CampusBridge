<?php
require_once 'config.php';
require_role('admin');

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
try {
    $pdo->exec("ALTER TABLE students ADD COLUMN tuition_total DECIMAL(10,2) NOT NULL DEFAULT 2500.00");
} catch (PDOException $e) {
}
try {
    $pdo->exec("ALTER TABLE students ADD COLUMN tuition_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00");
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_applicant'])) {
        $applicantId = intval($_POST['applicant_id']);
        $pdo->prepare('UPDATE applicants SET status = ? WHERE id = ?')->execute(['Approved', $applicantId]);
        set_flash('success', 'Applicant has been approved.', 'success');
        redirect('admin_dashboard.php');
    }

    if (isset($_POST['reject_applicant'])) {
        $applicantId = intval($_POST['applicant_id']);
        $pdo->prepare('UPDATE applicants SET status = ? WHERE id = ?')->execute(['Rejected', $applicantId]);
        set_flash('success', 'Applicant has been rejected.', 'success');
        redirect('admin_dashboard.php');
    }

    if (isset($_POST['convert_student'])) {
        $applicantId = intval($_POST['applicant_id']);
        $applicant = fetch_one('SELECT * FROM applicants WHERE id = ?', [$applicantId]);

        if ($applicant) {
            $exists = fetch_one('SELECT id FROM students WHERE user_id = ?', [$applicant['user_id']]);

            if (!$exists) {
                $registrationNumber = 'STU' . str_pad($applicantId, 4, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare('INSERT INTO students (user_id, registration_number, program_id, enrollment_year, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$applicant['user_id'], $registrationNumber, $applicant['program_id'] ?? 1, date('Y'), 'Active']);
                $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute(['student', $applicant['user_id']]);
                set_flash('success', 'Applicant has been converted to a student.', 'success');
            } else {
                set_flash('info', 'This applicant is already a student.', 'info');
            }
        }

        redirect('admin_dashboard.php');
    }

    if (isset($_POST['create_announcement'])) {
        $title = sanitize($_POST['title'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        $audience = sanitize($_POST['audience'] ?? 'public');
        $allowedAudiences = ['public', 'students', 'lecturers', 'applicants'];

        if (!in_array($audience, $allowedAudiences, true)) {
            $audience = 'public';
        }

        if (!empty($title) && !empty($message)) {
            $stmt = $pdo->prepare('INSERT INTO announcements (title, message, audience, created_at) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$title, $message, $audience]);
            set_flash('success', 'Announcement posted successfully for ' . ucfirst($audience) . '.', 'success');
            redirect('admin_dashboard.php');
        } else {
            set_flash('error', 'Please complete the announcement title and message.', 'danger');
        }
    }

    if (isset($_POST['create_lecturer'])) {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $departmentId = intval($_POST['department_id'] ?? 0);
        $phone = sanitize($_POST['phone'] ?? '');
        $office = sanitize($_POST['office'] ?? '');

        if (empty($name) || empty($email) || empty($password) || $departmentId <= 0) {
            set_flash('error', 'Please complete the lecturer name, email, password, and department.', 'danger');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Please provide a valid email address.', 'danger');
        } else {
            $existing = fetch_one('SELECT id FROM users WHERE email = ?', [$email]);

            if ($existing) {
                set_flash('error', 'A user with that email already exists.', 'danger');
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
                $stmt->execute([$name, $email, $hashed, 'lecturer']);
                $userId = $pdo->lastInsertId();

                $stmt = $pdo->prepare('INSERT INTO lecturers (user_id, department_id, phone, office, created_at) VALUES (?, ?, ?, ?, NOW())');
                $stmt->execute([$userId, $departmentId, $phone ?: null, $office ?: null]);

                set_flash('success', 'Lecturer account created successfully.', 'success');
                redirect('admin_dashboard.php');
            }
        }
    }

    if (isset($_POST['update_student_fees'])) {
        $studentId = intval($_POST['student_id'] ?? 0);
        $tuitionTotal = floatval($_POST['tuition_total'] ?? 0);
        $tuitionPaid = floatval($_POST['tuition_paid'] ?? 0);

        if ($studentId <= 0 || $tuitionTotal < 0 || $tuitionPaid < 0 || $tuitionPaid > $tuitionTotal) {
            set_flash('error', 'Please provide valid tuition totals and payment values.', 'danger');
        } else {
            $stmt = $pdo->prepare('UPDATE students SET tuition_total = ?, tuition_paid = ? WHERE id = ?');
            $stmt->execute([$tuitionTotal, $tuitionPaid, $studentId]);
            set_flash('success', 'Student fee record updated.', 'success');
            redirect('admin_dashboard.php');
        }
    }

    if (isset($_POST['verify_result_push'])) {
        $resultPushId = intval($_POST['result_push_id'] ?? 0);
        $decision = sanitize($_POST['decision'] ?? '');
        $resultPush = fetch_one(
            'SELECT rp.*, s.tuition_total, s.tuition_paid
             FROM result_pushes rp
             JOIN students s ON rp.student_id = s.id
             WHERE rp.id = ?',
            [$resultPushId]
        );

        if (!$resultPush || !in_array($decision, ['approved', 'rejected'], true)) {
            set_flash('error', 'Invalid result verification request.', 'danger');
        } else {
            $balance = max(0, floatval($resultPush['tuition_total']) - floatval($resultPush['tuition_paid']));

            if ($decision === 'approved' && $balance > 0) {
                set_flash('error', 'Cannot approve result: this student has an outstanding fee balance.', 'danger');
            } else {
                $stmt = $pdo->prepare('UPDATE result_pushes SET status = ?, verified_by = ?, verified_at = NOW() WHERE id = ?');
                $stmt->execute([$decision, $user['id'], $resultPushId]);
                set_flash('success', 'Result has been ' . $decision . ' successfully.', 'success');
            }
            redirect('admin_dashboard.php');
        }
    }

    if (isset($_POST['verify_result_sheet'])) {
        $sheetId = intval($_POST['sheet_id'] ?? 0);
        $decision = sanitize($_POST['decision'] ?? '');

        if ($sheetId <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
            set_flash('error', 'Invalid result sheet verification request.', 'danger');
        } else {
            $stmt = $pdo->prepare('UPDATE result_sheet_submissions SET status = ?, verified_by = ?, verified_at = NOW() WHERE id = ?');
            $stmt->execute([$decision, $user['id'], $sheetId]);
            set_flash('success', 'Result sheet has been ' . $decision . '.', 'success');
            redirect('admin_dashboard.php');
        }
    }
}

$applicants = fetch_all('SELECT a.*, u.name, u.email, p.name AS program_name, IF(EXISTS(SELECT 1 FROM students s WHERE s.user_id = u.id), 1, 0) AS is_student FROM applicants a JOIN users u ON a.user_id = u.id LEFT JOIN programs p ON a.program_id = p.id ORDER BY a.created_at DESC');
$departments = get_departments();
$stats = [
    'applicants' => fetch_one('SELECT COUNT(*) AS total FROM applicants')['total'],
    'students' => fetch_one('SELECT COUNT(*) AS total FROM students')['total'],
    'lecturers' => fetch_one('SELECT COUNT(*) AS total FROM lecturers')['total'],
    'courses' => fetch_one('SELECT COUNT(*) AS total FROM courses')['total'],
];
$adminResultPushes = fetch_all(
    'SELECT rp.*, c.code, c.title, su.name AS student_name, lu.name AS lecturer_name
     FROM result_pushes rp
     JOIN courses c ON rp.course_id = c.id
     JOIN students s ON rp.student_id = s.id
     JOIN users su ON s.user_id = su.id
     JOIN lecturers l ON rp.lecturer_id = l.id
     JOIN users lu ON l.user_id = lu.id
     WHERE rp.recipient = "admin"
     ORDER BY rp.pushed_at DESC
     LIMIT 12'
);
$pendingStudentResultPushes = fetch_all(
    'SELECT rp.*, c.code, c.title, su.name AS student_name, lu.name AS lecturer_name,
            s.tuition_total, s.tuition_paid
     FROM result_pushes rp
     JOIN courses c ON rp.course_id = c.id
     JOIN students s ON rp.student_id = s.id
     JOIN users su ON s.user_id = su.id
     JOIN lecturers l ON rp.lecturer_id = l.id
     JOIN users lu ON l.user_id = lu.id
     WHERE rp.recipient = "student" AND rp.status = "pending"
     ORDER BY rp.pushed_at DESC
     LIMIT 12'
);
$pendingResultSheets = fetch_all(
    'SELECT rs.*, c.code, c.title, lu.name AS lecturer_name
     FROM result_sheet_submissions rs
     JOIN courses c ON rs.course_id = c.id
     JOIN lecturers l ON rs.lecturer_id = l.id
     JOIN users lu ON l.user_id = lu.id
     WHERE rs.status = "pending"
     ORDER BY rs.submitted_at DESC
     LIMIT 12'
);
$studentFinanceRows = fetch_all(
    'SELECT s.id, s.registration_number, s.tuition_total, s.tuition_paid, u.name
     FROM students s
     JOIN users u ON s.user_id = u.id
     ORDER BY u.name'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Starford International University</title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-dark">
    <nav class="navbar navbar-expand-lg navbar-dark siu-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
            <img src="uploads/siu.jpeg" alt="SIU Logo" class="me-2 rounded-circle" style="height: 40px; width: 40px;">
            Starford International University
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#applications"><i class="bi bi-clipboard-check me-1"></i>Admissions</a></li>
                <li class="nav-item"><a class="nav-link" href="library.php"><i class="bi bi-book me-1"></i>Library</a></li>
                <li class="nav-item"><a class="nav-link" href="#finance"><i class="bi bi-cash-coin me-1"></i>Finance</a></li>
            </ul>
        </div>
    </div>
</nav>
<nav class="navbar navbar-expand-lg navbar-dark siu-navbar-secondary">
    <div class="container">
        <a class="navbar-brand siu-campus-brand" href="admin_dashboard.php">ADMIN DASHBOARD</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="btn btn-danger btn-sm ms-lg-2" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-5">
    <div class="row mb-4">
        <div class="col-lg-3 mb-3">
            <div class="card shadow-sm text-white bg-secondary h-100">
                <div class="card-body">
                    <h5 class="card-title">Applicants</h5>
                    <p class="display-6 mb-0"><?php echo sanitize($stats['applicants']); ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="card shadow-sm text-white bg-secondary h-100">
                <div class="card-body">
                    <h5 class="card-title">Students</h5>
                    <p class="display-6 mb-0"><?php echo sanitize($stats['students']); ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="card shadow-sm text-white bg-secondary h-100">
                <div class="card-body">
                    <h5 class="card-title">Lecturers</h5>
                    <p class="display-6 mb-0"><?php echo sanitize($stats['lecturers']); ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="card shadow-sm text-white bg-secondary h-100">
                <div class="card-body">
                    <h5 class="card-title">Courses</h5>
                    <p class="display-6 mb-0"><?php echo sanitize($stats['courses']); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php display_flash(); ?>
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3" id="applications">Applications for Review</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Program</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applicants as $applicant): ?>
                                    <tr>
                                        <td><?php echo sanitize($applicant['name']); ?></td>
                                        <td><?php echo sanitize($applicant['email']); ?></td>
                                        <td><?php echo sanitize($applicant['program_name'] ?: 'Not selected'); ?></td>
                                        <td><?php echo sanitize($applicant['status']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#applicant-<?php echo sanitize($applicant['id']); ?>">Review</button>
                                            <?php if ($applicant['status'] === 'Pending'): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="applicant_id" value="<?php echo sanitize($applicant['id']); ?>">
                                                    <button type="submit" name="approve_applicant" class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="applicant_id" value="<?php echo sanitize($applicant['id']); ?>">
                                                    <button type="submit" name="reject_applicant" class="btn btn-sm btn-danger">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($applicant['status'] === 'Approved' && !$applicant['is_student']): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="applicant_id" value="<?php echo sanitize($applicant['id']); ?>">
                                                    <button type="submit" name="convert_student" class="btn btn-sm btn-primary">Convert</button>
                                                </form>
                                            <?php elseif ($applicant['is_student']): ?>
                                                <span class="badge bg-success">Student</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php foreach ($applicants as $applicant): ?>
                        <div class="modal fade" id="applicant-<?php echo sanitize($applicant['id']); ?>" tabindex="-1" aria-labelledby="applicant-title-<?php echo sanitize($applicant['id']); ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="applicant-title-<?php echo sanitize($applicant['id']); ?>">Application: <?php echo sanitize($applicant['name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?php echo sanitize($applicant['email']); ?></dd>
                                            <dt class="col-sm-4">Phone</dt><dd class="col-sm-8"><?php echo sanitize($applicant['phone'] ?: 'Not provided'); ?></dd>
                                            <dt class="col-sm-4">Address</dt><dd class="col-sm-8"><?php echo sanitize($applicant['address'] ?: 'Not provided'); ?></dd>
                                            <dt class="col-sm-4">Nationality</dt><dd class="col-sm-8"><?php echo sanitize($applicant['nationality'] ?: 'Not provided'); ?></dd>
                                            <dt class="col-sm-4">Date of birth</dt><dd class="col-sm-8"><?php echo sanitize($applicant['birth_date'] ?: 'Not provided'); ?></dd>
                                            <dt class="col-sm-4">Previous school</dt><dd class="col-sm-8"><?php echo sanitize($applicant['previous_school'] ?: 'Not provided'); ?></dd>
                                            <dt class="col-sm-4">Program</dt><dd class="col-sm-8"><?php echo sanitize($applicant['program_name'] ?: 'Not selected'); ?></dd>
                                            <dt class="col-sm-4">Guardian / parent</dt><dd class="col-sm-8"><?php echo sanitize($applicant['guardian_name'] ?: 'Not provided'); ?></dd>
                                            <dt class="col-sm-4">Guardian phone</dt><dd class="col-sm-8"><?php echo sanitize($applicant['guardian_phone'] ?: 'Not provided'); ?></dd>
                                            <dt class="col-sm-4">Applied</dt><dd class="col-sm-8"><?php echo sanitize($applicant['created_at']); ?></dd>
                                            <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><?php echo sanitize($applicant['status']); ?></dd>
                                        </dl>
                                        <hr>
                                        <h6>Supporting documents</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php if (!empty($applicant['certificate_file'])): ?>
                                                <a class="btn btn-sm btn-outline-secondary" href="applicant_document.php?applicant_id=<?php echo urlencode((string) $applicant['id']); ?>&amp;document=transcript">View transcript / certificate</a>
                                            <?php else: ?>
                                                <span class="text-muted">Transcript / certificate not uploaded</span>
                                            <?php endif; ?>
                                            <?php if (!empty($applicant['id_document'])): ?>
                                                <a class="btn btn-sm btn-outline-secondary" href="applicant_document.php?applicant_id=<?php echo urlencode((string) $applicant['id']); ?>&amp;document=id">View ID document</a>
                                            <?php else: ?>
                                                <span class="text-muted">ID document not uploaded</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Results Pushed to Administrators</h5>
                    <?php if (empty($adminResultPushes)): ?>
                        <p>No lecturer result submissions for admin yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Grade</th>
                                        <th>Lecturer</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($adminResultPushes as $push): ?>
                                        <tr>
                                            <td><?php echo sanitize($push['student_name']); ?></td>
                                            <td><?php echo sanitize($push['code'] . ' - ' . $push['title']); ?></td>
                                            <td><?php echo sanitize($push['grade']); ?></td>
                                            <td><?php echo sanitize($push['lecturer_name']); ?></td>
                                            <td><?php echo sanitize($push['note'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Pending Student Results (Needs Admin Verification)</h5>
                    <?php if (empty($pendingStudentResultPushes)): ?>
                        <p>No pending student result pushes.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Grade</th>
                                        <th>Balance</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingStudentResultPushes as $push): ?>
                                        <?php $balance = max(0, floatval($push['tuition_total']) - floatval($push['tuition_paid'])); ?>
                                        <tr>
                                            <td><?php echo sanitize($push['student_name']); ?></td>
                                            <td><?php echo sanitize($push['code'] . ' - ' . $push['title']); ?></td>
                                            <td><?php echo sanitize($push['grade']); ?></td>
                                            <td>
                                                <?php if ($balance > 0): ?>
                                                    <span class="text-danger">$<?php echo sanitize(number_format($balance, 2)); ?></span>
                                                <?php else: ?>
                                                    <span class="text-success">Cleared</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="result_push_id" value="<?php echo sanitize($push['id']); ?>">
                                                    <input type="hidden" name="decision" value="approved">
                                                    <button type="submit" name="verify_result_push" class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="result_push_id" value="<?php echo sanitize($push['id']); ?>">
                                                    <input type="hidden" name="decision" value="rejected">
                                                    <button type="submit" name="verify_result_push" class="btn btn-sm btn-danger">Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Pending Excel Result Sheets</h5>
                    <?php if (empty($pendingResultSheets)): ?>
                        <p>No pending result sheets.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Lecturer</th>
                                        <th>Course</th>
                                        <th>File</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingResultSheets as $sheet): ?>
                                        <tr>
                                            <td><?php echo sanitize($sheet['lecturer_name']); ?></td>
                                            <td><?php echo sanitize($sheet['code'] . ' - ' . $sheet['title']); ?></td>
                                            <td><a href="<?php echo sanitize($sheet['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Open</a></td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="sheet_id" value="<?php echo sanitize($sheet['id']); ?>">
                                                    <input type="hidden" name="decision" value="approved">
                                                    <button type="submit" name="verify_result_sheet" class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="sheet_id" value="<?php echo sanitize($sheet['id']); ?>">
                                                    <input type="hidden" name="decision" value="rejected">
                                                    <button type="submit" name="verify_result_sheet" class="btn btn-sm btn-danger">Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Create Lecturer Account</h5>
                    <form method="post" action="admin_dashboard.php">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Select department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo sanitize($department['id']); ?>"><?php echo sanitize($department['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Office</label>
                            <input type="text" name="office" class="form-control">
                        </div>
                        <button type="submit" name="create_lecturer" class="btn btn-primary w-100">Create Lecturer</button>
                    </form>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Post Announcement</h5>
                    <form method="post" action="admin_dashboard.php">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Send To</label>
                            <select name="audience" class="form-select" required>
                                <option value="students">Students Only</option>
                                <option value="lecturers">Lecturers Only</option>
                                <option value="public">Public (Everyone)</option>
                                <option value="applicants">Applicants Only</option>
                            </select>
                        </div>
                        <button type="submit" name="create_announcement" class="btn btn-primary w-100">Publish Announcement</button>
                    </form>
                </div>
            </div>
            <div class="card shadow-sm mb-4" id="finance">
                <div class="card-body">
                    <h5 class="card-title mb-3">Finance: Student Tuition</h5>
                    <form method="post" action="admin_dashboard.php">
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">Select student</option>
                                <?php foreach ($studentFinanceRows as $studentFinance): ?>
                                    <option value="<?php echo sanitize($studentFinance['id']); ?>">
                                        <?php echo sanitize($studentFinance['name'] . ' (' . $studentFinance['registration_number'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Tuition</label>
                            <input type="number" step="0.01" min="0" name="tuition_total" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tuition Paid</label>
                            <input type="number" step="0.01" min="0" name="tuition_paid" class="form-control" required>
                        </div>
                        <button type="submit" name="update_student_fees" class="btn btn-primary w-100">Update Fee Record</button>
                    </form>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Admin Support</h5>
                    <p>Use this portal to approve applications, manage users, and publish campus announcements.</p>
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
