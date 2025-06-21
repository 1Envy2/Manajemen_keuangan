<?php
require_once '../config.php';
require_once '../includes/auth.php';

check_user_access(); // Pastikan hanya user yang bisa mengakses halaman ini

// Ambil ID pengguna yang sedang login
$user_id = get_user_id();

// Ambil data profil pengguna untuk sidebar
$username = $_SESSION['username'] ?? 'User';
$user_photo_sidebar = 'user_profile.jpeg'; // Default foto

$sql_user_photo = "SELECT photo FROM users WHERE id = ?";
$stmt_user_photo = $conn->prepare($sql_user_photo);
$stmt_user_photo->bind_param("i", $user_id);
$stmt_user_photo->execute();
$result_user_photo = $stmt_user_photo->get_result();
if ($row_user_photo = $result_user_photo->fetch_assoc()) {
    $photo_db_name = $row_user_photo['photo'] ?? '';
    $photo_path = __DIR__ . '/uploads/' . $photo_db_name; // path relatif dari file PHP ini
    $photo_url = (!empty($photo_db_name) && file_exists($photo_path))
        ? BASE_URL . '/user/uploads/' . $photo_db_name
        : BASE_URL . '/assets/profile.jpeg';
}

// Inisialisasi variabel form
$transaction_id = $_GET['id'] ?? null;
$amount = '';
$description = '';
$transaction_date = '';
$category_id = '';
$category_type = ''; // Untuk menyimpan tipe kategori yang terkait (income/expense)
$amount_err = '';
$description_err = '';
$transaction_date_err = '';
$category_id_err = '';
$success_msg = '';
$error_msg = '';

// Jika ID transaksi tidak valid atau tidak ada
if ($transaction_id === null || !is_numeric($transaction_id)) {
    $_SESSION['error_message'] = "ID transaksi tidak valid.";
    header('location: ' . BASE_URL . '/user/dashboard.php'); // Arahkan ke dashboard user
    exit;
}

// Ambil data transaksi dari database
$sql_get_transaction = "SELECT amount, description, transaction_date, category_id FROM transactions WHERE id = ? AND user_id = ?";
if ($stmt_get = $conn->prepare($sql_get_transaction)) {
    $stmt_get->bind_param("ii", $transaction_id, $user_id);
    if ($stmt_get->execute()) {
        $result_get = $stmt_get->get_result();
        if ($result_get->num_rows == 1) {
            $transaction_data = $result_get->fetch_assoc();
            $amount = $transaction_data['amount'];
            $description = $transaction_data['description'];
            $transaction_date = $transaction_data['transaction_date'];
            $category_id = $transaction_data['category_id'];

            // Ambil tipe kategori (income/expense) berdasarkan category_id
            $sql_get_category_type = "SELECT type FROM categories WHERE id = ?";
            if ($stmt_cat_type = $conn->prepare($sql_get_category_type)) {
                $stmt_cat_type->bind_param("i", $category_id);
                $stmt_cat_type->execute();
                $result_cat_type = $stmt_cat_type->get_result();
                if ($row_cat_type = $result_cat_type->fetch_assoc()) {
                    $category_type = $row_cat_type['type'];
                }
                $stmt_cat_type->close();
            }

        } else {
            $_SESSION['error_message'] = "Transaksi tidak ditemukan atau bukan milik Anda.";
            header('location: ' . BASE_URL . '/user/dashboard.php');
            exit;
        }
    } else {
        $error_msg = "Gagal mengambil data transaksi.";
    }
    $stmt_get->close();
}

// Ambil daftar kategori dari database
$categories = [];
$sql_categories = "SELECT id, name, type FROM categories ORDER BY name ASC";
$result_categories = $conn->query($sql_categories);
if ($result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[$row['type']][] = $row; // Kelompokkan kategori berdasarkan tipe
    }
}

// Handle pengiriman form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi input
    if (empty(trim($_POST['amount']))) {
        $amount_err = 'Jumlah tidak boleh kosong.';
    } elseif (!is_numeric($_POST['amount']) || $_POST['amount'] <= 0) {
        $amount_err = 'Jumlah harus berupa angka positif.';
    } else {
        $amount = trim($_POST['amount']);
    }

    $description = trim($_POST['description']); // Deskripsi bisa kosong

    if (empty(trim($_POST['transaction_date']))) {
        $transaction_date_err = 'Tanggal transaksi tidak boleh kosong.';
    } else {
        $transaction_date = trim($_POST['transaction_date']);
    }

    if (empty(trim($_POST['category_id']))) {
        $category_id_err = 'Kategori tidak boleh kosong.';
    } else {
        $new_category_id = trim($_POST['category_id']);
        // Pastikan kategori yang dipilih valid
        $category_valid = false;
        $temp_category_type = ''; // Untuk menyimpan tipe kategori yang baru dipilih
        foreach ($categories as $type_group) {
            foreach ($type_group as $cat) {
                if ($cat['id'] == $new_category_id) {
                    $category_valid = true;
                    $temp_category_type = $cat['type']; // Ambil tipe dari kategori yang dipilih
                    break 2; // Keluar dari kedua loop
                }
            }
        }
        if (!$category_valid) {
            $category_id_err = 'Kategori tidak valid.';
        } else {
            $category_id = $new_category_id;
            $category_type = $temp_category_type; // Perbarui tipe kategori yang akan disimpan
        }
    }


    // Jika tidak ada error, lakukan update
    if (empty($amount_err) && empty($description_err) && empty($transaction_date_err) && empty($category_id_err)) {
        // Kolom 'type' di tabel transactions diperbarui berdasarkan tipe category_id yang dipilih
        $sql_update = "UPDATE transactions SET amount = ?, description = ?, transaction_date = ?, category_id = ?, type = ? WHERE id = ? AND user_id = ?";
        if ($stmt_update = $conn->prepare($sql_update)) {
            $stmt_update->bind_param("dssisii", $amount, $description, $transaction_date, $category_id, $category_type, $transaction_id, $user_id);
            if ($stmt_update->execute()) {
                $success_msg = "Transaksi berhasil diperbarui!";
            } else {
                $error_msg = "Gagal memperbarui transaksi. Silakan coba lagi.";
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
    <title>Edit Transaksi - Finote</title>
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
            overflow: hidden;
            border: 3px solid var(--color-off-white);
            margin-bottom: 10px;
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
        }
        .menu-item:hover { background-color: var(--color-medium-blue); }
        .menu-item.active {
            background-color: var(--color-medium-blue);
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
            padding: 30px;
            background-color: var(--color-baby-blue);
        }
        .card {
            background-color: var(--color-off-white);
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #ffffff;
            padding: 20px 25px;
            border-bottom: 1px solid #dee2e6;
            margin: -25px -25px 25px -25px; /* Adjust to fit within card padding */
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--color-dark-blue);
            margin: 0;
        }
        /* Style untuk grup kategori */
        optgroup {
            font-weight: bold;
            color: var(--color-dark-blue);
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
            <img src="<?= htmlspecialchars($photo_url) ?>" alt="Profile">
        </div>
        <span class="welcome">Welcome back</span>
        <span class="name"><?= htmlspecialchars($username) ?></span>
    </div>
    <ul class="menu">
        <li class="menu-item"><a href="<?= BASE_URL ?>/user/dashboard.php"><span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span>Dashboard</a></li>
        <li class="menu-item active"><a href="<?= BASE_URL ?>/user/riwayat.php"><span class="menu-icon"><i class="fas fa-exchange-alt"></i></span>Riwayat</a></li>
        <li class="menu-item"><a href="<?= BASE_URL ?>/user/categories.php"><span class="menu-icon"><i class="fas fa-tags"></i></span>Kategori</a></li>
        <li class="menu-item"><a href="<?= BASE_URL ?>/user/profile.php"><span class="menu-icon"><i class="fas fa-cog"></i></span>Profile</a></li>
        <li class="menu-item"><a href="<?= BASE_URL ?>/logout.php"><span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span>Log Out</a></li>
    </ul>
</div>

<div class="main-content">
    <h2 class="mb-4">Edit Transaksi</h2>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success"><?= $success_msg ?></div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger"><?= $error_msg ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Form Edit Transaksi</div>
        </div>
        <div class="card-body">
            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $transaction_id; ?>" method="post">
                <div class="mb-3">
                    <label for="amount" class="form-label">Jumlah</label>
                    <input type="number" name="amount" id="amount"
                           class="form-control <?= (!empty($amount_err)) ? 'is-invalid' : ''; ?>"
                           value="<?= htmlspecialchars($amount); ?>" step="0.01">
                    <?php if (!empty($amount_err)): ?>
                        <div class="invalid-feedback"><?= $amount_err ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi</label>
                    <textarea name="description" id="description"
                              class="form-control <?= (!empty($description_err)) ? 'is-invalid' : ''; ?>"
                              rows="3"><?= htmlspecialchars($description); ?></textarea>
                    <?php if (!empty($description_err)): ?>
                        <div class="invalid-feedback"><?= $description_err ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="transaction_date" class="form-label">Tanggal Transaksi</label>
                    <input type="date" name="transaction_date" id="transaction_date"
                           class="form-control <?= (!empty($transaction_date_err)) ? 'is-invalid' : ''; ?>"
                           value="<?= htmlspecialchars($transaction_date); ?>">
                    <?php if (!empty($transaction_date_err)): ?>
                        <div class="invalid-feedback"><?= $transaction_date_err ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="category_id" class="form-label">Kategori</label>
                    <select name="category_id" id="category_id"
                            class="form-select <?= (!empty($category_id_err)) ? 'is-invalid' : ''; ?>">
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($categories as $type => $group): ?>
                            <optgroup label="<?= ($type == 'income' ? 'Pemasukan' : 'Pengeluaran'); ?>">
                                <?php foreach ($group as $cat): ?>
                                    <option value="<?= $cat['id']; ?>"
                                        <?= ($cat['id'] == $category_id) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($category_id_err)): ?>
                        <div class="invalid-feedback"><?= $category_id_err ?></div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Perbarui Transaksi</button>
                    <a href="<?= BASE_URL ?>/user/riwayat.php" class="btn btn-secondary">Kembali ke Riwayat Transaksi</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
