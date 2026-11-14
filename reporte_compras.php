
<?php
require_once "conexion.php";

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

// Consulta gerencial: total de compras por proveedor.
// Excluye las compras que tengan una ANULACION_COMPRA registrada
// en la tabla kardex.
$sql_prov = "
    SELECT
        p.id AS id_proveedor,
        p.nombre_empresa,
        COUNT(c.id) AS total_facturas,
        SUM(c.total) AS total_invertido
    FROM compras c
    INNER JOIN proveedores p
        ON c.proveedor_id = p.id
    WHERE NOT EXISTS (
        SELECT 1
        FROM kardex k
        WHERE k.id_documento = c.id
          AND k.tipo_movimiento = 'ANULACION_COMPRA'
    )
    GROUP BY p.id, p.nombre_empresa
    ORDER BY total_invertido DESC
";

$res_prov = $conn->query($sql_prov);

// Preparar los datos para Chart.js
$nombres_proveedores = [];
$montos_proveedores = [];
$filas_tabla = [];
$gran_total_compras = 0.00;

while ($fila = $res_prov->fetch_assoc()) {
    $filas_tabla[] = $fila;

    $nombres_proveedores[] = $fila["nombre_empresa"];

    $montos_proveedores[] = (float)$fila["total_invertido"];

    $gran_total_compras += (float)$fila["total_invertido"];
}

$total_proveedores = count($filas_tabla);
$total_facturas = 0;

foreach ($filas_tabla as $fila) {
    $total_facturas += (int)$fila["total_facturas"];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reporte Gerencial de Compras - INCB</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- Librería Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background-color: #F8FAFC;
            color: #1E293B;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        .card-dash {
            background: #FFFFFF;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 25px;
        }

        .tabla-contenedor {
            overflow-x: auto;
        }

        .grafica-contenedor {
            position: relative;
            min-height: 300px;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-dark bg-dark mb-4 shadow-sm">
    <div class="container">

        <span class="navbar-brand font-weight-bold">
            Sistema de Inventario y Ventas - INCB
        </span>

        <a href="historial_compras.php"
           class="btn btn-outline-light btn-sm">
            Volver al historial
        </a>

    </div>
</nav>

<div class="container mb-5">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">

        <div>
            <h3 class="font-weight-bold text-dark mb-0">
                Cierre Gerencial: Módulo de Compras
            </h3>

            <p class="text-muted mb-0">
                Consolidado de compras por proveedor e indicadores estratégicos.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a href="exportar_compras_csv.php"
               class="btn btn-success font-weight-bold shadow-sm">
                Descargar Reporte en Excel (CSV)
            </a>
        </div>

    </div>

    <div class="row">

        <!-- Tarjetas de indicadores -->
        <div class="col-lg-4">

            <div class="card-dash text-center bg-primary text-white">

                <h6 class="text-uppercase font-weight-bold mb-2">
                    Presupuesto Total Invertido
                </h6>

                <h2 class="font-weight-bold mb-0">
                    $<?= number_format($gran_total_compras, 2) ?>
                </h2>

                <small>
                    Compras sin anulación registrada en Kardex
                </small>

            </div>

            <div class="card-dash">

                <h6 class="font-weight-bold text-secondary mb-3">
                    Indicadores Clave (KPIs)
                </h6>

                <ul class="list-group list-group-flush">

                    <li class="list-group-item d-flex justify-content-between px-0">
                        Proveedores atendidos:

                        <span class="badge badge-primary badge-pill">
                            <?= $total_proveedores ?>
                        </span>
                    </li>

                    <li class="list-group-item d-flex justify-content-between px-0">
                        Facturas contabilizadas:

                        <span class="badge badge-primary badge-pill">
                            <?= $total_facturas ?>
                        </span>
                    </li>

                    <li class="list-group-item d-flex justify-content-between px-0">
                        Estado del reporte:

                        <span class="badge badge-success">
                            Generado
                        </span>
                    </li>

                </ul>

            </div>

        </div>

        <!-- Gráfica -->
        <div class="col-lg-8">

            <div class="card-dash">

                <h6 class="font-weight-bold text-dark mb-3">
                    Distribución de Gasto por Proveedor
                </h6>

                <div class="grafica-contenedor">

                    <canvas id="graficaCompras"></canvas>

                </div>

                <?php if ($total_proveedores === 0): ?>
                    <p class="text-muted text-center mt-3">
                        No hay compras registradas para mostrar en la gráfica.
                    </p>
                <?php endif; ?>

            </div>

        </div>

    </div>

    <!-- Tabla de consolidación -->
    <div class="card-dash p-0 overflow-hidden">

        <div class="card-header bg-dark text-white font-weight-bold">
            Detalle de Cierre por Proveedor
        </div>

        <div class="tabla-contenedor">

            <table class="table table-hover table-striped mb-0">

                <thead class="thead-light">

                    <tr>
                        <th>#</th>
                        <th>Nombre del proveedor</th>
                        <th class="text-center">Facturas emitidas</th>
                        <th class="text-right">Monto invertido ($)</th>
                        <th class="text-right">% de participación</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if ($total_proveedores > 0): ?>

                        <?php
                        $i = 1;

                        foreach ($filas_tabla as $prov):

                            $porcentaje = ($gran_total_compras > 0)
                                ? ((float)$prov["total_invertido"] / $gran_total_compras) * 100
                                : 0;
                        ?>

                            <tr>

                                <td><?= $i++ ?></td>

                                <td class="font-weight-bold">
                                    <?= escapar($prov["nombre_empresa"]) ?>
                                </td>

                                <td class="text-center">
                                    <?= (int)$prov["total_facturas"] ?>
                                </td>

                                <td class="text-right font-weight-bold">
                                    $<?= number_format((float)$prov["total_invertido"], 2) ?>
                                </td>

                                <td class="text-right">
                                    <?= number_format($porcentaje, 2) ?>%
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="text-center text-muted p-4">
                                No existen compras para mostrar.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

                <?php if ($total_proveedores > 0): ?>

                    <tfoot class="font-weight-bold">

                        <tr>
                            <td colspan="2">TOTAL GENERAL</td>

                            <td class="text-center">
                                <?= $total_facturas ?>
                            </td>

                            <td class="text-right">
                                $<?= number_format($gran_total_compras, 2) ?>
                            </td>

                            <td class="text-right">
                                <?= number_format(
                                    array_sum(
                                        array_map(
                                            function ($fila) use ($gran_total_compras) {
                                                return $gran_total_compras > 0
                                                    ? ((float)$fila["total_invertido"] / $gran_total_compras) * 100
                                                    : 0;
                                            },
                                            $filas_tabla
                                        )
                                    ),
                                    2
                                ) ?>%
                            </td>
                        </tr>

                    </tfoot>

                <?php endif; ?>

            </table>

        </div>

    </div>

</div>

<!-- Gráfica dinámica con Chart.js -->
<script>
const nombresProveedores = <?= json_encode(
    $nombres_proveedores,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const montosProveedores = <?= json_encode(
    $montos_proveedores,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const canvas = document.getElementById("graficaCompras");

new Chart(canvas, {

    type: "bar",

    data: {
        labels: nombresProveedores,

        datasets: [{
            label: "Total invertido ($ USD)",
            data: montosProveedores,
            backgroundColor: "rgba(2, 132, 199, 0.7)",
            borderColor: "rgba(2, 132, 199, 1)",
            borderWidth: 1,
            borderRadius: 6
        }]
    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return "Total invertido: $" +
                            Number(context.parsed.y).toFixed(2);
                    }
                }
            }
        },

        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: "Monto invertido ($)"
                }
            }
        }

    }

});
</script>

</body>
</html>
