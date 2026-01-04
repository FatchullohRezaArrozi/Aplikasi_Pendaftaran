<?php
// ===========================================
// File: add_registration.php
// Deskripsi: Menangani pendaftaran kelas khusus
// ===========================================

session_start();
require_once __DIR__ . '/db.php';

// Fungsi helper untuk mencegah XSS
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Anda harus login terlebih dahulu.</div>';
    exit;
}

// Hanya izinkan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Ambil data dari form
$user_id = (int)$_SESSION['user_id'];
$nim = trim((string)($_POST['nim'] ?? ''));
$nama_mk = trim((string)($_POST['nama_mk'] ?? ''));

// Validasi input
$errors = [];
if ($nim === '') {
    $errors[] = 'NIM wajib diisi.';
}
if ($nama_mk === '') {
    $errors[] = 'Nama Mata Kuliah wajib diisi.';
}

// Jika ada error, tampilkan pesan
if (!empty($errors)) {
    echo '<div class="alert alert-danger">' . e(implode('<br>', $errors)) . '</div>';
    exit;
}

// Simpan ke database
$stmt = $pdo->prepare('
    INSERT INTO registrations (user_id, nim, nama_mk, registered_at)
    VALUES (?, ?, ?, NOW())
');
$stmt->execute([$user_id, $nim, $nama_mk]);

// Jika sukses, tampilkan form baru + pesan sukses
?>

<div class="card-body" id="registration-form">
    <div class="alert alert-success mb-3">
        Pendaftaran berhasil disimpan!
    </div>

    <h5 class="mb-3">Form Pendaftaran</h5>

    <form hx-post="add_registration.php" hx-target="#registration-form" hx-swap="outerHTML">
        <div class="mb-3">
            <label class="form-label">NIM</label>
            <input name="nim" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Nama Mata Kuliah</label>
            <input name="nama_mk" class="form-control" required>
        </div>

        <button class="btn btn-primary w-100">Daftar Lagi</button>
    </form>
</div>
