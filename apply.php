<?php
require_once 'config.php';
require_role('applicant');

$user = get_user();
$applicant = fetch_one('SELECT * FROM applicants WHERE user_id = ?', [$user['id']]);
$programs = get_programs();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $nationality = sanitize($_POST['nationality'] ?? '');
    $dob = sanitize($_POST['dob'] ?? '');
    $previous_school = sanitize($_POST['previous_school'] ?? '');
    $program_id = intval($_POST['program_id'] ?? 0);
    $guardian_name = sanitize($_POST['guardian_name'] ?? '');
    $guardian_phone = sanitize($_POST['guardian_phone'] ?? '');

    $certificate = upload_file('certificate');
    $id_document = upload_file('id_document');

    if (empty($phone) || empty($address) || empty($nationality) || empty($dob) || empty($program_id)) {
        $error = 'Please complete all required fields.';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE applicants SET phone = ?, address = ?, nationality = ?, birth_date = ?, previous_school = ?, program_id = ?, guardian_name = ?, guardian_phone = ?, status = ?, updated_at = NOW()'
            . ($certificate ? ', certificate_file = ?' : '')
            . ($id_document ? ', id_document = ?' : '')
            . ' WHERE user_id = ?'
        );

        $params = [$phone, $address, $nationality, $dob, $previous_school, $program_id, $guardian_name, $guardian_phone, 'Pending'];
        if ($certificate) {
            $params[] = $certificate;
        }
        if ($id_document) {
            $params[] = $id_document;
        }
        $params[] = $user['id'];
        $stmt->execute($params);

        set_flash('success', 'Your application has been updated and is pending review.', 'success');
        redirect('applicant_dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Application - Starford International University</title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark siu-navbar-secondary">
    <div class="container">
        <a class="navbar-brand siu-campus-brand" href="applicant_dashboard.php">ADMISSION DASHBOARD</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="btn btn-danger btn-sm ms-lg-2" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4">Admission Application</h3>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
                    <?php endif; ?>
                    <?php display_flash(); ?>
                    <form method="post" action="apply.php" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo sanitize($applicant['phone'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nationality</label>
                                <input type="text" name="nationality" class="form-control" value="<?php echo sanitize($applicant['nationality'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="<?php echo sanitize($applicant['address'] ?? ''); ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control" value="<?php echo sanitize($applicant['birth_date'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Previous School</label>
                                <input type="text" name="previous_school" class="form-control" value="<?php echo sanitize($applicant['previous_school'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Program of Interest</label>
                            <select name="program_id" class="form-select" required>
                                <option value="">Select a program</option>
                                <?php foreach ($programs as $program): ?>
                                    <option value="<?php echo $program['id']; ?>" <?php echo ($applicant['program_id'] == $program['id']) ? 'selected' : ''; ?>>
                                        <?php echo sanitize($program['name'] . ' (' . $program['department_name'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Guardian / Parent Name</label>
                                <input type="text" name="guardian_name" class="form-control" value="<?php echo sanitize($applicant['guardian_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Guardian Phone</label>
                                <input type="text" name="guardian_phone" class="form-control" value="<?php echo sanitize($applicant['guardian_phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Transcript / Certificate</label>
                                <input type="file" name="certificate" class="form-control">
                                <?php if (!empty($applicant['certificate_file'])): ?>
                                    <small>Uploaded: <?php echo sanitize(basename($applicant['certificate_file'])); ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ID Document</label>
                                <input type="file" name="id_document" class="form-control">
                                <?php if (!empty($applicant['id_document'])): ?>
                                    <small>Uploaded: <?php echo sanitize(basename($applicant['id_document'])); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Application</button>
                        <a href="applicant_dashboard.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
