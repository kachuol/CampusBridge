<?php
require_once 'config.php';
require_role('admin');

$applicantId = filter_input(INPUT_GET, 'applicant_id', FILTER_VALIDATE_INT);
$document = $_GET['document'] ?? '';
$columns = [
    'transcript' => 'certificate_file',
    'id' => 'id_document',
];

if (!$applicantId || !isset($columns[$document])) {
    http_response_code(400);
    exit('Invalid document request.');
}

$column = $columns[$document];
$applicant = fetch_one("SELECT `$column` AS file_path FROM applicants WHERE id = ?", [$applicantId]);
if (!$applicant || empty($applicant['file_path'])) {
    http_response_code(404);
    exit('Document not found.');
}

$relativePath = str_replace('\\', '/', $applicant['file_path']);
if (strpos($relativePath, 'uploads/') !== 0 || basename($relativePath) !== substr($relativePath, strlen('uploads/'))) {
    http_response_code(404);
    exit('Document not found.');
}

$filePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
$uploadDirectory = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads');
if (!$filePath || !$uploadDirectory || strpos($filePath, $uploadDirectory . DIRECTORY_SEPARATOR) !== 0 || !is_file($filePath)) {
    http_response_code(404);
    exit('Document not found.');
}

$downloadName = basename($filePath);
$mimeType = function_exists('mime_content_type') ? mime_content_type($filePath) : false;
header('Content-Type: ' . ($mimeType ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit;
