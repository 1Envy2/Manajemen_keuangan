<?php
require_once '../config.php';
require_once '../includes/auth.php';

check_admin_access();
$id_user_admin = get_user_id(); // Menggunakan nama variabel yang lebih spesifik agar tidak bentrok

$username = '';
$email = '';
$phone = '';
$photo_db = '';
$created_at = '';

// Ambil data admin yang sedang login untuk sidebar
$query_admin = "SELECT username, email, phone, photo, created_at FROM users WHERE id = ? AND role = 'admin'";
$stmt_admin = $conn->prepare($query_admin);
$stmt_admin->bind_param("i", $id_user_admin);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
if ($result_admin->num_rows === 1) {
    $data_admin = $result_admin->fetch_assoc();
    $username = $data_admin['username'];
    $email = $data_admin['email'];
    $phone = $data_admin['phone'] ?? '';
    $photo_db_admin = $data_admin['photo'] ?? ''; // Nama variabel berbeda untuk foto admin
    $created_at = $data_admin['created_at'];
}
$stmt_admin->close();

$photo_url = (!empty($photo_db_admin) && file_exists('../uploads/' . $photo_db_admin))
    ? BASE_URL . '/uploads/' . $photo_db_admin
    : BASE_URL . '/assets/admin_profile.jpeg';

$current_page = basename($_SERVER['PHP_SELF']);

$category_id = $_GET['id'] ?? null;
$category_name = '';
$category_type = ''; // BARU: Inisialisasi variabel untuk tipe kategori
$name_err = '';
$type_err = ''; // BARU: Inisialisasi error untuk tipe
$success_msg = '';
$error_msg = '';

// Validasi ID kategori dari URL
if ($category_id === null || !is_numeric($category_id)) {
    $_SESSION['error_message'] = "ID kategori tidak valid.";
    header('location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

// Ambil data kategori yang akan diedit dari database
$sql_get = "SELECT name, type FROM categories WHERE id = ?"; // BARU: Ambil kolom 'type'
if ($stmt_get = $conn->prepare($sql_get)) {
    $stmt_get->bind_param("i", $category_id);
    if ($stmt_get->execute()) {
        $result_get = $stmt_get->get_result();
        if ($result_get->num_rows == 1) {
            $category_data = $result_get->fetch_assoc();
            $category_name = $category_data['name'];
            $category_type = $category_data['type']; // BARU: Set tipe kategori
        } else {
            $_SESSION['error_message'] = "Kategori tidak ditemukan.";
            header('location: ' . BASE_URL . '/admin/dashboard.php');
            exit;
        }
    } else {
        $error_msg = "Gagal mengambil data kategori.";
    }
    $stmt_get->close();
}

// Tangani pengiriman form (saat ada POST request)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi nama kategori
    if (empty(trim($_POST['category_name']))) {
        $name_err = 'Nama kategori tidak boleh kosong.';
    } else {
        $new_category_name = trim($_POST['category_name']);
        // Cek duplikat nama kategori, kecuali kategori yang sedang diedit
        $sql_check = "SELECT id FROM categories WHERE name = ? AND id != ?";
        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("si", $new_category_name, $category_id);
            if ($stmt_check->execute()) {
                $stmt_check->store_result();
                if ($stmt_check->num_rows == 1) {
                    $name_err = 'Kategori dengan nama ini sudah ada.';
                }
            } else {
                $error_msg = "Terjadi kesalahan saat cek nama kategori.";
            }
            $stmt_check->close();
        }
    }

    // BARU: Validasi tipe kategori
    if (empty(trim($_POST['category_type']))) {
        $type_err = 'Tipe kategori tidak boleh kosong.';
    } else {
        $new_category_type = trim($_POST['category_type']);
        if (!in_array($new_category_type, ['income', 'expense'])) {
            $type_err = 'Tipe kategori tidak valid.';
        }
    }


    // Lanjutkan jika tidak ada error pada nama dan tipe
    if (empty($name_err) && empty($type_err) && empty($error_msg)) {
        // BARU: Perbarui juga kolom 'type'
        $sql_update = "UPDATE categories SET name = ?, type = ? WHERE id = ?";
        if ($stmt_update = $conn->prepare($sql_update)) {
            $stmt_update->bind_param("ssi", $new_category_name, $new_category_type, $category_id); // 'ssi' untuk dua string dan satu integer
            if ($stmt_update->execute()) {
                $success_msg = 'Kategori berhasil diperbarui!';
                $category_name = $new_category_name;
                $category_type = $new_category_type; // BARU: Perbarui nilai tipe setelah berhasil
            } else {
                $error_msg = "Gagal memperbarui kategori.";
            }
            $stmt_update->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kategori</title>
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
            display: flex;
            flex-direction: column;
        }
        .content {
            padding: 30px;
            flex: 1;
            overflow-y: auto;
        }
        .page-title {
            font-size: 2rem;
            font-weight: 600;
            color: #222;
            margin-bottom: 20px;
        }
        .card {
            background-color: var(--color-off-white);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 25px;
        }
        .card-header {
            padding: 18px 25px;
            border-bottom: 1px solid var(--color-baby-blue);
            background-color: white;
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
            <img src="<?= $photo_url ?>?v=<?= time() ?>" alt="Foto Admin">
        </div>
        <div class="welcome">Welcome Admin</div>
        <div class="name"><?= htmlspecialchars($username) ?></div>
    </div>
    <ul class="menu">
        <li class="menu-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <a href="dashboard.php"><span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard</a>
        </li>
        <li class="menu-item <?= $current_page == 'profile_admin.php' ? 'active' : '' ?>">
            <a href="profile_admin.php"><span class="menu-icon"><i class="fas fa-user"></i></span> Profile</a>
        </li>
        <li class="menu-item <?= $current_page == 'tambah_kategori.php' || $current_page == 'edit_kategori.php' ? 'active' : '' ?>">
            <a href="tambah_kategori.php"><span class="menu-icon"><i class="fas fa-tags"></i></span> Manajemen Kategori</a>
        </li>
        <li class="menu-item">
            <a href="../logout.php"><span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span> Logout</a>
        </li>
    </ul>
</div>

<div class="main-content">
    <div class="content">
        <h2 class="page-title">Edit Kategori</h2>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Form Edit Kategori</div>
            </div>
            <div class="card-content">
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success"><?= $success_msg ?></div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger"><?= $error_msg ?></div>
                <?php endif; ?>

                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $category_id; ?>" method="post">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Nama Kategori</label>
                        <input type="text" name="category_name" id="category_name"
                               class="form-control <?= (!empty($name_err)) ? 'is-invalid' : ''; ?>"
                               value="<?= htmlspecialchars($category_name); ?>">
                        <div class="invalid-feedback"><?= $name_err ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="category_type" class="form-label">Tipe Kategori</label>
                        <select name="category_type" id="category_type"
                            class="form-select <?= (!empty($type_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Pilih Tipe</option>
                            <option value="income" <?= ($category_type == 'income') ? 'selected' : ''; ?>>Pemasukan (Income)</option>
                            <option value="expense" <?= ($category_type == 'expense') ? 'selected' : ''; ?>>Pengeluaran (Expense)</option>
                        </select>
                        <div class="invalid-feedback"><?= $type_err ?></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">Perbarui Kategori</button>
                        <a href="tambah_kategori.php" class="btn btn-secondary">Kembali ke Manajemen Kategori</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>