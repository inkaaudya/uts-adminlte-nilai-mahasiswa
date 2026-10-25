<?php
// Proses hapus mahasiswa, hanya menerima POST dari tombol Hapus.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak diizinkan.');
}

$nim = trim((string) ($_POST['nim'] ?? ''));
if (!preg_match('/^[0-9]{1,10}$/', $nim)) {
    http_response_code(400);
    exit('NIM tidak valid.');
}

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/fotomhs_helper.php';
try {
    $cari = mysqli_prepare($conn, 'SELECT foto FROM nilaimhsw WHERE nim = ?');
    mysqli_stmt_bind_param($cari, 's', $nim);
    mysqli_stmt_execute($cari);
    mysqli_stmt_bind_result($cari, $fotoDb);
    $adaFoto = mysqli_stmt_fetch($cari);
    mysqli_stmt_close($cari);
    $fotoHapus = $adaFoto ? $fotoDb : '';
    $stmt = mysqli_prepare($conn, 'DELETE FROM nilaimhsw WHERE nim = ?');
    mysqli_stmt_bind_param($stmt, 's', $nim);
    mysqli_stmt_execute($stmt);
    $terhapus = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    if ($terhapus > 0)
        hapusFotoMahasiswa($fotoHapus);
    header('Location: users.php?status=' . ($terhapus > 0 ? 'hapus_sukses' : 'tidak_ditemukan'));
    exit;
} catch (mysqli_sql_exception $e) {
    error_log('Hapus nilai: ' . $e->getMessage());
    header('Location: users.php?status=hapus_gagal');
    exit;
}
