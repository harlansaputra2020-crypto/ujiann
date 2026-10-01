<?php

session_start();

require_once __DIR__ . "/koneksi.php";
require_once __DIR__ . "/functions/whatsapp.php";


/*
|--------------------------------------------------------------------------
| JIKA SUDAH LOGIN
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['login']) &&
    $_SESSION['login'] === true
) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| VARIABEL
|--------------------------------------------------------------------------
*/

$error = "";

$nama = "";
$username = "";
$email = "";
$no_wa = "";


/*
|--------------------------------------------------------------------------
| PROSES REGISTER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_wa = trim($_POST['no_wa'] ?? '');

    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

    if (
        $nama === '' ||
        $username === '' ||
        $email === '' ||
        $no_wa === '' ||
        $password === '' ||
        $password_confirm === ''
    ) {

        $error = "Semua data wajib diisi.";

    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $error = "Format email tidak valid.";

    } elseif (
        !preg_match('/^[a-zA-Z0-9_]+$/', $username)
    ) {

        $error =
            "Username hanya boleh menggunakan huruf, angka, dan underscore.";

    } elseif (
        strlen($username) < 3
    ) {

        $error = "Username minimal 3 karakter.";

    } elseif (
        strlen($password) < 6
    ) {

        $error = "Password minimal 6 karakter.";

    } elseif (
        $password !== $password_confirm
    ) {

        $error = "Konfirmasi password tidak sama.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | CEK USERNAME DAN EMAIL
        |--------------------------------------------------------------------------
        */

        $stmt = $koneksi->prepare(
            "SELECT id
             FROM users_login
             WHERE username = ?
                OR email = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $error =
                "Query pengecekan akun gagal: " .
                $koneksi->error;

        } else {

            $stmt->bind_param(
                "ss",
                $username,
                $email
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $akunLama = $result->fetch_assoc();

            $stmt->close();


            /*
            |--------------------------------------------------------------------------
            | CEK DUPLIKAT
            |--------------------------------------------------------------------------
            */

            if ($akunLama) {

                $stmt = $koneksi->prepare(
                    "SELECT id
                     FROM users_login
                     WHERE username = ?
                     LIMIT 1"
                );

                $stmt->bind_param(
                    "s",
                    $username
                );

                $stmt->execute();

                $result = $stmt->get_result();

                $usernameExists =
                    $result->num_rows > 0;

                $stmt->close();


                if ($usernameExists) {

                    $error =
                        "Username sudah digunakan.";

                } else {

                    $error =
                        "Email sudah terdaftar.";

                }

            } else {


                /*
                |--------------------------------------------------------------------------
                | HASH PASSWORD
                |--------------------------------------------------------------------------
                */

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                /*
                |--------------------------------------------------------------------------
                | SIMPAN USER
                |--------------------------------------------------------------------------
                */

                $stmt = $koneksi->prepare(
                    "INSERT INTO users_login
                    (
                        nama,
                        username,
                        email,
                        no_wa,
                        password
                    )
                    VALUES
                    (?, ?, ?, ?, ?)"
                );


                if (!$stmt) {

                    $error =
                        "Query register gagal: " .
                        $koneksi->error;

                } else {

                    $stmt->bind_param(
                        "sssss",
                        $nama,
                        $username,
                        $email,
                        $no_wa,
                        $passwordHash
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | JALANKAN REGISTER
                    |--------------------------------------------------------------------------
                    */

                    if ($stmt->execute()) {

                        $userId = $stmt->insert_id;

                        $stmt->close();


                        /*
                        |--------------------------------------------------------------------------
                        | PESAN WHATSAPP
                        |--------------------------------------------------------------------------
                        */

                        $pesan =
"📢 *ASTS SYSTEM*

━━━━━━━━━━━━━━━━━━
*NOTIFIKASI REGISTRASI*
━━━━━━━━━━━━━━━━━━

👤 Nama:
{$nama}

🔑 Username:
{$username}

🆔 ID User:
{$userId}

📧 Email:
{$email}

📱 Nomor WhatsApp:
{$no_wa}

━━━━━━━━━━━━━━━━━━
✅ *AKUN BERHASIL DIBUAT*
━━━━━━━━━━━━━━━━━━

🕐 Waktu:
" . date('d-m-Y H:i:s') . "

━━━━━━━━━━━━━━━━━━
ASTS DATA SYSTEM
━━━━━━━━━━━━━━━━━━";


                        /*
                        |--------------------------------------------------------------------------
                        | KIRIM WHATSAPP
                        |--------------------------------------------------------------------------
                        */

                        $hasilWA = null;

                        try {

                            $hasilWA = kirimWhatsApp(
                                $no_wa,
                                $pesan
                            );

                        } catch (Throwable $e) {

                            $hasilWA = [

                                'status' => false,

                                'response' =>
                                    $e->getMessage()

                            ];

                        }


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
                                    'Registrasi berhasil dan notifikasi WhatsApp berhasil dikirim.'

                            ];

                            unset(
                                $_SESSION['wa_error']
                            );

                        } else {

                            $_SESSION['wa_notif'] = [

                                'status' => false,

                                'message' =>
                                    'Registrasi berhasil, tetapi notifikasi WhatsApp gagal dikirim.'

                            ];

                            $_SESSION['wa_error'] =
                                is_array($hasilWA)
                                    ? (
                                        $hasilWA['response']
                                        ?? 'Tidak ada response dari Fonnte.'
                                    )
                                    : 'Tidak ada response dari Fonnte.';

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | REDIRECT KE LOGIN
                        |--------------------------------------------------------------------------
                        */

                        header("Location: login.php");

                        exit;


                    } else {

                        $error =
                            "Registrasi gagal: " .
                            $stmt->error;

                        $stmt->close();

                    }
                }
            }
        }
    }
}

?>


<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Register | ASTS
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px 20px;

            font-family:
                'Segoe UI',
                Arial,
                sans-serif;

            color: #e0e7ff;

            background:

                radial-gradient(
                    circle at 10% 15%,
                    #4338ca 0%,
                    transparent 28%
                ),

                radial-gradient(
                    circle at 90% 85%,
                    #7e22ce 0%,
                    transparent 30%
                ),

                radial-gradient(
                    circle at 80% 10%,
                    #2563eb 0%,
                    transparent 22%
                ),

                linear-gradient(
                    135deg,
                    #080b20,
                    #111433,
                    #0b1028
                );

            overflow-x: hidden;
        }


        /* BACKGROUND DECORATION */

        body::before {

            content: "";

            position: fixed;

            width: 300px;

            height: 300px;

            border-radius: 50%;

            background:
                rgba(99,102,241,.12);

            filter: blur(60px);

            top: -100px;

            left: -100px;

            pointer-events: none;
        }


        body::after {

            content: "";

            position: fixed;

            width: 350px;

            height: 350px;

            border-radius: 50%;

            background:
                rgba(168,85,247,.12);

            filter: blur(70px);

            right: -120px;

            bottom: -120px;

            pointer-events: none;
        }


        /* CONTAINER */

        .register-wrapper {

            width: 100%;

            max-width: 980px;

            position: relative;

            z-index: 2;

            display: grid;

            grid-template-columns: 0.9fr 1.1fr;

            background:
                rgba(17,20,51,.92);

            border:
                1px solid #373b78;

            border-radius: 22px;

            overflow: hidden;

            box-shadow:
                0 25px 70px
                rgba(0,0,0,.45),

                0 0 40px
                rgba(99,102,241,.15);
        }


        /* LEFT SIDE */

        .register-info {

            padding: 45px 35px;

            display: flex;

            flex-direction: column;

            justify-content: center;

            background:

                linear-gradient(
                    145deg,
                    rgba(49,46,129,.8),
                    rgba(88,28,135,.55)
                );

            border-right:
                1px solid #373b78;

            position: relative;

            overflow: hidden;
        }


        .register-info::after {

            content: "";

            position: absolute;

            width: 200px;

            height: 200px;

            border-radius: 50%;

            border:
                1px solid
                rgba(196,181,253,.15);

            right: -80px;

            bottom: -80px;

            box-shadow:
                0 0 0 35px rgba(196,181,253,.04),
                0 0 0 70px rgba(196,181,253,.025);
        }


        .brand-icon {

            width: 65px;

            height: 65px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 17px;

            background:
                linear-gradient(
                    135deg,
                    #6366f1,
                    #8b5cf6
                );

            border:
                1px solid #a5b4fc;

            font-size: 28px;

            margin-bottom: 22px;

            box-shadow:
                0 10px 30px
                rgba(99,102,241,.35);
        }


        .register-info h1 {

            font-size: 30px;

            line-height: 1.2;

            color: #ffffff;

            margin-bottom: 13px;
        }


        .register-info h1 span {

            color: #c4b5fd;
        }


        .register-info > p {

            color: #cbd5e1;

            font-size: 14px;

            line-height: 1.7;

            max-width: 350px;

            margin-bottom: 30px;
        }


        .feature-list {

            display: flex;

            flex-direction: column;

            gap: 15px;
        }


        .feature {

            display: flex;

            align-items: center;

            gap: 12px;

            color: #dbeafe;

            font-size: 13px;
        }


        .feature-icon {

            width: 34px;

            height: 34px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            background:
                rgba(129,140,248,.15);

            border:
                1px solid
                rgba(129,140,248,.35);

            color: #c4b5fd;
        }


        /* RIGHT SIDE */

        .register-form {

            padding: 42px 40px;

            background:
                linear-gradient(
                    145deg,
                    #171a3b,
                    #0f112c
                );
        }


        .form-title {

            margin-bottom: 27px;
        }


        .form-title h2 {

            color: #f1f5f9;

            font-size: 24px;

            margin-bottom: 6px;
        }


        .form-title p {

            color: #94a3b8;

            font-size: 13px;
        }


        /* ERROR */

        .error {

            background:
                rgba(127,29,29,.35);

            border:
                1px solid #ef4444;

            color: #fecaca;

            padding: 13px 15px;

            margin-bottom: 20px;

            border-radius: 10px;

            font-size: 12px;

            line-height: 1.5;
        }


        /* FORM */

        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 18px;
        }


        .form-group {

            display: flex;

            flex-direction: column;

            gap: 7px;
        }


        .full {

            grid-column:
                1 / -1;
        }


        label {

            color: #c4b5fd;

            font-size: 12px;

            font-weight: 700;
        }


        .required {

            color: #f472b6;
        }


        input {

            width: 100%;

            padding: 12px 14px;

            border-radius: 9px;

            border:
                1px solid #373b78;

            background:
                #080b20;

            color: #f8fafc;

            font-family: inherit;

            font-size: 13px;

            outline: none;

            transition: .2s;
        }


        input::placeholder {

            color: #64748b;
        }


        input:hover {

            border-color:
                #6366f1;
        }


        input:focus {

            border-color:
                #818cf8;

            background:
                #0c1030;

            box-shadow:
                0 0 0 3px
                rgba(99,102,241,.15),

                0 0 15px
                rgba(99,102,241,.1);
        }


        /* PASSWORD ROW */

        .password-strength {

            font-size: 10px;

            color: #64748b;

            margin-top: 1px;
        }


        /* BUTTON */

        .buttons {

            display: flex;

            gap: 10px;

            margin-top: 25px;
        }


        .btn {

            flex: 1;

            padding: 13px 18px;

            border-radius: 9px;

            text-decoration: none;

            font-family: inherit;

            font-size: 13px;

            font-weight: 700;

            text-align: center;

            cursor: pointer;

            transition: .2s;
        }


        .btn-back {

            background:
                #1e1b4b;

            color:
                #c4b5fd;

            border:
                1px solid #4338ca;
        }


        .btn-back:hover {

            background:
                #312e81;

            transform:
                translateY(-1px);
        }


        .btn-register {

            border:
                1px solid #a78bfa;

            color: white;

            background:
                linear-gradient(
                    135deg,
                    #6366f1,
                    #8b5cf6
                );

            box-shadow:
                0 7px 20px
                rgba(99,102,241,.25);
        }


        .btn-register:hover {

            transform:
                translateY(-2px);

            background:
                linear-gradient(
                    135deg,
                    #818cf8,
                    #a78bfa
                );

            box-shadow:
                0 10px 25px
                rgba(139,92,246,.4);
        }


        /* LOGIN */

        .login-link {

            text-align: center;

            margin-top: 22px;

            padding-top: 20px;

            border-top:
                1px solid #292b5a;

            color: #94a3b8;

            font-size: 12px;
        }


        .login-link a {

            color:
                #a5b4fc;

            font-weight: 700;

            text-decoration: none;

            margin-left: 4px;
        }


        .login-link a:hover {

            color: white;
        }


        /* FOOTER */

        .footer {

            text-align: center;

            color: #64748b;

            font-size: 10px;

            margin-top: 15px;

            letter-spacing: .5px;
        }


        /* RESPONSIVE */

        @media (max-width: 800px) {

            .register-wrapper {

                grid-template-columns: 1fr;

                max-width: 550px;
            }


            .register-info {

                display: none;
            }


            .register-form {

                padding: 35px 28px;
            }
        }


        @media (max-width: 550px) {

            body {

                padding: 15px;
            }


            .register-wrapper {

                border-radius: 16px;
            }


            .register-form {

                padding: 27px 20px;
            }


            .form-grid {

                grid-template-columns: 1fr;

                gap: 16px;
            }


            .full {

                grid-column: auto;
            }


            .form-title h2 {

                font-size: 21px;
            }


            .buttons {

                flex-direction: column;
            }


            .btn {

                width: 100%;
            }
        }

    </style>

</head>


<body>


<div class="register-wrapper">


    <!--
    |--------------------------------------------------------------------------
    | INFORMASI
    |--------------------------------------------------------------------------
    -->

    <div class="register-info">

        <div class="brand-icon">
            👤
        </div>


        <h1>
            Buat Akun<br>
            <span>ASTS System</span>
        </h1>


        <p>
            Daftarkan akun baru untuk mengakses
            sistem pengelolaan data siswa ASTS
            dengan aman dan mudah.
        </p>


        <div class="feature-list">


            <div class="feature">

                <div class="feature-icon">
                    ✓
                </div>

                <span>
                    Sistem login yang aman
                </span>

            </div>


            <div class="feature">

                <div class="feature-icon">
                    🔐
                </div>

                <span>
                    Password terenkripsi
                </span>

            </div>


            <div class="feature">

                <div class="feature-icon">
                    📱
                </div>

                <span>
                    Notifikasi WhatsApp
                </span>

            </div>


            <div class="feature">

                <div class="feature-icon">
                    📊
                </div>

                <span>
                    Akses data siswa
                </span>

            </div>


        </div>

    </div>


    <!--
    |--------------------------------------------------------------------------
    | FORM REGISTER
    |--------------------------------------------------------------------------
    -->

    <div class="register-form">


        <div class="form-title">

            <h2>
                Daftar Akun
            </h2>

            <p>
                Lengkapi data berikut untuk membuat akun baru.
            </p>

        </div>


        <?php if ($error !== ''): ?>

            <div class="error">

                ⚠️

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="register.php"
            autocomplete="off"
        >


            <div class="form-grid">


                <!-- NAMA -->

                <div class="form-group full">

                    <label>
                        Nama Lengkap
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="nama"
                        value="<?= htmlspecialchars($nama) ?>"
                        placeholder="Masukkan nama lengkap"
                        autocomplete="name"
                        required
                    >

                </div>


                <!-- USERNAME -->

                <div class="form-group">

                    <label>
                        Username
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="username"
                        value="<?= htmlspecialchars($username) ?>"
                        placeholder="Contoh: rasya123"
                        autocomplete="username"
                        minlength="3"
                        maxlength="30"
                        pattern="[a-zA-Z0-9_]+"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label>
                        Email
                        <span class="required">*</span>
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($email) ?>"
                        placeholder="nama@email.com"
                        autocomplete="email"
                        required
                    >

                </div>


                <!-- WHATSAPP -->

                <div class="form-group full">

                    <label>
                        Nomor WhatsApp
                        <span class="required">*</span>
                    </label>

                    <input
                        type="tel"
                        name="no_wa"
                        value="<?= htmlspecialchars($no_wa) ?>"
                        placeholder="Contoh: 6281234567890"
                        autocomplete="tel"
                        required
                    >

                    <span class="password-strength">
                        Gunakan nomor WhatsApp aktif untuk menerima notifikasi.
                    </span>

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label>
                        Password
                        <span class="required">*</span>
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Minimal 6 karakter"
                        autocomplete="new-password"
                        minlength="6"
                        required
                    >

                </div>


                <!-- KONFIRMASI -->

                <div class="form-group">

                    <label>
                        Konfirmasi Password
                        <span class="required">*</span>
                    </label>

                    <input
                        type="password"
                        name="password_confirm"
                        placeholder="Ulangi password"
                        autocomplete="new-password"
                        minlength="6"
                        required
                    >

                </div>


            </div>


            <!-- BUTTON -->

            <div class="buttons">

                <a
                    href="login.php"
                    class="btn btn-back"
                >
                    ← Kembali
                </a>


                <button
                    type="submit"
                    class="btn btn-register"
                >
                    ✓ Buat Akun
                </button>

            </div>


        </form>


        <!-- LOGIN -->

        <div class="login-link">

            Sudah memiliki akun?

            <a href="login.php">
                Login di sini
            </a>

        </div>


        <div class="footer">

            ASTS Data System • © 2026

        </div>


    </div>

</div>


</body>

</html>