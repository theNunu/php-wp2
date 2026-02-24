<?php

use Services\EclipsoftOnBoarding;
/**
 * Plugin Name: Practica Man 1
 * Description: Plugin de prueba
 * Version: 1.0
 */
require_once plugin_dir_path(__FILE__) . 'services/Saludar.php';
require_once plugin_dir_path(__FILE__) . 'services/EclipsoftOnBoarding.php';

add_action('wp_ajax_nopriv_dpti_saludar', 'dpti_saludar');
add_action('wp_ajax_dpti_saludar', 'dpti_saludar');

function dpti_saludar()
{
    $response = Saludar::mandarSaludo();

    wp_send_json($response);

}

add_action('wp_ajax_dpti_login', 'dpti_login');
add_action('wp_ajax_nopriv_dpti_login', 'dpti_login');
//AuthenticationToken dpti_authentication_token
// function dpti_login() {
// echo "¡Hola, Mundo!";
//     $res = DptiEclipsoftOnBoarding::generateToken();

//     wp_send_json($res);
// }

function dpti_login()
{
    // echo '<pre>'; var_dump( 'waza'); echo '</pre>'; die();
    $token = EclipsoftOnBoarding::generateToken();
    // echo '<pre>'; var_dump( 'el token: ', $token); echo '</pre>'; die();
    // 🚨 401 EN UNA SOLA LINEA
    if (!$token)
        wp_send_json(["msg" => "No autorizado"], 401);

    // Validar obligatorios
    $required = [
        'nui',
        'givenName',
        'secondName',
        'surname1',
        'surname2',
        'province',
        'city',
        'country',
        'address',
        'email',
        'phoneNumber',
        'reason',
        'typeSign',
    ];



    foreach ($required as $r) {
        if (!isset($_POST[$r]) || empty($_POST[$r])) {
            wp_send_json([
                "status" => "error",
                "msg" => "El campo {$r} es obligatorio"
            ], 400);
        }
    }

    // 🔴 Validar archivo obligatorio correctamente
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
        wp_send_json([
            "status" => "error",
            "msg" => "El archivo es obligatorioo"
        ], 400);
    }

    $data = [
        'nui' => sanitize_text_field($_POST['nui']),
        'givenName' => sanitize_text_field($_POST['givenName']),
        'secondName' => sanitize_text_field($_POST['secondName']),
        'surname1' => sanitize_text_field($_POST['surname1']),
        'surname2' => sanitize_text_field($_POST['surname2']),
        'province' => sanitize_text_field($_POST['province']),
        'city' => sanitize_text_field($_POST['city']),
        'country' => sanitize_text_field($_POST['country']),
        'address' => sanitize_text_field($_POST['address']),
        'email' => sanitize_email($_POST['email']),
        'phoneNumber' => sanitize_text_field($_POST['phoneNumber']),
        'reason' => sanitize_text_field($_POST['reason']),
        'typeSign' => sanitize_text_field($_POST['typeSign']),
        'clientCode' => sanitize_text_field($_POST['clientCode'] ?? ''),
        'contractAmount' => sanitize_text_field($_POST['contractAmount'] ?? ''),
        'personalized_template_email_reception' => sanitize_text_field($_POST['personalized_template_email_reception'] ?? ''),

    ];

    $file = $_FILES['file'];

    if (!$token) {
        wp_send_json_error('No se pudo generar token');
    }

    $response = EclipsoftOnBoarding::crearSolicitud($data, $token, $file);

    // $response = EclipsoftOnBoarding::crearSolicitud($data, $token, $file);

    if (!isset($response['requestId'])) {
        wp_send_json([
            'mi_res' => $response,
        ]);
    }

    $requestId = $response['requestId'];

    $complete = EclipsoftOnBoarding::completeSign($requestId, $token);

    wp_send_json([
        // 'request_information' => $response,
        'complete_sign' => $complete,
    ]);

    // wp_send_json([
    //     'mi_res' => $response,
    // ]);

}