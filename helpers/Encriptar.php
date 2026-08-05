<?php
// Clase para encriptar y desencriptar texto de forma segura
class Crypto
{
    // Clave secreta y vector de inicialización cargados desde el archivo .env
    private static $secret_key = null;
    private static $iv = null;
    // Método de cifrado AES de 256 bits
    private static $encrypt_method = "AES-256-CBC";

    /**
     * Carga la clave y el IV desde el .env (con fallback a los valores originales).
     */
    private static function init()
    {
        if (self::$secret_key !== null) {
            return;
        }

        $env = parse_ini_file(__DIR__ . '/../.env');

        self::$secret_key = (!empty($env['CRYPTO_SECRET_KEY'])) ? $env['CRYPTO_SECRET_KEY'] : 'Sistema_Gestion_Floreria';
        self::$iv = (!empty($env['CRYPTO_IV'])) ? $env['CRYPTO_IV'] : 'secret_iv';
    }

    public static function encrypt($string)
    {
        self::init();

        // Genera una clave única basada en la frase secreta
        $key = hash('sha256', self::$secret_key);
        // Genera un vector de inicialización (IV) para mayor seguridad
        $iv = substr(hash('sha256', self::$iv), 0, 16);

        // Realiza el cifrado
        $output = openssl_encrypt($string, self::$encrypt_method, $key, 0, $iv);
        // Retorna el texto cifrado convertido a Base64
        return base64_encode($output);
    }

    public static function decrypt($string)
    {
        self::init();

        // Usa la misma clave y el mismo IV que el método de encriptar
        $key = hash('sha256', self::$secret_key);
        $iv = substr(hash('sha256', self::$iv), 0, 16);

        // Primero decodifica el Base64 y luego aplica la desencriptación
        $output = openssl_decrypt(base64_decode($string), self::$encrypt_method, $key, 0, $iv);
        return $output;
    }
}
