<?php
require_once '../config.php';
require_once '../includes/auth.php';

check_admin_access();

$id = get_user_id();
$username = '';
$email = '';
$photo_db = '';
$phone = '';
$created_at = '';

$query = "SELECT username, email, photo, phone, created_at FROM users WHERE id = ? AND role = 'admin'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $data = $result->fetch_assoc();
    $username = $data['username'];
    $email = $data['email'];
    $photo_db = $data['photo'] ?? '';
    $phone = $data['phone'] ?? '';
    $created_at = $data['created_at'];
}
$stmt->close();
$conn->close();

$photo_url = (!empty($photo_db) && file_exists('../uploads/' . $photo_db))
    ? BASE_URL . '/uploads/' . $photo_db
    : BASE_URL . '/assets/admin_profile.jpeg';

$member_since = !empty($created_at) ? date('F Y', strtotime($created_at)) : '';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Admin - Finote</title>
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
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--color-baby-blue);
            display: flex;
            min-height: 100vh;
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
        }

        .profile {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding-bottom: 20px;
        }

        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 10px;
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
        }

        .menu-item {
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .menu-item:hover,
        .menu-item.active {
            background-color: var(--color-medium-blue);
        }

        .menu-item a {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
        }

        .menu-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            padding: 40px;
            background-color: var(--color-baby-blue);
        }

        .section-card {
            background: var(--color-off-white);
            max-width: 800px;
            margin: 0 auto 30px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            position: relative;
            text-align: center;
        }

        .section-card h2 {
            font-weight: bold;
            color: var(--color-dark-blue);
            margin-bottom: 20px;
        }

        .edit-link {
            position: absolute;
            top: 30px;
            right: 30px;
            font-weight: bold;
            font-size: 0.95rem;
            color: var(--color-medium-blue);
            text-decoration: none;
        }

        .edit-link:hover {
            text-decoration: underline;
        }

        .profile-img {
            margin-bottom: 15px;
        }

        .profile-img img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 5px solid var(--color-light-blue);
            object-fit: cover;
            background: white;
        }

        .profile-name {
            font-size: 24px;
            font-weight: bold;
            color: var(--color-dark-blue);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 40px;
            margin-top: 30px;
            text-align: left;
        }

        .info-label {
            font-weight: bold;
            color: var(--color-medium-blue);
        }

        .info-value {
            color: var(--color-dark-blue);
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

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <div class="logo-icon">F</div> Finote Admin
    </div>
    <div class="profile">
        <div class="avatar">
            <img src="<?= $photo_url ?>?v=<?= time() ?>" alt="Foto Admin">
        </div>
        <div class="welcome">Welcome Admin</div>
        <div class="name"><?= htmlspecialchars($username) ?></div>
    </div>
    <ul class="menu">
        <li class="menu-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <a href="dashboard.php"><span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard</a>
        </li>
        <li class="menu-item <?= $current_page == 'profile_admin.php' ? 'active' : '' ?>">
            <a href="profile_admin.php"><span class="menu-icon"><i class="fas fa-user"></i></span> Profile</a>
        </li>
        <li class="menu-item <?= $current_page == 'tambah_kategori.php' ? 'active' : '' ?>">
            <a href="tambah_kategori.php"><span class="menu-icon"><i class="fas fa-tags"></i></span>Manajemen Kategori</a>
        </li>
        <li class="menu-item">
            <a href="../logout.php"><span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span> Logout</a>
        </li>
    </ul>
</div>

<!-- Main Profile Content -->
<div class="main-content">

    <!-- Profile Section -->
    <div class="section-card">
        <h2>My Profile</h2>
        <a href="edit_admin.php" class="edit-link">Edit</a>
        <div class="profile-img">
            <img src="<?= $photo_url ?>?v=<?= time() ?>" alt="Foto Profil">
        </div>
        <div class="profile-name"><?= htmlspecialchars($username) ?></div>
    </div>

    <!-- Info Section -->
    <div class="section-card">
        <h2>Personal Information</h2>
        <a href="edit_admin.php" class="edit-link">Edit</a>
        <div class="info-grid">
            <div>
                <div class="info-label">Username</div>
                <div class="info-value"><?= htmlspecialchars($username) ?></div>
            </div>
            <div>
                <div class="info-label">Email Address</div>
                <div class="info-value"><?= htmlspecialchars($email) ?></div>
            </div>
            <div>
                <div class="info-label">Phone Number</div>
                <div class="info-value"><?= htmlspecialchars($phone) ?></div>
            </div>
            <div>
                <div class="info-label">Member Since</div>
                <div class="info-value"><?= $member_since ?></div>
            </div>
        </div>
    </div>
</div>

</body>
</html>