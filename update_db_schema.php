<?php
require_once __DIR__ . '/db.php';

try {
    // 1. Modify users table to add nim column if it doesn't exist
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'nim'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN nim VARCHAR(20) NULL AFTER role");
        $pdo->exec("ALTER TABLE users ADD UNIQUE KEY idx_nim_unique (nim)");
        echo "Added 'nim' column to users table.<br>";
    } else {
        echo "'nim' column already exists.<br>";
    }

    // 2. Modify enum for role
    // Note: Changing ENUM in MySQL can be tricky if data exists, but we are adding values.
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user', 'mahasiswa', 'dosen') NOT NULL DEFAULT 'user'");
    echo "Updated 'role' ENUM.<br>";

    // 3. Add foreign key constraint for nim in users table referencing mahasiswa table
    // Ensure nim in users has same type/collation as in mahasiswa
    // We try to add constraint only if it doesn't exist (hard to check easily in pure SQL without query information_schema, so we try/catch)

    try {
        $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_user_nim FOREIGN KEY (nim) REFERENCES mahasiswa(nim) ON DELETE SET NULL");
        echo "Added Foreign Key constraint for 'nim' in users table.<br>";
    } catch (PDOException $e) {
        // Ignore if FK already exists or fails due to data mismatch (though we expect clean slate or compatible data)
        echo "FK creation note: " . $e->getMessage() . "<br>";
    }

    echo "Database upgrade completed successfully.";

} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
