<?php
require_once __DIR__ . '/db.php';

try {
    // Disable foreign key checks temporarily to avoid complex dependency issues, 
    // although CASCADE should handle it, this is safer for mass deletion.
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    // Delete all users
    $stmt = $pdo->prepare('DELETE FROM users');
    $stmt->execute();

    // Reset Auto Increment (optional but good for clean slate)
    $pdo->exec('ALTER TABLE users AUTO_INCREMENT = 1');

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    echo "Berhasil menghapus semua akun user.";
} catch (PDOException $e) {
    die("Gagal menghapus akun: " . $e->getMessage());
}
