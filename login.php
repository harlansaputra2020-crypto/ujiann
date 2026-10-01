<?php
session_start();

require_once __DIR__ . "/koneksi.php";
require_once __DIR__ . "/functions/whatsapp.php";

// Jika sudah login
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: index.php");
    exit;
}

$error = "";
$info  = (($_GET['pesan'] ?? '') === 'logout') ? 'Logout berhasil dan notifikasi WhatsApp telah dikirim.' : '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username/email dan password wajib diisi.";
    } else {

        $stmt = $koneksi->prepare("
            SELECT id, nama, username, email, no_wa, password
            FROM users_login
            WHERE username = ? OR email = ?
            LIMIT 1
        ");

        if (!$stmt) {
            $error = "Terjadi kesalahan database: " . $koneksi->error;
        } else {

            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {

                    session_regenerate_id(true);

                    $_SESSION['login']    = true;
                    $_SESSION['id']       = $user['id'];
                    $_SESSION['nama']     = $user['nama'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email']    = $user['email'];
                    $_SESSION['no_wa']    = $user['no_wa'] ?? '';

                    $pesan =
"📌 *NOTIFIKASI LOGIN SISTEM*

Halo *{$user['nama']}*,

Akun Anda baru saja login ke Sistem Pendataan Siswa ASTS.

👤 Username: {$user['username']}
📧 Email: {$user['email']}
🕐 Waktu: " . date('d-m-Y H:i:s') . "

Jika aktivitas ini bukan dilakukan oleh Anda, segera amankan akun Anda atau hubungi administrator.";

                    $nomorWA = trim($user['no_wa'] ?? '');

                    if ($nomorWA === '' && defined('FONNTE_TARGET')) {
                        $nomorWA = FONNTE_TARGET;
                    }

                    if ($nomorWA !== '') {
                        try {
                            $hasilWA = kirimWhatsApp($nomorWA, $pesan);

                            if (is_array($hasilWA) && isset($hasilWA['status']) && $hasilWA['status'] === true) {
                                $_SESSION['wa_notif'] = [
                                    'status'  => true,
                                    'message' => 'Login berhasil dan notifikasi WhatsApp telah dikirim.'
                                ];
                                unset($_SESSION['wa_error']);
                            } else {
                                $_SESSION['wa_notif'] = [
                                    'status'  => false,
                                    'message' => 'Login berhasil, namun notifikasi WhatsApp gagal terkirim.'
                                ];
                                $_SESSION['wa_error'] = $hasilWA['response'] ?? 'Tidak ada respon dari layanan WhatsApp.';
                            }
                        } catch (Throwable $e) {
                            $_SESSION['wa_notif'] = [
                                'status'  => false,
                                'message' => 'Login berhasil, terjadi kesalahan sistem pengiriman WhatsApp.'
                            ];
                            $_SESSION['wa_error'] = $e->getMessage();
                        }
                    } else {
                        $_SESSION['wa_notif'] = [
                            'status'  => false,
                            'message' => 'Login berhasil, nomor WhatsApp tidak tersedia.'
                        ];
                        $_SESSION['wa_error'] = 'Nomor WhatsApp user kosong.';
                    }

                    $stmt->close();
                    header("Location: index.php");
                    exit;

                } else {
                    $error = "Password yang Anda masukkan salah.";
                }

            } else {
                $error = "Username atau email tidak terdaftar.";
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login | ASTS Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#0a0e1a; --sf:#121829; --sf2:#1a2236; --line:#232d45; --tx:#e8ecf6; --mu:#8893ad;
  --ac:#7c5cff; --ac2:#22d3ee; --pk:#f472b6; --glow:rgba(124,92,255,.22);
}
[data-theme=light]{
  --bg:#f4f5fa; --sf:#ffffff; --sf2:#f0f1f8; --line:#e2e4ef; --tx:#161a2b; --mu:#6b7390; --glow:rgba(124,92,255,.12);
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--tx);min-height:100vh;display:grid;place-items:center;padding:24px;
  background-image:radial-gradient(800px 420px at 80% -10%,var(--glow),transparent 60%),radial-gradient(600px 400px at 0% 110%,rgba(34,211,238,.10),transparent 60%)}
a{color:var(--ac)}
button{font:inherit;color:inherit;cursor:pointer}
:focus-visible{outline:2px solid var(--ac2);outline-offset:2px}
.tg{position:fixed;top:20px;right:20px;width:40px;height:40px;border-radius:50%;background:var(--sf);border:1px solid var(--line);font-size:16px}
.tg:hover{border-color:var(--ac)}
.box{width:100%;max-width:420px}
.brand{text-align:center;margin-bottom:24px}
.logo{width:56px;height:56px;border-radius:16px;margin:0 auto 14px;background:linear-gradient(135deg,var(--ac),var(--ac2));display:grid;place-items:center;font-family:'Space Grotesk';font-weight:700;color:#fff;font-size:24px;box-shadow:0 10px 28px -8px var(--ac)}
.brand h1{font-family:'Space Grotesk';font-size:28px;letter-spacing:-.5px}
.brand p{color:var(--mu);font-size:14px;margin-top:4px}
.card{background:var(--sf);border:1px solid var(--line);border-radius:20px;padding:30px}
.card h2{font-size:20px}
.card .sub{color:var(--mu);font-size:13px;margin:4px 0 22px}
.msg{margin-bottom:18px;padding:12px 15px;border-radius:12px;font-size:13px;font-weight:500}
.msg.ok{background:rgba(34,211,238,.1);border:1px solid rgba(34,211,238,.35);color:var(--ac2)}
[data-theme=light] .msg.ok{color:#0e7490}
.msg.er{background:rgba(244,114,182,.12);border:1px solid rgba(244,114,182,.4);color:var(--pk)}
.f{margin-bottom:18px}
label{display:block;font-size:13px;font-weight:600;margin-bottom:8px}
.in{position:relative}
input{width:100%;padding:13px 15px;border-radius:11px;border:1px solid var(--line);background:var(--sf2);color:var(--tx);font:inherit;font-size:14px;transition:border-color .2s}
input:focus{outline:none;border-color:var(--ac);box-shadow:0 0 0 3px var(--glow)}
.in input{padding-right:100px}
.eye{position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:12px;font-weight:600;color:var(--mu);padding:6px 8px;border-radius:8px}
.eye:hover{color:var(--tx);background:var(--sf)}
.go{width:100%;margin-top:6px;padding:13px;border:none;border-radius:11px;font-weight:700;font-size:14px;color:#fff;background:linear-gradient(135deg,var(--ac),#5b8cff);box-shadow:0 8px 20px -8px var(--ac);transition:transform .15s}
.go:hover{transform:translateY(-1px)}
.alt{text-align:center;margin-top:20px;font-size:13px;color:var(--mu)}
.alt a{font-weight:600;text-decoration:none}
.alt a:hover{text-decoration:underline}
.foot{text-align:center;margin-top:22px;font-size:12px;color:var(--mu)}
@media(prefers-reduced-motion:reduce){*{transition:none!important}}
</style>
</head>
<body>
<button class="tg" id="tg" aria-label="Ganti tema">◐</button>

<div class="box">
  <div class="brand">
    <div class="logo">A</div>
    <h1>ASTS Portal</h1>
    <p>Sistem pendataan siswa</p>
  </div>

  <div class="card">
    <h2>Selamat datang kembali</h2>
    <p class="sub">Silakan masuk dengan akun Anda untuk melanjutkan.</p>

    <?php if ($info): ?><div class="msg ok"><?= htmlspecialchars($info) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="msg er"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" action="login.php" autocomplete="off">
      <div class="f">
        <label for="username">Username atau email</label>
        <input type="text" id="username" name="username" placeholder="Masukkan username/email" autocomplete="username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div class="f">
        <label for="password">Password</label>
        <div class="in">
          <input type="password" id="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
          <button type="button" class="eye" id="eye" aria-label="Tampilkan password">Lihat</button>
        </div>
      </div>
      <button type="submit" class="go">Masuk sekarang</button>
    </form>

    <p class="alt">Belum punya akun? <a href="register.php">Daftar akun baru</a></p>
  </div>

  <p class="foot">&copy; <?= date('Y') ?> ASTS Portal System</p>
</div>

<script>
const root = document.documentElement;
const saved = localStorage.getItem('tema');
if (saved) root.dataset.theme = saved;
document.getElementById('tg').onclick = () => {
  const t = root.dataset.theme === 'dark' ? 'light' : 'dark';
  root.dataset.theme = t; localStorage.setItem('tema', t);
};
const p = document.getElementById('password'), eye = document.getElementById('eye');
eye.onclick = () => {
  const show = p.type === 'password';
  p.type = show ? 'text' : 'password';
  eye.textContent = show ? 'Sembunyikan' : 'Lihat';
};
</script>
</body>
</html>