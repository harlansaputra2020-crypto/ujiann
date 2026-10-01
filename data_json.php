<?php

header("Content-Type: application/json");

require_once "koneksi.php";

$query = mysqli_query(
    $koneksi,
    "SELECT * FROM users ORDER BY id DESC"
);

$data = [];

while ($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}

echo json_encode($data, JSON_PRETTY_PRINT);

?>