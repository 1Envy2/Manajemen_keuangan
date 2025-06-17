<?php
// admin/hapus_kategori.php

require_once '../config.php'; // Panggil config.php yang ada di root
require_once '../includes/auth.php'; // Panggil auth.php dari folder includes

check_admin_access(); // Pastikan hanya admin yang bisa mengakses halaman ini

$category_id = $_GET['id'] ?? null; // Ambil ID kategori dari URL

if ($category_id === null || !is_numeric($category_id)) {
    // Jika ID tidak valid, redirect kembali ke dashboard admin dengan pesan error
    $_SESSION['error_message'] = "ID kategori tidak valid.";
    header('location: ' . BASE_URL . '/admin/dashboard.php'); // LINK KEMBALI KE DASHBOARD ADMIN
    exit;
}

// --- Cek apakah kategori ini masih digunakan oleh transaksi mana pun ---
$sql_check_transactions = "SELECT COUNT(*) AS total_transactions FROM transactions WHERE category_id = ?";
if ($stmt_check = $conn->prepare($sql_check_transactions)) {
    $stmt_check->bind_param("i", $category_id);
    if ($stmt_check->execute()) {
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_assoc();
        $total_transactions = $row_check['total_transactions'];
        $stmt_check->close();

        if ($total_transactions > 0) {
            // Jika kategori masih digunakan, beri peringatan dan jangan hapus
            $_SESSION['error_message'] = "Kategori ini tidak dapat dihapus karena masih digunakan oleh " . $total_transactions . " transaksi.";
            header('location: ' . BASE_URL . '/admin/dashboard.php'); // LINK KEMBALI KE DASHBOARD ADMIN
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Terjadi kesalahan saat memeriksa transaksi terkait kategori.";
        header('location: ' . BASE_URL . '/admin/dashboard.php'); // LINK KEMBALI KE DASHBOARD ADMIN
        exit;
    }
} else {
    $_SESSION['error_message'] = "Gagal mempersiapkan statement cek transaksi.";
    header('location: ' . BASE_URL . '/admin/dashboard.php'); // LINK KEMBALI KE DASHBOARD ADMIN
    exit;
}


// --- Jika tidak ada transaksi yang menggunakan kategori ini, lanjutkan proses hapus ---
$sql_delete = "DELETE FROM categories WHERE id = ?";
if ($stmt_delete = $conn->prepare($sql_delete)) {
    $stmt_delete->bind_param("i", $category_id);
    if ($stmt_delete->execute()) {
        $_SESSION['success_message'] = "Kategori berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus kategori. Silakan coba lagi.";
    }
    $stmt_delete->close();
} else {
    $_SESSION['error_message'] = "Gagal mempersiapkan statement penghapusan kategori.";
}

$conn->close(); // Tutup koneksi database

// Redirect kembali ke dashboard admin
header('location: ' . BASE_URL . '/admin/dashboard.php'); // LINK KEMBALI KE DASHBOARD ADMIN
exit;
?>