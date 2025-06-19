<?php
// user/categories.php

// Panggil config.php terlebih dahulu, karena di dalamnya ada definisi BASE_URL dan koneksi $conn
require_once '../config.php';
// Panggil auth.php untuk fungsi-fungsi autentikasi dan otorisasi
require_once '../includes/auth.php';

// Cek akses: Pastikan user sudah login dan role-nya adalah 'user'
// Fungsi ini akan mengalihkan (redirect) jika user tidak memenuhi syarat
check_user_access();

// Ambil user_id dan username dari session
$user_id = get_user_id();
$username = $_SESSION['username'] ?? 'User'; // Mengambil dari session

// Ambil data user lengkap dari database jika dibutuhkan (misalnya untuk foto)
$user_data_from_db = [];
$query_user_data = "SELECT photo FROM users WHERE id = ?";
if ($stmt_user_data = $conn->prepare($query_user_data)) {
    $stmt_user_data->bind_param("i", $user_id);
    if ($stmt_user_data->execute()) {
        $result_user_data = $stmt_user_data->get_result();
        $user_data_from_db = $result_user_data->fetch_assoc();
    }
    $stmt_user_data->close();
}

// Tentukan path foto profil
// Asumsi 'uploads/' ada di root proyek, dan 'assets/profile.jpeg' ada di public/assets/
$photo = (!empty($user_data_from_db['photo']) && file_exists('uploads/' . $user_data_from_db['photo']))
         ? BASE_URL . 'user/uploads/' . $user_data_from_db['photo']
         : BASE_URL . '/assets/profile.jpeg';


// --- Hanya ambil daftar kategori global (tanpa user_id di query) ---
$categories_list = [];
$sql_list = "SELECT id, name, type FROM categories ORDER BY type ASC, name ASC"; // Urutkan berdasarkan tipe lalu nama
if ($stmt_list = $conn->prepare($sql_list)) {
    // Tidak ada bind_param untuk user_id di sini karena kategori global
    if ($stmt_list->execute()) {
        $result_list = $stmt_list->get_result();
        while ($row_list = $result_list->fetch_assoc()) {
            $categories_list[] = $row_list;
        }
    } else {
        // Tangani error jika gagal mengambil kategori
        error_log("Error fetching categories for user: " . $stmt_list->error);
    }
    $stmt_list->close();
}

$conn->close(); // Tutup koneksi database setelah semua operasi selesai
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finote - Daftar Kategori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        /* CSS yang disalin dari dashboard user Anda, idealnya dipindahkan ke file CSS terpisah */
        :root {
            --color-dark-blue: #254E7A;
            --color-medium-blue: #5584B0;
            --color-light-blue: #82C2E6;
            --color-baby-blue: #CBE3EF;
            --color-off-white: #F7F3EA;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--color-baby-blue);
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 280px;
            background-color: var(--color-dark-blue);
            color: var(--color-off-white);
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            background-color: var(--color-off-white);
            color: var(--color-dark-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.2em;
        }

        .profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--color-medium-blue);
            margin-bottom: 10px;
            overflow: hidden;
            border: 3px solid var(--color-off-white);
        }
        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .welcome { font-size: 0.85rem; color: var(--color-baby-blue); }
        .name { font-weight: 600; font-size: 1.1rem; color: var(--color-off-white); }

        .menu { list-style: none; padding: 0; margin: 0; }
        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }
        .menu-item:hover { background-color: var(--color-medium-blue); color: var(--color-off-white); }
        .menu-item.active { background-color: var(--color-medium-blue); color: var(--color-off-white); font-weight: 600; }
        .menu-item a { color: inherit; text-decoration: none; display: flex; align-items: center; width: 100%; }
        .menu-icon { margin-right: 15px; width: 20px; text-align: center; font-size: 1.1em; }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .header {
            background-color: var(--color-off-white);
            padding: 20px 30px;
            border-bottom: 1px solid var(--color-baby-blue);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 5px rgba(0,0,0,0.05);
        }
        .title h1 { font-size: 1.8rem; margin-bottom: 3px; color: var(--color-dark-blue); }
        .subtitle { font-size: 0.9rem; color: #777; }
        .header-right { display: flex; align-items: center; gap: 25px; }
        .user-info { text-align: right; }
        .user-info p { font-size: 0.95rem; font-weight: 600; color: var(--color-dark-blue); margin-bottom: 0; }
        .user-info span { font-size: 0.8em; color: #666; }
        .header .avatar { width: 40px; height: 40px; border: none; } /* Override avatar di header */

        .content {
            padding: 30px;
            flex: 1;
            overflow-y: auto;
        }

        .card {
            background-color: var(--color-off-white);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .card-header {
            padding: 18px 25px;
            border-bottom: 1px solid var(--color-baby-blue);
            background-color: #fcfcfc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--color-dark-blue);
        }
        .card-content {
            padding: 25px;
        }

        .form-control {
            border-color: var(--color-baby-blue);
        }
        .form-control:focus {
            border-color: var(--color-medium-blue);
            box-shadow: 0 0 0 0.25rem rgba(85, 132, 176, 0.25);
        }
        .btn-primary {
            background-color: var(--color-dark-blue);
            border-color: var(--color-dark-blue);
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: var(--color-medium-blue);
            border-color: var(--color-medium-blue);
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .help-block {
            color: #dc3545; /* Merah untuk error */
            font-size: 0.85em;
            margin-top: 5px;
            display: block;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: var(--color-baby-blue);
        }
        .table thead {
            background-color: var(--color-medium-blue);
            color: var(--color-off-white);
        }
        .table thead th {
            border-bottom: 2px solid var(--color-dark-blue);
        }
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: var(--color-light-blue) !important;
            cursor: default;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .badge-income {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-expense {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon">F</div>
            <span>Finote</span>
        </div>
        
        <div class="profile">
            <div class="avatar">
                <img src="<?= $photo ?>" alt="Profile">
            </div>
            <span class="welcome">Welcome back</span>
            <span class="name"><?php echo htmlspecialchars($username); ?></span>
        </div>
        
        <ul class="menu">
            <li class="menu-item">
                <a href="<?= BASE_URL ?>/user/dashboard.php">
                    <span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?= BASE_URL ?>/user/riwayat.php">
                    <span class="menu-icon"><i class="fas fa-exchange-alt"></i></span>
                    <span>Riwayat</span>
                </a>
            </li>
            <li class="menu-item active">
                <a href="<?= BASE_URL ?>/user/categories.php">
                    <span class="menu-icon"><i class="fas fa-tags"></i></span>
                    <span>Kategori</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?= BASE_URL ?>/user/profile.php">
                    <span class="menu-icon"><i class="fas fa-cog"></i></span>
                    <span>Profile</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?= BASE_URL ?>/logout.php">
                    <span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span>
                    <span>Log Out</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div class="title">
                <h1>Daftar Kategori</h1> <!-- Judul diubah -->
                <div class="subtitle">Lihat kategori transaksi yang tersedia</div> <!-- Subtitle diubah -->
            </div>
        </div>
        
        <div class="content">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="card-title">Daftar Kategori Transaksi</div>
                </div>
                <div class="card-content">
                    <?php if (empty($categories_list)): ?>
                        <div class="alert alert-info" role="alert">
                            Belum ada kategori yang tersedia.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Kategori</th>
                                        <th>Tipe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories_list as $cat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                            <td>
                                                <span class="badge rounded-pill <?php echo ($cat['type'] == 'income' ? 'badge-income' : 'badge-expense'); ?>">
                                                    <?php echo ($cat['type'] == 'income' ? 'Pemasukan' : 'Pengeluaran'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Form tambah/edit kategori dihilangkan dari sini -->
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
