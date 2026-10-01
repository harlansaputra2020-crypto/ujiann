<?php
session_start();

// ====== KONEKSI DATABASE ======
$conn = new mysqli("localhost", "root", "", "ujian_asts");
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
// ==============================

$namaUser = $_SESSION['nama'] ?? ($_SESSION['username'] ?? 'Administrator');
$flash = $_SESSION['pesan'] ?? ($_SESSION['success'] ?? '');
unset($_SESSION['pesan'], $_SESSION['success']);

$rows = [];
$q = $conn->query("SELECT * FROM users ORDER BY id DESC");
while ($r = $q->fetch_assoc()) $rows[] = $r;

$total = count($rows);
$laki = 0;
foreach ($rows as $r) if (stripos($r['gender'], 'laki') !== false) $laki++;
$perempuan = $total - $laki;
$pLaki = $total ? round($laki / $total * 100) : 0;

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Siswa | ASTS</title>
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
  background-image:radial-gradient(900px 400px at 85% -10%,var(--glow),transparent 60%);transition:background-color .3s}
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
.user .av{width:36px;height:36px}
.user b{display:block;font-size:13px}
.user small{color:var(--mu);font-size:11px}
.out{margin-top:10px;display:block;text-align:center;padding:11px;border-radius:11px;font-size:14px;font-weight:600;border:1px solid var(--line);color:var(--pk);transition:background .2s}
.out:hover{background:var(--sf2)}

main{flex:1;min-width:0;padding:36px 42px}
.top{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
h1{font-family:'Space Grotesk';font-size:32px;letter-spacing:-.5px}
.top p{color:var(--mu);font-size:14px;margin-top:4px}
.right{display:flex;gap:10px;align-items:center}
.chip{font-size:13px;color:var(--mu);background:var(--sf);border:1px solid var(--line);padding:9px 14px;border-radius:999px}
.tg{width:40px;height:40px;border-radius:50%;background:var(--sf);border:1px solid var(--line);font-size:16px}
.tg:hover{border-color:var(--ac)}
.flash{margin-top:22px;padding:13px 18px;border-radius:12px;font-size:14px;font-weight:500;background:rgba(34,211,238,.1);border:1px solid rgba(34,211,238,.35);color:var(--ac2)}
[data-theme=light] .flash{color:#0e7490}

.grid{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:16px;margin-top:24px}
.card{background:var(--sf);border:1px solid var(--line);border-radius:18px;padding:22px 24px}
.card span{font-size:13px;color:var(--mu)}
.num{font-family:'Space Grotesk';font-size:44px;font-weight:700;line-height:1;margin-top:8px}
.hero{background:linear-gradient(135deg,var(--ac),#4f46e5 60%,#0ea5c6);border:none;color:#fff;position:relative;overflow:hidden}
.hero span{color:rgba(255,255,255,.75)}
.hero .num{font-size:60px}
.hero::after{content:"";position:absolute;right:-40px;top:-40px;width:170px;height:170px;border-radius:50%;border:28px solid rgba(255,255,255,.1)}
.ring{display:flex;align-items:center;gap:18px}
.donut{width:78px;height:78px;border-radius:50%;flex-shrink:0;background:conic-gradient(var(--ac2) 0 <?= $pLaki ?>%,var(--pk) 0);display:grid;place-items:center}
.donut i{width:54px;height:54px;border-radius:50%;background:var(--sf);display:block}
.dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:6px}

.panel{background:var(--sf);border:1px solid var(--line);border-radius:18px;margin-top:18px;overflow:hidden}
.ph{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;padding:22px 26px}
.ph h2{font-size:18px}
.ph small{color:var(--mu);font-size:13px}
.tools{display:flex;gap:10px}
.tools input{width:240px;padding:11px 15px;border-radius:11px;border:1px solid var(--line);background:var(--sf2);color:var(--tx);font:inherit;font-size:14px}
.btn{padding:11px 18px;border-radius:11px;font-weight:600;font-size:14px;color:#fff;background:linear-gradient(135deg,var(--ac),#5b8cff);box-shadow:0 6px 18px -6px var(--ac);transition:transform .15s}
.btn:hover{transform:translateY(-1px)}
.wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:900px}
th{text-align:left;font-size:12px;font-weight:600;color:var(--mu);padding:12px 26px;background:var(--sf2);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
td{padding:15px 26px;font-size:14px;border-bottom:1px solid var(--line)}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:var(--sf2)}
.who{display:flex;align-items:center;gap:12px}
.av{width:38px;height:38px;border-radius:12px;display:grid;place-items:center;font-weight:700;font-size:14px;color:#fff;background:linear-gradient(135deg,var(--ac),var(--ac2));flex-shrink:0}
.who b{display:block;font-weight:600}
.who small,.mu{color:var(--mu);font-size:12px}
.tag{padding:4px 11px;border-radius:999px;font-size:12px;font-weight:600}
.tag.l{background:rgba(34,211,238,.14);color:var(--ac2)}
.tag.p{background:rgba(244,114,182,.14);color:var(--pk)}
[data-theme=light] .tag.l{color:#0e7490}
.act{display:flex;gap:8px}
.act a{padding:7px 13px;border-radius:9px;font-size:13px;font-weight:600;border:1px solid var(--line);color:var(--mu);transition:all .2s}
.act a.e:hover{border-color:var(--ac);color:var(--ac)}
.act a.d:hover{border-color:var(--pk);color:var(--pk)}
.empty{text-align:center;padding:40px;color:var(--mu)}

@media(max-width:900px){
  body{flex-direction:column}
  .side{width:100%;height:auto;position:static}
  .menu{flex-direction:row;flex-wrap:wrap}
  main{padding:24px 16px}
  .grid{grid-template-columns:1fr}
  .tools input{width:100%}
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
    <div><h1>Dashboard</h1><p>Kelola dan pantau data siswa dengan mudah.</p></div>
    <div class="right">
      <div class="chip"><?= date('d F Y') ?></div>
      <button class="tg" id="tg" aria-label="Ganti tema">◐</button>
    </div>
  </div>

  <?php if ($flash): ?><div class="flash"><?= e($flash) ?></div><?php endif; ?>

  <section class="grid">
    <div class="card hero"><span>Total siswa</span><div class="num"><?= $total ?></div></div>
    <div class="card"><span>Laki-laki</span><div class="num"><?= $laki ?></div></div>
    <div class="card ring">
      <div class="donut"><i></i></div>
      <div>
        <span>Perempuan</span>
        <div class="num" style="font-size:34px"><?= $perempuan ?></div>
        <div class="mu" style="margin-top:8px"><span class="dot" style="background:var(--ac2)"></span><?= $pLaki ?>% laki-laki</div>
      </div>
    </div>
  </section>

  <section class="panel">
    <div class="ph">
      <div><h2>Data siswa</h2><small>Daftar seluruh data siswa yang tersimpan</small></div>
      <div class="tools">
        <input type="search" id="cari" placeholder="Cari siswa..." aria-label="Cari siswa">
        <a href="tambah.php" class="btn">Tambah data</a>
      </div>
    </div>
    <div class="wrap">
      <table id="tabel">
        <thead><tr><th>No</th><th>Siswa</th><th>NISN</th><th>TTL</th><th>Gender</th><th>Email</th><th>Alamat</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="8" class="empty">Belum ada data siswa. Klik "Tambah data" untuk mulai.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $i => $r): $isL = stripos($r['gender'], 'laki') !== false; ?>
          <tr>
            <td class="mu"><?= $i + 1 ?></td>
            <td><div class="who"><span class="av"><?= e(strtoupper(mb_substr($r['name'], 0, 1))) ?></span>
              <div><b><?= e($r['name']) ?></b><small>ID <?= e($r['id']) ?></small></div></div></td>
            <td><?= e($r['nisn']) ?></td>
            <td><?= e($r['ttl']) ?></td>
            <td><span class="tag <?= $isL ? 'l' : 'p' ?>"><?= $isL ? 'Laki-laki' : 'Perempuan' ?></span></td>
            <td><?= e($r['email']) ?></td>
            <td><?= e($r['address']) ?></td>
            <td><div class="act">
              <a class="e" href="edit.php?id=<?= (int)$r['id'] ?>">Ubah</a>
              <a class="d" href="hapus.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Hapus data <?= e(addslashes($r['name'])) ?>?')">Hapus</a>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
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
document.getElementById('cari').addEventListener('input', function () {
  const k = this.value.toLowerCase();
  document.querySelectorAll('#tabel tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(k) ? '' : 'none';
  });
});
</script>
</body>
</html>