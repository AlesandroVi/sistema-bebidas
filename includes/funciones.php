<?php

/**
 * Formatea un precio en pesos: sin decimales si es entero ($1.200)
 * y con dos decimales si tiene centavos ($1.550,50).
 */
function precio($monto)
{
    $monto = (float)$monto;
    $decimales = floor($monto) == $monto ? 0 : 2;
    return '$' . number_format($monto, $decimales, ',', '.');
}

/** Atajo para escapar texto antes de mostrarlo en HTML. */
function e($texto)
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}
