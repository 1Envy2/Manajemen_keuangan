<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "finote";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$id = $_SESSION['id'];

// Ambil data user dari database
$getUser = $conn->query("SELECT username, photo FROM users WHERE id = $id");
if ($getUser && $getUser->num_rows === 1) {
    $userRow = $getUser->fetch_assoc();
    $username = $userRow['username'];
    $photo = $userRow['photo'];
} else {
    $username = 'User';
    $photo = '';
}

$photo_url = !empty($photo) ? 'uploads/' . $photo : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($username))) . '?d=identicon&s=80';

$filter = $_GET['filter'] ?? 'all';
$where = "WHERE t.user_id = $id"; 

if ($filter === 'harian') {
    $where .= " AND t.transaction_date = CURDATE()";
} elseif ($filter === 'mingguan') {
    $where .= " AND YEARWEEK(t.transaction_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'bulanan') {
    $where .= " AND MONTH(t.transaction_date) = MONTH(CURDATE()) AND YEAR(t.transaction_date) = YEAR(CURDATE())";
}

$query = "
    SELECT t.*, c.name AS category_name 
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    $where 
    ORDER BY t.transaction_date DESC
";

$result = $conn->query($query);
if (!$result) {
    die("Query Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Riwayat Transaksi</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      display: flex;
      background-color: #eaeaea;
    }

    .sidebar {
      width: 260px;
      background-color: #1b3553;
      color: #fff;
      height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 20px;
      box-sizing: border-box;
    }

    .logo {
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 20px;
    }

    .profile {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 30px;
    }

    .profile img {
      width: 75px;
      height: 75px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 10px;
      border: 3px solid #cbe3ef;
    }

    .profile p {
      margin: 0;
      font-size: 16px;
      font-weight: 600;
    }

    .welcome-text {
      font-size: 14px;
      margin-bottom: 5px;
      color: #cbe3ef;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
    }

    .sidebar ul li {
      margin-bottom: 10px;
    }

    .sidebar ul li a {
      display: block;
      padding: 12px 18px;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      transition: 0.3s;
    }

    .sidebar ul li a:hover,
    .sidebar ul li.active a {
      background-color: #5584b0;
    }

    .logout {
      text-align: center;
      padding: 10px;
      background-color: #5584b0;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
    }

    .logout:hover {
      background-color: #82c2e6;
    }

    .main-content {
      flex: 1;
      padding: 30px;
      background-color: #f4f6f8;
      box-sizing: border-box;
    }

    .main-content h1 {
      color: #254e7a;
      margin-bottom: 10px;
    }

    .main-content p {
      margin: 0 0 20px;
      color: #254e7a;
      font-weight: bold;
    }

    .filters {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }

    .filters a button {
      background-color: #82c2e6;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      color: white;
      font-weight: bold;
      transition: 0.3s ease;
    }

    .filters a button:hover {
      background-color: #5584b0;
      transform: scale(1.05);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    thead {
      background-color: #5584b0;
      color: white;
    }

    th, td {
      padding: 16px;
      text-align: left;
    }

    tbody tr {
      border-bottom: 1px solid #f1f1f1;
      transition: background-color 0.3s;
    }

    tbody tr:hover {
      background-color: #f9f9f9;
    }

    tbody tr td:first-child {
      border-left: 5px solid #82c2e6;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <div>
      <div class="logo">Finote</div>
      <div class="profile">
        <img src="<?= $photo_url ?>" alt="Profile">
        <div class="welcome-text">Welcome back</div>
        <p><?= htmlspecialchars($username) ?></p>
      </div>
      <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li class="active"><a href="riwayat.php">Riwayat</a></li>
        <li><a href="profile.php">Profile</a></li>
      </ul>
    </div>
    <div class="logout">
      <a href="logout.php" style="color:white; text-decoration:none;">Log Out</a>
    </div>
  </div>

  <div class="main-content">
    <h1>Riwayat Transaksi Anda</h1>
    <p>Pengguna: <?= htmlspecialchars($username) ?></p>

    <div class="filters">
      <a href="riwayat.php"><button>All</button></a>
      <a href="riwayat.php?filter=harian"><button>Harian</button></a>
      <a href="riwayat.php?filter=mingguan"><button>Mingguan</button></a>
      <a href="riwayat.php?filter=bulanan"><button>Bulanan</button></a>
    </div>

    <table>
      <thead>
        <tr>
          <th>Type</th>
          <th>Amount</th>
          <th>Category</th>
          <th>Date</th>
          <th>Description</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= ucfirst($row['type']) ?></td>
              <td style="color: <?= $row['type'] === 'income' ? 'green' : 'red' ?>;">
                Rp <?= number_format($row['amount'], 0, ',', '.') ?>
              </td>
              <td><?= htmlspecialchars($row['category_name']) ?></td>
              <td><?= date('d M Y', strtotime($row['transaction_date'])) ?></td>
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