<?php
require_once '../config.php';
require_once '../includes/auth.php';

check_user_access();

// Ambil user_id dari session (disediakan oleh auth.php)
$id = get_user_id();

// Ambil data dari form (menggunakan name atribut input dari edit_profile.php)
$username_input = $_POST['username'] ?? ''; 
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';

// Validasi sederhana (Anda bisa menambahkan validasi yang lebih kuat)
if (empty($username_input) || empty($email) || empty($phone)) {
    $_SESSION['error_message_profile'] = "Semua field (Username, Email, Phone Number) harus diisi.";
    header("Location: " . BASE_URL . "/user/profile.php"); // Redirect kembali ke profile dengan error
    exit;
}

// Handle foto profil (jika diupload)
$photo_name = null; // Default null jika tidak ada update foto
$update_photo = false;

// Periksa apakah ada file foto yang diupload
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['photo']['tmp_name'];
    $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    // Buat nama file unik untuk menghindari konflik
    $photo_name = uniqid('profile_') . '.' . $file_extension;
    
    // Tentukan direktori upload di root proyek
    $target_dir = 'uploads/';
    $target_path = $target_dir . $photo_name;

    // Pastikan folder uploads ada di root
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true); 
    }

    // Pindahkan file yang diupload
    if (move_uploaded_file($tmp_name, $target_path)) {
        $update_photo = true;

        $sql_get_old_photo = "SELECT photo FROM users WHERE id = ?";
        if ($stmt_old_photo = $conn->prepare($sql_get_old_photo)) {
            $stmt_old_photo->bind_param("i", $id);
            $stmt_old_photo->execute();
            $result_old_photo = $stmt_old_photo->get_result();
            if ($row_old_photo = $result_old_photo->fetch_assoc()) {
                $old_photo_name = $row_old_photo['photo'];
                if (!empty($old_photo_name) && $old_photo_name !== 'profile.jpeg') { 
                    $old_photo_path = $target_dir . $old_photo_name;
                    if (file_exists($old_photo_path)) {
                        unlink($old_photo_path); // Hapus file foto lama
                    }
                }
            }
            $stmt_old_photo->close();
        }
    } else {
        $_SESSION['error_message_profile'] = "Gagal mengunggah foto. Silakan coba lagi.";
        header("Location: " . BASE_URL . "/user/profile.php");
        exit;
    }
}


// Update data ke database (dengan atau tanpa foto)
if ($update_photo) {
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, photo = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $username_input, $email, $phone, $photo_name, $id);
} else {
    // Hanya update kolom yang tidak terkait foto
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username_input, $email, $phone, $id);
}

if ($stmt->execute()) {
    // Perbarui session username jika nama diubah
    $_SESSION['username'] = $username_input;
    
    $_SESSION['success_message_profile'] = "Profil berhasil diperbarui!";
    header("Location: " . BASE_URL . "/user/profile.php"); // Redirect ke halaman profile dengan pesan sukses
    exit;
} else {
    $_SESSION['error_message_profile'] = "Gagal memperbarui profil: " . $conn->error;
    header("Location: " . BASE_URL . "/user/profile.php"); // Redirect ke halaman profile dengan pesan error
    exit;
}

$stmt->close();
$conn->close();
?>
