
<?php
require_once "conexion.php";

function escapar($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

$id_compra = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id_compra || $id_compra <= 0) {
    http_response_code(400);
    die("El identificador de compra no es válido.");
}

// Consultar encabezado de compra
$sql_compra = "SELECT
                    c.id,
                    c.fecha,
                    c.total,
                    p.nombre_empresa,
                    p.contacto,
                    p.telefono,
                    p.direccion,
                    u.nombre_completo
               FROM compras c
               INNER JOIN proveedores p
                   ON c.proveedor_id = p.id
               INNER JOIN usuarios u
                   ON c.usuario_id = u.id
               WHERE c.id = ?";

$stmt = $conn->prepare($sql_compra);
$stmt->bind_param("i", $id_compra);
$stmt->execute();

$compra = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$compra) {
    http_response_code(404);
    die("La compra solicitada no existe.");
}

// Consultar productos de la compra
$sql_detalle = "SELECT
                    dc.id,
                    dc.cantidad,
                    dc.precio_compra,
                    pr.nombre_producto
                FROM detalle_compras dc
                INNER JOIN productos pr
                    ON dc.producto_id = pr.id
                WHERE dc.compra_id = ?
                ORDER BY dc.id ASC";

$stmt = $conn->prepare($sql_detalle);
$stmt->bind_param("i", $id_compra);
$stmt->execute();

$resultado_detalle = $stmt->get_result();

$detalles = [];

while ($fila = $resultado_detalle->fetch_assoc()) {
    $detalles[] = $fila;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de compra #<?= escapar($compra["id"]) ?></title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 30px;
            color: #333;
        }

        .contenedor {
            max-width: 950px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,.08);
        }

        h1 {
            color: #243447;
        }

        .datos {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #243447;
            color: white;
        }

        .total {
            text-align: right;
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
        }

        .boton {
            display: inline-block;
            padding: 10px 15px;
            background: #2878a7;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px 5px 5px 0;
        }

        .verde {
            background: #23834a;
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .datos {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="contenedor">

    <h1>Detalle de compra #<?= escapar($compra["id"]) ?></h1>

    <div class="datos">
        <div>
            <strong>Proveedor:</strong>
            <?= escapar($compra["nombre_empresa"]) ?>
        </div>

        <div>
            <strong>Fecha:</strong>
            <?= escapar($compra["fecha"]) ?>
        </div>

        <div>
            <strong>Contacto:</strong>
            <?= escapar($compra["contacto"] ?? "No registrado") ?>
        </div>

        <div>
            <strong>Teléfono:</strong>
            <?= escapar($compra["telefono"] ?? "No registrado") ?>
        </div>

        <div>
            <strong>Dirección:</strong>
            <?= escapar($compra["direccion"] ?? "No registrada") ?>
        </div>

        <div>
            <strong>Usuario responsable:</strong>
            <?= escapar($compra["nombre_completo"]) ?>
        </div>
    </div>

    <h2>Productos comprados</h2>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio de compra</th>
                <th>Subtotal</th>
            </tr>
        </thead>

        <tbody>
            <?php if (count($detalles) > 0): ?>

                <?php foreach ($detalles as $detalle): ?>

                    <?php
                    $subtotal =
                        (int)$detalle["cantidad"] *
                        (float)$detalle["precio_compra"];
                    ?>

                    <tr>
                        <td>
                            <?= escapar($detalle["nombre_producto"]) ?>
                        </td>

                        <td>
                            <?= escapar($detalle["cantidad"]) ?>
                        </td>

                        <td>
                            $<?= number_format(
                                (float)$detalle["precio_compra"],
                                2
                            ) ?>
                        </td>

                        <td>
                            $<?= number_format($subtotal, 2) ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="4">
                        Esta compra no tiene productos registrados.
                    </td>
                </tr>

            <?php endif; ?>
        </tbody>
    </table>

    <div class="total">
        Total de compra:
        $<?= number_format((float)$compra["total"], 2) ?>
    </div>

    <a class="boton" href="historial_compras.php">
        Volver al historial
    </a>

    <a
        class="boton verde"
        href="comprobante_compra.php?id=<?= urlencode((string)$compra["id"]) ?>"
    >
        Generar comprobante
    </a>

</div>

</body>
</html>
