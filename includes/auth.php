<?php
// Pastikan session sudah dimulai sebelum memanggil fungsi-fungsi ini
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Pastikan BASE_URL sudah didefinisikan di config.php yang akan di-include di file utama
// require_once __DIR__ . '/../config.php'; // Contoh jika config.php ada di root

function is_logged_in() {
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}

function get_user_role() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

function get_user_id() {
    return isset($_SESSION['id']) ? $_SESSION['id'] : null;
}

function check_login_status() {
    if (!is_logged_in()) {
        header('location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function check_admin_access() {
    check_login_status(); // Pastikan sudah login
    if (get_user_role() !== 'admin') {
        // Redirect ke dashboard user atau halaman error akses
        header('location: ' . BASE_URL . '/user/dashboard.php'); // Atau halaman 403.php
        exit;
    }
}

function check_user_access() {
    check_login_status(); // Pastikan sudah login
    if (get_user_role() !== 'user') {
        // Redirect ke dashboard admin atau halaman error akses
        header('location: ' . BASE_URL . '/admin/dashboard.php'); // Atau halaman 403.php
        exit;
    }
}

// Fungsi untuk redirect user yang sudah login dari halaman login/register
function redirect_if_logged_in() {
    if (is_logged_in()) {
        if (get_user_role() === 'admin') {
            header('location: ' . BASE_URL . '/admin/dashboard.php');
            exit;
        } elseif (get_user_role() === 'user') {
            header('location: ' . BASE_URL . '/user/dashboard.php');
            exit;
        }
    }
}
?>