<?php

if (!defined('ABSPATH')) exit;

add_shortcode('contador_ajax', function(){

    ob_start();
    ?>

    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <div class="container mt-5">
        <div class="card shadow-lg p-4 text-center">
            <h3>🔥 Contador AJAX</h3>

            <h1 id="contadorNumero" class="display-4 mt-3">0</h1>

            <button id="btnSumar" class="btn btn-primary btn-lg mt-3">
                ➕ Sumar
            </button>

            <div id="mensaje" class="mt-4"></div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function(){

        const btn = document.getElementById("btnSumar");
        const numero = document.getElementById("contadorNumero");
        const mensaje = document.getElementById("mensaje");

        btn.addEventListener("click", function(){

            fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "action=incrementar_contador"
            })
            .then(res => res.json())
            .then(data => {

                if(data.success){
                    numero.innerText = data.data.contador;

                    mensaje.innerHTML = `
                        <div class="alert alert-success">
                            ${data.data.mensaje}
                        </div>
                    `;
                }

            });

        });

    });
    </script>

    <?php
    return ob_get_clean();
});