
<?php
require_once "conexion.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

$error = "";
$compra_guardada = false;
$id_compra_guardada = 0;
$id_producto_guardado = 0;

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $proveedor_id = filter_input(INPUT_POST, "proveedor_id", FILTER_VALIDATE_INT);
    $usuario_id = filter_input(INPUT_POST, "usuario_id", FILTER_VALIDATE_INT);
    $producto_id = filter_input(INPUT_POST, "producto_id", FILTER_VALIDATE_INT);
    $cantidad = filter_input(INPUT_POST, "cantidad", FILTER_VALIDATE_INT);
    $precio_compra = filter_input(INPUT_POST, "precio_compra", FILTER_VALIDATE_FLOAT);

    if (
        !$proveedor_id || !$usuario_id || !$producto_id ||
        !$cantidad || $cantidad <= 0 ||
        $precio_compra === false || $precio_compra === null ||
        $precio_compra <= 0
    ) {
        $error = "Verifica los datos. La cantidad y el costo deben ser mayores que cero.";
    } else {

        try {
            $conn->begin_transaction();

            // Verificar que el proveedor exista
            $stmt = $conn->prepare(
                "SELECT id FROM proveedores WHERE id = ?"
            );
            $stmt->bind_param("i", $proveedor_id);
            $stmt->execute();
            $proveedor = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$proveedor) {
                throw new Exception("El proveedor seleccionado no existe.");
            }

            // Verificar que el usuario exista
            $stmt = $conn->prepare(
                "SELECT id FROM usuarios WHERE id = ?"
            );
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $usuario = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$usuario) {
                throw new Exception("El usuario seleccionado no existe.");
            }

            // Bloquear el producto durante la operación
            $stmt = $conn->prepare(
                "SELECT id, stock
                 FROM productos
                 WHERE id = ?
                 FOR UPDATE"
            );
            $stmt->bind_param("i", $producto_id);
            $stmt->execute();
            $producto = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$producto) {
                throw new Exception("El producto seleccionado no existe.");
            }

            // Calcular el stock anterior y el nuevo stock
            $stock_anterior = (int)$producto["stock"];
            $stock_actual = $stock_anterior + $cantidad;

            // Calcular el total de la compra
            $total = $cantidad * $precio_compra;

            // 1. Registrar encabezado de compra
            $stmt = $conn->prepare(
                "INSERT INTO compras
                    (proveedor_id, usuario_id, fecha, total)
                 VALUES (?, ?, NOW(), ?)"
            );
            $stmt->bind_param(
                "iid",
                $proveedor_id,
                $usuario_id,
                $total
            );
            $stmt->execute();

            $id_compra = $conn->insert_id;
            $stmt->close();

            // 2. Registrar detalle de compra
            $stmt = $conn->prepare(
                "INSERT INTO detalle_compras
                    (compra_id, producto_id, cantidad, precio_compra)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "iiid",
                $id_compra,
                $producto_id,
                $cantidad,
                $precio_compra
            );
            $stmt->execute();
            $stmt->close();

            // 3. Actualizar el stock del producto
            $stmt = $conn->prepare(
                "UPDATE productos
                 SET stock = ?
                 WHERE id = ?"
            );
            $stmt->bind_param(
                "ii",
                $stock_actual,
                $producto_id
            );
            $stmt->execute();
            $stmt->close();

            // 4. Registrar automáticamente la entrada en el Kardex
            $tipo_movimiento = "ENTRADA_COMPRA";
            $cantidad_salida = 0;
            $motivo = "Entrada de inventario por compra";

            $stmt = $conn->prepare(
                "INSERT INTO kardex (
                    id_producto,
                    id_documento,
                    tipo_movimiento,
                    fecha,
                    cantidad_entrada,
                    cantidad_salida,
                    costo_unitario,
                    stock_anterior,
                    stock_actual,
                    motivo
                ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)"
            );

           $stmt->bind_param(
    "iisiidiis",
    $producto_id,
    $id_compra,
    $tipo_movimiento,
    $cantidad,
    $cantidad_salida,
    $precio_compra,
    $stock_anterior,
    $stock_actual,
    $motivo
);

            $stmt->execute();
            $stmt->close();

            // Confirmar todos los cambios
            $conn->commit();

            $compra_guardada = true;
            $id_compra_guardada = $id_compra;
            $id_producto_guardado = $producto_id;

        } catch (Throwable $e) {

            $conn->rollback();

            $error = "No se pudo registrar la compra: " . $e->getMessage();
        }
    }
}

// Consultar proveedores
$proveedores = $conn->query(
    "SELECT id, nombre_empresa
     FROM proveedores
     ORDER BY nombre_empresa ASC"
);

// Consultar usuarios
$usuarios = $conn->query(
    "SELECT id, nombre_completo
     FROM usuarios
     ORDER BY nombre_completo ASC"
);

// Consultar productos
$productos = $conn->query(
    "SELECT id, nombre_producto, stock
     FROM productos
     ORDER BY nombre_producto ASC"
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrar compra</title>

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
            max-width: 750px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
        }

        h1 {
            color: #243447;
            margin-top: 0;
        }

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        select, input {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }

        button, .boton {
            display: inline-block;
            margin-top: 22px;
            padding: 12px 18px;
            background: #2878a7;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
        }

        .mensaje {
            padding: 14px;
            border-radius: 5px;
            margin-bottom: 18px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .exito {
            background: #d4edda;
            color: #155724;
        }

        .ayuda {
            color: #666;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .contenedor {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h1>Registrar nueva compra</h1>

    <p>Registra una compra y actualiza automáticamente el inventario y el Kardex.</p>

    <?php if ($error !== ""): ?>
        <div class="mensaje error">
            <?= escapar($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($compra_guardada): ?>

        <div class="mensaje exito">
            <strong>¡Compra registrada correctamente!</strong><br>
            Número de compra: #<?= (int)$id_compra_guardada ?><br>
            La entrada también fue registrada en el Kardex.
        </div>

        <a class="boton"
           href="detalle_compra.php?id=<?= (int)$id_compra_guardada ?>">
            Ver detalle de compra
        </a>

        <a class="boton"
           href="kardex_producto.php?id=<?= (int)$id_producto_guardado ?>">
            Ver Kardex del producto
        </a>

        <a class="boton"
           href="registrar_compra.php">
            Registrar otra compra
        </a>

    <?php else: ?>

        <?php if (
            $proveedores->num_rows === 0 ||
            $usuarios->num_rows === 0 ||
            $productos->num_rows === 0
        ): ?>

            <div class="mensaje error">
                Necesitas tener al menos un proveedor, un usuario y un producto
                registrados antes de realizar una compra.
            </div>

        <?php else: ?>

            <form method="POST" action="">

                <label for="proveedor_id">Proveedor:</label>

                <select name="proveedor_id" id="proveedor_id" required>
                    <option value="">Selecciona un proveedor</option>

                    <?php while ($fila = $proveedores->fetch_assoc()): ?>
                        <option value="<?= (int)$fila["id"] ?>">
                            <?= escapar($fila["nombre_empresa"]) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="usuario_id">Usuario responsable:</label>

                <select name="usuario_id" id="usuario_id" required>
                    <option value="">Selecciona un usuario</option>

                    <?php while ($fila = $usuarios->fetch_assoc()): ?>
                        <option value="<?= (int)$fila["id"] ?>">
                            <?= escapar($fila["nombre_completo"]) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="producto_id">Producto:</label>

                <select name="producto_id" id="producto_id" required>
                    <option value="">Selecciona un producto</option>

                    <?php while ($fila = $productos->fetch_assoc()): ?>
                        <option value="<?= (int)$fila["id"] ?>">
                            <?= escapar($fila["nombre_producto"]) ?>
                            (Stock actual: <?= (int)$fila["stock"] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="cantidad">Cantidad comprada:</label>

                <input
                    type="number"
                    name="cantidad"
                    id="cantidad"
                    min="1"
                    step="1"
                    value="15"
                    required
                >

                <label for="precio_compra">Costo de compra por unidad ($):</label>

                <input
                    type="number"
                    name="precio_compra"
                    id="precio_compra"
                    min="0.01"
                    step="0.01"
                    placeholder="Ejemplo: 10.00"
                    required
                >

                <p class="ayuda">
                    Introduce el costo al que compraste cada unidad,
                    no el precio de venta del producto.
                </p>

                <button type="submit">
                    Guardar compra y registrar Kardex
                </button>

                <a class="boton" href="historial_compras.php">
                    Volver al historial
                </a>

            </form>

        <?php endif; ?>

    <?php endif; ?>

</div>

</body>
</html>
