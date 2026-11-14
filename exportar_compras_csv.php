
<?php
require_once "conexion.php";

// Evitar que se genere salida antes de los encabezados CSV.
$sql = "SELECT
            c.id,
            c.fecha,
            p.nombre_empresa,
            u.nombre_completo,
            c.total,
            CASE
                WHEN EXISTS (
                    SELECT 1
                    FROM kardex k
                    WHERE k.id_documento = c.id
                      AND k.tipo_movimiento = 'ANULACION_COMPRA'
                )
                THEN 'ANULADA'
                ELSE 'SIN ANULACION REGISTRADA'
            END AS estado
        FROM compras c
        INNER JOIN proveedores p ON c.proveedor_id = p.id
        INNER JOIN usuarios u ON c.usuario_id = u.id
        ORDER BY c.fecha DESC, c.id DESC";

$resultado = $conn->query($sql);

if (!$resultado) {
    die("Error al consultar las compras: " . $conn->error);
}

// Encabezados para descargar el archivo CSV.
$nombreArchivo = "reporte_compras_" . date("Ymd_His") . ".csv";

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$nombreArchivo\"");
header("Pragma: no-cache");
header("Expires: 0");

// Abrir la salida del navegador.
$salida = fopen("php://output", "w");

// BOM UTF-8 para facilitar la lectura de caracteres en Excel.
fwrite($salida, "\xEF\xBB\xBF");

// Encabezados de las columnas.
fputcsv($salida, [
    "ID FACTURA",
    "FECHA DE EMISION",
    "PROVEEDOR",
    "USUARIO RESPONSABLE",
    "TOTAL FACTURA",
    "ESTADO"
], ";", '"', "\\");

// Exportar los registros.
while ($fila = $resultado->fetch_assoc()) {
    fputcsv($salida, [
        $fila["id"],
        $fila["fecha"],
        $fila["nombre_empresa"],
        $fila["nombre_completo"],
        number_format((float)$fila["total"], 2, ".", ""),
        $fila["estado"]
    ], ";", '"', "\\");
}

fclose($salida);
exit;
?>
