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
// Asumsi 'uploads/' ada di root proyek, dan 'assets/profile.jpeg' ada di public/assets/
$photo_db_name = $user_data_from_db['photo'] ?? '';
$photo_url = (!empty($photo_db_name) && file_exists('uploads/' . $photo_db_name))
             ? BASE_URL . '/uploads/' . $photo_db_name
             : BASE_URL . '/assets/profile.jpeg'; // Gunakan foto default lokal

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
            background-color: var(--color-baby-blue); /* Menggunakan warna dari palet */
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px; /* Konsisten dengan dashboard */
            background-color: var(--color-dark-blue);
            color: var(--color-off-white);
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px;
            box-sizing: border-box;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }

        .logo {
            font-size: 1.5rem; /* Konsisten dengan dashboard */
            font-weight: bold;
            margin-bottom: 30px; /* Konsisten dengan dashboard */
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo-icon { /* Tambahan untuk logo ikon */
            width: 40px; height: 40px; background-color: var(--color-off-white);
            color: var(--color-dark-blue); display: flex; align-items: center;
            justify-content: center; border-radius: 5px; font-weight: bold;
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

        .profile img {
            width: 80px; /* Konsisten dengan dashboard */
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 3px solid var(--color-off-white);
        }

        .profile p {
            margin: 0;
            font-size: 1.1rem; /* Konsisten dengan dashboard */
            font-weight: 600;
            color: var(--color-off-white);
        }
        .profile .welcome-text { /* Mengubah nama kelas agar konsisten */
            font-size: 0.85rem; color: var(--color-baby-blue); margin-bottom: 5px; /* Konsisten */
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            padding: 12px 15px; /* Konsisten dengan dashboard */
            border-radius: 5px; /* Konsisten */
            margin-bottom: 8px; /* Konsisten */
            transition: 0.3s;
            cursor: pointer;
        }

        .sidebar ul li a {
            display: block;
            padding: 0; /* Padding sudah di li */
            color: inherit; /* Warna dari parent li */
            text-decoration: none;
            display: flex; /* Untuk ikon */
            align-items: center;
        }

        .sidebar ul li a:hover,
        .sidebar ul li.active a {
            background-color: var(--color-medium-blue);
        }
        .sidebar ul li a .fas { /* Style untuk ikon */
            margin-right: 15px;
            width: 20px;
            text-align: center;
            font-size: 1.1em;
        }


        .logout {
            text-align: center;
            padding: 12px 15px; /* Konsisten dengan menu item */
            background-color: var(--color-medium-blue);
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: auto; /* Push ke bawah */
        }

        .logout:hover {
            background-color: var(--color-light-blue);
        }
        .logout a { /* Overide untuk link logout */
            color: inherit !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            font-weight: bold;
        }
        .logout a .fas {
            margin-right: 15px;
        }


        .main-content {
            flex: 1;
            padding: 30px;
            background-color: var(--color-off-white); /* Menggunakan warna dari palet */
            box-sizing: border-box;
        }

        .main-content h1 {
            color: var(--color-dark-blue);
            margin-bottom: 10px;
            font-size: 2.2rem;
        }

        .main-content .subtitle { /* Tambahan subtitle */
            margin: 0 0 20px;
            color: #777;
            font-weight: normal;
            font-size: 1rem;
        }

        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 25px; /* Tambah jarak */
        }

        .filters a button {
            background-color: var(--color-light-blue);
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            color: var(--color-dark-blue); /* Warna teks konsisten */
            font-weight: bold;
            transition: 0.3s ease;
            text-decoration: none; /* Hilangkan underline dari a */
        }

        .filters a button:hover,
        .filters a.active button { /* Style untuk filter aktif */
            background-color: var(--color-medium-blue);
            color: var(--color-off-white);
            transform: scale(1.02); /* Sedikit zoom */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--color-off-white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        thead {
            background-color: var(--color-medium-blue);
            color: var(--color-off-white);
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--color-baby-blue); /* Border konsisten */
        }
        th:first-child, td:first-child {
            padding-left: 25px; /* Padding kiri lebih */
        }
        th:last-child, td:last-child {
            padding-right: 25px; /* Padding kanan lebih */
        }

        tbody tr {
            transition: background-color 0.3s;
        }

        tbody tr:hover {
            background-color: var(--color-baby-blue); /* Warna hover konsisten */
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
    </style>
</head>
<body>
    <div class="sidebar">
        <div>
            <div class="logo">
                <div class="logo-icon">F</div> Finote
            </div>
            <div class="profile">
                <img src="<?= htmlspecialchars($photo_url) ?>" alt="Profile">
                <div class="welcome-text">Welcome back</div>
                <p><?= htmlspecialchars($username) ?></p>
            </div>
            <ul>
                <li><a href="<?= BASE_URL ?>/user/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="active"><a href="<?= BASE_URL ?>/user/riwayat.php"><i class="fas fa-exchange-alt"></i> Riwayat</a></li>
                <li><a href="<?= BASE_URL ?>/user/categories.php"><i class="fas fa-tags"></i> Kategori</a></li>
                <li><a href="<?= BASE_URL ?>/user/profile.php"><i class="fas fa-cog"></i> Profile</a></li>
            </ul>
        </div>
        <div class="logout">
            <a href="<?= BASE_URL ?>/logout.php">
                <i class="fas fa-sign-out-alt"></i> Log Out
            </a>
        </div>
    </div>

    <div class="main-content">
        <h1>Riwayat Transaksi Anda</h1>
        <p class="subtitle">Pengguna: <?= htmlspecialchars($username) ?></p>

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
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="<?= $row['type'] === 'income' ? 'income-border' : 'expense-border' ?>"><?= ucfirst($row['type']) ?></td>
                            <td class="<?= $row['type'] === 'income' ? 'amount-income' : 'amount-expense' ?>">
                                Rp <?= number_format($row['amount'], 0, ',', '.') ?>
                            </td>
                            <td><?= htmlspecialchars($row['category_name']) ?></td>
                            <td class="text-muted"><?= date('d M Y', strtotime($row['transaction_date'])) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">Tidak ada transaksi untuk filter ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>