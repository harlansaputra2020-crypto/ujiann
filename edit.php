<?php

session_start();

/* CEK LOGIN */
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

/* LOAD DATABASE DAN WHATSAPP */
require_once __DIR__ . "/koneksi.php";
require_once __DIR__ . "/functions/whatsapp.php";

/* Ubah 08xxx / +62xxx / 620xxx menjadi 62xxx */
function formatNomorWA($no)
{
    $no = preg_replace('/\D/', '', $no);
    if (strpos($no, '0') === 0) {
        $no = '62' . substr($no, 1);
    }
    if (strpos($no, '620') === 0) {
        $no = '62' . substr($no, 3);
    }
    return $no;
}

/* AMBIL ID */
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die("ID data tidak valid.");
}

/* AMBIL DATA SISWA */
$stmt = $koneksi->prepare("SELECT * FROM users WHERE id = ?");

if (!$stmt) {
    die("Gagal membuat query: " . $koneksi->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Data siswa dengan ID $id tidak ditemukan.");
}

$error = "";

/* PROSES UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name'] ?? '');
    $nisn    = trim($_POST['nisn'] ?? '');
    $ttl     = trim($_POST['ttl'] ?? '');
    $gender  = trim($_POST['gender'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Pertahankan isian jika terjadi error
    $data = array_merge($data, [
        'name' => $name, 'nisn' => $nisn, 'ttl' => $ttl, 'gender' => $gender,
        'email' => $email, 'phone' => $phone, 'address' => $address,
    ]);

    $nomorWA = formatNomorWA($phone);

    /* VALIDASI */
    if ($name === '' || $nisn === '' || $ttl === '' || $gender === '' || $email === '' || $phone === '' || $address === '') {

        $error = "Semua data harus diisi.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    } elseif (!preg_match('/^62\d{8,13}$/', $nomorWA)) {

        $error = "Nomor WhatsApp tidak valid. Contoh: 08123456789";

    } else {

        /* UPDATE DATA */
        $query = "
            UPDATE users SET
                name = ?,
                nisn = ?,
                ttl = ?,
                gender = ?,
                email = ?,
                phone = ?,
                address = ?
            WHERE id = ?
        ";

        $stmt = $koneksi->prepare($query);

        if (!$stmt) {

            $error = "Query update gagal: " . $koneksi->error;

        } else {

            $stmt->bind_param("sssssssi", $name, $nisn, $ttl, $gender, $email, $nomorWA, $address, $id);

            if ($stmt->execute()) {

                $stmt->close();

                $namaAdmin = $_SESSION['nama'] ?? 'Administrator';

                /* PESAN WHATSAPP */
                $pesan =
"✏️ *ASTS - SISTEM DATA SISWA*

━━━━━━━━━━━━━━━━━━
📢 *NOTIFIKASI EDIT DATA*
━━━━━━━━━━━━━━━━━━

👤 Nama:
{$name}

🆔 ID:
{$id}

🔢 NISN:
{$nisn}

🎂 TTL:
{$ttl}

⚧ Gender:
{$gender}

📧 Email:
{$email}

📱 No. WhatsApp:
{$nomorWA}

🏠 Alamat:
{$address}

━━━━━━━━━━━━━━━━━━
✅ *DATA BERHASIL DIPERBARUI*
━━━━━━━━━━━━━━━━━━

👨‍💻 Diubah oleh:
{$namaAdmin}

🕐 Waktu:
" . date('d-m-Y H:i:s') . "

━━━━━━━━━━━━━━━━━━
📚 SISTEM DATA SISWA
━━━━━━━━━━━━━━━━━━";

                /* KIRIM WHATSAPP KE NOMOR YANG DIINPUT */
                try {
                    $hasilWA = kirimWhatsApp($nomorWA, $pesan);
                } catch (Throwable $e) {
                    $hasilWA = [
                        'status'   => false,
                        'response' => $e->getMessage()
                    ];
                }

                /* CEK HASIL WHATSAPP */
                if (is_array($hasilWA) && isset($hasilWA['status']) && $hasilWA['status'] === true) {

                    $_SESSION['wa_notif'] = [
                        'status'  => true,
                        'message' => 'Data berhasil diperbarui dan notifikasi WhatsApp berhasil dikirim.'
                    ];
                    unset($_SESSION['wa_error']);

                } else {

                    $_SESSION['wa_notif'] = [
                        'status'  => false,
                        'message' => 'Data berhasil diperbarui, tetapi notifikasi WhatsApp gagal dikirim.'
                    ];

                    $_SESSION['wa_error'] = is_array($hasilWA)
                        ? ($hasilWA['response'] ?? 'Tidak ada response dari Fonnte.')
                        : ($hasilWA ?? 'Tidak ada response dari Fonnte.');
                }

                header("Location: index.php");
                exit;

            } else {

                $error = "Data gagal diperbarui: " . $stmt->error;
                $stmt->close();
            }
        }
    }
}


$namaUser = $_SESSION['nama'] ?? ($_SESSION['username'] ?? 'Administrator');
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Data Siswa | ASTS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#0a0e1a; --sf:#121829; --sf2:#1a2236; --line:#232d45; --tx:#e8ecf6; --mu:#8893ad;
  --ac:#7c5cff; --ac2:#22d3ee; --pk:#f472b6; --glow:rgba(124,92,255,.18);
}
[data-theme=light]{
  --bg:#f4f5fa; --sf:#ffffff; --sf2:#f0f1f8; --line:#e2e4ef; --tx:#161a2b; --mu:#6b7390; --glow:rgba(124,92,255,.10);
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--tx);display:flex;min-height:100vh;
  background-image:radial-gradient(900px 400px at 85% -10%,var(--glow),transparent 60%)}
a{color:inherit;text-decoration:none}
button{font:inherit;color:inherit;cursor:pointer}
:focus-visible{outline:2px solid var(--ac2);outline-offset:2px}
.side{width:244px;background:var(--sf);border-right:1px solid var(--line);padding:26px 16px;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;flex-shrink:0}
.brand{display:flex;align-items:center;gap:12px;padding:0 8px 24px}
.logo{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,var(--ac),var(--ac2));display:grid;place-items:center;font-family:'Space Grotesk';font-weight:700;color:#fff;font-size:18px}
.brand strong{display:block;font-size:15px}
.brand small{color:var(--mu);font-size:12px}
.menu{display:flex;flex-direction:column;gap:4px}
.menu a{padding:11px 14px;border-radius:11px;font-size:14px;font-weight:500;color:var(--mu);transition:all .2s}
.menu a:hover{background:var(--sf2);color:var(--tx)}
.menu a.on{background:linear-gradient(90deg,var(--glow),transparent);color:var(--tx);box-shadow:inset 3px 0 0 var(--ac)}
.user{margin-top:auto;display:flex;align-items:center;gap:10px;padding:12px;border-radius:14px;background:var(--sf2);border:1px solid var(--line)}
.av{width:36px;height:36px;border-radius:12px;display:grid;place-items:center;font-weight:700;font-size:14px;color:#fff;background:linear-gradient(135deg,var(--ac),var(--ac2));flex-shrink:0}
.user b{display:block;font-size:13px}
.user small{color:var(--mu);font-size:11px}
.out{margin-top:10px;display:block;text-align:center;padding:11px;border-radius:11px;font-size:14px;font-weight:600;border:1px solid var(--line);color:var(--pk);transition:background .2s}
.out:hover{background:var(--sf2)}
main{flex:1;min-width:0;padding:36px 42px}
.top{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;max-width:820px}
h1{font-family:'Space Grotesk';font-size:32px;letter-spacing:-.5px}
.top p{color:var(--mu);font-size:14px;margin-top:4px}
.tg{width:40px;height:40px;border-radius:50%;background:var(--sf);border:1px solid var(--line);font-size:16px}
.tg:hover{border-color:var(--ac)}
.card{background:var(--sf);border:1px solid var(--line);border-radius:18px;margin-top:24px;max-width:820px;overflow:hidden}
.ch{padding:22px 28px;border-bottom:1px solid var(--line)}
.ch h2{font-size:18px}
.ch small{color:var(--mu);font-size:13px}
form{padding:26px 28px}
.f{margin-bottom:20px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:18px}
label{display:block;font-size:13px;font-weight:600;margin-bottom:8px}
label i{color:var(--pk);font-style:normal}
input,select,textarea{width:100%;padding:12px 15px;border-radius:11px;border:1px solid var(--line);background:var(--sf2);color:var(--tx);font:inherit;font-size:14px;transition:border-color .2s}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--ac);box-shadow:0 0 0 3px var(--glow)}
textarea{min-height:110px;resize:vertical}
.hint{display:block;margin-top:6px;font-size:12px;color:var(--mu)}
.note{padding:14px 16px;border-radius:12px;background:var(--sf2);border:1px solid var(--line);font-size:13px;color:var(--mu)}
.err{margin-bottom:20px;padding:13px 16px;border-radius:12px;font-size:14px;font-weight:500;background:rgba(244,114,182,.12);border:1px solid rgba(244,114,182,.4);color:var(--pk)}
.acts{display:flex;justify-content:flex-end;gap:10px;margin-top:24px;padding-top:22px;border-top:1px solid var(--line)}
.btn{padding:11px 20px;border-radius:11px;font-weight:600;font-size:14px;border:none}
.btn.g{background:transparent;border:1px solid var(--line);color:var(--mu)}
.btn.g:hover{background:var(--sf2);color:var(--tx)}
.btn.p{color:#fff;background:linear-gradient(135deg,var(--ac),#5b8cff);box-shadow:0 6px 18px -6px var(--ac);transition:transform .15s}
.btn.p:hover{transform:translateY(-1px)}
@media(max-width:900px){
  body{flex-direction:column}
  .side{width:100%;height:auto;position:static}
  .menu{flex-direction:row;flex-wrap:wrap}
  main{padding:24px 16px}
  .row{grid-template-columns:1fr}
}
@media(prefers-reduced-motion:reduce){*{transition:none!important}}
</style>
</head>
<body>

<aside class="side">
  <div class="brand"><div class="logo">A</div><div><strong>ASTS Panel</strong><small>Sistem data siswa</small></div></div>
  <nav class="menu">
    <a href="index.php" class="on">Dashboard</a>
    <a href="data_json.php">Data JSON</a>
    <a href="tambah.php">Tambah siswa</a>
  </nav>
  <div class="user">
    <div class="av"><?= e(strtoupper(mb_substr($namaUser, 0, 1))) ?></div>
    <div><b><?= e($namaUser) ?></b><small>Administrator</small></div>
  </div>
  <a href="logout.php" class="out">Keluar</a>
</aside>

<main>
  <div class="top">
    <div><h1>Edit data siswa</h1><p>Perbarui informasi siswa yang tersimpan.</p></div>
    <button class="tg" id="tg" aria-label="Ganti tema">◐</button>
  </div>

  <section class="card">
    <div class="ch"><h2>Informasi siswa</h2><small>Silakan ubah data yang diperlukan kemudian simpan perubahan.</small></div>
    <form method="POST" autocomplete="off">
      <?php if ($error !== ''): ?><div class="err"><?= e($error) ?></div><?php endif; ?>

      <div class="row">
        <div class="f">
          <label for="name">Nama lengkap <i>*</i></label>
          <input id="name" type="text" name="name" value="<?= e($data['name'] ?? '') ?>" placeholder="Masukkan nama lengkap" required>
        </div>
        <div class="f">
          <label for="nisn">NISN <i>*</i></label>
          <input id="nisn" type="text" name="nisn" value="<?= e($data['nisn'] ?? '') ?>" placeholder="Masukkan NISN" maxlength="10" required>
        </div>
      </div>

      <div class="row">
        <div class="f">
          <label for="ttl">Tempat / tanggal lahir <i>*</i></label>
          <input id="ttl" type="text" name="ttl" value="<?= e($data['ttl'] ?? '') ?>" placeholder="Contoh: Sumenep, 12 Januari 2008" required>
        </div>
        <div class="f">
          <label for="gender">Jenis kelamin <i>*</i></label>
          <select id="gender" name="gender" required>
            <option value="">Pilih jenis kelamin</option>
            <option value="Laki-laki" <?= ($data['gender'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="Perempuan" <?= ($data['gender'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
          </select>
        </div>
      </div>

      <div class="row">
        <div class="f">
          <label for="email">Email <i>*</i></label>
          <input id="email" type="email" name="email" value="<?= e($data['email'] ?? '') ?>" placeholder="contoh@email.com" required>
        </div>
        <div class="f">
          <label for="phone">Nomor WhatsApp <i>*</i></label>
          <input id="phone" type="tel" name="phone" value="<?= e($data['phone'] ?? '') ?>" placeholder="Contoh: 08123456789" inputmode="tel" required>
          <span class="hint">Notifikasi perubahan data dikirim ke nomor ini.</span>
        </div>
      </div>

      <div class="f">
        <label for="address">Alamat <i>*</i></label>
        <textarea id="address" name="address" placeholder="Masukkan alamat lengkap" required><?= e($data['address'] ?? '') ?></textarea>
      </div>

      <div class="note"><b>Informasi:</b> Setelah data disimpan, sistem akan mengirimkan notifikasi perubahan data melalui WhatsApp Fonnte ke nomor yang diisi di atas.</div>

      <div class="acts">
        <a href="index.php" class="btn g">Batal</a>
        <button type="submit" class="btn p">Simpan perubahan</button>
      </div>
    </form>
  </section>
</main>

<script>
const root = document.documentElement;
const saved = localStorage.getItem('tema');
if (saved) root.dataset.theme = saved;
document.getElementById('tg').onclick = () => {
  const t = root.dataset.theme === 'dark' ? 'light' : 'dark';
  root.dataset.theme = t; localStorage.setItem('tema', t);
};
</script>
</body>
</html>