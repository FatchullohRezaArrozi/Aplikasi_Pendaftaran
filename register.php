<?php
// register.php
session_start();
require_once __DIR__ . '/db.php';


function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}


$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$namalengkap = trim((string) ($_POST['namalengkap'] ?? ''));
$admin_key = (string) ($_POST['admin_key'] ?? '');

// Data Khusus Mahasiswa
$nim = trim((string) ($_POST['nim'] ?? ''));
$jurusan = trim((string) ($_POST['jurusan'] ?? ''));
$angkatan = trim((string) ($_POST['angkatan'] ?? ''));

// Logic Role
$role = 'mahasiswa'; // Default
if ($admin_key === 'admin123') {
    $role = 'admin';
}


$errors = [];
if ($username === '')
    $errors[] = 'Username wajib diisi.';
if ($password === '')
    $errors[] = 'Password wajib diisi.';
if ($namalengkap === '')
    $errors[] = 'Nama lengkap wajib diisi.';

if ($role === 'mahasiswa') {
    if ($nim === '')
        $errors[] = 'NIM wajib diisi untuk mahasiswa.';
    if ($jurusan === '')
        $errors[] = 'Jurusan wajib dipilih untuk mahasiswa.';
    if ($angkatan === '')
        $errors[] = 'Angkatan wajib diisi untuk mahasiswa.';
}


if (!empty($errors)) {
    ?>
    <form id="register-form" hx-post="register.php" hx-target="#register-form" hx-swap="outerHTML">
        <div class="alert alert-danger"><?= e(implode('<br>', $errors)) ?></div>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input name="username" class="form-control" value="<?= e($username) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <input name="password" type="password" class="form-control" id="reg-pass-err1" required>
                <button class="btn btn-outline-secondary password-toggle" type="button"
                    onclick="togglePassword('reg-pass-err1', this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input name="namalengkap" class="form-control" value="<?= e($namalengkap) ?>" required>
        </div>
        <div class="mb-4">
            <label class="form-label">Kode Admin (Opsional)</label>
            <input name="admin_key" type="password" class="form-control" placeholder="Isi jika Anda adalah Admin">
            <div class="form-text" style="font-size: 0.75rem;">Kosongkan jika Anda Mahasiswa.</div>
        </div>
        <div id="register-feedback"></div>
        <button class="btn btn-success">Buat Akun</button>
    </form>
    <?php
    exit;
}


// cek unik username
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    ?>
    <form id="register-form" hx-post="register.php" hx-target="#register-form" hx-swap="outerHTML">
        <div class="alert alert-danger">Username sudah terpakai.</div>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input name="username" class="form-control" value="<?= e($username) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <input name="password" type="password" class="form-control" id="reg-pass-err2" required>
                <button class="btn btn-outline-secondary password-toggle" type="button"
                    onclick="togglePassword('reg-pass-err2', this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input name="namalengkap" class="form-control" value="<?= e($namalengkap) ?>" required>
        </div>
        <div class="mb-4">
            <label class="form-label">Kode Admin (Opsional)</label>
            <input name="admin_key" type="password" class="form-control" placeholder="Isi jika Anda adalah Admin">
            <div class="form-text" style="font-size: 0.75rem;">Kosongkan jika Anda Mahasiswa.</div>
        </div>
        <div id="register-feedback"></div>
        <button class="btn btn-success">Buat Akun</button>
    </form>
    <?php
    exit;
}


$pw_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    // 1. Masukkan ke users
    $stmt = $pdo->prepare('INSERT INTO users (username, password, namalengkap, role, nim) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$username, $pw_hash, $namalengkap, $role, $role === 'mahasiswa' ? $nim : null]);

    // 2. Jika Mahasiswa, masukkan ke tabel mahasiswa
    if ($role === 'mahasiswa') {
        // Cek apakah NIM sudah ada di tabel mahasiswa (opsional, tapi bagus untuk integritas)
        $stmt_cek = $pdo->prepare('SELECT nim FROM mahasiswa WHERE nim = ?');
        $stmt_cek->execute([$nim]);
        if ($stmt_cek->fetch()) {
            // NIM sudah ada, mungkin hanya update nama/jurusan atau biarkan error (duplicate entry)
            // Di sini kita biarkan, asumsi pendaftaran baru = data baru. 
            // Namun jika user mendaftar ulang dengan NIM sama tapi username beda, ini akan error di Primary Key Mahasiswa.
            // Kita gunakan INSERT IGNORE atau ON DUPLICATE UPDATE untuk aman, atau biarkan error catch.
            // Kita coba INSERT biasa, jika gagal catch exception.
        }

        $stmt_mhs = $pdo->prepare('INSERT INTO mahasiswa (nim, nama, jurusan, angkatan) VALUES (?, ?, ?, ?)');
        $stmt_mhs->execute([$nim, $namalengkap, $jurusan, $angkatan]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    // Tampilkan error (misal duplicate NIM)
    ?>
    <div class="alert alert-danger">Gagal membuat akun: <?= e($e->getMessage()) ?></div>
    <?php
    exit;
}


// Balikan fragment sukses yang akan menggantikan form registrasi
echo "<div class='alert alert-success'>Akun berhasil dibuat, silakan login.</div>";