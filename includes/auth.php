<?php
require_once __DIR__ . '/functions.php';

function admin_login(): bool
{
    return !empty($_SESSION['admin_id']);
}

function wajib_admin(): void
{
    if (!admin_login()) {
        redirect('login.php');
    }
}

function coba_login(string $username, string $password): bool
{
    $st = db()->prepare('SELECT * FROM admin WHERE username = ?');
    $st->execute([trim($username)]);
    $a = $st->fetch();
    if ($a && password_verify($password, $a['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $a['id'];
        $_SESSION['admin_nama'] = $a['nama'] ?: $a['username'];
        return true;
    }
    usleep(500000); // perlambat percobaan tebak password
    return false;
}
