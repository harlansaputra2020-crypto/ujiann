<?php

session_start();

/*
|--------------------------------------------------------------------------
| CEK LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['login']) ||
    $_SESSION['login'] !== true
) {
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| LOAD DATABASE DAN WHATSAPP
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/koneksi.php";
require_once __DIR__ . "/functions/whatsapp.php";


/*
|--------------------------------------------------------------------------
| AMBIL ID
|--------------------------------------------------------------------------
*/

$id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($id <= 0) {

    $_SESSION['wa_notif'] = [
        'status' => false,
        'message' => 'ID data tidak valid.'
    ];

    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA SISWA SEBELUM DIHAPUS
|--------------------------------------------------------------------------
*/

$stmt = $koneksi->prepare(
    "SELECT * FROM users WHERE id = ?"
);


if (!$stmt) {

    $_SESSION['wa_notif'] = [
        'status' => false,
        'message' => 'Gagal mengambil data siswa.'
    ];

    header("Location: index.php");
    exit;
}


$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();


$result = $stmt->get_result();

$data = $result->fetch_assoc();


$stmt->close();


/*
|--------------------------------------------------------------------------
| CEK DATA
|--------------------------------------------------------------------------
*/

if (!$data) {

    $_SESSION['wa_notif'] = [
        'status' => false,
        'message' => 'Data siswa tidak ditemukan.'
    ];

    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| SIMPAN DATA UNTUK PESAN WHATSAPP
|--------------------------------------------------------------------------
*/

$namaSiswa = $data['name'] ?? '-';
$nisn = $data['nisn'] ?? '-';
$ttl = $data['ttl'] ?? '-';
$gender = $data['gender'] ?? '-';
$email = $data['email'] ?? '-';
$address = $data['address'] ?? '-';


/*
|--------------------------------------------------------------------------
| HAPUS DATA
|--------------------------------------------------------------------------
*/

$stmt = $koneksi->prepare(
    "DELETE FROM users WHERE id = ?"
);


if (!$stmt) {

    $_SESSION['wa_notif'] = [
        'status' => false,
        'message' => 'Query hapus gagal dibuat.'
    ];

    header("Location: index.php");
    exit;
}


$stmt->bind_param(
    "i",
    $id
);


/*
|--------------------------------------------------------------------------
| EKSEKUSI HAPUS
|--------------------------------------------------------------------------
*/

if ($stmt->execute()) {

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | NAMA ADMIN
    |--------------------------------------------------------------------------
    */

    $namaAdmin =
        $_SESSION['nama']
        ?? 'Administrator';


    /*
    |--------------------------------------------------------------------------
    | PESAN WHATSAPP
    |--------------------------------------------------------------------------
    */

    $pesan =
"🗑️ *ASTS SYSTEM*

━━━━━━━━━━━━━━━━━━
📢 *NOTIFIKASI HAPUS DATA*
━━━━━━━━━━━━━━━━━━

👤 Nama: {$namaSiswa}
🆔 ID: {$id}
🔢 NISN: {$nisn}
🎂 TTL: {$ttl}
⚧ Gender: {$gender}
📧 Email: {$email}
🏠 Alamat: {$address}

━━━━━━━━━━━━━━━━━━
❌ *DATA TELAH DIHAPUS*
━━━━━━━━━━━━━━━━━━

👨‍💻 Dihapus oleh:
{$namaAdmin}

🕐 Waktu:
" . date('d-m-Y H:i:s') . "

━━━━━━━━━━━━━━━━━━
🚀 ASTS SPACE PANEL
━━━━━━━━━━━━━━━━━━";


    /*
    |--------------------------------------------------------------------------
    | KIRIM WHATSAPP
    |--------------------------------------------------------------------------
    */

    $hasilWA = kirimWhatsApp(
        FONNTE_TARGET,
        $pesan
    );


    /*
    |--------------------------------------------------------------------------
    | CEK HASIL WHATSAPP
    |--------------------------------------------------------------------------
    */

    if (
        is_array($hasilWA) &&
        isset($hasilWA['status']) &&
        $hasilWA['status'] === true
    ) {

        $_SESSION['wa_notif'] = [

            'status' => true,

            'message' =>
                'Data siswa berhasil dihapus dan notifikasi WhatsApp berhasil dikirim.'

        ];

        unset(
            $_SESSION['wa_error']
        );

    } else {

        $_SESSION['wa_notif'] = [

            'status' => false,

            'message' =>
                'Data siswa berhasil dihapus, tetapi notifikasi WhatsApp gagal dikirim.'

        ];


        /*
        |--------------------------------------------------------------------------
        | SIMPAN ERROR FONNTE
        |--------------------------------------------------------------------------
        */

        $_SESSION['wa_error'] =
            is_array($hasilWA)
                ? (
                    $hasilWA['response']
                    ?? 'Tidak ada response dari Fonnte.'
                )
                : (
                    $hasilWA
                    ?? 'Tidak ada response dari Fonnte.'
                );
    }


    /*
    |--------------------------------------------------------------------------
    | KEMBALI KE INDEX
    |--------------------------------------------------------------------------
    */

    header(
        "Location: index.php"
    );

    exit;


} else {

    $error =
        $stmt->error;

    $stmt->close();


    $_SESSION['wa_notif'] = [

        'status' => false,

        'message' =>
            'Data gagal dihapus: ' . $error

    ];


    header(
        "Location: index.php"
    );

    exit;
}