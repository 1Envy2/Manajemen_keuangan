<?php
// user/riwayat.php

// Panggil config.php terlebih dahulu, karena di dalamnya ada definisi BASE_URL dan koneksi $conn
require_once '../config.php';
// Panggil auth.php untuk fungsi-fungsi autentikasi dan otorisasi
require_once '../includes/auth.php';

// Cek akses: Pastikan user sudah login dan role-nya adalah 'user'
// Fungsi ini akan mengalihkan (redirect) jika user tidak memenuhi syarat
check_user_access();

// Ambil user_id dan username dari session (disediakan oleh auth.php)
$id = get_user_id();
$username = $_SESSION['username'] ?? 'User';

// Ambil data user lengkap dari database untuk foto profil
$user_data_from_db = [];
$query_user_data = "SELECT photo, email FROM users WHERE id = ?";
if ($stmt_user_data = $conn->prepare($query_user_data)) {
    $stmt_user_data->bind_param("i", $id);
    if ($stmt_user_data->execute()) {
        $result_user_data = $stmt_user_data->get_result();
        $user_data_from_db = $result_user_data->fetch_assoc();
    }
    $stmt_user_data->close();
}

// Tentukan path foto profil
// Perbaikan path: menggunakan __DIR__ untuk path sistem file, dan BASE_URL untuk path web
$photo_db_name = $user_data_from_db['photo'] ?? '';
$photo_url = (!empty($photo_db_name) && file_exists(__DIR__ . '/../uploads/' . $photo_db_name))
             ? BASE_URL . 'uploads/' . $photo_db_name
             : BASE_URL . '/assets/profile.jpeg';


$filter = $_GET['filter'] ?? 'all';
$where_clauses = ["t.user_id = ?"]; // Selalu filter berdasarkan user_id

// Menggunakan prepared statements untuk filter tanggal juga
$param_types = 'i'; // Tipe parameter untuk user_id
$param_values = [$id]; // Nilai parameter untuk user_id

if ($filter === 'harian') {
    $where_clauses[] = "t.transaction_date = CURDATE()";
} elseif ($filter === 'mingguan') {
    $where_clauses[] = "YEARWEEK(t.transaction_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'bulanan') {
    $where_clauses[] = "MONTH(t.transaction_date) = MONTH(CURDATE()) AND YEAR(t.transaction_date) = YEAR(CURDATE())";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

$query_transactions = "
    SELECT t.*, c.name AS category_name
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    $where_sql
    ORDER BY t.transaction_date DESC
";

// PERBAIKAN: Gunakan prepared statement untuk query transaksi
if ($stmt_transactions = $conn->prepare($query_transactions)) {
    // Bind parameter dinamis berdasarkan filter
    // Untuk filter harian/mingguan/bulanan, CURDATE() dsb. tidak perlu dibind,
    // hanya user_id yang perlu.
    $stmt_transactions->bind_param($param_types, ...$param_values);

    if ($stmt_transactions->execute()) {
        $result = $stmt_transactions->get_result();
    } else {
        die("Query Error: " . $stmt_transactions->error);
    }
    $stmt_transactions->close();
} else {
    die("Prepare Statement Error: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finote - Riwayat Transaksi</title>
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
            margin: 0; /* Pastikan tidak ada margin default body */
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

        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .filters a {
            text-decoration: none; /* Penting untuk menghilangkan underline default pada link */
        }

        .filters a button {
            background-color: var(--color-light-blue);
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            color: var(--color-dark-blue);
            font-weight: bold;
            transition: 0.3s ease;
            display: inline-flex; /* Agar tombol tetap sejajar dan bisa di-flex */
            align-items: center;
            justify-content: center;
            white-space: nowrap; /* Mencegah teks tombol pecah baris */
        }

        .filters a button:hover,
        .filters a.active button {
            background-color: var(--color-medium-blue);
            color: var(--color-off-white);
            transform: scale(1.02);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--color-off-white);
            border-radius: 12px;
            overflow: hidden; /* Penting untuk border-radius pada tabel */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        thead {
            background-color: var(--color-medium-blue);
            color: var(--color-off-white);
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--color-baby-blue);
        }
        th:first-child, td:first-child {
            padding-left: 25px;
        }
        th:last-child, td:last-child {
            padding-right: 25px;
        }

        tbody tr {
            transition: background-color 0.3s;
        }

        tbody tr:hover {
            background-color: var(--color-baby-blue);
        }
        /* Border kiri untuk income/expense row */
        tbody tr td:first-child {
            border-left: 5px solid;
        }
        tbody tr td.income-border {
            border-left-color: #22c55e; /* Green */
        }
        tbody tr td.expense-border {
            border-left-color: #dc3545; /* Red */
        }

        .amount-income {
            color: #28a745; /* Bootstrap success green */
            font-weight: bold;
        }
        .amount-expense {
            color: #dc3545; /* Bootstrap danger red */
            font-weight: bold;
        }
        .text-muted {
            color: #6c757d !important;
        }

        /* MEDIA QUERIES untuk responsivitas (opsional, sesuaikan breakpoint dashboard Anda) */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .sidebar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
                margin-top: 20px;
            }
            .sidebar ul li {
                flex: 1 1 auto; /* Memungkinkan item mengisi ruang */
                max-width: 150px; /* Batasi lebar item di mobile */
                text-align: center;
            }
            .sidebar ul li a {
                justify-content: center;
                flex-direction: column;
            }
            .sidebar ul li a .fas {
                margin-right: 0;
                margin-bottom: 5px;
            }
            .profile {
                margin-bottom: 10px;
                padding-bottom: 10px;
            }
            .logout {
                margin-top: 20px;
            }
            .main-content {
                padding: 20px;
            }
            .main-content h1 {
                font-size: 1.8rem;
            }
            .filters {
                justify-content: center;
            }
            table, thead, tbody, th, td, tr {
                display: block; /* Membuat tabel responsif dengan stack */
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            tr { border: 1px solid var(--color-baby-blue); margin-bottom: 15px; border-radius: 8px; overflow: hidden; }
            td {
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            td:before {
                content: attr(data-label);
                position: absolute;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                color: var(--color-dark-blue);
            }
            td:first-child, th:first-child { padding-left: 16px; }
            td:last-child, th:last-child { padding-right: 16px; }
            tbody tr td.income-border, tbody tr td.expense-border {
                border-left: none; /* Hilangkan border kiri pada mobile tabel stack */
            }
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
            <span class="name"><?php echo htmlspecialchars($username); ?></span>
        </div>

        <ul class="menu">
            <li class="menu-item">
                <a href="<?= BASE_URL ?>/user/dashboard.php">
                    <span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item active">
                <a href="<?= BASE_URL ?>/user/riwayat.php">
                    <span class="menu-icon"><i class="fas fa-exchange-alt"></i></span>
                    <span>Riwayat</span>
                </a>
            </li>
             <li class="menu-item">
                <a href="<?= BASE_URL ?>/user/add_transaction.php">
                    <span class="menu-icon"><i class="fas fa-plus-circle"></i></span>
                    <span>Tambah Transaksi</span>
                </a>
            </li>
            <li class="menu-item">
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
                <h1>Riwayat Transaksi Anda</h1>
                <div class="subtitle">Pengguna: <?= htmlspecialchars($username) ?></div>
            </div>
        </div>

        <div class="content">
            <?php
            // Menampilkan pesan sukses/error dari sesi
            if (isset($_SESSION['success_message'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                unset($_SESSION['success_message']);
            }
            if (isset($_SESSION['error_message'])) {
                echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']);
            }
            ?>

            <div class="filters">
                <a href="<?= BASE_URL ?>/user/riwayat.php" class="<?php echo ($filter === 'all' ? 'active' : ''); ?>"><button>Semua</button></a>
                <a href="<?= BASE_URL ?>/user/riwayat.php?filter=harian" class="<?php echo ($filter === 'harian' ? 'active' : ''); ?>"><button>Harian</button></a>
                <a href="<?= BASE_URL ?>/user/riwayat.php?filter=mingguan" class="<?php echo ($filter === 'mingguan' ? 'active' : ''); ?>"><button>Mingguan</button></a>
                <a href="<?= BASE_URL ?>/user/riwayat.php?filter=bulanan" class="<?php echo ($filter === 'bulanan' ? 'active' : ''); ?>"><button>Bulanan</button></a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Tipe</th>
                        <th>Jumlah</th>
                        <th>Kategori</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Aksi</th> </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="<?= $row['type'] === 'income' ? 'income-border' : 'expense-border' ?>" data-label="Tipe"><?= ucfirst($row['type']) ?></td>
                                <td class="<?= $row['type'] === 'income' ? 'amount-income' : 'amount-expense' ?>" data-label="Jumlah">
                                    Rp <?= number_format($row['amount'], 0, ',', '.') ?>
                                </td>
                                <td data-label="Kategori"><?= htmlspecialchars($row['category_name']) ?></td>
                                <td class="text-muted" data-label="Tanggal"><?= date('d M Y', strtotime($row['transaction_date'])) ?></td>
                                <td data-label="Keterangan"><?= htmlspecialchars($row['description']) ?></td>
                                <td data-label="Aksi"> <a href="<?= BASE_URL ?>/user/edit_transaction.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-info me-2">Edit</a>
                                    <a href="<?= BASE_URL ?>/user/hapus_transaction.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Anda yakin ingin menghapus transaksi ini?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 20px;">Tidak ada transaksi untuk filter ini.</td> </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>