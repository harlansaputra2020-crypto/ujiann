<?php

/*
|--------------------------------------------------------------------------
| KONFIGURASI FONNTE
|--------------------------------------------------------------------------
|
| ISI DENGAN TOKEN FONNTE ASLI KAMU
|
*/

define('FONNTE_TOKEN', 'hRJXt283eJtsgtrgnAqQ');

/*
|--------------------------------------------------------------------------
| NOMOR TUJUAN
|--------------------------------------------------------------------------
|
| Gunakan format:
| 628xxxxxxxxxx
|
| Contoh:
| 6281234567890
|
*/

define('FONNTE_TARGET', '6285604692519');


/*
|--------------------------------------------------------------------------
| FUNGSI KIRIM WHATSAPP
|--------------------------------------------------------------------------
*/

function kirimWhatsApp($target, $pesan)
{

    /*
    |--------------------------------------------------------------------------
    | CEK TOKEN
    |--------------------------------------------------------------------------
    */

    if (
        !defined('FONNTE_TOKEN') ||
        FONNTE_TOKEN === '' ||
        FONNTE_TOKEN === 'MASUKKAN_TOKEN_FONNTE_ASLI'
    ) {

        return [
            'status' => false,
            'response' => 'Token Fonnte belum diisi.'
        ];

    }


    /*
    |--------------------------------------------------------------------------
    | CEK TARGET
    |--------------------------------------------------------------------------
    */

    if (
        !$target ||
        $target === '628XXXXXXXXXX'
    ) {

        return [
            'status' => false,
            'response' => 'Nomor WhatsApp tujuan belum diisi.'
        ];

    }


    /*
    |--------------------------------------------------------------------------
    | CEK CURL
    |--------------------------------------------------------------------------
    */

    if (!function_exists('curl_init')) {

        return [
            'status' => false,
            'response' => 'cURL PHP belum aktif.'
        ];

    }


    /*
    |--------------------------------------------------------------------------
    | CURL
    |--------------------------------------------------------------------------
    */

    $curl = curl_init();


    curl_setopt_array($curl, [

        CURLOPT_URL =>
            'https://api.fonnte.com/send',

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_POST =>
            true,

        CURLOPT_POSTFIELDS => [

            'target' =>
                $target,

            'message' =>
                $pesan

        ],

        CURLOPT_HTTPHEADER => [

            'Authorization: ' .
                FONNTE_TOKEN

        ],

        CURLOPT_TIMEOUT =>
            30,

        CURLOPT_CONNECTTIMEOUT =>
            10

    ]);


    /*
    |--------------------------------------------------------------------------
    | KIRIM
    |--------------------------------------------------------------------------
    */

    $response =
        curl_exec($curl);


    /*
    |--------------------------------------------------------------------------
    | CEK CURL ERROR
    |--------------------------------------------------------------------------
    */

    if ($response === false) {

        $error =
            curl_error($curl);

        curl_close($curl);


        return [

            'status' =>
                false,

            'response' =>
                'CURL Error: ' . $error

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | HTTP STATUS
    |--------------------------------------------------------------------------
    */

    $httpCode =
        curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );


    curl_close($curl);


    /*
    |--------------------------------------------------------------------------
    | JSON RESPONSE
    |--------------------------------------------------------------------------
    */

    $hasil =
        json_decode(
            $response,
            true
        );


    /*
    |--------------------------------------------------------------------------
    | JIKA RESPONSE BUKAN JSON
    |--------------------------------------------------------------------------
    */

    if (!is_array($hasil)) {

        return [

            'status' =>
                false,

            'response' =>
                "Response Fonnte tidak valid. HTTP {$httpCode}: {$response}"

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | STATUS BERHASIL
    |--------------------------------------------------------------------------
    */

    if (
        isset($hasil['status']) &&
        $hasil['status'] === true
    ) {

        return [

            'status' =>
                true,

            'response' =>
                $hasil

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | STATUS GAGAL
    |--------------------------------------------------------------------------
    */

    return [

        'status' =>
            false,

        'response' =>
            $hasil

    ];

}