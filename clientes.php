
<?php
require_once "config/db.php";

// Consultar todos los clientes
$sql = "SELECT
            id,
            nombre_completo,
            tipo_documento,
            numero_documento,
            telefono,
            email,
            tipo_cliente,
            limite_credito,
            saldo_actual,
            estado
        FROM clientes
        ORDER BY nombre_completo ASC";

$stmt = $pdo->query($sql);
$clientes = $stmt->fetchAll();

// Función para proteger la salida HTML
function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

// Mensajes recibidos desde cliente_guardar.php
$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartera de Clientes | Sistema de Inventario</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f5f6f8;
        }

        .encabezado {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 22px;
        }

        .tabla-contenedor {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #dee2e6;
            padding: 20px;
        }

        .progress {
            height: 10px;
            min-width: 110px;
        }

        .dato-secundario {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .table th {
            white-space: nowrap;
        }
    </style>
</head>

<body>

<div class="container-fluid px-4 py-4">

    <!-- ENCABEZADO -->
    <div class="encabezado mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

            <div>
                <h2 class="mb-1">
                    <i class="bi bi-people-fill text-primary"></i>
                    Cartera de Clientes
                </h2>
                <p class="text-muted mb-0">
                    Administración de clientes y control de crédito.
                </p>
            </div>

            <button class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalCliente">
                <i class="bi bi-person-plus-fill"></i>
                Registrar cliente
            </button>

        </div>
    </div>

    <!-- MENSAJES -->
    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= escapar($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= escapar($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- TABLA DE CLIENTES -->
    <div class="tabla-contenedor">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Listado de clientes</h5>
            <span class="badge text-bg-secondary">
                <?= count($clientes) ?> clientes registrados
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">

                <thead class="table-light">
                    <tr>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th>Tipo</th>
                        <th>Límite de crédito</th>
                        <th>Saldo actual</th>
                        <th>Crédito disponible</th>
                        <th>Consumo de crédito</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($clientes) > 0): ?>

                    <?php foreach ($clientes as $cliente): ?>

                        <?php
                        $limite = (float)$cliente['limite_credito'];
                        $saldo = (float)$cliente['saldo_actual'];

                        // Crédito disponible
                        $disponible = $limite - $saldo;

                        // Porcentaje de utilización
                        if ($limite > 0) {
                            $porcentaje = ($saldo / $limite) * 100;
                        } else {
                            $porcentaje = ($saldo > 0) ? 100 : 0;
                        }

                        // Evitar valores negativos
                        $porcentaje = max(0, $porcentaje);

                        // La barra visual no debe superar el 100%
                        $anchoBarra = min(100, $porcentaje);

                        // Determinar color de la barra
                        if ($cliente['estado'] === 'BLOQUEADO' || $porcentaje >= 90) {
                            $colorBarra = 'bg-danger';
                        } elseif ($porcentaje >= 60) {
                            $colorBarra = 'bg-warning';
                        } else {
                            $colorBarra = 'bg-success';
                        }

                        // Color del estado
                        $colorEstado = 'success';

                        if ($cliente['estado'] === 'BLOQUEADO') {
                            $colorEstado = 'danger';
                        } elseif ($cliente['estado'] === 'INACTIVO') {
                            $colorEstado = 'secondary';
                        }
                        ?>

                        <tr>

                            <td>
                                <strong><?= escapar($cliente['nombre_completo']) ?></strong>
                                <div class="dato-secundario">
                                    <?= escapar($cliente['telefono'] ?: 'Sin teléfono') ?>
                                </div>
                            </td>

                            <td>
                                <?= escapar($cliente['numero_documento']) ?>
                                <div class="dato-secundario">
                                    <?= escapar($cliente['tipo_documento']) ?>
                                </div>
                            </td>

                            <td>
                                <?php if ($cliente['tipo_cliente'] === 'FISCAL'): ?>
                                    <span class="badge text-bg-info">Fiscal</span>
                                <?php else: ?>
                                    <span class="badge text-bg-light border">Final</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                $<?= number_format($limite, 2) ?>
                            </td>

                            <td>
                                $<?= number_format($saldo, 2) ?>
                            </td>

                            <td class="<?= $disponible < 0 ? 'text-danger fw-bold' : 'fw-semibold' ?>">
                                $<?= number_format($disponible, 2) ?>
                            </td>

                            <td style="min-width: 170px;">

                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Utilización</small>
                                    <small class="fw-semibold">
                                        <?= number_format($porcentaje, 1) ?>%
                                    </small>
                                </div>

                                <div class="progress"
                                     role="progressbar"
                                     aria-valuenow="<?= min(100, $porcentaje) ?>"
                                     aria-valuemin="0"
                                     aria-valuemax="100">

                                    <div class="progress-bar <?= $colorBarra ?>"
                                         style="width: <?= $anchoBarra ?>%">
                                    </div>

                                </div>

                            </td>

                            <td>
                                <span class="badge text-bg-<?= $colorEstado ?>">
                                    <?= escapar($cliente['estado']) ?>
                                </span>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            No hay clientes registrados.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>
        </div>
    </div>

</div>


<!-- MODAL PARA REGISTRAR CLIENTE -->
<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <form action="cliente_guardar.php" method="POST">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus"></i>
                        Registrar nuevo cliente
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-12">
                            <label class="form-label">Nombre completo *</label>
                            <input type="text"
                                   name="nombre_completo"
                                   class="form-control"
                                   maxlength="150"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tipo de documento *</label>
                            <select name="tipo_documento" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="DUI">DUI</option>
                                <option value="NIT">NIT</option>
                                <option value="PASAPORTE">Pasaporte</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Número de documento *</label>
                            <input type="text"
                                   name="numero_documento"
                                   class="form-control"
                                   maxlength="20"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tipo de cliente *</label>
                            <select name="tipo_cliente" class="form-select" required>
                                <option value="FINAL">Consumidor final</option>
                                <option value="FISCAL">Cliente fiscal</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Límite de crédito ($) *</label>
                            <input type="number"
                                   name="limite_credito"
                                   class="form-control"
                                   min="0"
                                   step="0.01"
                                   value="0.00"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text"
                                   name="telefono"
                                   class="form-control"
                                   maxlength="20">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   maxlength="100">
                        </div>

                        <div class="col-md-12">
                            <small class="text-muted">
                                El saldo inicial será $0.00 y el estado será ACTIVO.
                            </small>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i>
                        Guardar cliente
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
