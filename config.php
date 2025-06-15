<?php
// config/database.php

$host = "localhost";
$user = "root";
$pass = ""; // sesuaikan jika ada password MySQL
$dbname = "finote"; // Nama database Anda

// Buat koneksi ke database menggunakan MySQLi
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Periksa koneksi
if (!$conn) {
    // Log error ini untuk debugging, jangan tampilkan langsung di produksi
    error_log("Koneksi database gagal: " . mysqli_connect_error());
    die("Maaf, terjadi masalah koneksi database. Silakan coba lagi nanti."); // Pesan user-friendly
}

// Opsional: Set character set ke utf8mb4 (sangat direkomendasikan untuk dukungan Unicode penuh)
// Pastikan database dan tabel Anda juga menggunakan utf8mb4_unicode_ci atau utf8mb4_general_ci
mysqli_set_charset($conn, "utf8mb4");

// Variabel $conn sekarang tersedia untuk digunakan di file lain yang me-require file ini
?>