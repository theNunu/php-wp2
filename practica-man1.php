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

    function dpti_login() {
        // echo '<pre>'; var_dump( 'waza'); echo '</pre>'; die();

        $token = EclipsoftOnBoarding::generateToken();

        // echo '<pre>'; var_dump( 'el token: ', $token); echo '</pre>'; die();

        // 🚨 401 EN UNA SOLA LINEA
        if(!$token) wp_send_json(["msg"=>"No autorizado"],401);

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
        // 'file',
        'reason',
        'typeSign',

        // 'clientCode',
        // 'contractAmount',
        // 'personalized_template_email_reception'
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

    //     if (!isset($_POST['email'])) {
    //     wp_send_json_error('No se recibió email.');
    // }

    // $email = sanitize_email($_POST['email']);

    // // Validar formato de email
    // if (!is_email($email)) {
    //     wp_send_json_error('Formato de correo electrónico inválido.');
    // }
    // Filter_var($email, FILTER_VALIDATE_EMAIL);


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
        'email' => sanitize_text_field($_POST['email']),
        'phoneNumber' => sanitize_text_field($_POST['phoneNumber']),
        'reason' => sanitize_text_field($_POST['reason']),
        // 'file' => sanitize_text_field($_POST['file']),
        'typeSign' => sanitize_text_field($_POST['typeSign']),

        'clientCode' => sanitize_text_field($_POST['clientCode'] ?? ''),
        'contractAmount' => sanitize_text_field($_POST['contractAmount'] ?? ''),
        'personalized_template_email_reception' => sanitize_text_field($_POST['personalized_template_email_reception'] ?? ''),

        ];
//         echo "<pre>";
// var_dump($data);
// echo "</pre>";
// die();
// var_dump('el email: ',$data['email']);
// die();

        $file = $_FILES['file'];


        if(!$token){
            wp_send_json_error('No se pudo generar token');
        }

        // 👇 AHORA LE PASAS EL TOKEN A SERVICE
        $response = EclipsoftOnBoarding::crearSolicitud($data, $token,$file);


        // function request infro

        // $RESPUESTA = [];
        // $id = $RESPUESTA['requestid'];

        //function complete sign ing  ===>>> $id, $token
        
        // wp_send_json(['token_iddd' => $token]);
        wp_send_json([
            'mi_res' => $response,
        ]);

    }