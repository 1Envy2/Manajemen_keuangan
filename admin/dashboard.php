<?php
// admin/dashboard.php

// Panggil config.php terlebih dahulu, karena di dalamnya ada definisi BASE_URL dan koneksi $conn
require_once '../config.php';
// Panggil auth.php untuk fungsi-fungsi autentikasi dan otorisasi
require_once '../includes/auth.php';

// Cek akses: Pastikan user sudah login dan role-nya adalah 'admin'
// Fungsi ini akan mengalihkan (redirect) jika user tidak memenuhi syarat
check_admin_access();

// Ambil username dari session untuk ditampilkan di sidebar
$admin_username = $_SESSION['username'] ?? 'Admin';

// --- Mengambil Data Semua User dengan role 'user' saja ---
$all_users = [];
$sql_all_users = "SELECT id, username, email, role, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC";
if ($stmt_all_users = $conn->prepare($sql_all_users)) {
    if ($stmt_all_users->execute()) {
        $result_all_users = $stmt_all_users->get_result();
        while ($row = $result_all_users->fetch_assoc()) {
            $all_users[] = $row;
        }
    }
    $stmt_all_users->close();
}

// --- Mengambil Data Semua Kategori ---
$all_categories = [];
$sql_all_categories = "SELECT id, name, created_at FROM categories ORDER BY name ASC";
if ($stmt_all_categories = $conn->prepare($sql_all_categories)) {
    if ($stmt_all_categories->execute()) {
        $result_all_categories = $stmt_all_categories->get_result();
        while ($row = $result_all_categories->fetch_assoc()) {
            $all_categories[] = $row;
        }
    }
    $stmt_all_categories->close();
}

// Menangani pesan sukses/error dari operasi CRUD kategori (jika ada)
$success_message = '';
$error_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

$conn->close(); // Tutup koneksi setelah semua query selesai
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finote Admin - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* CSS ini disalin dari dashboard user Anda, bisa dipindahkan ke file CSS terpisah */
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
            display: flex;
            min-height: 100vh;
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

        .welcome {
            font-size: 0.85rem;
            color: var(--color-baby-blue);
        }

        .name {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--color-off-white);
        }

        .menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }

        .menu-item:hover {
            background-color: var(--color-medium-blue);
            color: var(--color-off-white);
        }

        .menu-item.active {
            background-color: var(--color-medium-blue);
            color: var(--color-off-white);
            font-weight: 600;
        }

        .menu-item a {
            color: inherit;
            text-decoration: none;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .menu-icon {
            margin-right: 15px;
            width: 20px;
            text-align: center;
            font-size: 1.1em;
        }

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
            flex-shrink: 0;
        }

        .title h1 {
            font-size: 1.8rem;
            margin-bottom: 3px;
            color: var(--color-dark-blue);
        }

        .subtitle {
            font-size: 0.9rem;
            color: #777;
        }

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
            margin-bottom: 25px; /* Added margin-bottom for spacing between cards */
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

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
        }

        .table tbody + tbody {
            border-top: 2px solid #dee2e6;
        }

        .table .table {
            background-color: #fff;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .table-hover tbody tr:hover {
            color: #212529;
            background-color: rgba(0, 0, 0, 0.075);
        }

        .btn-action {
            margin-right: 5px;
        }
        .btn-action.btn-sm {
            padding: .25rem .5rem;
            font-size: .875rem;
            border-radius: .2rem;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon">F</div> <span>Finote Admin</span>
        </div>
        
        <div class="profile">
            <div class="avatar">
                <img src="<?= BASE_URL ?>/assets/admin_profile.jpeg" alt="Admin Profile"> 
            </div>
            <span class="welcome">Welcome Admin</span>
            <span class="name"><?php echo htmlspecialchars($admin_username); ?></span>
        </div>
        
        <ul class="menu">
            <li class="menu-item active">
                <a href="<?= BASE_URL ?>/admin/dashboard.php">
                    <span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?= BASE_URL ?>/admin/tambah_kategori.php"> <span class="menu-icon"><i class="fas fa-tags"></i></span>
                    <span>Manajemen Kategori</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?= BASE_URL ?>/logout.php"> <span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span>
                    <span>Log Out</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div class="title">
                <h1>Admin Dashboard</h1>
                <div class="subtitle">Manajemen Sistem Finote</div>
            </div>
        </div>
        
        <div class="content">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">Daftar Pengguna (Role User)</div>
                    </div>
                <div class="card-content">
                    <?php if (empty($all_users)): ?>
                        <div class="alert alert-info" role="alert">
                            Belum ada pengguna terdaftar dengan role 'user'.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Terdaftar Sejak</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_users as $user_data): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user_data['id']); ?></td>
                                            <td><?php echo htmlspecialchars($user_data['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user_data['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user_data['role']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($user_data['created_at'])); ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/admin/users/edit.php?id=<?php echo $user_data['id']; ?>" class="btn btn-primary btn-sm btn-action" title="Edit Pengguna"><i class="fas fa-edit"></i></a>
                                                <a href="<?= BASE_URL ?>/admin/users/delete.php?id=<?php echo $user_data['id']; ?>" class="btn btn-danger btn-sm btn-action" title="Hapus Pengguna" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini? Semua data transaksi pengguna ini juga akan ikut terhapus!');"><i class="fas fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">Daftar Kategori</div> <a href="<?= BASE_URL ?>/admin/tambah_kategori.php" class="btn btn-success btn-sm"><i class="fas fa-plus me-2"></i> Tambah Kategori Baru</a>
                </div>
                <div class="card-content">
                    <?php if (empty($all_categories)): ?>
                        <div class="alert alert-info" role="alert">
                            Belum ada kategori yang ditambahkan.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nama Kategori</th>
                                        <th>Ditambahkan Pada</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_categories as $category): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['id']); ?></td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($category['created_at'])); ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/admin/edit_kategori.php?id=<?php echo $category['id']; ?>" class="btn btn-primary btn-sm btn-action"><i class="fas fa-edit"></i> Edit</a>
                                                <a href="<?= BASE_URL ?>/admin/hapus_kategori.php?id=<?php echo $category['id']; ?>" class="btn btn-danger btn-sm btn-action" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini? Kategori ini tidak dapat dihapus jika masih digunakan oleh transaksi mana pun.');"><i class="fas fa-trash-alt"></i> Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>