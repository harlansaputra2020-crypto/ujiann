<?php
session_start();

// ====== KONFIGURASI ======
$token      = 'hRJXt283eJtsgtrgnAqQ';
$nomorTujuan = '6285604692519';
$halamanLogin = 'login.php';
// =========================

// Ambil data user sebelum session dihapus
$nama = $_SESSION['nama'] ?? ($_SESSION['username'] ?? 'Pengguna');

// Kirim notifikasi WhatsApp (Fonnte)
$waktu = date('d-m-Y H:i:s');
$pesan = "Halo $nama,\n\nAnda telah *logout* dari ASTS Panel pada $waktu.\n\nJika ini bukan Anda, segera ganti password Anda.";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => 'https://api.fonnte.com/send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_POSTFIELDS     => [
        'target'      => $nomorTujuan,
        'message'     => $pesan,
        'countryCode' => '62',
    ],
    CURLOPT_HTTPHEADER     => ["Authorization: $token"],
]);
curl_exec($curl);
curl_close($curl);

// Hapus session setelah WA dikirim
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header("Location: $halamanLogin?pesan=logout");
exit;