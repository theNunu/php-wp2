<?php
namespace Services;

class EclipsoftOnBoarding
{
    public static $username = 'deprati';
    public static $password = 'deprati';


    // public static function mandarSaludo(){
    //     echo "¡Hola, Mundo!";

    // }

    public static function defineCRedentials()
    {
        if (strtoupper($_SESSION['dpti_env']) == 'PROD') {
            self::$username = 'deprati';
            self::$password = 'FwhUvf6@&Lhb44D267';
            // self::$password = 'deprati';
        } elseif (strtoupper($_SESSION['dpti_env']) == 'QA') {
            self::$username = 'deprati';
            self::$password = 'deprati';
        } else {
            self::$username = 'deprati';
            self::$password = 'deprati';
        }
    }

    public static function obtenerBasePath()
    {
        if (strtoupper($_SESSION['dpti_env']) == 'PROD') {
            $basePath = 'https://eclipsoft.dev/onboarding-back-deprati';
            // $basePath = BASE_URL_ECLIPSFOT_OTP_DEV;
            //define( 'BASE_URL_ONBOARDING', 'https://eclipsoft.dev/onboarding-back-deprati');  // DEV - QA MODE
        } elseif (strtoupper($_SESSION['dpti_env']) == 'QA') {
            // $basePath = BASE_URL_ONBOARDING;
            $basePath = 'https://eclipsoft.dev/onboarding-back-deprati';
        } else {
            // $basePath = BASE_URL_ONBOARDING;
            $basePath = 'https://eclipsoft.dev/onboarding-back-deprati';
        }

        return $basePath;
    }


    public static function getToken()
    {
        try {
            $basePath = EclipsoftOnBoarding::obtenerBasePath();
            EclipsoftOnBoarding::defineCRedentials();
            $url = $basePath . "/api/authenticate";

            $fields = array(
                'username' => self::$username,
                'password' => self::$password,
            );

            $headers = array(
                'Content-type' => 'application/json'
            );

            $body = wp_json_encode($fields);
            $args = array(
                'body' => $body,
                'method' => 'POST',
                'headers' => $headers,
                'timeout' => 1200,
            );


            $response = wp_remote_request($url, $args);
            $datatoken = wp_remote_retrieve_body($response);


            $token_decode = json_decode($datatoken);
            $token = $token_decode->id_token;
            return $token;
        } catch (\Throwable $th) {
            return '';
        }
    }


    public static function generateToken()
    {
        // 🔍 BUSCAR TOKEN GUARDADO
        $token = get_transient('dpti_auth_token');

        if ($token) {
            return $token;
        }

        $url = self::obtenerBasePath() . "/api/authenticate";

        $fields = [
            'username' => self::$username,
            'password' => self::$password,
        ];

        $args = [
            'body' => wp_json_encode($fields),
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        $response = wp_remote_request($url, $args);
        $body = wp_remote_retrieve_body($response);

        $decode = json_decode($body);

        if (!isset($decode->id_token)) {
            return false;
        }

        $token = $decode->id_token;

        // ⏱ TOKEN 20 MINUTOS
        // set_transient('dpti_auth_token',$token,20 * MINUTE_IN_SECONDS);

        // 🧪 TOKEN 30 SEGUNDOS SOLO PRUEBAS
        set_transient('dpti_auth_token', $token, 30);

        return $token;
    }
    public static function validateToken()
    {
        try {
            $maxAttempts = 3;
            $attempt = 1;
            $success = false;
            $token = '';

            while ($attempt <= $maxAttempts && !$success) {
                $token = EclipsoftOnBoarding::getToken();

                if (!isset($token) || $token == '') {
                    $attempt++;
                    sleep(2);
                } else {
                    $success = true;
                }
            }


            $basePath = EclipsoftOnBoarding::obtenerBasePath();
            $url = $basePath . "/api/otp/validate";


            $email = sanitize_text_field($_POST['data']['email']);
            // $telf  = sanitize_text_field($_POST['data']['telf']);
            // $cod_otp  = sanitize_text_field($_POST['data']['cod_otp']);


            $fields = [
                'email' => $email,
                // 'otp' => $cod_otp
            ];

            $headers = array(
                'Authorization' => "Bearer $token",
                'Content-type' => 'application/json'
            );

            $body = wp_json_encode($fields);
            $args = array(
                'body' => $body,
                'method' => 'POST',
                'headers' => $headers,
                'timeout' => 100, // 1min de espera
            );


            $response = wp_remote_request($url, $args);
            $data = wp_remote_retrieve_body($response);

            return $data;
        } catch (\Throwable $th) {
            return ["msg" => $th->getMessage()];
        }
    }

    public static function crearSolicitud($data, $token, $file)
    {
        // echo '<pre>'; var_dump( 'el token: ', $token, 'la data: ',$data,'archivo: ', $file); echo '</pre>'; die();
        if (empty($token)) {
            return false;
        }

        // =========================
        // 1️⃣ VALIDAR MIME REAL
        // =========================
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        // finfo_close($finfo);

        if ($mimeType !== 'application/pdf') {
            return [
                "status" => "error",
                "msg" => "El archivo debe ser un PDF válido"
            ];
        }

        // =========================
        // 2️⃣ VALIDAR NOMBRE
        // =========================
        $fileName = strtoupper($file['name']); // Para evitar problemas de mayúsculas

        if (
            strpos($fileName, 'CONTRATO') === false &&
            strpos($fileName, 'NEGADA') === false
        ) {
            return [
                "status" => "error",
                "msg" => "El nombre del archivo debe contener la palabra CONTRATO o NEGADA"
            ];
        }


        $url = self::obtenerBasePath() . "/api/request-information";
        //  $url = self::obtenerBasePath()."/api/authenticate";

        $headers = [
            'Authorization' => "Bearer " . $token,
            'Content-Type' => 'multipart/form-data'
            // 'Content-type' => 'application/json'
        ];
        // echo "<pre>";
// var_dump('mis datos: ',$data);
// echo "</pre>";


        $args = [
            'body' => $data,
            'method' => 'POST',
            'headers' => $headers,
            'timeout' => 100
        ];

        $response = wp_remote_post($url, $args);

        echo "<pre>";
        var_dump('la respuestaaa: ', $response);
        echo "</pre>";
        die();

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        // $body = wp_remote_retrieve_body($response);

        echo "<pre>";
        var_dump('el cuerpoo: ', $body);
        echo "</pre>";
        die();
        $decode = json_decode($body, true);

        // 🔥 CAPTURAR RESPUESTA REAL DEL API
        $requestId = $decode['requestId'] ?? null;
        $link = $decode['url'] ?? $decode['link'] ?? null;

        if (!$requestId || !$link) {
            return false;
        }

        return [
            "status" => "200 OK",
            "requestId" => $requestId,
            "url" => $link,
            "detail" => "Informacion guardada correctamente, ID: " . $requestId .
                " . Enlace y otp enviado por correo correctamente a " .
                $data['email']
        ];


        // $response = wp_remote_request($url,$args);

        // if(is_wp_error($response)){
        //     return false;
        // }

        // $body = wp_remote_retrieve_body($response);
        // $decode = json_decode($body,true);

        // return [
        //     "url" => $decode['url'] ?? null
        // ];
    }



}