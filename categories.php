<?php
// categories.php
session_start();

// Cek jika user belum login, redirect ke halaman login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: login.php');
    exit;
}

require_once 'config.php';

$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

$name = $type = '';
$name_err = $type_err = '';
$success_message = '';
$edit_id = null;
$current_category_name = '';
$current_category_type = '';

// --- Handle Create & Update ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi Name
    if (empty(trim($_POST['name']))) {
        $name_err = 'Nama kategori tidak boleh kosong.';
    } else {
        $name = trim($_POST['name']);
    }

    // Validasi Type
    if (empty(trim($_POST['type']))) {
        $type_err = 'Pilih tipe kategori.';
    } elseif (!in_array(trim($_POST['type']), ['income', 'expense'])) {
        $type_err = 'Tipe kategori tidak valid.';
    } else {
        $type = trim($_POST['type']);
    }

    $post_edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : null;

    if (empty($name_err) && empty($type_err)) {
        if ($post_edit_id > 0) { // Update existing category
            // Pastikan kategori yang diedit milik user atau global
            $sql = "UPDATE categories SET name = ?, type = ? WHERE id = ? AND (user_id = ? OR user_id IS NULL)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('ssii', $name, $type, $post_edit_id, $user_id);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $success_message = 'Kategori berhasil diperbarui.';
                    } else {
                        $success_message = 'Tidak ada perubahan pada kategori atau kategori tidak ditemukan/bukan milik Anda.';
                    }
                } else {
                    echo 'Error updating category: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else { // Create new category
            $sql = "INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('iss', $user_id, $name, $type);
                if ($stmt->execute()) {
                    $success_message = 'Kategori berhasil ditambahkan.';
                    // Clear form after successful addition
                    $name = $type = '';
                } else {
                    echo 'Error adding category: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// --- Handle Delete ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    // Cek apakah kategori ini milik user atau global sebelum menghapus
    // Jika ada transaksi yang menggunakan kategori ini, ON DELETE RESTRICT akan mencegah penghapusan
    $sql = "DELETE FROM categories WHERE id = ? AND (user_id = ? OR user_id IS NULL)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('ii', $delete_id, $user_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success_message = 'Kategori berhasil dihapus.';
            } else {
                $success_message = 'Kategori tidak dapat dihapus (mungkin sedang digunakan atau bukan milik Anda).';
            }
        } else {
            // Tangani error jika ada foreign key constraint (ON DELETE RESTRICT)
            if ($conn->errno == 1451) { // MySQL error code for foreign key constraint fail
                $success_message = 'Kategori tidak dapat dihapus karena masih digunakan dalam transaksi.';
            } else {
                echo 'Error deleting category: ' . $stmt->error;
            }
        }
        $stmt->close();
    }
    // Redirect untuk membersihkan parameter GET setelah delete
    header('location: categories.php?msg=' . urlencode($success_message));
    exit;
}

// --- Handle Edit (Populate Form) ---
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $sql_edit = "SELECT name, type FROM categories WHERE id = ? AND (user_id = ? OR user_id IS NULL)";
    if ($stmt_edit = $conn->prepare($sql_edit)) {
        $stmt_edit->bind_param('ii', $edit_id, $user_id);
        $stmt_edit->execute();
        $result_edit = $stmt_edit->get_result();
        if ($result_edit->num_rows == 1) {
            $row_edit = $result_edit->fetch_assoc();
            $current_category_name = $row_edit['name'];
            $current_category_type = $row_edit['type'];
        } else {
            // Kategori tidak ditemukan atau bukan milik user, reset edit mode
            $edit_id = null;
        }
        $stmt_edit->close();
    }
}

// Tampilkan pesan sukses setelah redirect dari delete
if (isset($_GET['msg'])) {
    $success_message = htmlspecialchars($_GET['msg']);
}

// Ambil semua kategori pengguna (dan global)
$categories_list = [];
$sql_list = "SELECT id, name, type FROM categories WHERE user_id = ? OR user_id IS NULL ORDER BY type DESC, name ASC";
if ($stmt_list = $conn->prepare($sql_list)) {
    $stmt_list->bind_param('i', $user_id);
    $stmt_list->execute();
    $result_list = $stmt_list->get_result();
    while ($row_list = $result_list->fetch_assoc()) {
        $categories_list[] = $row_list;
    }
    $stmt_list->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finote - Kelola Kategori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
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
            cursor: default; /* Ubah menjadi default karena tidak ada link di baris tabel */
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
                <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($username))); ?>?d=identicon&s=80" alt="Profile">
            </div>
            <span class="welcome">Welcome back</span>
            <span class="name"><?php echo htmlspecialchars($username); ?></span>
        </div>
        
        <ul class="menu">
            <li class="menu-item">
                <a href="dashboard.php">
                    <span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="transaction_history.php">
                    <span class="menu-icon"><i class="fas fa-exchange-alt"></i></span>
                    <span>Riwayat</span>
                </a>
            </li>
            <li class="menu-item active">
                <a href="categories.php">
                    <span class="menu-icon"><i class="fas fa-tags"></i></span>
                    <span>Kategori</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="settings.php">
                    <span class="menu-icon"><i class="fas fa-cog"></i></span>
                    <span>Settings</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="logout.php">
                    <span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span>
                    <span>Log Out</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div class="title">
                <h1>Kelola Kategori</h1>
                <div class="subtitle">Tambah, edit, atau hapus kategori Anda</div>
            </div>
            
            <div class="header-right">
                <div class="user-info">
                    <p>Halo, <?php echo htmlspecialchars($username); ?></p>
                    <span>Pengguna Finote</span>
                </div>
                
                <div class="avatar" style="width: 40px; height: 40px;">
                    <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($username))); ?>?d=identicon&s=40" alt="User">
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="card-title"><?php echo ($edit_id ? 'Edit' : 'Tambah'); ?> Kategori</div>
                </div>
                <div class="card-content">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($edit_id ?? ''); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($current_category_name); ?>" placeholder="Contoh: Belanja Bulanan">
                            <span class="help-block"><?php echo $name_err; ?></span>
                        </div>
                        
                        <div class="mb-3">
                            <label for="type" class="form-label">Tipe Kategori</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">Pilih Tipe</option>
                                <option value="income" <?php echo ($current_category_type == 'income' ? 'selected' : ''); ?>>Pemasukan</option>
                                <option value="expense" <?php echo ($current_category_type == 'expense' ? 'selected' : ''); ?>>Pengeluaran</option>
                            </select>
                            <span class="help-block"><?php echo $type_err; ?></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> <?php echo ($edit_id ? 'Perbarui' : 'Tambah'); ?> Kategori</button>
                        <?php if ($edit_id): ?>
                            <a href="categories.php" class="btn btn-secondary ms-2"><i class="fas fa-times me-2"></i> Batalkan Edit</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">Daftar Kategori</div>
                </div>
                <div class="card-content">
                    <?php if (empty($categories_list)): ?>
                        <div class="alert alert-info" role="alert">
                            Belum ada kategori yang ditambahkan.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Kategori</th>
                                        <th>Tipe</th>
                                        <th class="text-center">Aksi</th>
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
                                            <td class="text-center">
                                                <a href="categories.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-info text-white me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="categories.php?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini? Menghapus kategori yang sedang digunakan dalam transaksi akan menyebabkan error.');"><i class="fas fa-trash-alt"></i></a>
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