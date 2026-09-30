<?php
// public_html/index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$roleRedirects = [
    'admin'     => '/public_html/pages/admin-panel/admin-dashboard.html',
    'executive' => '/public_html/pages/medical-executive-panel/executive-dashboard.html',
    'doctor'    => '/public_html/php/doctor-panel/dashboard.php',
    'surgeon'   => '/public_html/pages/surgeon-panel/dashboard.html',
    'patient'   => '/public_html/pages/patient-panel/dashboard.html',
];

$role = $_SESSION['role'] ?? null;
if ($role && isset($roleRedirects[$role])) {
    header('Location: ' . $roleRedirects[$role]);
    exit;
}

header('Location: /public_html/pages/login.html');
exit;
