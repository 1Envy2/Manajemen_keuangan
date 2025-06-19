<?php
require_once '../config.php';
require_once '../includes/auth.php';

check_admin_access();

// Ambil username dan foto admin dari session/database
$admin_username = $_SESSION['username'] ?? 'Admin';
$admin_photo = 'admin_profile.jpeg';

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

// Untuk menu aktif
$current_page = basename($_SERVER['PHP_SELF']);

// Inisialisasi form
$category_name = '';
$name_err = '';
$success_msg = '';
$error_msg = '';

// Handle form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty(trim($_POST['category_name']))) {
        $name_err = 'Nama kategori tidak boleh kosong.';
    } else {
        $category_name = trim($_POST['category_name']);

        // Cek duplikat
        $sql = "SELECT id FROM categories WHERE name = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $category_name);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $name_err = 'Kategori dengan nama ini sudah ada.';
            }
            $stmt->close();
        }
    }

    if (empty($name_err)) {
        $sql = "INSERT INTO categories (name) VALUES (?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $category_name);
            if ($stmt->execute()) {
                $success_msg = "Kategori berhasil ditambahkan!";
                $category_name = '';
            } else {
                $error_msg = "Gagal menambahkan kategori. Silakan coba lagi.";
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Kategori - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
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
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
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
    <h2 class="mb-4">Tambah Kategori Baru</h2>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success"><?= $success_msg ?></div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger"><?= $error_msg ?></div>
    <?php endif; ?>

    <div class="card" style="border-radius: 12px; overflow: hidden;">
    <div class="card-header" style="background-color: #ffffff; padding: 20px 25px; border-bottom: 1px solid #dee2e6;">
        <div class="card-title" style="font-size: 1.25rem; font-weight: 600; color: var(--color-dark-blue); margin: 0;">
            Form Tambah Kategori
        </div>
    </div>
    <div class="card-content" style="background-color: #f8f5ef; padding: 25px;">
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success" role="alert"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger" role="alert"><?= $error_msg ?></div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
            <div class="mb-3">
                <label for="category_name" class="form-label">Nama Kategori</label>
                <input type="text" name="category_name" id="category_name"
                    class="form-control <?= (!empty($name_err)) ? 'is-invalid' : ''; ?>"
                    value="<?= htmlspecialchars($category_name); ?>">
                <?php if (!empty($name_err)): ?>
                    <div class="invalid-feedback"><?= $name_err ?></div>
                <?php endif; ?>
            </div>
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary px-4"
                    style="background-color: #007bff; border-color: #007bff;">
                    Tambah Kategori
                </button>
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-secondary px-4"
                    style="background-color: #6c757d; border-color: #6c757d;">
                    Kembali ke Dashboard Admin
                </a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
