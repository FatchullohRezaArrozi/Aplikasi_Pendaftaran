<?php
// login.php
session_start();
require_once __DIR__ . '/db.php';


function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}


$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');


if ($username === '' || $password === '') {
    echo "<form id=\"login-form\" hx-post=\"login.php\" hx-swap=\"outerHTML\">";
    echo "<div class=\"alert alert-danger\">Username dan password wajib diisi.</div>";
    // echo the rest of the form (for simplicity we re-output a minimal form)
    echo "</form>";
    exit;
}


$stmt = $pdo->prepare('SELECT id, password, namalengkap, role, nim FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch();


if (!$user || !password_verify($password, $user['password'])) {
    echo "<div class=\"alert alert-danger\">Login gagal: username atau password salah.</div>";
    echo file_get_contents(__DIR__ . '/_login_form_fragment.html');
    exit;
}


// sukses
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['namalengkap'] = $user['namalengkap'];
$_SESSION['role'] = $user['role'];
$_SESSION['nim'] = $user['nim'];


// Redirect klien HTMX ke dashboard.php. HTMX mengerti header HX-Redirect.
header('HX-Redirect: dashboard.php');
exit;