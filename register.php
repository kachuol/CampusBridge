<?php
require_once 'config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $existing = fetch_one('SELECT id FROM users WHERE email = ?', [$email]);

        if ($existing) {
            $error = 'An account with that email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([$name, $email, $hashed, 'applicant']);
            $userId = $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO applicants (user_id, status, created_at) VALUES (?, ?, NOW())');
            $stmt->execute([$userId, 'Pending']);

            set_flash('success', 'Your applicant account has been created. Please login to continue.', 'success');
            redirect('login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Registration - Starford International University</title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark siu-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
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
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4">Applicant Registration</h3>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
                    <?php endif; ?>
                    <form method="post" action="register.php">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register as Applicant</button>
                    </form>
                    <p class="mt-3 text-center">Already registered? <a href="login.php" style="color: blue;">Login now</a></p>
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
