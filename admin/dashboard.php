<?php
require_once '../config.php';
require_once '../includes/auth.php';

check_admin_access();

$admin_username = $_SESSION['username'] ?? 'Admin';
$admin_photo = 'admin_profile.jpeg';
$current_page = basename($_SERVER['PHP_SELF']);

$sql_admin_photo = "SELECT photo FROM users WHERE username = ? AND role = 'admin'";
$stmt = $conn->prepare($sql_admin_photo);
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $photo_db = $row['photo'] ?? '';
    $admin_photo = (!empty($photo_db) && file_exists('../uploads/' . $photo_db)) ? $photo_db : 'admin_profile.jpeg';
}
$stmt->close();

$all_users = [];
$sql_all_users = "SELECT id, username, email, role, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC";
if ($stmt = $conn->prepare($sql_all_users)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_users[] = $row;
    }
    $stmt->close();
}

$all_categories = [];
$sql_all_categories = "SELECT id, name, created_at FROM categories ORDER BY name ASC";
if ($stmt = $conn->prepare($sql_all_categories)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_categories[] = $row;
    }
    $stmt->close();
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Finote</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --color-dark-blue: #254E7A;
            --color-medium-blue: #5584B0;
            --color-light-blue: #82C2E6;
            --color-baby-blue: #CBE3EF;
            --color-off-white: #F7F3EA;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
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
        }
        .profile {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding-bottom: 20px;
        }
        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 10px;
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
        }
        .menu-item {
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 5px;
            transition: 0.3s;
        }
        .menu-item:hover,
        .menu-item.active {
            background-color: var(--color-medium-blue);
        }
        .menu-item a {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
        }
        .menu-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .main-content {
            flex: 1;
            padding: 30px;
        }
        .card {
            background-color: var(--color-off-white);
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
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
    </style>
</head>
<body>
<div class="sidebar">
    <div class="logo">
        <div class="logo-icon">F</div> Finote Admin
    </div>
    <div class="profile">
        <div class="avatar">
            <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($admin_photo) ?>?v=<?= time() ?>" alt="Admin Foto">
        </div>
        <div class="welcome">Welcome Admin</div>
        <div class="name"><?= htmlspecialchars($admin_username) ?></div>
    </div>
    <ul class="menu">
        <li class="menu-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/admin/dashboard.php"><span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard</a>
        </li>
        <li class="menu-item <?= $current_page == 'profile_admin.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/admin/profile_admin.php"><span class="menu-icon"><i class="fas fa-user"></i></span> Profile</a>
        </li>
        <li class="menu-item <?= $current_page == 'tambah_kategori.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/admin/tambah_kategori.php"><span class="menu-icon"><i class="fas fa-tags"></i></span> Manajemen Kategori</a>
        </li>
        <li class="menu-item">
            <a href="<?= BASE_URL ?>/logout.php"><span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span> Logout</a>
        </li>
    </ul>
</div>

<div class="main-content">
    <h2>Dashboard Admin</h2>
    <?php if (!empty($success_message)): ?><div class="alert alert-success"><?= $success_message ?></div><?php endif; ?>
    <?php if (!empty($error_message)): ?><div class="alert alert-danger"><?= $error_message ?></div><?php endif; ?>

    <!-- Daftar Pengguna -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Daftar Pengguna</div>
        </div>
        <div class="card-content">
            <?php if (empty($all_users)): ?>
                <div class="alert alert-info">Belum ada pengguna.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Terdaftar</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= $user['role'] ?></td>
                                <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <a href="hapus_pengguna.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Yakin hapus pengguna ini?')"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Daftar Kategori -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Daftar Kategori</div>
            <a href="tambah_kategori.php" class="btn btn-success btn-sm"><i class="fas fa-plus me-2"></i> Tambah Kategori</a>
        </div>
        <div class="card-content">
            <?php if (empty($all_categories)): ?>
                <div class="alert alert-info">Belum ada kategori.</div>
            <?php else: ?>
                <table class="table table-striped">
                    <thead><tr><th>ID</th><th>Nama</th><th>Ditambahkan</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($all_categories as $cat): ?>
                            <tr>
                                <td><?= $cat['id'] ?></td>
                                <td><?= htmlspecialchars($cat['name']) ?></td>
                                <td><?= date('d M Y', strtotime($cat['created_at'])) ?></td>
                                <td>
                                    <a href="edit_kategori.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                    <a href="hapus_kategori.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus kategori ini?')"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
