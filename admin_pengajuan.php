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
    header('Location: mahasiswa_dashboard.php');
    exit;
}
$namalengkap = (string) ($_SESSION['namalengkap'] ?? '');

// 2. Logic List Pengajuan
$sql = '
    SELECT 
        p.id, p.nim, p.tanggal_mulai, p.tanggal_selesai, p.jenis_cuti, 
        p.alasan, p.status, p.approved_at, p.catatan_approval, p.created_at,
        m.nama as nama_mahasiswa,
        u.namalengkap as approved_by_name
    FROM pengajuan p
    LEFT JOIN mahasiswa m ON p.nim = m.nim
    LEFT JOIN users u ON p.approved_by = u.id
    ORDER BY p.created_at DESC
';
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pengajuan_list = $stmt->fetchAll();

?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Verifikasi Cuti — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/htmx.org@1.9.2"></script>
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

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
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
                    <li class="nav-item"><a class="nav-link" href="admin_mahasiswa.php">Data Mahasiswa</a></li>
                    <li class="nav-item"><a class="nav-link active" href="admin_pengajuan.php">Verifikasi Cuti</a></li>
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
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4 fw-bold text-primary">Daftar Pengajuan Cuti Mahasiswa</h4>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Tanggal</th>
                                <th>Mahasiswa</th>
                                <th>Detail Cuti</th>
                                <th>Alasan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pengajuan_list as $p): ?>
                                <tr id="row-pengajuan-<?= $p['id'] ?>">
                                    <td><small class="text-muted"><?= date('d/m/Y', strtotime($p['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?= e($p['nama_mahasiswa']) ?></span><br>
                                        <small><?= e($p['nim']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?= e($p['jenis_cuti']) ?></span><br>
                                        <small><?= date('d/m', strtotime($p['tanggal_mulai'])) ?> -
                                            <?= date('d/m', strtotime($p['tanggal_selesai'])) ?></small>
                                    </td>
                                    <td>
                                        <small class="fst-italic">"<?= e($p['alasan']) ?>"</small>
                                    </td>
                                    <td>
                                        <?php if ($p['status'] === 'pending'): ?>
                                            <span class="badge status-pending">Pending</span>
                                        <?php elseif ($p['status'] === 'approved'): ?>
                                            <span class="badge status-approved">Approved</span>
                                        <?php else: ?>
                                            <span class="badge status-rejected">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success" hx-post="approve_pengajuan.php"
                                                hx-vals='{"id": "<?= $p['id'] ?>", "action": "approve"}'
                                                hx-target="#row-pengajuan-<?= $p['id'] ?>" hx-swap="outerHTML" title="Setujui">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" hx-post="approve_pengajuan.php"
                                                hx-vals='{"id": "<?= $p['id'] ?>", "action": "reject"}'
                                                hx-target="#row-pengajuan-<?= $p['id'] ?>" hx-swap="outerHTML" title="Tolak">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-danger ms-1" hx-post="delete_pengajuan.php"
                                            hx-vals='{"id": "<?= $p['id'] ?>"}' hx-target="#row-pengajuan-<?= $p['id'] ?>"
                                            hx-swap="outerHTML" hx-confirm="Yakin hapus?">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>