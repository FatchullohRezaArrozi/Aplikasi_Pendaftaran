<?php
session_start();
require_once __DIR__ . '/db.php';

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// 1. Cek Login & Role
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_role = $_SESSION['role'] ?? 'user';
if ($user_role === 'mahasiswa') {
    // Mahasiswa dilarang masuk sini
    header('Location: mahasiswa_dashboard.php');
    exit;
}

$namalengkap = (string) ($_SESSION['namalengkap'] ?? '');

// 2. Logic Data Mahasiswa (CRUD)
$stmt = $pdo->prepare('SELECT nim, nama, jurusan, angkatan, email, telepon, created_at FROM mahasiswa ORDER BY nim DESC');
$stmt->execute();
$mahasiswa_list = $stmt->fetchAll();

?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Data Mahasiswa — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/htmx.org@1.9.2"></script>
    <!-- Use same admin styles from dashboard -->
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background: #fff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }
    </style>
</head>

<body>

    <!-- Navigation (Admin) -->
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
                <i class="bi bi-shield-lock-fill"></i> Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="admin_mahasiswa.php">Data Mahasiswa</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_pengajuan.php">Verifikasi Cuti</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php">Manajemen User</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="me-3 text-muted small">Admin: <strong><?= e($namalengkap) ?></strong></span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <!-- Form Tambah -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body" id="mahasiswa-form">
                        <h5 class="card-title mb-3"><i class="bi bi-person-plus"></i> Tambah Mahasiswa</h5>
                        <form hx-post="add_mahasiswa.php" hx-target="#mahasiswa-form" hx-swap="outerHTML">
                            <div class="mb-3">
                                <label class="form-label">NIM <span class="text-danger">*</span></label>
                                <input name="nim" class="form-control" required placeholder="Contoh: 12345678">
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
                                <label class="form-label">Angkatan</label>
                                <input name="angkatan" type="number" class="form-control" placeholder="2024">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input name="email" type="email" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telepon</label>
                                <input name="telepon" class="form-control" placeholder="08xxxxxxxxxx">
                            </div>
                            <button class="btn btn-primary w-100"><i class="bi bi-save"></i> Simpan Data</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabel Data -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-list-ul"></i> Daftar Mahasiswa Terdaftar</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle rounded-3 overflow-hidden">
                                <thead class="table-dark">
                                    <tr>
                                        <th>NIM</th>
                                        <th>Nama & Jurusan</th>
                                        <th>Kontak</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mahasiswa_list as $mhs): ?>
                                        <tr id="row-<?= e($mhs['nim']) ?>">
                                            <td class="fw-bold"><?= e($mhs['nim']) ?></td>
                                            <td>
                                                <?= e($mhs['nama']) ?><br>
                                                <small class="text-muted"><?= e($mhs['jurusan']) ?>
                                                    (<?= e($mhs['angkatan']) ?>)</small>
                                            </td>
                                            <td>
                                                <?php if ($mhs['email']): ?>
                                                    <div class="small"><i class="bi bi-envelope"></i> <?= e($mhs['email']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($mhs['telepon']): ?>
                                                    <div class="small"><i class="bi bi-telephone"></i> <?= e($mhs['telepon']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger" hx-post="delete_mahasiswa.php"
                                                    hx-vals='{"nim": "<?= e($mhs['nim']) ?>"}'
                                                    hx-target="#row-<?= e($mhs['nim']) ?>" hx-swap="outerHTML"
                                                    hx-confirm="Yakin ingin menghapus data mahasiswa ini?">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (count($mahasiswa_list) === 0): ?>
                                <p class="text-center text-muted py-3">Belum ada data mahasiswa.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>