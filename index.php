<?php
session_start();
require_once __DIR__ . '/db.php';


function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


// Jika sudah login redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aplikasi Pengajuan Cuti Mahasiswa — Login / Registrasi</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.2"></script>
    <style>
        /* Custom Styles for Aesthetic Look */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%);
            /* Alternative aesthetic option (uncomment to switch): */
            /* background: linear-gradient(135deg, #0093E9 0%, #80D0C7 100%); */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: none;
        }

        .card-header-text {
            color: #2d3748;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .form-label {
            font-weight: 500;
            color: #4a5568;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            border-color: #667eea;
            background-color: #fff;
        }

        /* Password Toggle Button */
        .password-toggle {
            border-top-right-radius: 12px !important;
            border-bottom-right-radius: 12px !important;
            border-left: none;
            background-color: #f8fafc;
            border-color: #e2e8f0;
            cursor: pointer;
            z-index: 10;
        }

        .password-toggle:hover {
            background-color: #e2e8f0;
        }

        .input-group>.form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .btn-primary {
            background: linear-gradient(to right, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(102, 126, 234, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(102, 126, 234, 0.3);
            background: linear-gradient(to right, #5a6fd6, #673ab7);
        }

        .btn-success {
            background: linear-gradient(to right, #0ba360, #3cba92);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(11, 163, 96, 0.25);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(11, 163, 96, 0.3);
            background: linear-gradient(to right, #0a9256, #32a685);
        }

        .divider {
            border-right: 1px solid #e2e8f0;
        }

        @media (max-width: 768px) {
            .divider {
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 2rem;
                margin-bottom: 2rem;
            }
        }

        .section-title {
            position: relative;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d3748;
        }

        .section-subtitle {
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-lg-10">
                <div class="card main-card p-4 p-md-5">
                    <h2 class="text-center mb-5 card-header-text">Aplikasi Pengajuan Cuti Mahasiswa</h2>

                    <div class="row g-5">
                        <!-- Login Section -->
                        <div class="col-md-6 divider">
                            <h4 class="section-title">Login</h4>
                            <p class="section-subtitle">Masuk dengan akun yang sudah terdaftar.</p>
                            <form id="login-form" hx-post="login.php" hx-swap="outerHTML">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input name="username" class="form-control" placeholder="Masukkan username"
                                        required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <input name="password" type="password" class="form-control" id="login-password"
                                            placeholder="Masukkan password" required>
                                        <button class="btn btn-outline-secondary password-toggle" type="button"
                                            onclick="togglePassword('login-password', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="login-feedback" class="mb-3"></div>
                                <button class="btn btn-primary">Masuk Sekarang</button>
                            </form>
                        </div>

                        <!-- Register Section -->
                        <div class="col-md-6">
                            <h4 class="section-title">Registrasi</h4>
                            <p class="section-subtitle">Belum punya akun? Daftar sekarang.</p>
                            <form id="register-form" hx-post="register.php" hx-target="#register-form"
                                hx-swap="outerHTML">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input name="username" class="form-control" placeholder="Pilih username" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <input name="password" type="password" class="form-control"
                                            id="register-password" placeholder="Buat password" required>
                                        <button class="btn btn-outline-secondary password-toggle" type="button"
                                            onclick="togglePassword('register-password', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input name="namalengkap" class="form-control" placeholder="Nama lengkap sesuai KTM"
                                        required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Kode Admin (Opsional)</label>
                                    <input name="admin_key" type="password" class="form-control" 
                                        placeholder="Isi jika Anda adalah Admin">
                                    <div class="form-text" style="font-size: 0.75rem;">Kosongkan jika Anda Mahasiswa.</div>
                                </div>
                                <div id="register-feedback" class="mb-3"></div>
                                <button class="btn btn-success">Buat Akun Baru</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4 text-muted" style="font-size: 0.8rem;">
                    &copy; 2025 Universitas Example - All Rights Reserved
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>

</html>