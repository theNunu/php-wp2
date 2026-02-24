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
        if (empty($token)) {
            return [
                "status" => "error",
                "msg" => "Token vacío"
            ];
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
        $fileName = strtoupper($file['name']);

        if (
            strpos($fileName, 'CONTRATO') === false &&
            strpos($fileName, 'NEGADA') === false
        ) {
            return [
                "status" => "error",
                "msg" => "El nombre del archivo debe contener CONTRATO o NEGADA"
            ];
        }

        $url = self::obtenerBasePath() . "/api/request-information";

        // 🔥 IMPORTANTE: construir multipart real
        $postFields = $data;

        // Adjuntar archivo REAL
        $postFields['file'] = new \CURLFile( //El new CURLFile() es lo que transforma todo en multipart real.
            $file['tmp_name'],
            'application/pdf',
            $file['name']
        );

        // =========================
        // 3️⃣ cURL: Detecta que hay un archivo, Cambia el Content-Type a multipart/form-data
        // =========================
        // echo '<pre>';
        // var_dump('la data: ', $data);
        $ch = curl_init();
        //CURLFile genera multipart correctamente.
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true, //“No imprimas la respuesta, devuélvemela como string”.
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields, //Aquí PHP detecta el CURLFile y construye multipart.
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$token}"
                // 🚨 NO pongas Content-Type
            ],
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return [
                "status" => "error",
                "msg" => curl_error($ch)
            ];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // curl_close($ch);

        $decode = json_decode($response, true);

        if ($httpCode !== 200) {
            return $decode ?: [
                "status" => "error",
                "msg" => "Error HTTP: {$httpCode}",
                "raw" => $response
            ];
        }

        return [
            "status" => $decode['status'] ?? '200 OK',
            "requestId" => $decode['requestId'] ?? null,
            "url" => $decode['url'] ?? null,
            "detail" => $decode['detail'] ?? null
        ];
    }

    public static function completeSign($requestId, $token)
    {
        if (empty($requestId) || empty($token)) {
            return [
                "status" => "error",
                "msg" => "RequestId o Token vacío"
            ];
        }

        $url = self::obtenerBasePath() . "/api/complete-sign";

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [], // si no requiere body
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$token}",
                "Cookie: onb_request={$requestId}",
                "User-Agent: Mozilla/5.0",
                "X-Forwarded-For: " . $_SERVER['REMOTE_ADDR'],
            ],
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return [
                "status" => "error",
                "msg" => curl_error($ch)
            ];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $decode = json_decode($response, true);

        if ($httpCode !== 200) {
            return $decode ?: [
                "status" => "error",
                "msg" => "Error HTTP: {$httpCode}",
                "raw" => $response
            ];
        }

        return $decode;
    }



}