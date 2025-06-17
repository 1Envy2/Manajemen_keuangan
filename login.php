<?php
// login.php
// Mulai sesi
session_start();

// Panggil config.php terlebih dahulu untuk BASE_URL dan koneksi $conn
require_once 'config.php';
// Panggil auth.php untuk fungsi-fungsi autentikasi dan otorisasi
require_once 'includes/auth.php'; // Pastikan path ini benar (dari root ke includes/auth.php)

// Redirect jika user sudah login (menggunakan fungsi dari auth.php)
redirect_if_logged_in();

$username = $password = '';
$username_err = $password_err = '';

// Proses form jika data disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi username
    if (empty(trim($_POST['username']))) {
        $username_err = 'Masukkan username.';
    } else {
        $username = trim($_POST['username']);
    }

    // Validasi password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Masukkan password.';
    } else {
        $password = trim($_POST['password']);
    }

    // Cek kredensial
    if (empty($username_err) && empty($password_err)) {
        // PERBAIKAN: Tambahkan `role` di SELECT statement
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('s', $param_username);
            $param_username = $username;

            if ($stmt->execute()) {
                $stmt->store_result();

                // Cek jika username ada, lalu verifikasi password
                if ($stmt->num_rows == 1) {
                    // PERBAIKAN: Tambahkan `$role` di bind_result
                    $stmt->bind_result($id, $username, $hashed_password, $role);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password benar, simpan data di session variables
                            $_SESSION['loggedin'] = true;
                            $_SESSION['id'] = $id;
                            $_SESSION['username'] = $username;
                            $_SESSION['role'] = $role; // <<< Simpan role ke session

                            // Redirect ke dashboard yang sesuai berdasarkan role
                            if ($role === 'admin') {
                                header('location: ' . BASE_URL . '/admin/dashboard.php');
                            } else {
                                header('location: ' . BASE_URL . '/user/dashboard.php');
                            }
                            exit; // Penting: Hentikan eksekusi script setelah redirect
                        } else {
                            // Password salah
                            $password_err = 'Password yang Anda masukkan salah.';
                        }
                    }
                } else {
                    // Username tidak ditemukan
                    $username_err = 'Tidak ada akun dengan username tersebut.';
                }
            } else {
                echo 'Oops! Terjadi kesalahan. Silakan coba lagi nanti.';
            }
            $stmt->close();
        }
    }

    // Koneksi $conn akan ditutup secara otomatis di akhir script
    // atau Anda bisa tambahkan $conn->close(); di sini jika tidak ada logika lain
    // yang membutuhkannya setelah proses login.
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finote - Login</title>
    <style>
        /* CSS tetap sama */
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
            margin-bottom: 18px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 7px;
            color: var(--color-dark-blue); /* Biru tua gelap untuk label */
            font-weight: bold;
        }
        input[type="text"], input[type="password"] {
            width: calc(100% - 22px);
            padding: 12px;
            border: 1px solid var(--color-medium-blue); /* Border biru sedang */
            border-radius: 5px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: var(--color-dark-blue); /* Border biru tua saat fokus */
            outline: none;
        }
        .help-block {
            color: #dc3545; /* Merah untuk error */
            font-size: 0.85em;
            margin-top: 5px;
            display: block;
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
        <h2>Login ke Finote</h2>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Login">
            </div>
            <p>Belum punya akun? <a href="register.php">Daftar sekarang</a>.</p>
        </form>
    </div>
</body>
</html>