<?php
require_once '../config.php';
require_once '../includes/auth.php';

check_user_access();

$user_id = get_user_id();

// Tentukan tipe transaksi dari parameter GET, default ke 'income'
$type = isset($_GET['type']) ? $_GET['type'] : 'income';
if (!in_array($type, ['income', 'expense'])) {
    $type = 'income'; // Fallback jika type tidak valid di URL
}

$amount = $description = $transaction_date = $category_id = '';
$amount_err = $description_err = $transaction_date_err = $category_err = '';

// Untuk menampilkan pesan sukses/error yang diset dari redirect sebelumnya
$success_message = '';
$error_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

$categories_from_db = [];
// PERBAIKAN QUERY: Hapus user_id dari WHERE karena kategori global, tambahkan type
$sql_categories = "SELECT id, name, type FROM categories ORDER BY type ASC, name ASC";
if ($stmt_cat = $conn->prepare($sql_categories)) {
    // Tidak perlu bind_param untuk user_id di sini karena kategori global
    if ($stmt_cat->execute()) {
        $result_cat = $stmt_cat->get_result();
        while ($row_cat = $result_cat->fetch_assoc()) {
            $categories_from_db[] = $row_cat;
        }
    } else {
        $error_message = "Gagal memuat kategori: " . $stmt_cat->error;
    }
    $stmt_cat->close();
}


// Proses form jika data disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil tipe transaksi dari hidden input
    $post_type = trim($_POST['transaction_type'] ?? '');
    if (!in_array($post_type, ['income', 'expense'])) {
        $type = 'income'; // Fallback
        $error_message = 'Tipe transaksi tidak valid saat submit.';
    } else {
        $type = $post_type; // Update tipe berdasarkan submit form
    }

    // Validasi Jumlah
    if (empty(trim($_POST['amount']))) {
        $amount_err = 'Jumlah tidak boleh kosong.';
    } else {
        $clean_amount_input = trim($_POST['amount']);
        $clean_amount_input = str_replace(',', '.', $clean_amount_input);
        $clean_amount_input = preg_replace("/[^0-9.]/", "", $clean_amount_input);
        $parsed_amount = filter_var($clean_amount_input, FILTER_VALIDATE_FLOAT);

        if ($parsed_amount === false || $parsed_amount <= 0) {
            $amount_err = 'Jumlah harus angka positif yang valid.';
        } else {
            $amount = number_format($parsed_amount, 2, '.', ''); 
        }
    }

    // Validasi Kategori
    if (empty(trim($_POST['category_id']))) {
        $category_err = 'Pilih kategori.';
    } else {
        $category_id = trim($_POST['category_id']);
        // PERBAIKAN VALIDASI KATEGORI: Cek id dan type kategori dari tabel global
        $sql_check_cat = "SELECT id FROM categories WHERE id = ? AND type = ?"; 
        if ($stmt_check = $conn->prepare($sql_check_cat)) {
            $stmt_check->bind_param('is', $category_id, $type);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows == 0) {
                $category_err = 'Kategori tidak valid atau tidak cocok dengan tipe transaksi ini.';
                $category_id = ''; // Reset invalid category
            }
            $stmt_check->close();
        } else {
            $error_message = "Gagal menyiapkan query kategori validasi: " . $conn->error;
        }
    }

    // Validasi Tanggal
    if (empty(trim($_POST['transaction_date']))) {
        $transaction_date_err = 'Tanggal tidak boleh kosong.';
    } else {
        $transaction_date = trim($_POST['transaction_date']);
        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $transaction_date)) {
             $transaction_date_err = 'Format tanggal tidak valid (YYYY-MM-DD).';
        }
    }

    // Deskripsi (opsional)
    $description = trim($_POST['description']);


    // Jika tidak ada error validasi, masukkan transaksi ke database
    if (empty($amount_err) && empty($category_err) && empty($transaction_date_err) && empty($error_message)) {
        $sql_insert = "INSERT INTO transactions (user_id, category_id, type, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";

        if ($stmt_insert = $conn->prepare($sql_insert)) {
            $stmt_insert->bind_param('iisdss', $user_id, $category_id, $type, $amount, $description, $transaction_date);

            if ($stmt_insert->execute()) {
                $_SESSION['success_message'] = 'Transaksi berhasil ditambahkan!'; // Set pesan sukses di session
                // Kosongkan form setelah sukses atau redirect
                $amount = $description = '';
                $category_id = ''; // Reset kategori yang dipilih
                // Opsional: Redirect ke dashboard setelah sukses
                header('location: ' . BASE_URL . '/user/dashboard.php'); // Redirect ke dashboard user
                exit;
            } else {
                $error_message = 'Terjadi kesalahan saat menyimpan transaksi: ' . $stmt_insert->error;
            }
            $stmt_insert->close();
        } else {
            $error_message = "Gagal menyiapkan query insert transaksi: " . $conn->error;
        }
    } else {
        
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finote - Tambah Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
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
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background-color: var(--color-off-white);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            max-width: 500px;
            width: 100%;
        }
        h2 {
            color: var(--color-dark-blue);
            text-align: center;
            margin-bottom: 30px;
        }
        .form-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }
        .form-button {
            flex: 1;
            padding: 12px;
            border: 1px solid var(--color-medium-blue);
            border-radius: 8px;
            background-color: var(--color-off-white);
            color: var(--color-medium-blue);
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .form-button.active {
            background-color: var(--color-medium-blue);
            color: var(--color-off-white);
            border-color: var(--color-medium-blue);
        }
        .form-button:hover:not(.active) {
            background-color: var(--color-baby-blue);
            color: var(--color-dark-blue);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--color-dark-blue);
            font-size: 1rem;
        }
        .form-control { /* Menggunakan kelas Bootstrap untuk input */
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--color-baby-blue);
            border-radius: 8px;
            font-size: 1rem;
            color: #333;
            background-color: #fcfcfc;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            border-color: var(--color-medium-blue);
            box-shadow: 0 0 0 0.25rem rgba(85, 132, 176, 0.25); /* Warna shadow Bootstrap */
            outline: none;
        }
        .help-block {
            color: #dc3545; /* Merah untuk error */
            font-size: 0.85em;
            margin-top: 5px;
            display: block;
        }
        .success-message {
            color: #28a745; /* Hijau untuk sukses */
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .error-message {
            color: #dc3545; /* Merah untuk error */
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .btn-submit {
            background-color: var(--color-dark-blue);
            color: var(--color-off-white);
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: var(--color-medium-blue);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: var(--color-dark-blue);
            text-decoration: none;
            font-weight: bold;
            font-size: 0.95em;
        }
        .back-link:hover {
            text-decoration: underline;
            color: var(--color-medium-blue);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Tambah Transaksi</h2>
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?type=<?php echo htmlspecialchars($type); ?>" method="post">
            <input type="hidden" name="transaction_type" id="transaction_type_hidden" value="<?php echo htmlspecialchars($type); ?>">

            <div class="form-buttons">
                <button type="button" class="form-button <?php echo ($type == 'income' ? 'active' : ''); ?>" data-type="income">Pemasukan</button>
                <button type="button" class="form-button <?php echo ($type == 'expense' ? 'active' : ''); ?>" data-type="expense">Pengeluaran</button>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="amount">Jumlah (Rp)</label>
                <input type="text" id="amount" name="amount" class="form-control" placeholder="Contoh: 150000.00" value="<?php echo htmlspecialchars($amount); ?>">
                <span class="help-block"><?php echo $amount_err; ?></span>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="category_id">Kategori</label>
                <select id="category_id" name="category_id" class="form-control">
                    <option value="">Pilih kategori</option>
                    <?php foreach ($categories_from_db as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['id']); ?>" 
                                data-type="<?php echo htmlspecialchars($cat['type']); ?>"
                                <?php 
                                // Pilih kategori yang sesuai jika ada error validasi atau setelah reload
                                if ($category_id == $cat['id']) {
                                    echo 'selected';
                                }
                                ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="help-block"><?php echo $category_err; ?></span>
            </div>

            <div class="form-group">
                <label class="form-label" for="transaction_date">Tanggal</label>
                <input type="date" id="transaction_date" name="transaction_date" class="form-control" value="<?php echo (!empty($transaction_date) ? htmlspecialchars($transaction_date) : date('Y-m-d')); ?>">
                <span class="help-block"><?php echo $transaction_date_err; ?></span>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="description">Keterangan (Opsional)</label>
                <input type="text" id="description" name="description" class="form-control" placeholder="Misal: Gaji bulan Juni" value="<?php echo htmlspecialchars($description); ?>">
                <span class="help-block"><?php echo $description_err; ?></span>
            </div>
            
            <button type="submit" class="btn-submit"><i class="fas fa-save me-2"></i> Simpan Transaksi</button>
        </form>
        <a href="<?= BASE_URL ?>/user/dashboard.php" class="back-link"><i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formButtons = document.querySelectorAll('.form-button');
            const hiddenTypeInput = document.getElementById('transaction_type_hidden');
            const categorySelect = document.getElementById('category_id');
            const categoryOptions = categorySelect.querySelectorAll('option'); 

            // Fungsi untuk menampilkan/menyembunyikan kategori berdasarkan tipe transaksi
            function toggleCategoryOptions(selectedType) {
                categoryOptions.forEach(option => {
                    const optionType = option.dataset.type; 

                    // Tampilkan hanya jika tipe opsi sesuai dengan tipe yang dipilih
                    // Atau jika ini adalah opsi "Pilih kategori" (yang tidak punya data-type)
                    if (option.value === "" || optionType === selectedType) {
                        option.style.display = ''; 
                    } else {
                        option.style.display = 'none'; 
                    }
                });
                
                if (categorySelect.selectedOptions.length > 0) {
                    if (categorySelect.selectedOptions[0].style.display === 'none') {
                        categorySelect.value = ""; // Reset jika opsi aktif tersembunyi
                    }
                } else if (categorySelect.value !== "") {
                    
                     let foundSelected = false;
                     categoryOptions.forEach(option => {
                        if (option.value === "<?php echo htmlspecialchars($category_id); ?>" && option.style.display !== 'none') {
                            option.selected = true;
                            foundSelected = true;
                        } else {
                            option.selected = false;
                        }
                     });
                     if (!foundSelected) {
                        categorySelect.value = "";
                     }
                }
                
            }

            toggleCategoryOptions(hiddenTypeInput.value);

            formButtons.forEach(button => {
                button.addEventListener('click', function() {
                    formButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    const selectedType = this.dataset.type;
                    hiddenTypeInput.value = selectedType; // Update hidden input
                    
                    // Update URL agar tipe transaksi tetap ada jika di-refresh
                    const url = new URL(window.location.href);
                    url.searchParams.set('type', selectedType);
                    window.history.pushState({path:url.href},'',url.href);

                    toggleCategoryOptions(selectedType); // Panggil fungsi untuk mengatur tampilan kategori
                });
            });

            // Set default date to today if not set on load or after validation error
            if (!document.getElementById('transaction_date').value) {
                document.getElementById('transaction_date').value = new Date().toISOString().slice(0, 10);
            }
        });
    </script>
</body>
</html>
