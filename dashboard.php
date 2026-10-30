<?php
require_once 'config.php';
require_login();

$user = get_user();

switch ($user['role']) {
    case 'applicant':
        redirect('applicant_dashboard.php');
        break;
    case 'student':
        redirect('student_dashboard.php');
        break;
    case 'lecturer':
        redirect('lecturer_dashboard.php');
        break;
    case 'admin':
        redirect('admin_dashboard.php');
        break;
    default:
        set_flash('auth', 'Unknown user role. Please contact the system administrator.', 'danger');
        redirect('login.php');
}
