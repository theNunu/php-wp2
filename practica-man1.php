<?php
/**
 * Plugin Name: Practica Man 1
 * Description: Plugin de prueba
 * Version: 1.0
 */
require_once plugin_dir_path(__FILE__) . 'services/Saludar.php';

add_action('wp_ajax_nopriv_dpti_saludar', 'dpti_saludar');
add_action('wp_ajax_dpti_saludar', 'dpti_saludar');

function dpti_saludar()
{
    $response = Saludar::mandarSaludo();

    wp_send_json($response);

}