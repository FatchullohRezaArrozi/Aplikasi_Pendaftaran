<?php
require_once __DIR__ . '/db.php';

try {
    $u = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $r = $pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
    $m = $pdo->query("SELECT COUNT(*) FROM mahasiswa")->fetchColumn();

    echo "Users: $u\n";
    echo "Registrations: $r\n";
    echo "Mahasiswa: $m\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
