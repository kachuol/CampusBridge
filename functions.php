<?php
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function sanitize($value)
{
    return htmlspecialchars(trim($value));
}

function set_flash($key, $message, $type = 'info')
{
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type,
    ];
}

function display_flash()
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    foreach ($_SESSION['flash'] as $flash) {
        echo '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show" role="alert">';
        echo sanitize($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }

    unset($_SESSION['flash']);
}

function is_logged_in()
{
    return !empty($_SESSION['user_id']);
}

function require_login()
{
    if (!is_logged_in()) {
        set_flash('auth', 'Please login to continue.', 'warning');
        redirect('login.php');
    }
}

function require_role($role)
{
    require_login();

    if ($_SESSION['user_role'] !== $role) {
        set_flash('auth', 'You do not have permission to access this page.', 'danger');
        redirect('login.php');
    }
}

function fetch_one($sql, $params = [])
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function fetch_all($sql, $params = [])
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_user()
{
    if (!is_logged_in()) {
        return null;
    }

    return fetch_one('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
}

function upload_file($field)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $fileName = basename($_FILES[$field]['name']);
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'];

    if (!in_array(strtolower($extension), $allowed, true)) {
        return null;
    }

    $targetName = uniqid($field . '_', true) . '.' . $extension;
    $targetPath = __DIR__ . '/uploads/' . $targetName;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
        return null;
    }

    return 'uploads/' . $targetName;
}

function get_programs()
{
    return fetch_all('SELECT p.*, d.name AS department_name FROM programs p JOIN departments d ON p.department_id = d.id ORDER BY p.name');
}

function get_announcements($audience = 'public')
{
    return fetch_all('SELECT * FROM announcements WHERE audience IN (?, \'public\') ORDER BY created_at DESC LIMIT 12', [$audience]);
}

function get_events()
{
    return fetch_all('SELECT * FROM events ORDER BY event_date DESC LIMIT 12');
}

function get_student_stats()
{
    $total_students = fetch_one('SELECT COUNT(*) as count FROM students WHERE status = "Active"');
    $total_graduates = 10; // Fixed value as requested
    $total_applicants = fetch_one('SELECT COUNT(*) as count FROM applicants');

    return [
        'enrolled_students' => $total_students['count'] ?? 0,
        'graduates' => $total_graduates,
        'applicants' => $total_applicants['count'] ?? 0
    ];
}

function get_departments()
{
    return fetch_all('SELECT * FROM departments ORDER BY name');
}
