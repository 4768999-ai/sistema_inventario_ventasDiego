
<?php
// Archivo: config/db.php
// Conexion PDO para el modulo de Ventas y Clientes.

$host = "localhost";
$dbname = "sistema_inventario";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {
    die("Error de conexion con la base de datos: " . $e->getMessage());
}
?>
