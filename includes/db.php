<?php

/**
 * Conexión a MySQL.
 *
 * Por defecto usa la configuración de XAMPP (usuario root sin contraseña).
 * En otro entorno se pueden definir las variables de entorno
 * DB_HOST, DB_USER, DB_PASS y DB_NAME para no tocar el código.
 */
function conectar()
{
    $host = getenv('DB_HOST') ?: 'localhost';
    $usuario = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';
    $base_de_datos = getenv('DB_NAME') ?: 'bebidas_db';

    $conn = new mysqli($host, $usuario, $password, $base_de_datos);

    if ($conn->connect_error) {
        die('Error de conexión: ' . $conn->connect_error);
    }

    // utf8mb4 permite guardar emojis (utf8 de MySQL solo admite 3 bytes por carácter)
    $conn->set_charset('utf8mb4');
    return $conn;
}
