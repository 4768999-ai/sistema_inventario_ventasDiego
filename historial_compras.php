
<?php
require_once "conexion.php";

$sql = "SELECT
            c.id,
            c.fecha,
            c.total,
            p.nombre_empresa,
            u.nombre_completo
        FROM compras c
        INNER JOIN proveedores p
            ON c.proveedor_id = p.id
        INNER JOIN usuarios u
            ON c.usuario_id = u.id
        ORDER BY c.fecha DESC, c.id DESC";

$resultado = $conn->query($sql);

function escapar($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de compras</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
            color: #333;
        }

        .contenedor {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,.08);
        }

        h1 {
            margin-top: 0;
            color: #243447;
        }

        .tabla-contenedor {
            overflow-x: auto;
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

        tr:nth-child(even) {
            background: #f7f7f7;
        }

        .acciones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .boton {
            display: inline-block;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
            font-size: 13px;
        }

        .detalle {
            background: #2878a7;
        }

        .comprobante {
            background: #23834a;
        }

        .vacio {
            text-align: center;
            padding: 25px;
            color: #777;
        }
    </style>
</head>
<body>

<div class="contenedor">

    <h1>Historial de compras</h1>
    <p>Consulta las compras registradas en el sistema.</p>

    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>Usuario responsable</th>
                    <th>Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($resultado->num_rows > 0): ?>

                    <?php while ($compra = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?= escapar($compra["id"]) ?></td>

                            <td>
                                <?= escapar($compra["fecha"]) ?>
                            </td>

                            <td>
                                <?= escapar($compra["nombre_empresa"]) ?>
                            </td>

                            <td>
                                <?= escapar($compra["nombre_completo"]) ?>
                            </td>

                            <td>
                                $<?= number_format(
                                    (float)$compra["total"],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <div class="acciones">
                                    <a
                                        class="boton detalle"
                                        href="detalle_compra.php?id=<?= urlencode((string)$compra["id"]) ?>"
                                    >
                                        Ver detalle
                                    </a>

                                    <a
                                        class="boton comprobante"
                                        href="comprobante_compra.php?id=<?= urlencode((string)$compra["id"]) ?>"
                                    >
                                        Comprobante
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="vacio">
                            Todavía no hay compras registradas.
                        </td>
                    </tr>

                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>

<?php
$resultado->free();
$conn->close();
?>
