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

$current_user_id = (int)$_SESSION['user_id'];
$delete_user_id = (int)($_POST['user_id'] ?? 0);

if ($delete_user_id === 0) {
    http_response_code(400);
    exit;
}

// Prevent self-deletion
if ($delete_user_id === $current_user_id) {
    echo '<tr id="row-user-' . $delete_user_id . '">';
    echo '<td colspan="6" class="text-center text-danger">';
    echo '<i class="bi bi-exclamation-triangle"></i> Anda tidak dapat menghapus akun sendiri.';
    echo '</td>';
    echo '</tr>';
    exit;
}

// Hapus user
$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$delete_user_id]);

// Return empty (row will be removed)
echo '';
