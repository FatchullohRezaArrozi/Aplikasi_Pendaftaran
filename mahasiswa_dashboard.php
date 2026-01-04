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
if ($user_role !== 'mahasiswa') {
    // Jika bukan mahasiswa, kembalikan ke dashboard utama (atau admin)
    header('Location: dashboard.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$namalengkap = (string) ($_SESSION['namalengkap'] ?? '');
$nim_user = $_SESSION['nim'] ?? '';

// 2. Logic Pengajuan (Sama seperti add_pengajuan.php namun disederhanakan untuk view ini)
// Kita hanya perlu menampilkan history di sini, form akan ditangani via HTMX ke add_pengajuan.php

// Ambil History Pengajuan
$stmt = $pdo->prepare('
    SELECT 
        p.id, p.nim, p.tanggal_mulai, p.tanggal_selesai, p.jenis_cuti, 
        p.alasan, p.status, p.approved_at, p.catatan_approval, p.created_at,
        u.namalengkap as approved_by_name
    FROM pengajuan p
    LEFT JOIN users u ON p.approved_by = u.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
');
$stmt->execute([$user_id]);
$pengajuan_list = $stmt->fetchAll();

// Ambil daftar mahasiswa untuk dropdown
$stmt_mhs = $pdo->prepare('SELECT nim, nama FROM mahasiswa ORDER BY nama ASC');
$stmt_mhs->execute();
$mahasiswa_list = $stmt_mhs->fetchAll();

?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Halaman Mahasiswa — Sistem Pengajuan Cuti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/htmx.org@1.9.2"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f0f2f5;
        }

        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        .navbar-brand {
            font-weight: 700;
            color: #4361ee !important;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            margin-bottom: 20px;
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

    <!-- Simple Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="bi bi-mortarboard-fill"></i> Portal Mahasiswa</a>
            <div class="d-flex align-items-center gap-3">
                <span class="d-none d-md-inline text-muted">Halo, <strong><?= e($namalengkap) ?></strong></span>
                <a href="users.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-person"></i> Profil
                </a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <!-- Notification Alert -->
        <?php if (!empty($pengajuan_list)): ?>
            <?php $latest = $pengajuan_list[0]; ?>
            <?php if ($latest['status'] === 'approved'): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                        <div>
                            <strong>Permintaan Disetujui!</strong>
                            <div>Pengajuan cuti Anda untuk tanggal
                                <strong><?= date('d M Y', strtotime($latest['tanggal_mulai'])) ?></strong> s/d
                                <strong><?= date('d M Y', strtotime($latest['tanggal_selesai'])) ?></strong> telah disetujui.
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif ($latest['status'] === 'rejected'): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-x-circle-fill fs-4 me-3"></i>
                        <div>
                            <strong>Permintaan Ditolak</strong>
                            <div>Pengajuan cuti Anda untuk tanggal
                                <strong><?= date('d M Y', strtotime($latest['tanggal_mulai'])) ?></strong> telah ditolak.</div>
                            <?php if (!empty($latest['catatan_approval'])): ?>
                                <div class="mt-2 small bg-white bg-opacity-75 p-2 rounded border border-danger-subtle text-danger">
                                    <strong>Catatan Admin:</strong> <?= e($latest['catatan_approval']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="row">
            <!-- Form Pengajuan -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body" id="pengajuan-form">
                        <h5 class="card-title mb-4 fw-bold text-primary"><i class="bi bi-plus-circle"></i> Buat
                            Pengajuan Baru</h5>
                        <!-- Form uses HTMX -->
                        <form hx-post="add_pengajuan.php" hx-target="#pengajuan-form" hx-swap="outerHTML">
                            <div class="mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Mahasiswa</label>
                                <label class="form-label text-muted small text-uppercase fw-bold">Mahasiswa</label>
                                <select name="nim" class="form-select" required>
                                    <option value="">Pilih Mahasiswa</option>
                                    <?php foreach ($mahasiswa_list as $mhs): ?>
                                        <option value="<?= e($mhs['nim']) ?>" <?= ($nim_user == $mhs['nim']) ? 'selected' : '' ?>>
                                            <?= e($mhs['nim']) ?> - <?= e($mhs['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Tanggal Mulai</label>
                                <input name="tanggal_mulai" type="date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Tanggal
                                    Selesai</label>
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
                                <textarea name="alasan" class="form-control" rows="3"
                                    placeholder="Jelaskan alasan pengajuan..." required></textarea>
                            </div>
                            <button class="btn btn-primary w-100 py-2 fw-bold"><i class="bi bi-send"></i> Kirim
                                Pengajuan</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- History Table -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4 fw-bold text-primary"><i class="bi bi-clock-history"></i> Riwayat
                            Pengajuan Saya</h5>

                        <!-- INFORMASI STATUS TERKINI -->
                        <div class="mb-4">
                            <?php if (count($pengajuan_list) > 0): ?>
                                <?php $latest_widget = $pengajuan_list[0]; ?>
                                <div class="p-3 rounded-3 border 
                                    <?php
                                    if ($latest_widget['status'] === 'approved') echo 'bg-success-subtle border-success text-success-emphasis';
                                    elseif ($latest_widget['status'] === 'rejected') echo 'bg-danger-subtle border-danger text-danger-emphasis';
                                    else echo 'bg-warning-subtle border-warning text-warning-emphasis';
                                    ?>">
                                    <h6 class="fw-bold mb-2"><i class="bi bi-info-circle-fill"></i> Status Pengajuan Terakhir</h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge 
                                                <?php
                                                if ($latest_widget['status'] === 'approved') echo 'bg-success';
                                                elseif ($latest_widget['status'] === 'rejected') echo 'bg-danger';
                                                else echo 'bg-warning text-dark';
                                                ?> mb-1">
                                                <?= strtoupper($latest_widget['status']) ?>
                                            </span>
                                            <div class="small">
                                                Tanggal: <strong><?= date('d M', strtotime($latest_widget['tanggal_mulai'])) ?></strong>
                                            </div>
                                        </div>
                                        <div class="text-end small">
                                            <?php if ($latest_widget['status'] === 'rejected'): ?>
                                                <div class="fw-bold">Alasan Penolakan:</div>
                                                <div><?= e($latest_widget['catatan_approval'] ?? '-') ?></div>
                                            <?php elseif ($latest_widget['status'] === 'approved'): ?>
                                                <div>Disetujui pada:</div>
                                                <div><?= date('d M Y', strtotime($latest_widget['approved_at'])) ?></div>
                                            <?php else: ?>
                                                <div>Menunggu konfirmasi admin.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info border-info-subtle">
                                    <i class="bi bi-info-circle"></i> Status pengajuan Anda (Diterima/Ditolak/Pending) akan muncul di sini setelah Anda melakukan pengajuan.
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (count($pengajuan_list) > 0): ?>
                            <div class="table-responsive" id="pengajuan-table">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal Pengajuan</th>
                                            <th>Periode</th>
                                            <th>Jenis</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pengajuan_list as $p): ?>
                                            <tr>
                                                <td><small
                                                        class="text-muted"><?= date('d M Y, H:i', strtotime($p['created_at'])) ?></small>
                                                </td>
                                                <td>
                                                    <?= date('d/m/y', strtotime($p['tanggal_mulai'])) ?> -
                                                    <?= date('d/m/y', strtotime($p['tanggal_selesai'])) ?>
                                                    <div class="small text-muted fst-italic">"<?= e($p['alasan']) ?>"</div>
                                                </td>
                                                <td><span
                                                        class="badge bg-light text-dark border"><?= e($p['jenis_cuti']) ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($p['status'] === 'pending'): ?>
                                                        <span class="badge status-pending border border-warning"><i
                                                                class="bi bi-hourglass"></i> Pending</span>
                                                    <?php elseif ($p['status'] === 'approved'): ?>
                                                        <span class="badge status-approved border border-success"><i
                                                                class="bi bi-check-circle"></i> Disetujui</span>
                                                        <?php if ($p['approved_by_name']): ?>
                                                            <div class="small text-success mt-1">Oleh: <?= e($p['approved_by_name']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge status-rejected border border-danger"><i
                                                                class="bi bi-x-circle"></i> Ditolak</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                                <p class="text-muted">Belum ada riwayat pengajuan cuti.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Listen to HTMX events to refresh table if needed, though simpler just to refresh the page or use HTMX for table too.
        // For simplicity, we assume user refreshes to see new list OR we can add a trigger.
        document.body.addEventListener('htmx:afterOnLoad', function (evt) {
            // Check if the source was the form
            if (evt.detail.elt.tagName === 'FORM' && evt.detail.xhr.status === 200) {
                // Optional: reload page to update table or refetch table
                // location.reload(); 
                // Updating table dynamically would require another endpoint. 
                // For now, let's leave it as form feedback, user can refresh.
            }
        });
    </script>
</body>

</html>