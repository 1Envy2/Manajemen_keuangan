<?php
session_start();
$host = "localhost";
$user_db = "root";
$pass = "";
$db = "finote";

$conn = new mysqli($host, $user_db, $pass, $db);
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}

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
  $email = $data['email'];
  $photo = $data['photo'] ?? '';
  $phone = $data['phone'] ?? '';

  $user = [
    'name' => $name,
    'photo' => !empty($photo) ? 'uploads/' . $photo : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . '?d=identicon&s=80',
    'email' => $email,
    'phone' => $phone
  ];

  $firstName = explode(' ', $name)[0];
  $lastName = explode(' ', $name)[1] ?? '';
} else {
  die("User tidak ditemukan.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Profile</title>
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

    .section-card {
      background: #cbe3ef;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      margin-bottom: 30px;
      width: 100%;
      max-width: 700px;
      position: relative;
      text-align: left;
    }

    .section-card h2 {
      margin: 0 0 20px 0;
      font-size: 24px;
      color: #254e7a;
      text-align: center;
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
      color: #5584b0;
      font-size: 16px;
    }

    input[type="text"],
    input[type="email"],
    input[type="tel"] {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 16px;
      box-sizing: border-box;
    }

    .btn-submit {
      margin-top: 25px;
      background-color: #254e7a;
      color: white;
      padding: 12px 25px;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      display: block;
      width: 100%;
      max-width: 200px;
      margin-left: auto;
      margin-right: auto;
      transition: background-color 0.3s;
    }

    .btn-submit:hover {
      background-color: #5584b0;
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
      <h2>Edit Profile</h2>
      <form action="update_profile.php" method="POST" enctype="multipart/form-data">
        <label for="first_name">First Name</label>
        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($firstName) ?>" required />

        <label for="last_name">Last Name</label>
        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($lastName) ?>" />

        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required />

        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required />

        <label for="photo">Profile Photo (optional)</label>
        <input type="file" id="photo" name="photo" accept="image/*" />

        <button type="submit" class="btn-submit">Save Changes</button>
      </form>
    </div>
  </div>

</body>
</html>