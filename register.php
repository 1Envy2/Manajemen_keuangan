<?php
// register.php
require_once 'config.php'; // Pastikan config.php sudah di-update dengan DB_NAME 'finote_db'

$username = $email = $password = $confirm_password = '';
$username_err = $email_err = $password_err = $confirm_password_err = '';
$registration_success = '';

// Proses form jika data disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi username
    if (empty(trim($_POST['username']))) {
        $username_err = 'Username tidak boleh kosong.';
    } else {
        // Cek apakah username sudah ada
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('s', $param_username);
            $param_username = trim($_POST['username']);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $username_err = 'Username ini sudah terdaftar.';
                } else {
                    $username = trim($_POST['username']);
                }
            } else {
                echo 'Oops! Terjadi kesalahan. Silakan coba lagi nanti.';
            }
            $stmt->close();
        }
    }

    // Validasi email (opsional, bisa dihilangkan jika tidak perlu)
    if (empty(trim($_POST['email']))) {
        $email_err = 'Email tidak boleh kosong.';
    } else if (!filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
        $email_err = 'Format email tidak valid.';
    } else {
        // Cek apakah email sudah ada (jika Anda ingin email unik)
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('s', $param_email);
            $param_email = trim($_POST['email']);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $email_err = 'Email ini sudah terdaftar.';
                } else {
                    $email = trim($_POST['email']);
                }
            } else {
                echo 'Oops! Terjadi kesalahan. Silakan coba lagi nanti.';
            }
            $stmt->close();
        }
    }

    // Validasi password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Password tidak boleh kosong.';
    } elseif (strlen(trim($_POST['password'])) < 6) {
        $password_err = 'Password minimal harus 6 karakter.';
    } else {
        $password = trim($_POST['password']);
    }

    // Validasi konfirmasi password
    if (empty(trim($_POST['confirm_password']))) {
        $confirm_password_err = 'Konfirmasi password tidak boleh kosong.';
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = 'Password tidak cocok.';
        }
    }

    // Jika tidak ada error, masukkan user ke database
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('sss', $param_username, $param_email, $param_password);

            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Hash password!

            if ($stmt->execute()) {
                $registration_success = 'Registrasi berhasil! Silakan <a href="login.php">login</a>.';
                // Kosongkan field form setelah sukses
                $username = $email = $password = $confirm_password = '';
            } else {
                echo 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
            }
            $stmt->close();
        }
    }

    $conn->close(); // Tutup koneksi setelah selesai
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finote - Register</title>
    <style>
        :root {
            --color-dark-blue: #254E7A;
            --color-medium-blue: #5584B0;
            --color-light-blue: #82C2E6;
            --color-baby-blue: #CBE3EF;
            --color-off-white: #F7F3EA;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--color-light-blue); /* Menggunakan biru muda sebagai latar belakang */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
        }
        .wrapper {
            background-color: var(--color-off-white); /* Krem muda untuk kotak form */
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2); /* Sedikit lebih gelap agar menonjol */
            width: 350px;
            text-align: center;
        }
        h2 {
            margin-bottom: 25px;
            color: var(--color-dark-blue); /* Biru tua gelap untuk judul */
        }
        .form-group {
            margin-bottom: 18px; /* Sedikit lebih banyak ruang */
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 7px;
            color: var(--color-dark-blue); /* Biru tua gelap untuk label */
            font-weight: bold;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: calc(100% - 22px);
            padding: 12px; /* Padding lebih besar */
            border: 1px solid var(--color-medium-blue); /* Border biru sedang */
            border-radius: 5px; /* Radius sedikit lebih besar */
            box-sizing: border-box;
            transition: border-color 0.3s ease; /* Transisi saat hover/focus */
        }
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus {
            border-color: var(--color-dark-blue); /* Border biru tua saat fokus */
            outline: none;
        }
        .help-block {
            color: #dc3545; /* Merah untuk error */
            font-size: 0.85em;
            margin-top: 5px;
            display: block; /* Pastikan di baris baru */
        }
        .success-msg {
            color: #28a745; /* Hijau untuk sukses */
            margin-bottom: 15px;
            font-weight: bold;
        }
        .btn {
            background-color: var(--color-dark-blue); /* Biru tua gelap untuk tombol */
            color: var(--color-off-white); /* Tulisan krem muda */
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.05em;
            width: 100%;
            box-sizing: border-box;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: var(--color-medium-blue); /* Biru sedang saat hover */
        }
        p {
            margin-top: 25px;
            font-size: 0.9em;
            color: #555;
        }
        a {
            color: var(--color-dark-blue); /* Biru tua gelap untuk link */
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
            color: var(--color-medium-blue); /* Biru sedang saat hover */
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Daftar Akun Finote</h2>
        <?php if (!empty($registration_success)): ?>
            <p class="success-msg"><?php echo $registration_success; ?></p>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <span class="help-block"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" value="<?php echo htmlspecialchars($password); ?>">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="confirm_password" value="<?php echo htmlspecialchars($confirm_password); ?>">
                <span class="help-block"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Daftar">
            </div>
            <p>Sudah punya akun? <a href="login.php">Login di sini</a>.</p>
        </form>
    </div>
</body>
</html>