<?php
require_once 'config.php';

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $message_text = sanitize($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message_text)) {
        $message = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please provide a valid email address.';
    } else {
        // For now, just set a success message. In a real app, you'd send an email or save to DB.
        set_flash('success', 'Thank you for your message! We will get back to you soon.', 'success');
        redirect('index.php#contact');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Starford International University</title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-nav sticky-top">
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
                <li class="nav-item"><a class="nav-link" href="index.php#about"><i class="bi bi-info-circle me-1"></i>About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#programs"><i class="bi bi-book me-1"></i>Programs</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#admissions"><i class="bi bi-clipboard-check me-1"></i>Admissions</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#news"><i class="bi bi-newspaper me-1"></i>News</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php#contact"><i class="bi bi-envelope me-1"></i>Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a></li>
                <li class="nav-item"><a class="nav-link" href="register.php"><i class="bi bi-pencil-square me-1"></i>Apply</a></li>
            </ul>
        </div>
    </div>
</nav>

<header class="hero bg-light py-4">
    <div class="container text-center">
        <h1 class="display-6">Contact Us</h1>
        <p class="lead">Get in touch with Starford International University</p>
    </div>
</header>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-envelope-fill text-primary fs-1 mb-3"></i>
                        <h3 class="card-title fw-bold">Contact Form Status</h3>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-danger border-0 shadow-sm text-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php display_flash(); ?>


                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                <i class="bi bi-envelope-fill text-primary fs-4 me-3"></i>
                                <div>
                                    <strong>Email Us</strong><br>
                                    <small class="text-muted">info@starford.edu</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                <i class="bi bi-telephone-fill text-success fs-4 me-3"></i>
                                <div>
                                    <strong>Call Us</strong><br>
                                    <small class="text-muted">+2119 2130 2503</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="index.php#contact" class="btn btn-primary btn-lg me-3">
                            <i class="bi bi-arrow-left me-2"></i>Back to Contact Form
                        </a>
                        <a href="index.php" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-house me-2"></i>Go to Homepage
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-light py-5">
    <div class="container">
        <div class="row">
            <!-- University Info -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="d-flex align-items-center mb-3">
                    <img src="uploads/siu.jpeg" alt="SIU Logo" class="me-3 rounded-circle" style="height: 50px; width: 50px;">
                    <div>
                        <h5 class="mb-0">Starford International University</h5>
                        <small class="text-muted">Education for Sustainable Development</small>
                    </div>
                </div>
                <p class="mb-3">Dedicated to delivering quality education through innovative online platforms and modern academic management systems.</p>
                <div class="d-flex">
                    <a href="#" class="text-light me-3" title="Facebook"><i class="bi bi-facebook fs-5"></i></a>
                    <a href="#" class="text-light me-3" title="Twitter"><i class="bi bi-twitter fs-5"></i></a>
                    <a href="#" class="text-light me-3" title="LinkedIn"><i class="bi bi-linkedin fs-5"></i></a>
                    <a href="#" class="text-light me-3" title="Instagram"><i class="bi bi-instagram fs-5"></i></a>
                    <a href="#" class="text-light me-3" title="YouTube"><i class="bi bi-youtube fs-5"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-uppercase fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php#about" class="text-light text-decoration-none"><i class="bi bi-info-circle me-2"></i>About Us</a></li>
                    <li class="mb-2"><a href="index.php#programs" class="text-light text-decoration-none"><i class="bi bi-book me-2"></i>Programs</a></li>
                    <li class="mb-2"><a href="index.php#admissions" class="text-light text-decoration-none"><i class="bi bi-clipboard-check me-2"></i>Admissions</a></li>
                    <li class="mb-2"><a href="index.php#news" class="text-light text-decoration-none"><i class="bi bi-newspaper me-2"></i>News</a></li>
                    <li class="mb-2"><a href="index.php#contact" class="text-light text-decoration-none"><i class="bi bi-envelope me-2"></i>Contact</a></li>
                </ul>
            </div>

            <!-- Student Resources -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-uppercase fw-bold mb-3">Student Portal</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="login.php" class="text-light text-decoration-none"><i class="bi bi-box-arrow-in-right me-2"></i>Student Login</a></li>
                    <li class="mb-2"><a href="register.php" class="text-light text-decoration-none"><i class="bi bi-pencil-square me-2"></i>Apply Online</a></li>
                    <li class="mb-2"><a href="#" class="text-light text-decoration-none"><i class="bi bi-calendar-event me-2"></i>Academic Calendar</a></li>
                    <li class="mb-2"><a href="#" class="text-light text-decoration-none"><i class="bi bi-file-earmark-text me-2"></i>Library</a></li>
                    <li class="mb-2"><a href="#" class="text-light text-decoration-none"><i class="bi bi-trophy me-2"></i>Alumni</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-4 col-md-6 mb-4">
                <h6 class="text-uppercase fw-bold mb-3">Contact Information</h6>
                <div class="d-flex align-items-start mb-2">
                    <i class="bi bi-geo-alt-fill me-3 mt-1"></i>
                    <div>
                        <strong>Address:</strong><br>
                        Hai referendum Eden Avenue<br>
                        Juba, South Sudan
                    </div>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-telephone-fill me-3"></i>
                    <div>
                        <strong>Phone:</strong><br>
                        +2119 2130 2503
                    </div>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-envelope-fill me-3"></i>
                    <div>
                        <strong>Email:</strong><br>
                        info@starford.edu
                    </div>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-clock-fill me-3"></i>
                    <div>
                        <strong>Office Hours:</strong><br>
                        Mon - Fri: 8:00 AM - 5:00 PM
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
    </div>
</footer>
    <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>