<?php
session_start();
require_once __DIR__ . '/db.php';

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Anda harus login terlebih dahulu.</div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Ambil data dari form
$username = trim((string) ($_POST['username'] ?? ''));
$password = trim((string) ($_POST['password'] ?? ''));
$namalengkap = trim((string) ($_POST['namalengkap'] ?? ''));
$role = trim((string) ($_POST['role'] ?? 'user'));
$nim = trim((string) ($_POST['nim'] ?? ''));

// Validasi input
$errors = [];
if ($username === '') {
    $errors[] = 'Username wajib diisi.';
}
if ($password === '') {
    $errors[] = 'Password wajib diisi.';
} elseif (strlen($password) < 6) {
    $errors[] = 'Password minimal 6 karakter.';
}
if ($namalengkap === '') {
    $errors[] = 'Nama lengkap wajib diisi.';
}
if (!in_array($role, ['user', 'admin', 'mahasiswa', 'dosen'])) {
    $errors[] = 'Role tidak valid.';
}

if ($role === 'mahasiswa') {
    if ($nim === '') {
        $errors[] = 'NIM wajib diisi untuk mahasiswa.';
    } else {
        $stmt = $pdo->prepare('SELECT nim FROM mahasiswa WHERE nim = ?');
        $stmt->execute([$nim]);
        if (!$stmt->fetch()) {
            $errors[] = 'NIM tidak ditemukan di data mahasiswa.';
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE nim = ?');
        $stmt->execute([$nim]);
        if ($stmt->fetch()) {
            $errors[] = 'NIM sudha digunakan user lain.';
        }
    }
}

// Cek duplikasi username
if ($username !== '') {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $errors[] = 'Username sudah digunakan.';
    }
}

if (!empty($errors)) {
    ?>
    <div class="card-body" id="user-form">
        <div class="alert alert-danger">
            <?= e(implode('<br>', $errors)) ?>
        </div>
        <h5 class="card-title mb-3"><i class="bi bi-person-plus-fill"></i> Tambah User</h5>
        <form hx-post="add_user.php" hx-target="#user-form" hx-swap="outerHTML">
            <div class="mb-3">
                <label class="form-label">Username <span class="text-danger">*</span></label>
                <input name="username" class="form-control" value="<?= e($username) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <input name="password" type="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                <input name="namalengkap" class="form-control" value="<?= e($namalengkap) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role <span class="text-danger">*</span></label>
                <select name="role" class="form-select" required
                    onchange="document.getElementById('add-user-nim').style.display = this.value === 'mahasiswa' ? 'block' : 'none'">
                    <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="mahasiswa" <?= $role === 'mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
                    <option value="dosen" <?= $role === 'dosen' ? 'selected' : '' ?>>Dosen</option>
                </select>
            </div>
            <div class="mb-3" id="add-user-nim" style="display: <?= $role === 'mahasiswa' ? 'block' : 'none' ?>;">
                <label class="form-label">NIM (Wajib untuk Mahasiswa)</label>
                <input name="nim" class="form-control" value="<?= e($nim) ?>">
            </div>
            <button class="btn btn-primary w-100"><i class="bi bi-save"></i> Simpan</button>
        </form>
    </div>
    <?php
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$final_nim = ($role === 'mahasiswa') ? $nim : null;

// Simpan ke database
$stmt = $pdo->prepare('
    INSERT INTO users (username, password, namalengkap, role, nim)
    VALUES (?, ?, ?, ?, ?)
');
$stmt->execute([$username, $hashed_password, $namalengkap, $role, $final_nim]);

?>
<div class="card-body" id="user-form">
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i> User berhasil ditambahkan!
    </div>
    <h5 class="card-title mb-3"><i class="bi bi-person-plus-fill"></i> Tambah User</h5>
    <form hx-post="add_user.php" hx-target="#user-form" hx-swap="outerHTML">
        <div class="mb-3">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <input name="password" type="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input name="namalengkap" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Role <span class="text-danger">*</span></label>
            <select name="role" class="form-select" required onchange="document.getElementById('add-user-nim').style.display = this.value === 'mahasiswa' ? 'block' : 'none'">
                <option value="user">User</option>
                <option value="admin">Admin</option>
                <option value="mahasiswa">Mahasiswa</option>
                <option value="dosen">Dosen</option>
            </select>
        </div>
        <div class="mb-3" id="add-user-nim" style="display: none;">
                <label class="form-label">NIM (Wajib untuk Mahasiswa)</label>
                <input name="nim" class="form-control" placeholder="Masukkan NIM">
        </div>
        <button class="btn btn-primary w-100"><i class="bi bi-save"></i> Simpan</button>
    </form>
</div>
<script>
    htmx.ajax('GET', 'users.php', { target: '#users-table', swap: 'outerHTML', select: '#users-table' });
</script>