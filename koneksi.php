<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "ujian_asts";

$koneksi = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if ($koneksi->connect_error) {
    die("Koneksi database gagal: " . $koneksi->connect_error);
}

$koneksi->set_charset("utf8mb4");