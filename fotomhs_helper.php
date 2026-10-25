<?php
// Untuk menjaga utilitas upload foto mahasiswa, simpan hanya path relatif di database
function unggahFotoMahasiswa(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) throw new RuntimeException('Upload foto gagal. Coba lagi.');
    if (($file['size'] ?? 0) <= 0 || $file['size'] > 2 * 1024 * 1024) throw new RuntimeException('Ukuran foto maksimal 2 MB.');
    if (!is_uploaded_file($file['tmp_name'])) throw new RuntimeException('File foto tidak valid.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ekstensi = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'][$mime] ?? null;
    if (!$ekstensi || @getimagesize($file['tmp_name']) === false) throw new RuntimeException('Foto harus JPG, PNG, atau WEBP.');
    $folder = __DIR__ . '/uploads/mahasiswa';
    if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) throw new RuntimeException('Folder foto gagal dibuat.');
    $nama = bin2hex(random_bytes(16)) . '.' . $ekstensi;
    if (!move_uploaded_file($file['tmp_name'], $folder . '/' . $nama)) throw new RuntimeException('Foto gagal disimpan.');
    return 'uploads/mahasiswa/' . $nama;
}
function hapusFotoMahasiswa(?string $path): void {
    if (!$path || !preg_match('~^uploads/mahasiswa/[a-f0-9]{32}\.(jpg|png|webp)$~', $path)) return;
    $file = __DIR__ . '/' . $path;
    if (is_file($file)) @unlink($file);
}
