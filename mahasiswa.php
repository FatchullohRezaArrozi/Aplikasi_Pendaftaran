<?php
session_start();
require_once __DIR__ . '/db.php';

function e(string $s): string
{
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (!isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$user_id = (int) $_SESSION['user_id'];
$namalengkap = (string) ($_SESSION['namalengkap'] ?? '');
$user_role = (string) ($_SESSION['role'] ?? 'user');

// --- LOGIC MAHASISWA ---
$mahasiswa_list = [];
if ($user_role === 'mahasiswa') {
  // Show only self
  $my_nim = $_SESSION['nim'] ?? '';
  $stmt = $pdo->prepare('SELECT nim, nama, jurusan, angkatan, email, telepon, created_at FROM mahasiswa WHERE nim = ?');
  $stmt->execute([$my_nim]);
  $mahasiswa_list = $stmt->fetchAll();
} else {
  // Show all
  $stmt = $pdo->prepare('SELECT nim, nama, jurusan, angkatan, email, telepon, created_at FROM mahasiswa ORDER BY nim DESC');
  $stmt->execute();
  $mahasiswa_list = $stmt->fetchAll();
}

// --- LOGIC PENGAJUAN ---
// Ambil daftar mahasiswa untuk dropdown (hanya nama/nim)
$stmt_mhs_dropdown = $pdo->prepare('SELECT nim, nama FROM mahasiswa ORDER BY nama ASC');
$stmt_mhs_dropdown->execute();
$mahasiswa_dropdown = $stmt_mhs_dropdown->fetchAll();

// Filter pengajuan berdasarkan role
$where_clause = '';
$params = [];

if ($user_role === 'mahasiswa') {
  $my_nim = $_SESSION['nim'] ?? '';
  $where_clause = 'WHERE p.nim = ?';
  $params[] = $my_nim;
}

$sql = '
    SELECT 
        p.id, p.nim, p.tanggal_mulai, p.tanggal_selesai, p.jenis_cuti, 
        p.alasan, p.status, p.approved_at, p.catatan_approval, p.created_at,
        m.nama as nama_mahasiswa,
        u.namalengkap as approved_by_name
    FROM pengajuan p
    LEFT JOIN mahasiswa m ON p.nim = m.nim
    LEFT JOIN users u ON p.approved_by = u.id
    ' . $where_clause . '
    ORDER BY p.created_at DESC
';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pengajuan_list = $stmt->fetchAll();

// Tentukan tab aktif default
$active_tab = 'mahasiswa';
if (isset($_GET['tab'])) {
  $active_tab = $_GET['tab'];
} elseif ($user_role === 'mahasiswa') {
  $active_tab = 'pengajuan';
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Data Mahasiswa & Pengajuan — Aplikasi Pengajuan Cuti Mahasiswa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://unpkg.com/htmx.org@1.9.2"></script>
  <style>
    .status-pending {
      background-color: #ffc107;
      color: #000;
    }

    .status-approved {
      background-color: #28a745;
      color: #fff;
    }

    .status-rejected {
      background-color: #dc3545;
      color: #fff;
    }
  </style>
</head>

<body class="bg-light">

  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
      <a class="navbar-brand" href="dashboard.php"><i class="bi bi-calendar-check"></i> Aplikasi Pengajuan Cuti
        Mahasiswa</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a class="nav-link" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="mahasiswa.php"><i class="bi bi-people"></i> Mahasiswa & Pengajuan</a>
          </li>
          <?php if ($user_role !== 'mahasiswa'): ?>
            <li class="nav-item">
              <a class="nav-link" href="users.php"><i class="bi bi-person-gear"></i> Users</a>
            </li>
          <?php endif; ?>
          <?php if ($user_role === 'mahasiswa'): ?>
            <li class="nav-item">
              <a class="nav-link" href="mahasiswa.php?tab=pengajuan"><i class="bi bi-file-text"></i> Pengajuan</a>
            </li>
          <?php endif; ?>
        </ul>
        <div class="d-flex align-items-center text-white">
          <span class="me-3"><i class="bi bi-person-circle"></i> <?php echo e($namalengkap); ?></span>
          <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
      </div>
    </div>
  </nav>

  <div class="container py-4">

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'mahasiswa' ? 'active' : '' ?>" id="mahasiswa-tab"
          data-bs-toggle="tab" data-bs-target="#mahasiswa-pane" type="button" role="tab"><i class="bi bi-people"></i>
          Data Mahasiswa</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'pengajuan' ? 'active' : '' ?>" id="pengajuan-tab"
          data-bs-toggle="tab" data-bs-target="#pengajuan-pane" type="button" role="tab"><i class="bi bi-file-text"></i>
          Data Pengajuan Cuti</button>
      </li>
    </ul>

    <div class="tab-content" id="myTabContent">

      <!-- TAB 1: DATA MAHASISWA -->
      <div class="tab-pane fade <?= $active_tab === 'mahasiswa' ? 'show active' : '' ?>" id="mahasiswa-pane"
        role="tabpanel">
        <div class="row">
          <!-- Form Tambah Mahasiswa (Admin Only) -->
          <?php if ($user_role !== 'mahasiswa'): ?>
            <div class="col-lg-4 mb-4">
              <div class="card shadow-sm">
                <div class="card-body" id="mahasiswa-form">
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
              </div>
            </div>
          <?php endif; ?>

          <!-- Daftar Mahasiswa -->
          <div class="<?= $user_role === 'mahasiswa' ? 'col-12' : 'col-lg-8' ?>">
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title mb-3"><i class="bi bi-list-ul"></i> Daftar Mahasiswa</h5>

                <?php if (count($mahasiswa_list) > 0): ?>
                  <div class="table-responsive" id="mahasiswa-table">
                    <table class="table table-striped table-hover align-middle">
                      <thead class="table-dark">
                        <tr>
                          <th>No</th>
                          <th>NIM</th>
                          <th>Nama</th>
                          <th>Jurusan</th>
                          <th>Angkatan</th>
                          <th>Kontak</th>
                          <th>Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($mahasiswa_list as $i => $mhs): ?>
                          <tr id="row-<?= e($mhs['nim']) ?>">
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= e($mhs['nim']) ?></strong></td>
                            <td><?= e($mhs['nama']) ?></td>
                            <td><?= e($mhs['jurusan']) ?></td>
                            <td><?= e($mhs['angkatan']) ?></td>
                            <td>
                              <?php if ($mhs['email']): ?>
                                <small><i class="bi bi-envelope"></i> <?= e($mhs['email']) ?></small><br>
                              <?php endif; ?>
                              <?php if ($mhs['telepon']): ?>
                                <small><i class="bi bi-telephone"></i> <?= e($mhs['telepon']) ?></small>
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if ($user_role !== 'mahasiswa'): ?>
                                <button class="btn btn-sm btn-danger" hx-post="delete_mahasiswa.php"
                                  hx-vals='{"nim": "<?= e($mhs['nim']) ?>"}' hx-target="#row-<?= e($mhs['nim']) ?>"
                                  hx-swap="outerHTML" hx-confirm="Yakin ingin menghapus mahasiswa <?= e($mhs['nama']) ?>?">
                                  <i class="bi bi-trash"></i>
                                </button>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Belum ada data mahasiswa. Silakan tambahkan data baru.
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: PENGAJUAN -->
      <div class="tab-pane fade <?= $active_tab === 'pengajuan' ? 'show active' : '' ?>" id="pengajuan-pane"
        role="tabpanel">
        <div class="row">
          <!-- Form Pengajuan Cuti (Mahasiswa Only) -->
          <?php if ($user_role === 'mahasiswa'): ?>
            <div class="col-lg-4 mb-4">
              <div class="card shadow-sm">
                <div class="card-body" id="pengajuan-form">
                  <h5 class="card-title mb-3"><i class="bi bi-file-earmark-plus"></i> Ajukan Cuti</h5>
                  <form hx-post="add_pengajuan.php" hx-target="#pengajuan-form" hx-swap="outerHTML">
                    <div class="mb-3">
                      <label class="form-label">Mahasiswa <span class="text-danger">*</span></label>
                      <input type="hidden" name="nim" value="<?= e($_SESSION['nim'] ?? '') ?>">
                      <input class="form-control"
                        value="<?= e($_SESSION['namalengkap'] ?? '') ?> (<?= e($_SESSION['nim'] ?? '') ?>)" readonly>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                      <input name="tanggal_mulai" type="date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                      <input name="tanggal_selesai" type="date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Jenis Cuti <span class="text-danger">*</span></label>
                      <select name="jenis_cuti" class="form-select" required>
                        <option value="">Pilih Jenis</option>
                        <option value="Sakit">Sakit</option>
                        <option value="Izin">Izin</option>
                        <option value="Cuti Bersama">Cuti Bersama</option>
                        <option value="Lainnya">Lainnya</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Alasan <span class="text-danger">*</span></label>
                      <textarea name="alasan" class="form-control" rows="3" required></textarea>
                    </div>
                    <button class="btn btn-primary w-100"><i class="bi bi-send"></i> Ajukan</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <!-- Daftar Pengajuan -->
          <!-- Daftar Pengajuan -->
          <div class="<?= $user_role === 'mahasiswa' ? 'col-lg-8' : 'col-12' ?>">
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title mb-3"><i class="bi bi-list-check"></i>
                  <?= $user_role === 'mahasiswa' ? 'Riwayat Pengajuan Saya' : 'Daftar Pengajuan Masuk' ?></h5>

                <?php if (count($pengajuan_list) > 0): ?>
                  <div class="table-responsive" id="pengajuan-table">
                    <table class="table table-striped table-hover align-middle">
                      <thead class="table-dark">
                        <tr>
                          <th>No</th>
                          <th>Mahasiswa</th>
                          <th>Periode</th>
                          <th>Jenis</th>
                          <th>Alasan</th>
                          <th>Status</th>
                          <th>Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($pengajuan_list as $i => $p): ?>
                          <tr id="row-pengajuan-<?= $p['id'] ?>">
                            <td><?= $i + 1 ?></td>
                            <td>
                              <strong><?= e($p['nim']) ?></strong><br>
                              <small><?= e($p['nama_mahasiswa']) ?></small>
                            </td>
                            <td>
                              <small>
                                <?= date('d/m/Y', strtotime($p['tanggal_mulai'])) ?><br>
                                s/d <?= date('d/m/Y', strtotime($p['tanggal_selesai'])) ?>
                              </small>
                            </td>
                            <td><span class="badge bg-info"><?= e($p['jenis_cuti']) ?></span></td>
                            <td>
                              <small><?= e(substr($p['alasan'], 0, 50)) ?><?= strlen($p['alasan']) > 50 ? '...' : '' ?></small>
                            </td>
                            <td>
                              <?php if ($p['status'] === 'pending'): ?>
                                <span class="badge status-pending"><i class="bi bi-clock"></i> Pending</span>
                              <?php elseif ($p['status'] === 'approved'): ?>
                                <span class="badge status-approved"><i class="bi bi-check-circle"></i> Disetujui</span>
                                <?php if ($p['approved_by_name']): ?>
                                  <br><small class="text-muted">oleh <?= e($p['approved_by_name']) ?></small>
                                <?php endif; ?>
                              <?php else: ?>
                                <span class="badge status-rejected"><i class="bi bi-x-circle"></i> Ditolak</span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if ($p['status'] === 'pending' && $user_role !== 'mahasiswa'): ?>
                                <button class="btn btn-sm btn-success mb-1" hx-post="approve_pengajuan.php"
                                  hx-vals='{"id": "<?= $p['id'] ?>", "action": "approve"}'
                                  hx-target="#row-pengajuan-<?= $p['id'] ?>" hx-swap="outerHTML" title="Setujui">
                                  <i class="bi bi-check-lg"></i>
                                </button>
                                <button class="btn btn-sm btn-warning mb-1" hx-post="approve_pengajuan.php"
                                  hx-vals='{"id": "<?= $p['id'] ?>", "action": "reject"}'
                                  hx-target="#row-pengajuan-<?= $p['id'] ?>" hx-swap="outerHTML" title="Tolak">
                                  <i class="bi bi-x-lg"></i>
                                </button>
                              <?php endif; ?>
                              <button class="btn btn-sm btn-danger mb-1" hx-post="delete_pengajuan.php"
                                hx-vals='{"id": "<?= $p['id'] ?>"}' hx-target="#row-pengajuan-<?= $p['id'] ?>"
                                hx-swap="outerHTML" hx-confirm="Yakin ingin menghapus pengajuan ini?" title="Hapus">
                                <i class="bi bi-trash"></i>
                              </button>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Belum ada pengajuan cuti.
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>