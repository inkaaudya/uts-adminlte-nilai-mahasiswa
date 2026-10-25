# Sistem Informasi Nilai Mahasiswa — AdminLTE 4
# UTS Praktikum Pemrograman Web

## Deskripsi Proyek

Sistem Informasi Nilai Mahasiswa merupakan aplikasi berbasis web yang dikembangkan menggunakan PHP, MySQL, dan template AdminLTE 4. Aplikasi ini digunakan untuk mengelola serta menampilkan data nilai mahasiswa dengan berbagai fitur pengolahan data.

Proyek ini dikembangkan untuk memenuhi tugas Ujian Tengah Semester (UTS).

## Fitur Aplikasi

1. **CRUD Data Mahasiswa** — Menambahkan, menampilkan, mengubah, dan menghapus data nilai mahasiswa.
2. **Foto Mahasiswa** — Menampilkan foto mahasiswa di sebelah nama serta mendukung pengunggahan foto.
3. **Searching** — Melakukan pencarian mahasiswa berdasarkan nama.
4. **Filtering** — Menyaring data mahasiswa berdasarkan predikat.
5. **Sorting** — Mengurutkan nilai akhir secara ascending dan descending.
6. **Pagination** — Menampilkan 5, 10, atau 15 data per halaman, dengan tombol First, Previous, Next, dan Last.
7. **Validasi dan Notifikasi** — Memberikan validasi input serta notifikasi ketika proses pengelolaan data dilakukan.

## Teknologi yang Digunakan

- PHP
- MySQL
- HTML, CSS, dan JavaScript
- Bootstrap 5
- AdminLTE 4
- Laragon

## Cara Menjalankan Aplikasi

1. Download atau clone repository proyek ini.
2. Pindahkan folder proyek ke direktori `C:\laragon\www` untuk Laragon atau `C:\xampp\htdocs` untuk XAMPP.
3. Jalankan Apache dan MySQL.
4. Buka phpMyAdmin.
5. Buat database dengan nama `db_nilai5a`.
6. Import file SQL yang tersedia di folder `database`.
7. Sesuaikan konfigurasi koneksi database pada `connect.php` dengan pengaturan lokal.
8. Jalankan aplikasi melalui browser:

   `http://localhost/tugasnilai_adminlte/users.php`

## Database

Database yang digunakan adalah `db_nilai5a`, dengan tabel utama `nilaimhsw`.

File ekspor database disertakan di folder `database` untuk memudahkan proses instalasi dan pengujian aplikasi.


## Identitas Pengembang

**Nama:** Inka Audya Miranti  
**NIM:** 3202416005  
**Program Studi:** D3 Teknik Informatika  
**Jurusan:** Teknik Elektro  
**Institusi:** Politeknik Negeri Pontianak
  

## Keterangan

Aplikasi dikembangkan dengan melakukan kustomisasi template AdminLTE serta menambahkan fitur pengelolaan data mahasiswa sesuai ketentuan tugas UTS.