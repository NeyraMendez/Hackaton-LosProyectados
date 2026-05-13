<?php
// =============================================
//  CAMPUSGO - Configuración BD
//  Archivo: php/config.php
// =============================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'campusgo');
define('DB_USER', 'campusgo_user');
define('DB_PASS', 'password123');
define('DB_CHARSET', 'utf8mb4');

function conectar() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
?>
