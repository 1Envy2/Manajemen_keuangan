<?php
// admin/tambah_kategori.php

require_once '../config.php'; // Panggil config.php yang ada di root
require_once '../includes/auth.php'; // Panggil auth.php dari folder includes

check_admin_access(); // Pastikan hanya admin yang bisa mengakses halaman ini

$category_name = '';
$name_err = '';
$success_msg = '';
$error_msg = '';

// Proses form saat submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi nama kategori
    if (empty(trim($_POST['category_name']))) {
        $name_err = 'Nama kategori tidak boleh kosong.';
    } else {
        $category_name = trim($_POST['category_name']);
        
        // Cek apakah kategori dengan nama ini sudah ada di database
        $sql = "SELECT id FROM categories WHERE name = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_name);
            $param_name = $category_name;
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $name_err = 'Kategori dengan nama ini sudah ada.';
                }
            } else {
                $error_msg = "Oops! Terjadi kesalahan saat memeriksa kategori. Silakan coba lagi nanti.";
            }
            $stmt->close();
        }
    }

    // Jika tidak ada error validasi, masukkan ke database
    if (empty($name_err) && empty($error_msg)) {
        $sql = "INSERT INTO categories (name) VALUES (?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_name);
            $param_name = $category_name; // Menggunakan nama kategori yang sudah divalidasi
            if ($stmt->execute()) {
                $success_msg = 'Kategori berhasil ditambahkan!';
                $category_name = ''; // Kosongkan field input setelah berhasil
            } else {
                $error_msg = "Gagal menambahkan kategori. Silakan coba lagi.";
            }
            $stmt->close();
        }
    }
}
$conn->close(); // Tutup koneksi database setelah semua operasi selesai
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Tambah Kategori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* CSS ini disalin dari dashboard admin Anda, idealnya dipindahkan ke file CSS terpisah */
        :root { --color-dark-blue: #254E7A; --color-medium-blue: #5584B0; --color-light-blue: #82C2E6; --color-baby-blue: #CBE3EF; --color-off-white: #F7F3EA; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--color-baby-blue); display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background-color: var(--color-dark-blue); color: var(--color-off-white); padding: 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.1); flex-shrink: 0; }
        .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 30px; font-size: 1.5rem; font-weight: bold; }
        .logo-icon { width: 40px; height: 40px; background-color: var(--color-off-white); color: var(--color-dark-blue); display: flex; align-items: center; justify-content: center; border-radius: 5px; font-weight: bold; font-size: 1.2em; }
        .profile { display: flex; flex-direction: column; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .avatar { width: 80px; height: 80px; border-radius: 50%; background-color: var(--color-medium-blue); margin-bottom: 10px; overflow: hidden; border: 3px solid var(--color-off-white); }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .welcome { font-size: 0.85rem; color: var(--color-baby-blue); }
        .name { font-weight: 600; font-size: 1.1rem; color: var(--color-off-white); }
        .menu { list-style: none; padding: 0; margin: 0; }
        .menu-item { display: flex; align-items: center; padding: 12px 15px; border-radius: 5px; margin-bottom: 8px; cursor: pointer; transition: background-color 0.3s, color 0.3s; }
        .menu-item:hover { background-color: var(--color-medium-blue); color: var(--color-off-white); }
        .menu-item.active { background-color: var(--color-medium-blue); color: var(--color-off-white); font-weight: 600; }
        .menu-item a { color: inherit; text-decoration: none; display: flex; align-items: center; width: 100%; }
        .menu-icon { margin-right: 15px; width: 20px; text-align: center; font-size: 1.1em; }
        .main-content { flex: 1; display: flex; flex-direction: column; }
        .header { background-color: var(--color-off-white); padding: 20px 30px; border-bottom: 1px solid var(--color-baby-blue); display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 5px rgba(0,0,0,0.05); flex-shrink: 0; }
        .title h1 { font-size: 1.8rem; margin-bottom: 3px; color: var(--color-dark-blue); }
        .subtitle { font-size: 0.9rem; color: #777; }
        .content { padding: 30px; flex: 1; overflow-y: auto; }
        .card { background-color: var(--color-off-white); border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); overflow: hidden; margin-bottom: 25px; }
        .card-header { padding: 18px 25px; border-bottom: 1px solid var(--color-baby-blue); background-color: #fcfcfc; display: flex; justify-content: space-between; align-items: center;}
        .card-title { font-size: 1.25rem; font-weight: 600; color: var(--color-dark-blue); }
        .card-content { padding: 25px; }
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
            <span class="name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
        </div>
        
        <ul class="menu">
            <li class="menu-item">
                <a href="<?= BASE_URL ?>/admin/dashboard.php">
                    <span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item active">
                <a href="<?= BASE_URL ?>/admin/tambah_kategori.php"> <span class="menu-icon"><i class="fas fa-tags"></i></span>
                    <span>Manajemen Kategori</span>
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
                <h1>Tambah Kategori</h1>
                <div class="subtitle">Tambahkan Kategori Transaksi Baru</div>
            </div>
        </div>
        
        <div class="content">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Form Tambah Kategori</div>
                </div>
                <div class="card-content">
                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $success_msg; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error_msg; ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Nama Kategori</label>
                            <input type="text" name="category_name" id="category_name" 
                                class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" 
                                value="<?php echo htmlspecialchars($category_name); ?>">
                            <span class="invalid-feedback"><?php echo $name_err; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary" style="background-color: var(--color-dark-blue); border-color: var(--color-dark-blue);">Tambah Kategori</button>
                            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-secondary">Kembali ke Dashboard Admin</a> </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>