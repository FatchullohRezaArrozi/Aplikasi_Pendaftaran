<?php
session_start();
require_once __DIR__ . '/db.php';

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$pengajuan_id = (int) ($_POST['id'] ?? 0);
$action = trim((string) ($_POST['action'] ?? ''));

if ($pengajuan_id === 0 || !in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    exit;
}

// Update status
$new_status = $action === 'approve' ? 'approved' : 'rejected';
$stmt = $pdo->prepare('
    UPDATE pengajuan 
    SET status = ?, approved_by = ?, approved_at = NOW()
    WHERE id = ?
');
$stmt->execute([$new_status, $user_id, $pengajuan_id]);

// Ambil data pengajuan yang sudah diupdate
$stmt = $pdo->prepare('
    SELECT 
        p.id, p.nim, p.tanggal_mulai, p.tanggal_selesai, p.jenis_cuti, 
        p.alasan, p.status, p.approved_at, p.catatan_approval, p.created_at,
        m.nama as nama_mahasiswa,
        u.namalengkap as approved_by_name
    FROM pengajuan p
    LEFT JOIN mahasiswa m ON p.nim = m.nim
    LEFT JOIN users u ON p.approved_by = u.id
    WHERE p.id = ?
');
$stmt->execute([$pengajuan_id]);
$p = $stmt->fetch();

if (!$p) {
    http_response_code(404);
    exit;
}

// Return updated row
?>
<tr id="row-pengajuan-<?= $p['id'] ?>">
    <td><small class="text-muted"><?= date('d/m/Y', strtotime($p['created_at'])) ?></small></td>
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
                hx-vals='{"id": "<?= $p['id'] ?>", "action": "approve"}' hx-target="#row-pengajuan-<?= $p['id'] ?>"
                hx-swap="outerHTML" title="Setujui">
                <i class="bi bi-check-lg"></i>
            </button>
            <button class="btn btn-sm btn-warning" hx-post="approve_pengajuan.php"
                hx-vals='{"id": "<?= $p['id'] ?>", "action": "reject"}' hx-target="#row-pengajuan-<?= $p['id'] ?>"
                hx-swap="outerHTML" title="Tolak">
                <i class="bi bi-x-lg"></i>
            </button>
        <?php endif; ?>
        <button class="btn btn-sm btn-danger ms-1" hx-post="delete_pengajuan.php" hx-vals='{"id": "<?= $p['id'] ?>"}'
            hx-target="#row-pengajuan-<?= $p['id'] ?>" hx-swap="outerHTML"
            hx-confirm="Yakin ingin menghapus pengajuan ini?">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>