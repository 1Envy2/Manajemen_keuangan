<?php
// config.php (File Koneksi Database Utama dan Konfigurasi Dasar)

// Konfigurasi Database sebagai konstanta
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // sesuaikan jika ada password MySQL
define('DB_NAME', 'finote'); // Nama database Anda

// --- URL Dasar Aplikasi Anda (BASE_URL) ---
// Ini penting untuk semua link dan redirect agar konsisten, selalu menunjuk ke akar aplikasi.

// 1. Tentukan protokol (http atau https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";

// 2. Dapatkan nama host (misalnya, localhost, namadomain.com)
$host = $_SERVER['HTTP_HOST'];

// 3. Dapatkan jalur sistem file absolut ke direktori yang berisi config.php
//    Karena config.php ada di root proyek Anda, ini adalah akar sistem file aplikasi Anda.
$app_filesystem_root = __DIR__;

// 4. Dapatkan document root dari web server (misalnya, C:/xampp/htdocs atau /var/www/html)
$document_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']); // Normalisasi path untuk Windows

// 5. Hitung jalur relatif dari document root ke akar aplikasi Anda
//    Ini akan menjadi bagian dari URL yang mengikuti host, misalnya '/Manajemen_keuangan'
$web_root_path = str_replace('\\', '/', substr($app_filesystem_root, strlen($document_root)));

// 6. Pastikan ada trailing slash untuk BASE_URL, kecuali jika itu adalah root server '/'
if (substr($web_root_path, -1) !== '/') {
    $web_root_path .= '/';
}
// Jika aplikasi berada langsung di document root (misalnya, http://localhost/),
// maka $web_root_path akan kosong, pastikan menjadi '/'
if (empty($web_root_path)) {
    $web_root_path = '/';
}


// 7. Definisikan BASE_URL
define('BASE_URL', $protocol . "://" . $host . $web_root_path);


// --- Koneksi Database ---
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Periksa koneksi
if (!$conn) {
    // Log error ini untuk debugging, jangan tampilkan langsung di produksi
    error_log("Koneksi database gagal: " . mysqli_connect_error());
    die("Maaf, terjadi masalah koneksi database. Silakan coba lagi nanti."); // Pesan user-friendly
}

// Opsional: Set character set ke utf8mb4 (sangat direkomendasikan untuk dukungan Unicode penuh)
mysqli_set_charset($conn, "utf8mb4");

// Variabel $conn sekarang tersedia untuk digunakan di file lain yang me-require file ini
?>