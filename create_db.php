<?php
// Definisi parameter server
$servername = "localhost";
$username = "root";
$password = "";

// Membuat koneksi ke server MySQL
$conn = mysqli_connect($servername, $username, $password);

// Memeriksa koneksi
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Membuat database baru
$sql = "CREATE DATABASE db_nilai5a";

if (mysqli_query($conn, $sql)) {
    echo "Database db_nilai5a berhasil dibuat atau sudah tersedia!";
} else {
    echo "Error creating database: " . mysqli_error($conn);
}

// Menutup koneksi
mysqli_close($conn);
?>