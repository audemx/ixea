<?php
/**
 * IXEA OS - Conexión con DB
 * /database.php
 */
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/config.php';

/**
 * Establece y devuelve una nueva conexión PDO a la Base de Datos.
 * @return PDO
 */
function connectDB() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-06:00'"
    ];
    
    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (\PDOException $e) {
        // En caso de error, registra el problema y termina la ejecución
        error_log("Error de conexión a la DB: " . $e->getMessage());
        die("Error crítico: No se pudo conectar a la Base de Datos."); 
    }
}
?>