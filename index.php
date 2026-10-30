<?php
require_once 'config.php';
$programs = get_programs();
$announcements = get_announcements();
$events = get_events();
$stats = get_student_stats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starford International University</title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
                <li class="nav-item"><a class="nav-link" href="#about"><i class="bi bi-info-circle me-1"></i>About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="#programs"><i class="bi bi-book me-1"></i>Programs</a></li>
                <li class="nav-item"><a class="nav-link" href="#admissions"><i class="bi bi-clipboard-check me-1"></i>Admissions</a></li>
                <li class="nav-item"><a class="nav-link" href="#news"><i class="bi bi-newspaper me-1"></i>News</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php"><i class="bi bi-envelope me-1"></i>Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a></li>
                <li class="nav-item"><a class="nav-link" href="register.php"><i class="bi bi-pencil-square me-1"></i>Apply</a></li>
            </ul>
        </div>
    </div>
</nav>

<header class="hero classic-hero py-5">
    <div class="container text-center">
        <p class="hero-kicker mb-2">Founded on Excellence</p>
        <h1 class="display-5">Welcome to Starford International University</h1>
        <p class="classic-tagline">"Education for sustainable development"</p>
        <p class="lead">A trusted academic platform for Learners</p>
        <a href="register.php" class="btn btn-primary btn-lg me-2">Apply Now</a>
        <a href="login.php" class="btn btn-outline-primary btn-lg">Student Login</a>
    </div>
</header>

<section class="py-5" id="about">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <p class="section-eyebrow mb-2">About SIU</p>
                <p>Starford International University is dedicated to delivering quality education through an intuitive online platform with admissions, academic management, and secure role-based access.</p>
                <p>We support applicants, students, lecturers and administrators with real-world tools built for modern academic workflows.</p>
            </div>
            <div class="col-lg-6 text-center">
                <img src="uploads/httpsimages.unsplash.comphoto-1509062522246-3755977927d7auto=format&fit=crop&w=1000&q=80.jpeg" class="img-fluid rounded shadow" alt="African Students Studying Computer Science">
            </div>
        </div>
    </div>
</section>

<section class="bg-grey py-5">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-md-4 mb-4">
                <div class="classic-feature h-100">
                    <h3>Modern Campus</h3>
                    <p>Flexible programs, digital learning and a friendly academic environment.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="classic-feature h-100">
                    <h3>Career Focused</h3>
                    <p>Practical academic programs designed to give graduates a competitive edge.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="classic-feature h-100">
                    <h3>Global Network</h3>
                    <p>International collaborations, events and community support for every learner.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5" id="programs">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">Academic Programs</h2>
            <a href="#admissions" class="btn btn-outline-primary">View Admission Requirements</a>
        </div>
        <div class="row g-4">
            <?php foreach ($programs as $program): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 classic-card">
                        <div class="card-body p-4 d-flex flex-column">
                            <h5 class="card-title mb-2"><?php echo sanitize($program['name']); ?></h5>
                            <p class="card-text mb-3"><strong>Faculty:</strong> <?php echo sanitize($program['department_name']); ?></p>
                            <p class="card-text text-muted mt-auto mb-0"><?php echo sanitize(substr($program['description'], 0, 120)); ?>...</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="bg-admissions py-5" id="admissions">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <p class="section-eyebrow mb-2">Admissions</p>
                <p>Start your journey with a secure online application. Applicants can register an account, upload supporting documents, and track their admission status.</p>
                <ul>
                    <li>Personal and academic details</li>
                    <li>Program selection and guardian information</li>
                    <li>Secure document uploads</li>
                    <li>Admin approval and status tracking</li>
                </ul>
                <a href="register.php" class="btn btn-primary">Start Application</a>
            </div>
            <div class="col-lg-6">
                <img src="uploads/httpsimages.unsplash.comphoto-1556761175-b413da4baf72auto=format&fit=crop&w=1000&q=80.jpeg" class="img-fluid rounded shadow" alt="African Students Studying Accounting and Finance">
            </div>
        </div>
    </div>
</section>

<section class="py-5" id="news">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-8">
                <p class="section-eyebrow mb-2">Campus Bulletin</p>
                <h2 class="mb-2 section-title">News & Events</h2>
                <p class="mb-0">Stay updated with the latest university news, upcoming events, and student achievements.</p>
            </div>
        </div>
        <!-- Featured Statistics -->
        <div class="bg-light rounded p-4 classic-stats">
            <div class="row text-center">
                <div class="col-md-3 mb-3">
                    <div class="h2 fw-bold text-primary mb-1">95%</div>
                    <small class="text-muted">Employment Rate</small>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="h2 fw-bold text-success mb-1">25+</div>
                    <small class="text-muted">Partner Universities</small>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="h2 fw-bold text-info mb-1">$2.5M</div>
                    <small class="text-muted">Campus Investment</small>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="h2 fw-bold text-warning mb-1">12</div>
                    <small class="text-muted">Countries Represented</small>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-contact py-5" id="contact">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <p class="section-eyebrow mb-2">Contact Office</p>
                <h2 class="display-5 fw-bold mb-3">Get In Touch</h2>
                <p class="lead text-white">Have questions about admissions, programs, or need assistance? We're here to help you every step of the way.</p>
            </div>
        </div>

        <div class="row g-5 justify-content-center">
            <!-- Quick Contact Form -->
            <div class="col-12 col-lg-7">
                <div class="card classic-card h-100">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h3 class="h2 fw-bold mb-3 text-center">Send us a Message</h3>
                            <p class="text-white">Fill out the form below and we'll get back to you as soon as possible.</p>
                        </div>

                        <form action="contact.php" method="post" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="contactName" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                        <input type="text" class="form-control form-control-lg" id="contactName" name="name" placeholder="Your full name" required>
                                        <div class="invalid-feedback">Please provide your full name.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="contactEmail" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                        <input type="email" class="form-control form-control-lg" id="contactEmail" name="email" placeholder="your.email@example.com" required>
                                        <div class="invalid-feedback">Please provide a valid email address.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="contactSubject" class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-tag-fill"></i></span>
                                    <select class="form-select form-select-lg" id="contactSubject" name="subject" required>
                                        <option value="">Choose a subject...</option>
                                        <option value="admissions">Admissions Inquiry</option>
                                        <option value="programs">Program Information</option>
                                        <option value="technical">Technical Support</option>
                                        <option value="general">General Inquiry</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a subject.</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="contactMessage" class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-chat-text-fill"></i></span>
                                    <textarea class="form-control form-control-lg" id="contactMessage" name="message" rows="5" placeholder="Tell us how we can help you..." required></textarea>
                                    <div class="invalid-feedback">Please provide a message.</div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send-fill me-2"></i>Send Message
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <small class="text-white-50">We respect your privacy and will never share your information.</small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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
                    <a href="#" class="text-light" title="YouTube"><i class="bi bi-youtube fs-5"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-uppercase fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#about" class="text-light text-decoration-none"><i class="bi bi-info-circle me-2"></i>About Us</a></li>
                    <li class="mb-2"><a href="#programs" class="text-light text-decoration-none"><i class="bi bi-book me-2"></i>Programs</a></li>
                    <li class="mb-2"><a href="#admissions" class="text-light text-decoration-none"><i class="bi bi-clipboard-check me-2"></i>Admissions</a></li>
                    <li class="mb-2"><a href="#news" class="text-light text-decoration-none"><i class="bi bi-newspaper me-2"></i>News & Events</a></li>
                    <li class="mb-2"><a href="#contact" class="text-light text-decoration-none"><i class="bi bi-envelope me-2"></i>Contact</a></li>
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
                        Hai referendum Eden Area<br>
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
<script src="assets/js/app.js"></script>
</body>
</html>
