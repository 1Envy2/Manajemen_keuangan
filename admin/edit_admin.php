<?php
require_once '../config.php';
require_once '../includes/auth.php';

check_admin_access();
$id = get_user_id();

$username = '';
$email = '';
$phone = '';
$photo_db = '';
$created_at = '';

$query = "SELECT username, email, phone, photo, created_at FROM users WHERE id = ? AND role = 'admin'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $data = $result->fetch_assoc();
    $username = $data['username'];
    $email = $data['email'];
    $phone = $data['phone'] ?? '';
    $photo_db = $data['photo'] ?? '';
    $created_at = $data['created_at'];
}
$stmt->close();
$conn->close();

$photo_url = (!empty($photo_db) && file_exists('../uploads/' . $photo_db))
    ? BASE_URL . '/uploads/' . $photo_db
    : BASE_URL . '/assets/admin_profile.jpeg';

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Profil Admin</title>
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

        .card-edit {
            background: var(--color-off-white);
            max-width: 600px;
            margin: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .card-edit h2 {
            text-align: center;
            color: var(--color-dark-blue);
            margin-bottom: 25px;
        }

        label {
            font-weight: bold;
            color: var(--color-dark-blue);
        }

        .form-control {
            border-radius: 6px;
            border: 1px solid var(--color-medium-blue);
        }

        .form-text {
            font-size: 0.9em;
            color: #777;
        }

        .btn-save {
            background-color: var(--color-dark-blue);
            color: white;
        }

        .btn-save:hover {
            background-color: var(--color-medium-blue);
        }

        .profile-img-preview {
            display: block;
            margin: 0 auto 20px auto;
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--color-light-blue);
        }

        .back {
            text-align: center;
            margin-top: 20px;
        }

        .back a {
            color: var(--color-dark-blue);
            text-decoration: none;
            font-weight: bold;
        }

        .back a:hover {
            text-decoration: underline;
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
            <a href="tambah_kategori.php"><span class="menu-icon"><i class="fas fa-tags"></i></span> Manajemen Kategori</a>
        </li>
        <li class="menu-item">
            <a href="../logout.php"><span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span> Logout</a>
        </li>
    </ul>
</div>

<!-- Konten Edit -->
<div class="main-content">
    <div class="card-edit">
        <h2>Edit Profil Admin</h2>
        <form action="update_admin.php" method="POST" enctype="multipart/form-data">
            <img src="<?= $photo_url ?>?v=<?= time() ?>" class="profile-img-preview" alt="Foto Admin">

            <div class="mb-3">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="email">Alamat Email</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="phone">No. Telepon</label>
                <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($phone) ?>" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="photo">Foto Profil</label>
                <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                <div class="form-text">Kosongkan jika tidak ingin mengganti foto.</div>
            </div>

            <button type="submit" class="btn btn-save w-100">Simpan Perubahan</button>
        </form>

        <div class="back mt-3">
            <a href="profile_admin.php">&larr; Kembali ke Profil</a>
        </div>
    </div>
</div>

</body>
</html>