<?php
// user/edit_profile.php

require_once '../config.php';
require_once '../includes/auth.php';
check_user_access();

$id = get_user_id();
$username = '';
$email = '';
$photo = '';
$phone = '';

$query = "SELECT username, email, photo, phone FROM users WHERE id = ?";
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

            $relative_path = 'uploads/' . $photo_db;
            $absolute_path = __DIR__ . '/' . $relative_path;
            $photo = (!empty($photo_db) && file_exists($absolute_path))
                     ? BASE_URL . 'user/uploads/' . $photo_db
                     : BASE_URL . '/assets/profile.jpeg';
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finote - Edit Profile</title>
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
        .section-card {
            background: var(--color-off-white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            max-width: 700px;
            margin: auto;
        }
        .btn-submit {
            background-color: var(--color-dark-blue);
            color: var(--color-off-white);
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
            <img src="<?= $photo ?>" alt="Profile">
        </div>
        <span class="welcome">Welcome back</span>
        <span class="name"><?= htmlspecialchars($username) ?></span>
    </div>
    <ul class="menu">
        <li class="menu-item"><a href="<?= BASE_URL ?>/user/dashboard.php"><span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span>Dashboard</a></li>
        <li class="menu-item"><a href="<?= BASE_URL ?>/user/riwayat.php"><span class="menu-icon"><i class="fas fa-exchange-alt"></i></span>Riwayat</a></li>
        <li class="menu-item"><a href="<?= BASE_URL ?>/user/categories.php"><span class="menu-icon"><i class="fas fa-tags"></i></span>Kategori</a></li>
        <li class="menu-item active"><a href="<?= BASE_URL ?>/user/profile.php"><span class="menu-icon"><i class="fas fa-cog"></i></span>Profile</a></li>
        <li class="menu-item"><a href="<?= BASE_URL ?>/logout.php"><span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span>Log Out</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="section-card">
        <h2 class="text-center mb-4">Edit Profile</h2>
        <?php if (isset($_SESSION['success_message_profile'])): ?>
            <div class="alert alert-success"> <?= $_SESSION['success_message_profile']; unset($_SESSION['success_message_profile']); ?> </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message_profile'])): ?>
            <div class="alert alert-danger"> <?= $_SESSION['error_message_profile']; unset($_SESSION['error_message_profile']); ?> </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/user/update_profile.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Phone</label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" required>
            </div>
            <div class="mb-3">
                <label for="photo" class="form-label">Profile Photo (optional)</label>
                <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
            </div>
            <button type="submit" class="btn-submit">Save Changes</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
