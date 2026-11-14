
<?php
require_once "conexion.php";

function escapar($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Validar identificador recibido por GET
$id_compra = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id_compra || $id_compra <= 0) {
    http_response_code(400);
    die("Identificador de compra inválido.");
}

// Consultar encabezado de la compra
$sql_compra = "SELECT
                    c.id,
                    c.fecha,
                    c.total,
                    p.nombre_empresa,
                    p.contacto,
                    p.telefono,
                    p.direccion,
                    u.nombre_completo,
                    u.usuario
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

// Comprobar que la compra exista
if (!$compra) {
    http_response_code(404);
    $conn->close();
    die("No se encontró el comprobante de esta compra.");
}

// Consultar los productos comprados
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

$resultado = $stmt->get_result();

$detalles = [];

while ($fila = $resultado->fetch_assoc()) {
    $detalles[] = $fila;
}

$stmt->close();
$conn->close();

// Fecha legible
$fecha_formateada = date(
    "d/m/Y h:i A",
    strtotime($compra["fecha"])
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Comprobante de compra #<?= escapar($compra["id"]) ?></title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f1f3f5;
            color: #222;
            margin: 0;
            padding: 30px;
        }

        .comprobante {
            position: relative;
            max-width: 850px;
            margin: auto;
            background: white;
            padding: 40px;
            border: 1px solid #ddd;
        }

        .encabezado {
            text-align: center;
            border-bottom: 3px solid #243447;
            padding-bottom: 18px;
            margin-bottom: 25px;
        }

        .encabezado h1 {
            margin: 0;
            font-size: 23px;
            color: #243447;
            text-transform: uppercase;
        }

        .encabezado p {
            margin: 6px 0;
            color: #555;
        }

        .titulo-comprobante {
            text-align: center;
            margin: 25px 0;
        }

        .titulo-comprobante h2 {
            margin-bottom: 5px;
            color: #243447;
        }

        .numero {
            font-size: 16px;
            font-weight: bold;
        }

        .datos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        .dato {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .dato strong {
            display: block;
            margin-bottom: 5px;
            color: #243447;
        }

        .seccion-titulo {
            margin: 25px 0 10px;
            color: #243447;
            font-size: 17px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 11px;
        }

        th {
            background: #243447;
            color: white;
            text-align: center;
        }

        td {
            font-size: 14px;
        }

        .numero-celda {
            text-align: center;
        }

        .derecha {
            text-align: right;
        }

        .total {
            margin-top: 20px;
            text-align: right;
            font-size: 21px;
            font-weight: bold;
            padding: 15px;
            border-top: 2px solid #243447;
        }

        .firmas {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-top: 85px;
            text-align: center;
        }

        .firma {
            border-top: 1px solid #222;
            padding-top: 10px;
            min-height: 55px;
        }

        .pie {
            text-align: center;
            margin-top: 45px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }

        .botones {
            max-width: 850px;
            margin: 20px auto;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .boton {
            display: inline-block;
            padding: 11px 18px;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            font-size: 14px;
        }

        .imprimir {
            background: #23834a;
        }

        .volver {
            background: #2878a7;
        }

        .vacio {
            text-align: center;
            padding: 20px;
        }

        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }

            body {
                background: white;
                margin: 0;
                padding: 0;
                color: black;
            }

            .no-print {
                display: none !important;
            }

            .comprobante {
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 10px;
                border: none;
                box-shadow: none;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .firmas {
                page-break-inside: avoid;
            }

            .encabezado,
            .titulo-comprobante,
            .datos,
            .total {
                page-break-inside: avoid;
            }
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .comprobante {
                padding: 15px;
            }

            .datos,
            .firmas {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .tabla-contenedor {
                overflow-x: auto;
            }

            .botones {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="botones no-print">

    <a
        class="boton volver"
        href="detalle_compra.php?id=<?= urlencode((string)$compra["id"]) ?>"
    >
        Volver al detalle
    </a>

    <button
        type="button"
        class="boton imprimir"
        onclick="window.print()"
    >
        Imprimir comprobante
    </button>

</div>

<main class="comprobante">

    <header class="encabezado">

        <h1>Instituto / Sistema de Inventario</h1>

        <p>Comprobante de registro de compras</p>

        <p>Documento generado por el sistema</p>

    </header>

    <section class="titulo-comprobante">

        <h2>COMPROBANTE DE COMPRA</h2>

        <p class="numero">
            N.º <?= str_pad(
                (string)$compra["id"],
                5,
                "0",
                STR_PAD_LEFT
            ) ?>
        </p>

    </section>

    <h3 class="seccion-titulo">Información de la compra</h3>

    <section class="datos">

        <div class="dato">
            <strong>Número de compra</strong>
            <?= escapar($compra["id"]) ?>
        </div>

        <div class="dato">
            <strong>Fecha de compra</strong>
            <?= escapar($fecha_formateada) ?>
        </div>

        <div class="dato">
            <strong>Proveedor</strong>
            <?= escapar($compra["nombre_empresa"]) ?>
        </div>

        <div class="dato">
            <strong>Contacto</strong>
            <?= escapar($compra["contacto"] ?? "No registrado") ?>
        </div>

        <div class="dato">
            <strong>Teléfono</strong>
            <?= escapar($compra["telefono"] ?? "No registrado") ?>
        </div>

        <div class="dato">
            <strong>Dirección</strong>
            <?= escapar($compra["direccion"] ?? "No registrada") ?>
        </div>

        <div class="dato">
            <strong>Usuario responsable</strong>
            <?= escapar($compra["nombre_completo"]) ?>
        </div>

        <div class="dato">
            <strong>Nombre de usuario</strong>
            <?= escapar($compra["usuario"]) ?>
        </div>

    </section>

    <h3 class="seccion-titulo">Detalle de productos</h3>

    <div class="tabla-contenedor">

        <table>

            <thead>
                <tr>
                    <th>N.º</th>
                    <th>Descripción del producto</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($detalles) > 0): ?>

                    <?php $contador = 1; ?>

                    <?php foreach ($detalles as $detalle): ?>

                        <?php
                        $subtotal =
                            (int)$detalle["cantidad"] *
                            (float)$detalle["precio_compra"];
                        ?>

                        <tr>

                            <td class="numero-celda">
                                <?= $contador++ ?>
                            </td>

                            <td>
                                <?= escapar($detalle["nombre_producto"]) ?>
                            </td>

                            <td class="numero-celda">
                                <?= escapar($detalle["cantidad"]) ?>
                            </td>

                            <td class="derecha">
                                $<?= number_format(
                                    (float)$detalle["precio_compra"],
                                    2
                                ) ?>
                            </td>

                            <td class="derecha">
                                $<?= number_format($subtotal, 2) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="vacio">
                            No hay productos registrados en esta compra.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <div class="total">
        TOTAL:
        $<?= number_format((float)$compra["total"], 2) ?>
    </div>

    <section class="firmas">

        <div class="firma">
            <strong>Entregado por</strong>
            <p>Firma del proveedor</p>
        </div>

        <div class="firma">
            <strong>Recibido por</strong>
            <p>Firma del responsable</p>
        </div>

    </section>

    <footer class="pie">
        <p>Comprobante generado electrónicamente.</p>
        <p>Documento correspondiente al registro de compra del sistema de inventario.</p>
    </footer>

</main>

</body>
</html>
