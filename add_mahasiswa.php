<?php
session_start();
require_once __DIR__ . '/db.php';

function e(string $s): string {
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
$nim = trim((string)($_POST['nim'] ?? ''));
$nama = trim((string)($_POST['nama'] ?? ''));
$jurusan = trim((string)($_POST['jurusan'] ?? ''));
$angkatan = trim((string)($_POST['angkatan'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$telepon = trim((string)($_POST['telepon'] ?? ''));

// Validasi input
$errors = [];
if ($nim === '') {
    $errors[] = 'NIM wajib diisi.';
}
if ($nama === '') {
    $errors[] = 'Nama wajib diisi.';
}
if ($jurusan === '') {
    $errors[] = 'Jurusan wajib diisi.';
}
if ($angkatan === '') {
    $errors[] = 'Angkatan wajib diisi.';
}

// Cek duplikasi NIM
if ($nim !== '') {
    $stmt = $pdo->prepare('SELECT nim FROM mahasiswa WHERE nim = ?');
    $stmt->execute([$nim]);
    if ($stmt->fetch()) {
        $errors[] = 'NIM sudah terdaftar.';
    }
}

if (!empty($errors)) {
    ?>
    <div class="card-body" id="mahasiswa-form">
        <div class="alert alert-danger">
            <?= e(implode('<br>', $errors)) ?>
        </div>
        <h5 class="card-title mb-3"><i class="bi bi-person-plus"></i> Tambah Mahasiswa</h5>
        <form hx-post="add_mahasiswa.php" hx-target="#mahasiswa-form" hx-swap="outerHTML">
            <div class="mb-3">
                <label class="form-label">NIM <span class="text-danger">*</span></label>
                <input name="nim" class="form-control" value="<?= e($nim) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                <input name="nama" class="form-control" value="<?= e($nama) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Jurusan <span class="text-danger">*</span></label>
                <select name="jurusan" class="form-select" required>
                    <option value="">Pilih Jurusan</option>
                    <option value="Teknik Informatika" <?= $jurusan === 'Teknik Informatika' ? 'selected' : '' ?>>Teknik Informatika</option>
                    <option value="Sistem Informasi" <?= $jurusan === 'Sistem Informasi' ? 'selected' : '' ?>>Sistem Informasi</option>
                    <option value="Teknik Komputer" <?= $jurusan === 'Teknik Komputer' ? 'selected' : '' ?>>Teknik Komputer</option>
                    <option value="Manajemen Informatika" <?= $jurusan === 'Manajemen Informatika' ? 'selected' : '' ?>>Manajemen Informatika</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Angkatan <span class="text-danger">*</span></label>
                <input name="angkatan" type="number" class="form-control" value="<?= e($angkatan) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input name="email" type="email" class="form-control" value="<?= e($email) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Telepon</label>
                <input name="telepon" class="form-control" value="<?= e($telepon) ?>">
            </div>
            <button class="btn btn-primary w-100"><i class="bi bi-save"></i> Simpan</button>
        </form>
    </div>
    <?php
    exit;
}

// Simpan ke database
$stmt = $pdo->prepare('
    INSERT INTO mahasiswa (nim, nama, jurusan, angkatan, email, telepon)
    VALUES (?, ?, ?, ?, ?, ?)
');
$stmt->execute([$nim, $nama, $jurusan, $angkatan, $email, $telepon]);

?>
<div class="card-body" id="mahasiswa-form">
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i> Mahasiswa berhasil ditambahkan!
    </div>
    <h5 class="card-title mb-3"><i class="bi bi-person-plus"></i> Tambah Mahasiswa</h5>
    <form hx-post="add_mahasiswa.php" hx-target="#mahasiswa-form" hx-swap="outerHTML">
        <div class="mb-3">
            <label class="form-label">NIM <span class="text-danger">*</span></label>
            <input name="nim" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input name="nama" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Jurusan <span class="text-danger">*</span></label>
            <select name="jurusan" class="form-select" required>
                <option value="">Pilih Jurusan</option>
                <option value="Teknik Informatika">Teknik Informatika</option>
                <option value="Sistem Informasi">Sistem Informasi</option>
                <option value="Teknik Komputer">Teknik Komputer</option>
                <option value="Manajemen Informatika">Manajemen Informatika</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Angkatan <span class="text-danger">*</span></label>
            <input name="angkatan" type="number" class="form-control" placeholder="2024" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Telepon</label>
            <input name="telepon" class="form-control" placeholder="08xxxxxxxxxx">
        </div>
        <button class="btn btn-primary w-100"><i class="bi bi-save"></i> Simpan</button>
    </form>
</div>
<script>
    // Reload table after successful add
    htmx.ajax('GET', 'mahasiswa.php', {target: '#mahasiswa-table', swap: 'outerHTML', select: '#mahasiswa-table'});
</script>
