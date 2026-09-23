<?php
/**
 * Genera un código de invitación aleatorio de 6 caracteres
 */
function generarCodigoInvitacion(int $longitud = 6): string {
    $caracteres = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $max = strlen($caracteres) - 1;
    $codigo = '';
    
    for ($i = 0; $i < $longitud; $i++) {
        $codigo .= $caracteres[random_int(0, $max)];
    }
    
    return $codigo;
}