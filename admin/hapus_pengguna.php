<?php
require_once '../config.php';
require_once '../includes/auth.php';

check_admin_access(); // Pastikan hanya admin bisa hapus

// Cek apakah ID tersedia dan valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = 'ID pengguna tidak valid.';
    header('Location: ../admin/dashboard.php');
    exit;
}

$id = (int)$_GET['id'];

// Cegah admin menghapus dirinya sendiri atau admin lain (opsional)
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || $user['role'] !== 'user') {
    $_SESSION['error_message'] = 'Pengguna tidak ditemukan atau bukan pengguna biasa.';
    header('Location: ../admin/dashboard.php');
    exit;
}

// Hapus data dari database
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Pengguna berhasil dihapus.';
} else {
    $_SESSION['error_message'] = 'Gagal menghapus pengguna.';
}
$stmt->close();

header('Location: ../admin/dashboard.php');
exit;
?>