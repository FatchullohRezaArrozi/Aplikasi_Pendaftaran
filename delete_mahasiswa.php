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

$nim = trim((string) ($_POST['nim'] ?? ''));

if ($nim === '') {
    http_response_code(400);
    exit;
}

// Cek apakah mahasiswa memiliki pengajuan
$stmt = $pdo->prepare('SELECT COUNT(*) as count FROM pengajuan WHERE nim = ?');
$stmt->execute([$nim]);
$result = $stmt->fetch();

if ($result['count'] > 0) {
    echo '<tr id="row-' . e($nim) . '">';
    echo '<td colspan="4" class="text-center text-danger">';
    echo '<i class="bi bi-exclamation-triangle"></i> Tidak dapat menghapus mahasiswa yang memiliki data pengajuan.';
    echo '</td>';
    echo '</tr>';
    exit;
}

// Hapus mahasiswa
$stmt = $pdo->prepare('DELETE FROM mahasiswa WHERE nim = ?');
$stmt->execute([$nim]);

// Return empty (row will be removed)
echo '';
