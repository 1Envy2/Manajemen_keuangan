<?php
require_once '../config.php';
require_once '../includes/auth.php';

session_start();
check_admin_access();

$id = get_user_id();
$username = $_POST['username'] ?? '';
$email    = $_POST['email'] ?? '';
$phone    = $_POST['phone'] ?? '';
$photo_name = null;
$update_photo = false;

// Validasi sederhana
if (empty($username) || empty($email) || empty($phone)) {
    $_SESSION['error_message_profile'] = "Username, email, dan no. telepon wajib diisi.";
    header("Location: edit_admin.php");
    exit;
}

// Ambil nama foto lama
$old_photo = '';
$stmt = $conn->prepare("SELECT photo FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($old_photo);
$stmt->fetch();
$stmt->close();

// Cek apakah ada file foto baru diunggah
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $tmp  = $_FILES['photo']['tmp_name'];
    $size = $_FILES['photo']['size'];
    $ext  = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $mime = mime_content_type($tmp);

    $allowed = ['image/jpeg', 'image/png'];
    if (!in_array($mime, $allowed)) {
        $_SESSION['error_message_profile'] = "Hanya file JPG dan PNG yang diperbolehkan.";
        header("Location: edit_admin.php");
        exit;
    }

    if ($size > 2 * 1024 * 1024) {
        $_SESSION['error_message_profile'] = "Ukuran maksimal file adalah 2MB.";
        header("Location: edit_admin.php");
        exit;
    }

    $photo_name = 'admin_' . time() . '.' . $ext;
    $target = '../uploads/' . $photo_name;

    if (move_uploaded_file($tmp, $target)) {
        if (!empty($old_photo) && file_exists('../uploads/' . $old_photo)) {
            unlink('../uploads/' . $old_photo);
        }
        $update_photo = true;
    } else {
        $_SESSION['error_message_profile'] = "Gagal mengunggah foto.";
        header("Location: edit_admin.php");
        exit;
    }
}

// Proses update database
if ($update_photo) {
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, photo = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $username, $email, $phone, $photo_name, $id);
} else {
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $email, $phone, $id);
}

if ($stmt->execute()) {
    $_SESSION['success_message_profile'] = "Profil berhasil diperbarui.";
} else {
    $_SESSION['error_message_profile'] = "Gagal memperbarui profil: " . $conn->error;
}
$stmt->close();

header("Location: profile_admin.php");
exit;
?>