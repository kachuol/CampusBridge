<?php
require_once 'config.php';

$adminEmail = 'admin@starford.edu';
$existing = fetch_one('SELECT id FROM users WHERE email = ?', [$adminEmail]);

if ($existing) {
    echo 'Admin user already exists. Please login with ' . sanitize($adminEmail);
    exit;
}

$password = password_hash('Admin@1234', PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())');
$stmt->execute(['Starford Admin', $adminEmail, $password, 'admin']);

echo 'Admin account created successfully. Email: ' . sanitize($adminEmail) . ' Password: Admin@1234';
