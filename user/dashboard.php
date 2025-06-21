<?php
require_once '../config.php';
require_once '../includes/auth.php';

check_user_access(); // Menggunakan check_user_access() untuk pengguna

$user_id = get_user_id();
$username = $_SESSION['username'] ?? 'User';

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

// PERBAIKAN PATH FOTO PROFIL
$photo_db_name = $user_data_from_db['photo'] ?? '';
$photo_url = (!empty($photo_db_name) && file_exists(__DIR__ . '/../uploads/' . $photo_db_name))
             ? BASE_URL . 'uploads/' . $photo_db_name
             : BASE_URL . '/assets/profile.jpeg';


$current_month = date('Y-m');
$first_day_of_month = date('Y-m-01');
$last_day_of_month = date('Y-m-t'); 

$total_income_month = 0;
$total_expense_month = 0;
$balance_month = 0;

$sql_income_month = "SELECT SUM(amount) AS total FROM transactions WHERE user_id = ? AND type = 'income' AND transaction_date BETWEEN ? AND ?";
if ($stmt_income_month = $conn->prepare($sql_income_month)) {
    $stmt_income_month->bind_param('iss', $user_id, $first_day_of_month, $last_day_of_month);
    if ($stmt_income_month->execute()) {
        $result_income_month = $stmt_income_month->get_result();
        $row_income_month = $result_income_month->fetch_assoc();
        $total_income_month = $row_income_month['total'] ?? 0;
    }
    $stmt_income_month->close();
}

$sql_expense_month = "SELECT SUM(amount) AS total FROM transactions WHERE user_id = ? AND type = 'expense' AND transaction_date BETWEEN ? AND ?";
if ($stmt_expense_month = $conn->prepare($sql_expense_month)) {
    $stmt_expense_month->bind_param('iss', $user_id, $first_day_of_month, $last_day_of_month);
    if ($stmt_expense_month->execute()) {
        $result_expense_month = $stmt_expense_month->get_result();
        $row_expense_month = $result_expense_month->fetch_assoc();
        $total_expense_month = $row_expense_month['total'] ?? 0;
    }
    $stmt_expense_month->close();
}

$balance_month = $total_income_month - $total_expense_month;

$total_income_overall = 0;
$total_expense_overall = 0;

$sql_income_overall = "SELECT SUM(amount) AS total FROM transactions WHERE user_id = ? AND type = 'income'";
if ($stmt_income_overall = $conn->prepare($sql_income_overall)) {
    $stmt_income_overall->bind_param('i', $user_id);
    if ($stmt_income_overall->execute()) {
        $result_income_overall = $stmt_income_overall->get_result();
        $row_income_overall = $result_income_overall->fetch_assoc();
        $total_income_overall = $row_income_overall['total'] ?? 0;
    }
    $stmt_income_overall->close();
}

$sql_expense_overall = "SELECT SUM(amount) AS total FROM transactions WHERE user_id = ? AND type = 'expense'";
if ($stmt_expense_overall = $conn->prepare($sql_expense_overall)) {
    $stmt_expense_overall->bind_param('i', $user_id);
    if ($stmt_expense_overall->execute()) {
        $result_expense_overall = $stmt_expense_overall->get_result();
        $row_expense_overall = $result_expense_overall->fetch_assoc();
        $total_expense_overall = $row_expense_overall['total'] ?? 0;
    }
    $stmt_expense_overall->close();
}

$balance_overall = $total_income_overall - $total_expense_overall;

$latest_transactions = [];
$sql_latest_transactions = "SELECT t.id, t.amount, t.description, t.transaction_date, t.type, c.name AS category_name
                             FROM transactions t
                             JOIN categories c ON t.category_id = c.id
                             WHERE t.user_id = ?
                             ORDER BY t.created_at DESC LIMIT 5";
if ($stmt_latest = $conn->prepare($sql_latest_transactions)) {
    $stmt_latest->bind_param('i', $user_id);
    if ($stmt_latest->execute()) {
        $result_latest = $stmt_latest->get_result();
        while ($row_latest = $result_latest->fetch_assoc()) {
            $latest_transactions[] = $row_latest;
        }
    }
    $stmt_latest->close();
}

$conn->close(); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finote - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .badge-custom { 
            background-color: var(--color-light-blue); 
            color: var(--color-dark-blue);
            font-size: 0.7em;
            padding: 3px 8px;
            border-radius: 12px;
            margin-left: auto;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .header {
            background-color: var(--color-off-white); /* Krem muda untuk header */
            padding: 20px 30px;
            border-bottom: 1px solid var(--color-baby-blue); /* Border baby blue */
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 5px rgba(0,0,0,0.05);
            flex-shrink: 0; /* Header tidak menyusut */
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

        .header-right {
            display: flex;
            align-items: center;
            gap: 25px; /* Jarak antar elemen di header kanan */
        }

        .search {
            position: relative;
        }

        .search input {
            padding: 10px 15px 10px 40px; /* Padding kiri lebih besar untuk ikon */
            border-radius: 25px; /* Lebih bulat */
            border: 1px solid var(--color-baby-blue);
            width: 280px; /* Lebar search bar */
            font-size: 0.95rem;
            background-color: #fcfcfc;
            transition: border-color 0.3s;
        }
        .search input:focus {
            border-color: var(--color-medium-blue);
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 15px; /* Posisi ikon */
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .user-info {
            text-align: right;
        }

        .user-info p {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--color-dark-blue);
            margin-bottom: 0;
        }

        .user-info span {
            font-size: 0.8em;
            color: #666;
        }

        .header .avatar { /* Override avatar di header */
            width: 40px;
            height: 40px;
            border: none;
        }

        .content {
            padding: 30px; /* Padding lebih besar */
            flex: 1;
            overflow-y: auto; /* Jika konten melebihi tinggi, tambahkan scroll */
        }

        .dashboard-grid { /* Ganti nama kelas dari .dashboard untuk menghindari konflik */
            display: grid;
            grid-template-columns: 3fr 1fr; /* Tetap grid layout */
            gap: 30px; /* Jarak antar kolom lebih besar */
        }

        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr; /* Satu kolom di layar kecil */
            }
            .sidebar {
                width: 250px; /* Sidebar lebih kecil di tablet */
            }
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%; /* Sidebar full width di mobile */
                height: auto;
                position: static;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .main-content {
                width: 100%;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .header-right {
                width: 100%;
                justify-content: space-between;
            }
            .search {
                flex-grow: 1; /* Search bar mengisi ruang */
            }
            .search input {
                width: 100%;
            }
        }


        .left-content {
            display: flex;
            flex-direction: column;
            gap: 25px; /* Jarak antar card lebih besar */
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); /* Responsif */
            gap: 20px;
        }

        .stat-card {
            padding: 20px;
            border-radius: 10px; /* Radius lebih besar */
            color: var(--color-off-white);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .stat-card.blue {
            background-color: var(--color-medium-blue); /* Biru sedang */
        }

        .stat-card.orange {
            background-color: #f97316; /* Warna yang tetap kontras untuk pengeluaran */
        }

        .stat-card.green {
            background-color: #22c55e; /* Warna yang tetap kontras untuk laba bersih */
        }

        .stat-card.white {
            background-color: var(--color-off-white);
            color: var(--color-dark-blue);
            border: 1px solid var(--color-baby-blue);
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }

        .stat-header {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .stat-title {
            margin-right: auto;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .stat-badge {
            background-color: rgba(255, 255, 255, 0.25);
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .stat-card.white .stat-badge { /* Badge untuk kartu putih */
            background-color: var(--color-light-blue);
            color: var(--color-dark-blue);
        }

        .stat-value {
            font-size: 1.6em;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .stat-chart {
            height: 40px; /* Tinggi chart placeholder */
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }
        .stat-card.white .stat-chart {
            background-color: var(--color-baby-blue);
        }

        .donut-chart {
            width: 100px; /* Ukuran chart lebih besar */
            height: 100px;
            margin: 0 auto 15px;
            position: relative;
            border-radius: 50%;
            /* Menggunakan warna palet untuk conic-gradient, ini placeholder untuk chart nantinya */
            background: conic-gradient(var(--color-dark-blue) 0% 60%, var(--color-baby-blue) 60% 100%);
        }

        .donut-hole {
            position: absolute;
            width: 65px; /* Ukuran hole */
            height: 65px;
            background-color: var(--color-off-white);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1em;
            color: var(--color-dark-blue);
        }

        .chart-label {
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--color-dark-blue);
        }

        .chart-sublabel {
            text-align: center;
            font-size: 0.9rem;
            color: #888;
        }

        .card {
            background-color: var(--color-off-white);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden; /* Penting untuk border-radius */
        }

        .card-header {
            padding: 18px 25px;
            border-bottom: 1px solid var(--color-baby-blue);
            background-color: #fcfcfc;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--color-dark-blue);
        }

        .card-content {
            padding: 25px;
        }

        .transaction-list-compact .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed var(--color-baby-blue); /* Garis putus-putus */
            font-size: 0.95em;
        }

        .transaction-list-compact .transaction-item:last-child {
            border-bottom: none;
        }
        .transaction-list-compact .description {
            font-weight: 500;
            color: var(--color-dark-blue);
        }
        .transaction-list-compact .amount {
            font-weight: bold;
        }
        .transaction-list-compact .date {
            font-size: 0.8em;
            color: #888;
        }
        .transaction-list-compact .income {
            color: #28a745;
        }
        .transaction-list-compact .expense {
            color: #dc3545;
        }

        /* Override Bootstrap list-group */
        .list-group-item {
            background-color: transparent !important;
            border-color: var(--color-baby-blue) !important;
            padding-left: 0;
            padding-right: 0;
        }
        .list-group-item:last-child {
            border-bottom-width: 0 !important;
        }

    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon">F</div> <span>Finote</span>
        </div>
        
        <div class="profile">
            <div class="avatar">
                <img src="<?= $photo_url ?>" alt="Profile">
            </div>
            <span class="welcome">Welcome back</span>
            <span class="name"><?php echo htmlspecialchars($username); ?></span>
        </div>
        
        <ul class="menu">
            <li class="menu-item active">
                <a href="<?= BASE_URL ?>/user/dashboard.php">
                    <span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?= BASE_URL ?>/user/riwayat.php"> <span class="menu-icon"><i class="fas fa-exchange-alt"></i></span>
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
                <a href="<?= BASE_URL ?>/user/categories.php"> <span class="menu-icon"><i class="fas fa-tags"></i></span>
                    <span>Kategori</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?= BASE_URL ?>/user/profile.php"> <span class="menu-icon"><i class="fas fa-cog"></i></span>
                    <span>Profile</span>
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
                <h1>Dashboard</h1>
                <div class="subtitle">Financial Note</div>
            </div>
        </div>
        
        <div class="content">
            <div class="dashboard-grid">
                <div class="left-content">
                    <div class="stats">
                        <div class="stat-card blue">
                            <div class="stat-header">
                                <span class="stat-title">Pemasukan Bulan Ini</span>
                                <span class="stat-badge">
                                    <?php
                                    // Anda mungkin ingin membandingkan dengan bulan sebelumnya untuk persentase yang bermakna
                                    // Untuk demo ini, kita akan asumsikan pertumbuhan jika ada pemasukan
                                    echo number_format($total_income_month > 0 ? 100 : 0, 0) . '%';
                                    ?>
                                </span> 
                            </div>
                            <div class="stat-value">Rp <?php echo number_format($total_income_month, 2, ',', '.'); ?></div>
                            <div class="stat-chart"></div> 
                        </div>
                        
                        <div class="stat-card orange">
                            <div class="stat-header">
                                <span class="stat-title">Pengeluaran Bulan Ini</span>
                                <span class="stat-badge">
                                    <?php
                                    // Anda mungkin ingin membandingkan dengan bulan sebelumnya untuk persentase yang bermakna
                                    // Untuk demo ini, kita akan asumsikan 0% jika tidak ada pengeluaran
                                    echo number_format($total_expense_month > 0 ? 100 : 0, 0) . '%';
                                    ?>
                                </span> 
                            </div>
                            <div class="stat-value">Rp <?php echo number_format($total_expense_month, 2, ',', '.'); ?></div>
                            <div class="stat-chart"></div>
                        </div>
                        
                        <div class="stat-card green">
                            <div class="stat-header">
                                <span class="stat-title">Saldo</span>
                                <span class="stat-badge">
                                    <?php
                                    // Persentase saldo bisa lebih kompleks, ini hanya contoh dasar
                                    echo $balance_month >= 0 ? '+' : '';
                                    echo number_format($balance_month != 0 ? ($balance_month / ($total_income_month + $total_expense_month > 0 ? $total_income_month + $total_expense_month : 1)) * 100 : 0, 0) . '%';
                                    ?>
                                </span> 
                            </div>
                            <div class="stat-value">Rp <?php echo number_format($balance_month, 2, ',', '.'); ?></div>
                            <div class="stat-chart"></div>
                        </div>

                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Pengeluaran Bulanan</div> </div>
                        <div class="card-content" style="display: flex; align-items: center; justify-content: space-around; padding: 20px;">
                            <div style="position: relative; width: 180px; height: 180px; margin-right: 20px;">
                                <canvas id="monthlyBalanceChart"></canvas>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                                    <div style="font-size: 0.9rem; color: #888;">Total</div>
                                    <div style="font-weight: bold; font-size: 1.2rem; color: var(--color-dark-blue);">
                                        Rp <?php echo number_format($balance_month, 0, ',', '.'); ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="mb-3">
                                    <div style="font-size: 0.9rem; color: #555;">Pemasukan</div>
                                    <div style="font-weight: bold; font-size: 1.1rem; color: var(--color-dark-blue);">
                                        <?php
                                            $total_all_transactions_for_chart = $total_income_month + $total_expense_month; // Total keseluruhan untuk persentase
                                            $income_percentage = ($total_all_transactions_for_chart > 0) ? ($total_income_month / $total_all_transactions_for_chart) * 100 : 0;
                                            echo number_format($income_percentage, 0) . '%';
                                        ?>
                                    </div>
                                    <div class="progress" style="height: 5px; background-color: var(--color-baby-blue);">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo number_format($income_percentage, 0); ?>%; background-color: var(--color-medium-blue);" aria-valuenow="<?php echo number_format($income_percentage, 0); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size: 0.9rem; color: #555;">Pengeluaran</div>
                                    <div style="font-weight: bold; font-size: 1.1rem; color: var(--color-dark-blue);">
                                        <?php
                                            $expense_percentage = ($total_all_transactions_for_chart > 0) ? ($total_expense_month / $total_all_transactions_for_chart) * 100 : 0;
                                            echo number_format($expense_percentage, 0) . '%';
                                        ?>
                                    </div>
                                    <div class="progress" style="height: 5px; background-color: var(--color-baby-blue);">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo number_format($expense_percentage, 0); ?>%; background-color: var(--color-dark-blue);" aria-valuenow="<?php echo number_format($expense_percentage, 0); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Transaksi Terakhir</div>
                        </div>
                        <div class="card-content">
                            <?php if (empty($latest_transactions)): ?>
                                <div class="alert alert-info" role="alert">
                                    Belum ada transaksi terbaru.
                                </div>
                            <?php else: ?>
                                <div class="transaction-list-compact">
                                    <?php foreach ($latest_transactions as $t): ?>
                                        <div class="transaction-item">
                                            <div>
                                                <div class="description"><?php echo htmlspecialchars($t['description']); ?></div>
                                                <div class="date"><?php echo date('d M Y', strtotime($t['transaction_date'])); ?> - <?php echo htmlspecialchars($t['category_name']); ?></div>
                                            </div>
                                            <div class="amount <?php echo ($t['type'] == 'income' ? 'income' : 'expense'); ?>">
                                                Rp <?php echo number_format($t['amount'], 2, ',', '.'); ?>
                                            </div>
                                            <div class="ms-auto d-flex align-items-center">
                                                <a href="<?= BASE_URL ?>/user/edit_transaction.php?id=<?= $t['id']; ?>" class="btn btn-sm btn-info me-2"><i class="fas fa-edit"></i></a>
                                                <a href="<?= BASE_URL ?>/user/hapus_transaction.php?id=<?= $t['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Anda yakin ingin menghapus transaksi ini?');"><i class="fas fa-trash-alt"></i></a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/user/riwayat.php" class="btn btn-outline-primary btn-sm mt-3 d-block"><i class="fas fa-history me-2"></i> Lihat Semua Transaksi</a>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Aksi Cepat</div>
                    </div>
                    <div class="card-content text-center">
                        <a href="<?= BASE_URL ?>/user/add_transaction.php?type=income" class="btn btn-success d-block mb-3 p-3 fs-5" style="background-color: #28a745; border-color: #28a745;"><i class="fas fa-plus-circle me-2"></i> Tambah Pemasukan</a>
                        <a href="<?= BASE_URL ?>/user/add_transaction.php?type=expense" class="btn btn-danger d-block p-3 fs-5" style="background-color: #dc3545; border-color: #dc3545;"><i class="fas fa-minus-circle me-2"></i> Tambah Pengeluaran</a>
                        
                        <hr class="my-4">

                        <h5 class="mb-3 text-start text-dark">Ringkasan Bulan Ini (<?php echo date('M Y'); ?>)</h5>
                        <ul class="list-group list-group-flush text-start">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Pemasukan: <span class="badge bg-success rounded-pill">Rp <?php echo number_format($total_income_month, 2, ',', '.'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Pengeluaran: <span class="badge bg-danger rounded-pill">Rp <?php echo number_format($total_expense_month, 2, ',', '.'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Saldo: <span class="badge bg-primary rounded-pill" style="background-color: var(--color-medium-blue) !important;">Rp <?php echo number_format($balance_month, 2, ',', '.'); ?></span>
                            </li>
                        </ul>
                        <a href="<?= BASE_URL ?>/user/riwayat.php" class="btn btn-outline-primary btn-sm mt-3 d-block" style="border-color: var(--color-medium-blue); color: var(--color-medium-blue);"><i class="fas fa-history me-2"></i> Lihat Semua Riwayat</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctxBalance = document.getElementById('monthlyBalanceChart');

        const totalIncomeMonth = <?php echo json_encode($total_income_month); ?>;
        const totalExpenseMonth = <?php echo json_encode($total_expense_month); ?>;
        const balanceMonth = <?php echo json_encode($balance_month); ?>;

        const balanceChartData = {
            labels: ['Pemasukan', 'Pengeluaran'],
            datasets: [{
                label: 'Ringkasan Bulan Ini',
                data: [totalIncomeMonth, totalExpenseMonth], 
                backgroundColor: [
                    '#5584B0',
                    '#254E7A'  
                ],
                borderColor: '#F7F3EA',
                borderWidth: 8,
                hoverOffset: 0 
            }]
        };

        if (totalIncomeMonth === 0 && totalExpenseMonth === 0) {
            const context = ctxBalance.getContext('2d');
            context.font = '16px Arial';
            context.textAlign = 'center';
            context.fillText('Belum ada data saldo bulan ini.', ctxBalance.width / 2, ctxBalance.height / 2);
        } else {
            new Chart(ctxBalance, {
                type: 'doughnut',
                data: balanceChartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%', 
                    plugins: {
                        legend: {
                            display: false 
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed !== null) {
                                        label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    events: ['mousemove', 'mouseout', 'click', 'touchstart', 'touchmove', 'touchend'],
                }
            });
        }
    });
    </script>
</body>
</html>