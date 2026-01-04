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

$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

// Prioritize POST data for NIM if available (to support manual selection), otherwise fallback to session
$nim = trim((string) ($_POST['nim'] ?? ''));
if ($nim === '' && $user_role === 'mahasiswa') {
    $nim = $_SESSION['nim'] ?? '';
}
$tanggal_mulai = trim((string) ($_POST['tanggal_mulai'] ?? ''));
$tanggal_selesai = trim((string) ($_POST['tanggal_selesai'] ?? ''));
$jenis_cuti = trim((string) ($_POST['jenis_cuti'] ?? ''));
$alasan = trim((string) ($_POST['alasan'] ?? ''));

// Validasi input
$errors = [];
if ($nim === '') {
    $errors[] = 'Mahasiswa wajib dipilih.';
}
if ($tanggal_mulai === '') {
    $errors[] = 'Tanggal mulai wajib diisi.';
}
if ($tanggal_selesai === '') {
    $errors[] = 'Tanggal selesai wajib diisi.';
}
if ($jenis_cuti === '') {
    $errors[] = 'Jenis cuti wajib dipilih.';
}
if ($alasan === '') {
    $errors[] = 'Alasan wajib diisi.';
}

// Validasi tanggal
if ($tanggal_mulai !== '' && $tanggal_selesai !== '') {
    if (strtotime($tanggal_selesai) < strtotime($tanggal_mulai)) {
        $errors[] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
    }
}

// Cek apakah mahasiswa ada
if ($nim !== '') {
    $stmt = $pdo->prepare('SELECT nim FROM mahasiswa WHERE nim = ?');
    $stmt->execute([$nim]);
    if (!$stmt->fetch()) {
        $errors[] = 'Mahasiswa tidak ditemukan.';
    }
}

// Ambil daftar mahasiswa untuk dropdown
$stmt_mhs = $pdo->prepare('SELECT nim, nama FROM mahasiswa ORDER BY nama ASC');
$stmt_mhs->execute();
$mahasiswa_list = $stmt_mhs->fetchAll();

if (!empty($errors)) {
    ?>
    <div class="card-body" id="pengajuan-form">
        <div class="alert alert-danger">
            <?= e(implode('<br>', $errors)) ?>
        </div>
        <h5 class="card-title mb-3"><i class="bi bi-file-earmark-plus"></i> Ajukan Cuti</h5>
        <form hx-post="add_pengajuan.php" hx-target="#pengajuan-form" hx-swap="outerHTML">
            <div class="mb-3">
                <label class="form-label">Mahasiswa <span class="text-danger">*</span></label>
                <select name="nim" class="form-select" required>
                    <option value="">Pilih Mahasiswa</option>
                    <?php foreach ($mahasiswa_list as $mhs): ?>
                        <option value="<?= e($mhs['nim']) ?>" <?= $nim === $mhs['nim'] ? 'selected' : '' ?>>
                            <?= e($mhs['nim']) ?> - <?= e($mhs['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                <input name="tanggal_mulai" type="date" class="form-control" value="<?= e($tanggal_mulai) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                <input name="tanggal_selesai" type="date" class="form-control" value="<?= e($tanggal_selesai) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Jenis Cuti <span class="text-danger">*</span></label>
                <select name="jenis_cuti" class="form-select" required>
                    <option value="">Pilih Jenis</option>
                    <option value="Sakit" <?= $jenis_cuti === 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                    <option value="Izin" <?= $jenis_cuti === 'Izin' ? 'selected' : '' ?>>Izin</option>
                    <option value="Cuti Bersama" <?= $jenis_cuti === 'Cuti Bersama' ? 'selected' : '' ?>>Cuti Bersama</option>
                    <option value="Lainnya" <?= $jenis_cuti === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Alasan <span class="text-danger">*</span></label>
                <textarea name="alasan" class="form-control" rows="3" required><?= e($alasan) ?></textarea>
            </div>
            <button class="btn btn-primary w-100"><i class="bi bi-send"></i> Ajukan</button>
        </form>
    </div>
    <?php
    exit;
}

// Simpan ke database
$stmt = $pdo->prepare('
    INSERT INTO pengajuan (user_id, nim, tanggal_mulai, tanggal_selesai, jenis_cuti, alasan, status)
    VALUES (?, ?, ?, ?, ?, ?, "pending")
');
$stmt->execute([$user_id, $nim, $tanggal_mulai, $tanggal_selesai, $jenis_cuti, $alasan]);

?>
<div class="card-body" id="pengajuan-form">
    <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
        <i class="bi bi-check-circle-fill me-2"></i> Pengajuan cuti berhasil dikirim!
    </div>
    <h5 class="card-title mb-4 fw-bold text-primary"><i class="bi bi-plus-circle"></i> Buat Pengajuan Baru</h5>
    <form hx-post="add_pengajuan.php" hx-target="#pengajuan-form" hx-swap="outerHTML">
        <div class="mb-3">
            <label class="form-label text-muted small text-uppercase fw-bold">Mahasiswa</label>
            <select name="nim" class="form-select" required>
                <option value="">Pilih Mahasiswa</option>
                <?php foreach ($mahasiswa_list as $mhs): ?>
                    <option value="<?= e($mhs['nim']) ?>"><?= e($mhs['nim']) ?> - <?= e($mhs['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label text-muted small text-uppercase fw-bold">Tanggal Mulai</label>
            <input name="tanggal_mulai" type="date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label text-muted small text-uppercase fw-bold">Tanggal Selesai</label>
            <input name="tanggal_selesai" type="date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label text-muted small text-uppercase fw-bold">Jenis Cuti</label>
            <select name="jenis_cuti" class="form-select" required>
                <option value="">Pilih Jenis...</option>
                <option value="Sakit">Sakit</option>
                <option value="Izin">Izin</option>
                <option value="Cuti Bersama">Cuti Bersama</option>
                <option value="Lainnya">Lainnya</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label text-muted small text-uppercase fw-bold">Alasan</label>
            <textarea name="alasan" class="form-control" rows="3" placeholder="Jelaskan alasan pengajuan..."
                required></textarea>
        </div>
        <button class="btn btn-primary w-100 py-2 fw-bold"><i class="bi bi-send"></i> Kirim Pengajuan</button>
    </form>
</div>
<script>
    // Reload halaman setelah 1.5 detik agar tabel terupdate
    setTimeout(function () {
        location.reload();
    }, 1500);
</script>