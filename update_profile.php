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

// Ambil data dari form
$firstName = $_POST['first_name'] ?? '';
$lastName = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';

// Gabungkan nama depan dan belakang jadi username
$username = trim($firstName . ' ' . $lastName);

// Handle foto profil (jika diupload)
$photo_name = '';
$update_photo = false;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
  $tmp_name = $_FILES['photo']['tmp_name'];
  $photo_name = basename($_FILES['photo']['name']);
  $target_path = 'uploads/' . $photo_name;

  // Pastikan folder uploads ada
  if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
  }

  move_uploaded_file($tmp_name, $target_path);
  $update_photo = true;
}

// Update data ke database (dengan atau tanpa foto)
if ($update_photo) {
  $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, photo = ? WHERE id = ?");
  $stmt->bind_param("ssssi", $username, $email, $phone, $photo_name, $id);
} else {
  $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ? WHERE id = ?");
  $stmt->bind_param("sssi", $username, $email, $phone, $id);
}

if ($stmt->execute()) {
  header("Location: profile.php?success=1");
  exit;
} else {
  echo "Gagal memperbarui profil: " . $conn->error;
}

$stmt->close();
$conn->close();
?>