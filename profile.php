<?php
session_start();

// Koneksi database
$host = "localhost";
$user_db = "root";
$pass = "";
$db = "finote";

$conn = new mysqli($host, $user_db, $pass, $db);
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}

// Cek session login
if (!isset($_SESSION['id'])) {
  header("Location: login.php");
  exit;
}

$id = $_SESSION['id'];
$query = "SELECT * FROM users WHERE id = $id";
$result = $conn->query($query);

if ($result->num_rows === 1) {
  $data = $result->fetch_assoc();
  $name = $data['username'];
  $photo = $data['photo'] ?? '';
  $email = $data['email'];
  $phone = $data['phone'] ?? '081234567890';

  $photo_url = !empty($photo) ? 'uploads/' . $photo : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . '?d=identicon&s=120';

  $user = [
    'name' => $name,
    'photo' => $photo_url,
    'email' => $email,
    'phone' => $phone
  ];
} else {
  die("User tidak ditemukan.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Profile</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      display: flex;
      background-color: #f7f3ea;
    }

    .sidebar {
      width: 250px;
      background-color: #254e7a;
      color: #fff;
      height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 20px;
      box-sizing: border-box;
    }

    .sidebar .top {
      display: flex;
      flex-direction: column;
    }

    .logo {
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 20px;
    }

    .profile-sidebar {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 30px;
    }

    .profile-sidebar img {
      width: 70px;
      border-radius: 50%;
      margin-bottom: 10px;
    }

    .profile-sidebar p {
      margin: 0;
      font-size: 18px;
      font-weight: bold;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
      width: 100%;
    }

    .sidebar ul li {
      padding: 10px;
      border-radius: 8px;
      transition: 0.3s;
    }

    .sidebar ul li a {
      text-decoration: none;
      color: white;
      display: block;
    }

    .sidebar ul li:hover,
    .sidebar ul li.active {
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
      background-color: #f7f3ea;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    h1 {
      color: #5584b0;
      margin-bottom: 20px;
    }

    .section-card {
      background: #cbe3ef;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      margin-bottom: 30px;
      width: 100%;
      max-width: 700px;
      position: relative;
      text-align: center;
    }

    .section-card h2 {
      margin: 0 0 20px 0;
      font-size: 24px;
      color: #254e7a;
    }

    .edit-link {
      position: absolute;
      top: 30px;
      right: 30px;
      font-size: 14px;
      color: #82c2e6;
      text-decoration: none;
    }

    .edit-link:hover {
      text-decoration: underline;
    }

    .profile-top {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 15px;
    }

    .profile-top img {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
    }

    .profile-top .name {
      font-size: 24px;
      font-weight: bold;
      color: #254e7a;
    }

    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px 40px;
      justify-content: center;
      text-align: left;
      margin-top: 20px;
    }

    .info-grid p {
      margin: 5px 0;
      font-size: 18px;
      color: #254e7a;
    }

    .info-label {
      font-weight: bold;
      margin-bottom: 2px;
      display: block;
      color: #5584b0;
    }

    @media (max-width: 600px) {
      .info-grid {
        grid-template-columns: 1fr;
        text-align: center;
      }
    }
  </style>
</head>
<body>

  <div class="sidebar">
    <div class="top">
      <div class="logo">Finote</div>
      <div class="profile-sidebar">
        <img src="<?= $user['photo'] ?>" alt="Profile" />
        <p><?= $user['name'] ?></p>
      </div>
      <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="riwayat.php">Riwayat</a></li>
        <li class="active"><a href="profile.php">Profile</a></li>
      </ul>
    </div>
    <div class="logout"><a href="logout.php" style="color:white; text-decoration:none;">Logout</a></div>
  </div>

<div class="main-content">
  <div class="section-card">
    <h2>My Profile</h2>
    <a href="edit_profile.php" class="edit-link">Edit</a>
    <div class="profile-top">
      <img src="<?= $user['photo'] ?>" alt="Profile" />
      <div class="name"><?= $user['name'] ?></div>
    </div>
  </div>

  <div class="section-card">
    <h2>Personal Information</h2>
    <a href="edit_profile.php" class="edit-link">Edit</a>
    <div class="info-grid">
      <div>
        <span class="info-label">First Name</span>
        <p><?= explode(' ', $user['name'])[0] ?></p>
      </div>
      <div>
        <span class="info-label">Last Name</span>
        <p><?= explode(' ', $user['name'])[1] ?? '-' ?></p>
      </div>
      <div>
        <span class="info-label">Email Address</span>
        <p><?= $user['email'] ?></p>
      </div>
      <div>
        <span class="info-label">Phone Number</span>
        <p><?= $user['phone'] ?></p>
      </div>
      <div>
        <span class="info-label">Username</span>
        <p><?= strtolower(str_replace(' ', '.', $user['name'])) ?></p>
      </div>
      <div>
        <span class="info-label">Member Since</span>
        <p>January 2024</p>
      </div>
    </div>
  </div>
</div>

</body>
</html>