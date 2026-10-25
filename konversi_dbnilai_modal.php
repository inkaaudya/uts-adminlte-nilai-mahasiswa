<?php
// Endpoint penyimpanan untuk modal AdminLTE 4 (respons JSON).
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respons($sukses, $pesan, $status = 200)
{
    http_response_code($status);
    echo json_encode(['success' => $sukses, 'message' => $pesan], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respons(false, 'Gunakan tombol Tambah Data pada users.php.', 405);
}

$nim = trim($_POST['nim'] ?? '');
$nama_mhsw = trim($_POST['nama_mhsw'] ?? '');
$nilai_akhir = trim($_POST['nilai_akhir'] ?? '');

// Validasi sesuai program asli Inka.
if (!preg_match('/^[0-9]{1,10}$/', $nim)) {
    respons(false, 'NIM wajib berupa angka, maksimal 10 digit.', 422);
}
if ($nama_mhsw === '' || !preg_match('/^[a-zA-Z\s]+$/', $nama_mhsw) || strlen($nama_mhsw) > 30) {
    respons(false, 'Nama wajib berisi huruf dan spasi (maksimal 30 karakter).', 422);
}
if ($nilai_akhir === '' || !preg_match('/^[0-9]+(\.[0-9]+)?$/', $nilai_akhir)) {
    respons(false, 'Nilai akhir harus berupa angka (gunakan titik untuk desimal).', 422);
}
$nilai_angka = (float) $nilai_akhir;
if ($nilai_angka < 0 || $nilai_angka > 100) {
    respons(false, 'Nilai akhir harus antara 0 sampai 100.', 422);
}

// Konversi nilai: persis mengikuti batas dan predikat pada file asli.
if ($nilai_angka <= 34.50) {
    $nilai_huruf = 'E';
    $predikat = 'Tidak Lulus';
} elseif ($nilai_angka <= 50.50) {
    $nilai_huruf = 'D';
    $predikat = 'Kurang';
} elseif ($nilai_angka <= 65.50) {
    $nilai_huruf = 'C';
    $predikat = 'Cukup';
} elseif ($nilai_angka <= 80.50) {
    $nilai_huruf = 'B';
    $predikat = 'Memuaskan';
} else {
    $nilai_huruf = 'A';
    $predikat = 'Sangat Memuaskan';
}

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/fotomhs_helper.php';
$foto = '';
try {
    $foto = unggahFotoMahasiswa($_FILES['foto'] ?? []);
} catch (RuntimeException $e) {
    respons(false, $e->getMessage(), 422);
}
$sql = 'INSERT INTO nilaimhsw (nim, namamhsw, nilaiakhir, nilaihuruf, predikat, foto) VALUES (?, ?, ?, ?, ?, ?)';
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    hapusFotoMahasiswa($foto);
    respons(false, 'Gagal menyiapkan query database.', 500);
}
mysqli_stmt_bind_param($stmt, 'ssdsss', $nim, $nama_mhsw, $nilai_angka, $nilai_huruf, $predikat, $foto);
try {
    $ok = mysqli_stmt_execute($stmt);
} catch (mysqli_sql_exception $e) {
    // 1062: NIM sudah terdaftar jika kolom NIM memiliki UNIQUE/PRIMARY KEY.
    $kode = (int) $e->getCode();
    hapusFotoMahasiswa($foto);
    mysqli_stmt_close($stmt);
    respons(false, $kode === 1062 ? 'NIM sudah terdaftar. Gunakan NIM lain.' : 'Gagal menyimpan data ke database.', $kode === 1062 ? 409 : 500);
}
mysqli_stmt_close($stmt);
if (!$ok) {
    hapusFotoMahasiswa($foto);
    respons(false, 'Gagal menyimpan data ke database.', 500);
}
respons(true, 'Data mahasiswa berhasil disimpan!');
