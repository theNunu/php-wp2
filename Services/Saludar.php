<?php

class Saludar
{

    public static function mandarSaludo()
    {
        wp_send_json([
            "status" => "ok",
            "1 mensaje" => "soy tilin"
        ]);

    }
}