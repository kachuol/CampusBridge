<?php
require_once 'config.php';
require_role('applicant');

$user = get_user();
$applicant = fetch_one(
    'SELECT a.*, p.name AS program_name, p.duration, p.level FROM applicants a LEFT JOIN programs p ON a.program_id = p.id WHERE a.user_id = ?',
    [$user['id']]
);
$programs = get_programs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Applicant Portal - Starford International University</title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-dark">
    <nav class="navbar navbar-expand-lg navbar-dark siu-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="applicant_dashboard.php">
            <img src="uploads/siu.jpeg" alt="SIU Logo" class="me-2 rounded-circle" style="height: 40px; width: 40px;">
            Starford International University
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#about"><i class="bi bi-info-circle me-1"></i>About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="#programs"><i class="bi bi-book me-1"></i>Programs</a></li>
                <li class="nav-item"><a class="nav-link" href="#admissions"><i class="bi bi-clipboard-check me-1"></i>Admissions</a></li>
                <li class="nav-item"><a class="nav-link" href="#news"><i class="bi bi-newspaper me-1"></i>News</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact"><i class="bi bi-envelope me-1"></i>Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a></li>
                <li class="nav-item"><a class="nav-link" href="register.php"><i class="bi bi-pencil-square me-1"></i>Apply</a></li>
            </ul>
        </div>
    </div>
</nav>
</nav>
<nav class="navbar navbar-expand-lg navbar-dark siu-navbar-secondary">
    <div class="container">
        <a class="navbar-brand siu-campus-brand" href="applicant_dashboard.php">APPLICANT DASHBOARD</a>
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
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title">Welcome, <?php echo sanitize($user['name']); ?></h3>
                    <p class="text-muted">Applicant portal for managing your application.</p>
                    <?php display_flash(); ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="p-3 bg-secondary text-white rounded">
                                <h5>Application Status</h5>
                                <p class="fs-4 mb-0"><?php echo sanitize($applicant['status'] ?? 'Pending'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="p-3 bg-secondary text-white rounded">
                                <h5>Selected Program</h5>
                                <p class="fs-5 mb-0"><?php echo sanitize($applicant['program_name'] ?? 'Not selected'); ?></p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h5>Application Summary</h5>
                    <p><strong>Phone:</strong> <?php echo sanitize($applicant['phone'] ?? 'N/A'); ?></p>
                    <p><strong>Previous School:</strong> <?php echo sanitize($applicant['previous_school'] ?? 'N/A'); ?></p>
                    <p><strong>Guardian:</strong> <?php echo sanitize($applicant['guardian_name'] ?? 'N/A'); ?> (<?php echo sanitize($applicant['guardian_phone'] ?? 'N/A'); ?>)</p>
                    <a href="apply.php" class="btn btn-primary">Update Application</a>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Your Profile</h5>
                    <p><strong>Email:</strong> <?php echo sanitize($user['email']); ?></p>
                    <p><strong>Joined:</strong> <?php echo sanitize(date('F j, Y', strtotime($user['created_at']))); ?></p>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Quick Links</h5>
                    <a href="apply.php" class="btn btn-outline-primary w-100 mb-2">Manage Application</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
