<?php
require_once '../config.php';
require_once '../includes/auth.php';

check_user_access();
$id = get_user_id();

$username = '';
$email = '';
$photo_db = '';
$phone = '';
$created_at = '';

$query = "SELECT username, email, photo, phone, created_at FROM users WHERE id = ?";
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $data = $result->fetch_assoc();
            $username = $data['username'];
            $email = $data['email'];
            $photo_db = $data['photo'] ?? '';
            $phone = $data['phone'] ?? '';
            $created_at = $data['created_at'] ?? '';

            $relative_path = '/user/uploads/' . $photo_db;
            $absolute_path = $_SERVER['DOCUMENT_ROOT'] . $relative_path;
            $photo_path = (!empty($photo_db) && file_exists($absolute_path))
                          ? BASE_URL . 'user/uploads/' . $photo_db
                          : BASE_URL . '/assets/profile.jpeg';

            $member_since = !empty($created_at) ? date('F Y', strtotime($created_at)) : '';
        } else {
            die("User tidak ditemukan.");
        }
    } else {
        die("Error mengambil data user: " . $conn->error);
    }
    $stmt->close();
} else {
    die("Error menyiapkan query: " . $conn->error);
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Finote - Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            overflow: hidden;
            border: 3px solid var(--color-off-white);
            margin-bottom: 10px;
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
        }
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
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h2 {
            color: var(--color-dark-blue);
            margin-bottom: 20px;
        }
        .section-card {
            background: var(--color-off-white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            width: 100%;
            max-width: 700px;
            position: relative;
            text-align: center;
            border: 1px solid var(--color-baby-blue);
        }
        .edit-link {
            position: absolute;
            top: 30px;
            right: 30px;
            font-size: 14px;
            color: var(--color-medium-blue);
            text-decoration: none;
            font-weight: bold;
        }
        .edit-link:hover {
            color: var(--color-dark-blue);
            text-decoration: underline;
        }
        .profile-top img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--color-light-blue);
        }
        .profile-top .name {
            font-size: 24px;
            font-weight: bold;
            color: var(--color-dark-blue);
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 40px;
            text-align: left;
            margin-top: 20px;
        }
        .info-label {
            font-weight: bold;
            color: var(--color-medium-blue);
            display: block;
            margin-bottom: 2px;
        }
        .info-grid p {
            font-size: 18px;
            color: var(--color-dark-blue);
            margin: 5px 0;
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
    <div class="logo">
        <div class="logo-icon">F</div>
        <span>Finote</span>
    </div>
    <div class="profile">
        <div class="avatar">
            <img src="<?= htmlspecialchars($photo_path) ?>" alt="Profile">
        </div>
        <span class="welcome">Welcome back</span>
        <span class="name"><?= htmlspecialchars($username) ?></span>
    </div>
    <ul class="menu">
        <li class="menu-item">
            <a href="<?= BASE_URL ?>/user/dashboard.php">
                <span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="<?= BASE_URL ?>/user/riwayat.php">
                <span class="menu-icon"><i class="fas fa-exchange-alt"></i></span>
                <span>Riwayat</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="<?= BASE_URL ?>/user/categories.php">
                <span class="menu-icon"><i class="fas fa-tags"></i></span>
                <span>Kategori</span>
            </a>
        </li>
        <li class="menu-item active">
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
    <div class="section-card">
        <h2>My Profile</h2>
        <a href="<?= BASE_URL ?>/user/edit_profile.php" class="edit-link">Edit</a>
        <div class="profile-top">
            <img src="<?= htmlspecialchars($photo_path) ?>" alt="Profile">
            <div class="name"><?= htmlspecialchars($username) ?></div>
        </div>
    </div>

    <div class="section-card">
        <h2>Personal Information</h2>
        <a href="<?= BASE_URL ?>/user/edit_profile.php" class="edit-link">Edit</a>
        <div class="info-grid">
            <div>
                <span class="info-label">Username</span>
                <p><?= htmlspecialchars($username) ?></p>
            </div>
            <div>
                <span class="info-label">Email Address</span>
                <p><?= htmlspecialchars($email) ?></p>
            </div>
            <div>
                <span class="info-label">Phone Number</span>
                <p><?= htmlspecialchars($phone) ?></p>
            </div>
            <div>
                <span class="info-label">Member Since</span>
                <p><?= htmlspecialchars($member_since) ?></p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
