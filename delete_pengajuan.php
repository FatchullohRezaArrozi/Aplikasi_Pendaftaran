<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$pengajuan_id = (int)($_POST['id'] ?? 0);

if ($pengajuan_id === 0) {
    http_response_code(400);
    exit;
}

// Hapus pengajuan
$stmt = $pdo->prepare('DELETE FROM pengajuan WHERE id = ?');
$stmt->execute([$pengajuan_id]);

// Return empty (row will be removed)
echo '';
