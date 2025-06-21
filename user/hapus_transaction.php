<?php
require_once '../config.php';
require_once '../includes/auth.php';

check_user_access(); // Pastikan hanya user yang bisa mengakses halaman ini

// Ambil ID pengguna yang sedang login
$user_id = get_user_id();

// Ambil ID transaksi dari URL
$transaction_id = $_GET['id'] ?? null;

// Cek jika ID transaksi tidak valid atau tidak ada
if ($transaction_id === null || !is_numeric($transaction_id)) {
    $_SESSION['error_message'] = "ID transaksi tidak valid.";
    header('location: ' . BASE_URL . '/user/riwayat.php');
    exit;
}

// Proses penghapusan transaksi
// Penting: Pastikan hanya transaksi milik user yang bisa dihapus!
$sql_delete = "DELETE FROM transactions WHERE id = ? AND user_id = ?";
if ($stmt_delete = $conn->prepare($sql_delete)) {
    $stmt_delete->bind_param("ii", $transaction_id, $user_id);
    if ($stmt_delete->execute()) {
        if ($stmt_delete->affected_rows > 0) {
            $_SESSION['success_message'] = "Transaksi berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Transaksi tidak ditemukan atau bukan milik Anda.";
        }
    } else {
        $_SESSION['error_message'] = "Gagal menghapus transaksi. Silakan coba lagi.";
    }
    $stmt_delete->close();
} else {
    $_SESSION['error_message'] = "Terjadi kesalahan database saat menyiapkan penghapusan.";
}

$conn->close();

// Arahkan kembali ke halaman riwayat transaksi
header('location: ' . BASE_URL . '/user/riwayat.php');
exit;
?>