<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (admin_login()) {
    redirect('admin/index.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    if (coba_login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        redirect('admin/index.php');
    }
    $error = 'Username atau password salah.';
}
$ligaNama = liga()['nama_liga'];
?><!doctype html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin — <?= h($ligaNama) ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <link href="<?= h(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="h-full flex items-center justify-center p-4 bg-gradient-to-br from-slate-50 via-slate-100 to-indigo-50/30 text-slate-800 antialiased font-sans">

<div class="w-full max-w-md">
    <!-- Card Container -->
    <div class="bg-white rounded-3xl p-8 sm:p-10 border border-slate-200/80 shadow-xl shadow-slate-200/50">
        <!-- Logo / Icon -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-600/30 mb-4">
                <i class="bi bi-grid-3x3-gap-fill text-2xl"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight"><?= h($ligaNama) ?></h1>
            <p class="text-xs font-medium text-slate-400 mt-1">Panel Masuk Administrator</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-sm"></i>
                <span><?= h($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Username</label>
                <div class="relative">
                    <i class="bi bi-person absolute left-3.5 top-2.5 text-slate-400 text-sm"></i>
                    <input name="username" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-10 pr-3.5 text-slate-800 form-input-focus placeholder-slate-400" placeholder="admin" required autofocus>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
                <div class="relative">
                    <i class="bi bi-lock absolute left-3.5 top-2.5 text-slate-400 text-sm"></i>
                    <input type="password" name="password" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-10 pr-3.5 text-slate-800 form-input-focus placeholder-slate-400" placeholder="••••••••" required>
                </div>
            </div>

            <button class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-md shadow-indigo-600/25 flex items-center justify-center gap-2 mt-2">
                <span>Masuk ke Dashboard</span>
                <i class="bi bi-arrow-right text-xs"></i>
            </button>
        </form>

        <div class="text-center mt-6 pt-6 border-t border-slate-100">
            <a href="<?= h(url('index.php')) ?>" class="text-xs font-medium text-slate-500 hover:text-indigo-600 transition-colors inline-flex items-center gap-1.5">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali ke Halaman Publik</span>
            </a>
        </div>
    </div>
</div>

</body>
</html>
