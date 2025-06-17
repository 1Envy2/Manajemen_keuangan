<?php
// user/profile.php

// Panggil config.php terlebih dahulu, karena di dalamnya ada definisi BASE_URL dan koneksi $conn
require_once '../config.php';
// Panggil auth.php untuk fungsi-fungsi autentikasi dan otorisasi
require_once '../includes/auth.php';

// Cek akses: Pastikan user sudah login dan role-nya adalah 'user'
// Fungsi ini akan mengalihkan (redirect) jika user tidak memenuhi syarat
check_user_access();

// Ambil user_id dari session (disediakan oleh auth.php)
$id = get_user_id();

$username = '';
$email = '';
$photo_db = ''; // Nama file foto dari database
$phone = '';
$created_at = ''; // Untuk 'Member Since'

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
            $created_at = $data['created_at'] ?? ''; // Ambil tanggal dibuat

            // Tentukan path foto profil
            // Asumsi 'uploads/' ada di root proyek, dan 'assets/profile.jpeg' ada di public/assets/
            $photo_url = (!empty($photo_db) && file_exists('uploads/' . $photo_db))
                         ? BASE_URL . '/uploads/' . $photo_db
                         : BASE_URL . '/assets/profile.jpeg'; // Gunakan foto default lokal
            
            // Format tanggal bergabung
            $member_since = '';
            if (!empty($created_at)) {
                $member_since = date('F Y', strtotime($created_at)); // Contoh: "January 2024"
            }

            // Untuk tampilan nama depan/belakang, jika username hanya satu kata, last name kosong
            $name_parts = explode(' ', $username, 2);
            $firstName = $name_parts[0];
            $lastName = $name_parts[1] ?? '';

        } else {
            // User tidak ditemukan (seharusnya tidak terjadi setelah check_user_access)
            die("User tidak ditemukan.");
        }
    } else {
        die("Error mengambil data user: " . $conn->error);
    }
    $stmt->close();
} else {
    die("Error menyiapkan query: " . $conn->error);
}

$conn->close(); // Tutup koneksi setelah semua query selesai
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finote - Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        /* Menggunakan variabel warna dari file lain untuk konsistensi */
        :root {
            --color-dark-blue: #254E7A;
            --color-medium-blue: #5584B0;
            --color-light-blue: #82C2E6;
            --color-baby-blue: #CBE3EF;
            --color-off-white: #F7F3EA;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            background-color: var(--color-baby-blue); /* Latar belakang dari palet */
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
        }

        .sidebar .top {
            display: flex;
            flex-direction: column;
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

        .profile-sidebar {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .profile-sidebar img {
            width: 80px; /* Konsisten dengan dashboard */
            height: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
            object-fit: cover;
            border: 3px solid var(--color-off-white);
        }

        .profile-sidebar p {
            margin: 0;
            font-size: 1.1rem; /* Konsisten dengan dashboard */
            font-weight: 600;
            color: var(--color-off-white);
        }
        .profile-sidebar span { /* Tambahan untuk welcome text */
            font-size: 0.85rem; color: var(--color-baby-blue); margin-top: 5px;
        }


        .sidebar ul {
            list-style: none;
            padding: 0;
            width: 100%;
        }

        .sidebar ul li {
            padding: 12px 15px; /* Konsisten dengan dashboard */
            border-radius: 5px; /* Konsisten dengan dashboard */
            margin-bottom: 8px; /* Konsisten dengan dashboard */
            transition: 0.3s;
            cursor: pointer;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: inherit; /* Warisi warna dari parent */
            display: flex; /* Agar ikon dan teks sejajar */
            align-items: center;
            width: 100%;
            font-size: 1em;
        }
        .sidebar ul li a .fas { /* Style untuk ikon */
            margin-right: 15px;
            width: 20px;
            text-align: center;
            font-size: 1.1em;
        }

        .sidebar ul li:hover {
            background-color: var(--color-medium-blue);
        }

        .sidebar ul li.active {
            background-color: var(--color-medium-blue);
            font-weight: 600;
        }

        .logout {
            text-align: center;
            padding: 12px 15px; /* Konsisten dengan menu item */
            background-color: var(--color-medium-blue); /* Warna konsisten */
            border-radius: 5px; /* Konsisten */
            cursor: pointer;
            transition: 0.3s;
            margin-top: auto; /* Push ke bawah */
        }

        .logout:hover {
            background-color: var(--color-light-blue);
            color: var(--color-dark-blue); /* Ubah warna teks saat hover */
        }
        .logout a { /* Overide untuk link logout */
            color: inherit !important; /* Pastikan warna teks berubah saat hover parent */
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
            background-color: var(--color-baby-blue);
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            color: var(--color-dark-blue); /* Dari palet */
            margin-bottom: 20px;
        }

        .section-card {
            background: var(--color-off-white); /* Warna kartu dari palet */
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            width: 100%;
            max-width: 700px;
            position: relative;
            text-align: center;
            border: 1px solid var(--color-baby-blue); /* Border konsisten */
        }

        .section-card h2 {
            margin: 0 0 20px 0;
            font-size: 24px;
            color: var(--color-dark-blue);
        }

        .edit-link {
            position: absolute;
            top: 30px;
            right: 30px;
            font-size: 14px;
            color: var(--color-medium-blue); /* Warna dari palet */
            text-decoration: none;
            font-weight: bold;
        }

        .edit-link:hover {
            text-decoration: underline;
            color: var(--color-dark-blue);
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
            border: 5px solid var(--color-light-blue); /* Border dari palet */
            box-shadow: 0 0 0 5px rgba(0,0,0,0.05); /* Sedikit shadow */
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
            justify-content: center;
            text-align: left;
            margin-top: 20px;
        }

        .info-grid p {
            margin: 5px 0;
            font-size: 18px;
            color: var(--color-dark-blue);
        }

        .info-label {
            font-weight: bold;
            margin-bottom: 2px;
            display: block;
            color: var(--color-medium-blue);
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
            <div class="logo">
                <div class="logo-icon">F</div> Finote
            </div>
            <div class="profile-sidebar">
                <img src="<?= htmlspecialchars($photo_url) ?>" alt="Profile" />
                <span class="welcome">Welcome back</span>
                <p><?= htmlspecialchars($username) ?></p>
            </div>
            <ul>
                <li><a href="<?= BASE_URL ?>/user/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/user/riwayat.php"><i class="fas fa-exchange-alt"></i> Riwayat</a></li>
                <li><a href="<?= BASE_URL ?>/user/categories.php"><i class="fas fa-tags"></i> Kategori</a></li>
                <li class="active"><a href="<?= BASE_URL ?>/user/profile.php"><i class="fas fa-cog"></i> Profile</a></li>
            </ul>
        </div>
        <div class="logout">
            <a href="<?= BASE_URL ?>/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

<div class="main-content">
    <div class="section-card">
        <h2>My Profile</h2>
        <a href="<?= BASE_URL ?>/user/edit_profile.php" class="edit-link">Edit</a>
        <div class="profile-top">
            <img src="<?= htmlspecialchars($photo_url) ?>" alt="Profile" />
            <div class="name"><?= htmlspecialchars($username) ?></div>
        </div>
    </div>

    <div class="section-card">
        <h2>Personal Information</h2>
        <a href="<?= BASE_URL ?>/user/edit_profile.php" class="edit-link">Edit</a>
        <div class="info-grid">
            <div>
                <span class="info-label">Username</span> <p><?= htmlspecialchars($username) ?></p>
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