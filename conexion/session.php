<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => false, // Si es HTTP usar false, si es HTTPS usar true
        'cookie_httponly' => true, // Evita que JavaScript acceda a la sesión
        'cookie_samesite' => 'Lax', // Protege contra CSRF
        'use_strict_mode' => true, // Fuerza el uso de sesiones seguras
    ]);

  
}
