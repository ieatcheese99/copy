<?php
session_start(); // TAMBAHKAN INI - ini yang hilang!

$db_server = "localhost";
$db_user = "root";
$db_pass = ""; // Biasanya kosong di Laragon
$db_name = "data_produk_2"; // Ganti dengan nama database Anda

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set charset untuk menghindari masalah encoding
$conn->set_charset("utf8");
?>
