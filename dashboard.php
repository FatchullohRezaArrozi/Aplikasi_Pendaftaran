<?php
session_start();
require_once __DIR__ . '/db.php';

function e(string $s): string
{
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// 1. Cek Login
if (!isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$user_role = (string) ($_SESSION['role'] ?? 'user');

// 2. REDIRECT MAHASISWA
if ($user_role === 'mahasiswa') {
  header('Location: mahasiswa_dashboard.php');
  exit;
}

// --- LOGIC KHUSUS ADMIN/DOSEN DI BAWAH INI ---
$user_id = (int) $_SESSION['user_id'];
$namalengkap = (string) ($_SESSION['namalengkap'] ?? '');

// Inisialisasi variabel stats
$stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
$recent_submissions = [];
$db_error = false;

try {
  // Statistik GLOBAL (Admin/Dosen)
  $stmt_stats = $pdo->prepare('
          SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
              SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
              SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected
          FROM pengajuan
      ');
  $stmt_stats->execute();
  $stats = $stmt_stats->fetch();

  // Pengajuan Masuk Terbaru
  $stmt = $pdo->prepare('
          SELECT 
              p.id, p.nim, p.tanggal_mulai, p.tanggal_selesai, p.jenis_cuti, 
              p.status, p.created_at,
              m.nama as nama_mahasiswa
          FROM pengajuan p
          LEFT JOIN mahasiswa m ON p.nim = m.nim
          ORDER BY p.created_at DESC
          LIMIT 10
      ');
  $stmt->execute();
  $recent_submissions = $stmt->fetchAll();

} catch (PDOException $e) {
  $db_error = true;
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }

    .stat-card-inner {
      padding: 1.5rem;
      position: relative;
      overflow: hidden;
    }

    .stat-icon-bg {
      position: absolute;
      right: -20px;
      bottom: -20px;
      font-size: 6rem;
      opacity: 0.1;
      transform: rotate(-15deg);
    }

    .bg-gradient-primary-soft {
      background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);
      color: #333;
    }

    .bg-gradient-warning-soft {
      background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
      color: #333;
    }

    .bg-gradient-success-soft {
      background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
      color: #333;
    }

    .bg-gradient-danger-soft {
      background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
      color: #333;
    }
  </style>
</head>

<body>

  <!-- Admin Navigation -->
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
          <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_mahasiswa.php">Data Mahasiswa</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_pengajuan.php">Verifikasi Cuti</a></li>
          <li class="nav-item"><a class="nav-link" href="users.php">Manajemen User</a></li>
        </ul>
        <div class="d-flex align-items-center">
          <span class="me-3 text-muted small">Halo, <strong><?= e($namalengkap) ?></strong></span>
          <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill">Logout</a>
        </div>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h4 class="mb-4 text-primary fw-bold">Dashboard Overview</h4>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
      <div class="col-md-3">
        <div class="card bg-gradient-primary-soft h-100">
          <div class="stat-card-inner">
            <div class="h2 fw-bold mb-0"><?= $stats['total'] ?? 0 ?></div>
            <div class="text-uppercase small fw-bold text-muted">Total Pengajuan</div>
            <i class="bi bi-folder2-open stat-icon-bg"></i>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-gradient-warning-soft h-100">
          <div class="stat-card-inner">
            <div class="h2 fw-bold mb-0"><?= $stats['pending'] ?? 0 ?></div>
            <div class="text-uppercase small fw-bold text-muted">Menunggu Aksi</div>
            <i class="bi bi-hourglass-split stat-icon-bg"></i>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-gradient-success-soft h-100">
          <div class="stat-card-inner">
            <div class="h2 fw-bold mb-0"><?= $stats['approved'] ?? 0 ?></div>
            <div class="text-uppercase small fw-bold text-muted">Disetujui</div>
            <i class="bi bi-check-circle stat-icon-bg"></i>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-gradient-danger-soft h-100">
          <div class="stat-card-inner">
            <div class="h2 fw-bold mb-0"><?= $stats['rejected'] ?? 0 ?></div>
            <div class="text-uppercase small fw-bold text-muted">Ditolak</div>
            <i class="bi bi-x-circle stat-icon-bg"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Submissions Table -->
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="fw-bold m-0"><i class="bi bi-list-task"></i> Pengajuan Terbaru</h5>
          <a href="admin_pengajuan.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
        </div>

        <?php if (count($recent_submissions) > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Mahasiswa</th>
                  <th>Tanggal Cuti</th>
                  <th>Jenis</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_submissions as $sub): ?>
                  <tr>
                    <td>
                      <span class="fw-bold"><?= e($sub['nama_mahasiswa']) ?></span><br>
                      <small class="text-muted"><?= e($sub['nim']) ?></small>
                    </td>
                    <td>
                      <?= date('d M', strtotime($sub['tanggal_mulai'])) ?> -
                      <?= date('d M Y', strtotime($sub['tanggal_selesai'])) ?>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= e($sub['jenis_cuti']) ?></span></td>
                    <td>
                      <?php if ($sub['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark">Pending</span>
                      <?php elseif ($sub['status'] === 'approved'): ?>
                        <span class="badge bg-success">Approved</span>
                      <?php else: ?>
                        <span class="badge bg-danger">Rejected</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-muted text-center py-4">Belum ada data pengajuan masuk.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>