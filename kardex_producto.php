<?php
session_start();

require_once 'conexion.php';

// Validar el ID recibido por la URL.
$producto_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$producto_id || $producto_id <= 0) {
    exit("Error: debe seleccionar un producto válido.");
}

// Consultar el producto usando los nombres reales de tu base de datos.
$sql_producto = "SELECT id, nombre_producto, precio, stock
                 FROM productos
                 WHERE id = ?
                 LIMIT 1";

$stmt_producto = $conn->prepare($sql_producto);
$stmt_producto->bind_param("i", $producto_id);
$stmt_producto->execute();

$resultado_producto = $stmt_producto->get_result();
$producto = $resultado_producto->fetch_assoc();
$stmt_producto->close();

if (!$producto) {
    exit("Error: el producto solicitado no existe.");
}

// Consultar todos los movimientos registrados en el Kardex.
$sql_kardex = "SELECT
                    id,
                    id_documento,
                    tipo_movimiento,
                    fecha,
                    cantidad_entrada,
                    cantidad_salida,
                    costo_unitario,
                    stock_anterior,
                    stock_actual,
                    motivo
               FROM kardex
               WHERE id_producto = ?
               ORDER BY fecha DESC, id DESC";

$stmt_kardex = $conn->prepare($sql_kardex);
$stmt_kardex->bind_param("i", $producto_id);
$stmt_kardex->execute();

$movimientos = $stmt_kardex->get_result();

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kardex - <?php echo escapar($producto['nombre_producto']); ?></title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        body {
            background: #f4f6f9;
        }

        .contenedor {
            margin-top: 35px;
            margin-bottom: 35px;
        }

        .card {
            border: none;
            border-radius: 10px;
        }

        .titulo {
            color: #1f2937;
            font-weight: 700;
        }

        .tabla th {
            white-space: nowrap;
            font-size: 13px;
        }

        .tabla td {
            vertical-align: middle;
            font-size: 13px;
        }

        .stock-actual {
            font-size: 17px;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="container-fluid contenedor">

    <!-- Información general del producto -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center flex-wrap">

                <div>
                    <h3 class="titulo mb-2">
                        📋 Kardex de Movimientos
                    </h3>

                    <h5 class="mb-1">
                        Producto:
                        <strong>
                            <?php echo escapar($producto['nombre_producto']); ?>
                        </strong>
                    </h5>

                    <p class="text-muted mb-0">
                        Código interno: #<?php echo (int)$producto['id']; ?>
                    </p>
                </div>

                <div class="text-right mt-3 mt-md-0">
                    <span class="badge badge-success p-2 stock-actual">
                        Stock actual:
                        <?php echo (int)$producto['stock']; ?> unidades
                    </span>

                    <div class="mt-3">
                        <a href="productos.php"
                           class="btn btn-outline-secondary btn-sm">
                            ⬅ Volver a productos
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Historial de movimientos -->
    <div class="card shadow-sm">

        <div class="card-header bg-dark text-white">
            <strong>Historial cronológico de entradas y salidas</strong>
        </div>

        <div class="card-body p-0 table-responsive">

            <table class="table table-hover table-striped text-center mb-0 tabla">

                <thead class="thead-light">
                    <tr>
                        <th># Trans.</th>
                        <th>Fecha / Hora</th>
                        <th>Tipo de movimiento</th>
                        <th>Documento</th>
                        <th>Costo unitario</th>
                        <th>Stock anterior</th>
                        <th class="text-success">+ Entrada</th>
                        <th class="text-danger">- Salida</th>
                        <th>Nuevo saldo</th>
                        <th>Motivo</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($movimientos->num_rows === 0): ?>

                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            No hay movimientos registrados para este producto.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php while ($mov = $movimientos->fetch_assoc()): ?>

                        <tr>
                            <td>
                                #<?php echo (int)$mov['id']; ?>
                            </td>

                            <td>
                                <?php
                                echo date(
                                    'd/m/Y H:i',
                                    strtotime($mov['fecha'])
                                );
                                ?>
                            </td>

                            <td>
                                <?php if ($mov['tipo_movimiento'] === 'ENTRADA_COMPRA'): ?>

                                    <span class="badge badge-primary">
                                        📥 Entrada por compra
                                    </span>

                                <?php elseif ($mov['tipo_movimiento'] === 'ANULACION_COMPRA'): ?>

                                    <span class="badge badge-danger">
                                        ↩ Anulación de compra
                                    </span>

                                <?php elseif ($mov['tipo_movimiento'] === 'SALIDA_VENTA'): ?>

                                    <span class="badge badge-warning">
                                        📤 Salida por venta
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-info">
                                        <?php echo escapar($mov['tipo_movimiento']); ?>
                                    </span>

                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (
                                    in_array(
                                        $mov['tipo_movimiento'],
                                        ['ENTRADA_COMPRA', 'ANULACION_COMPRA'],
                                        true
                                    )
                                ): ?>

                                    <a href="detalle_compra.php?id=<?php echo (int)$mov['id_documento']; ?>"
                                       target="_blank">
                                        Factura #<?php echo (int)$mov['id_documento']; ?>
                                    </a>

                                <?php else: ?>

                                    #<?php echo (int)$mov['id_documento']; ?>

                                <?php endif; ?>
                            </td>

                            <td>
                                $<?php echo number_format((float)$mov['costo_unitario'], 2); ?>
                            </td>

                            <td>
                                <?php echo (int)$mov['stock_anterior']; ?>
                            </td>

                            <td class="font-weight-bold text-success">
                                <?php
                                echo ((int)$mov['cantidad_entrada'] > 0)
                                    ? '+' . (int)$mov['cantidad_entrada']
                                    : '-';
                                ?>
                            </td>

                            <td class="font-weight-bold text-danger">
                                <?php
                                echo ((int)$mov['cantidad_salida'] > 0)
                                    ? '-' . (int)$mov['cantidad_salida']
                                    : '-';
                                ?>
                            </td>

                            <td class="font-weight-bold">
                                <?php echo (int)$mov['stock_actual']; ?>
                            </td>

                            <td>
                                <?php echo escapar($mov['motivo'] ?? ''); ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>

</div>

</body>
</html>

<?php
$stmt_kardex->close();
$conn->close();
?>
