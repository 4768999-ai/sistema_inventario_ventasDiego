
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servidor = "localhost";
$usuario = "root";
$password = "";
$base_datos = "sistema_inventario";

try {
    $conn = new mysqli(
        $servidor,
        $usuario,
        $password,
        $base_datos
    );

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    error_log("Error de conexión: " . $e->getMessage());
    die("No se pudo conectar con la base de datos.");
}
