<?php
session_start();
require_once __DIR__ . '/../config.php';

// ===============================
// AUTO LOGIN DARI REMEMBER TOKEN
// ===============================
if (!isset($_SESSION['status_user']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    $q = mysqli_query($koneksi,
        "SELECT * FROM t_user WHERE remember_token='$token'"
    );

    if (mysqli_num_rows($q) > 0) {
        $user = mysqli_fetch_assoc($q);

        // bikin session ulang
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['npp_user'] = $user['npp_user'];
        $_SESSION['role_user'] = $user['role_user'];
        $_SESSION['status_user'] = 'login';
    }
}

// ===============================
// PROTEKSI KOORDINATOR
// ===============================
if (!isset($_SESSION['status_user']) || $_SESSION['role_user'] != 'koordinator') {
    header("Location: ../login.php?pesan=belum_login");
    exit;
}
?>