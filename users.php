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

// Ambil daftar users
$users_list = [];
if ($user_role === 'mahasiswa') {
  $stmt = $pdo->prepare('SELECT id, username, namalengkap, role, nim, created_at FROM users WHERE id = ?');
  $stmt->execute([$user_id]);
  $users_list = $stmt->fetchAll();
} else {
  $stmt = $pdo->prepare('SELECT id, username, namalengkap, role, nim, created_at FROM users ORDER BY created_at DESC');
  $stmt->execute();
  $users_list = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manajemen User — Aplikasi Pengajuan Cuti Mahasiswa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://unpkg.com/htmx.org@1.9.2"></script>
</head>

<body class="bg-light">

  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
      <a class="navbar-brand" href="dashboard.php"><i class="bi bi-calendar-check"></i> Aplikasi Pengajuan Cuti</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <?php if ($user_role === 'mahasiswa'): ?>
            <li class="nav-item">
              <a class="nav-link" href="mahasiswa_dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="admin_mahasiswa.php"><i class="bi bi-people"></i> Mahasiswa</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="admin_pengajuan.php"><i class="bi bi-file-text"></i> Verifikasi Cuti</a>
            </li>
            <li class="nav-item">
              <a class="nav-link active" href="users.php"><i class="bi bi-person-gear"></i> Users</a>
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
    <div class="row">
      <!-- Form Tambah User -->
      <!-- Form Tambah User (Admin Only) -->
      <?php if ($user_role !== 'mahasiswa'): ?>
        <div class="col-lg-4 mb-4">
          <div class="card shadow-sm">
            <div class="card-body" id="user-form">
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
                  <div class="mb-3">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-select" required
                      onchange="document.getElementById('add-user-nim').style.display = this.value === 'mahasiswa' ? 'block' : 'none'">
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
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Daftar Users -->
    <div class="<?= $user_role === 'mahasiswa' ? 'col-12' : 'col-lg-8' ?>">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3"><i class="bi bi-people-fill"></i> Daftar User</h5>

          <?php if (count($users_list) > 0): ?>
            <div class="table-responsive" id="users-table">
              <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                  <tr>
                    <th>No</th>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Role</th>
                    <th>Terdaftar</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users_list as $i => $usr): ?>
                    <tr id="row-user-<?= $usr['id'] ?>">
                      <td><?= $i + 1 ?></td>
                      <td>
                        <strong><?= e($usr['username']) ?></strong>
                        <?php if (!empty($usr['nim'])): ?>
                          <br><small class="text-muted">NIM: <?= e($usr['nim']) ?></small>
                        <?php endif; ?>
                      </td>
                      <td><?= e($usr['namalengkap']) ?></td>
                      <td>
                        <?php if ($usr['role'] === 'admin'): ?>
                          <span class="badge bg-danger"><i class="bi bi-shield-fill"></i> Admin</span>
                        <?php elseif ($usr['role'] === 'mahasiswa'): ?>
                          <span class="badge bg-info text-dark"><i class="bi bi-mortarboard-fill"></i> Mhs</span>
                        <?php elseif ($usr['role'] === 'dosen'): ?>
                          <span class="badge bg-warning text-dark"><i class="bi bi-person-workspace"></i> Dosen</span>
                        <?php else: ?>
                          <span class="badge bg-secondary"><i class="bi bi-person"></i> User</span>
                        <?php endif; ?>
                      </td>
                      <td><small><?= e($usr['created_at']) ?></small></td>
                      <td>
                        <?php if ($usr['id'] !== $user_id && $user_role !== 'mahasiswa'): ?>
                          <button class="btn btn-sm btn-danger" hx-post="delete_user.php"
                            hx-vals='{"user_id": "<?= $usr['id'] ?>"}' hx-target="#row-user-<?= $usr['id'] ?>"
                            hx-swap="outerHTML" hx-confirm="Yakin ingin menghapus user <?= e($usr['username']) ?>?">
                            <i class="bi bi-trash"></i>
                          </button>
                        <?php else: ?>
                          <span class="badge bg-info text-dark"><i class="bi bi-person-check"></i>
                            <?= $usr['id'] === $user_id ? 'Anda' : '-' ?></span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info">
              <i class="bi bi-info-circle"></i> Belum ada data user.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>