
<?php
require_once "conexion.php";

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

// Consultar todos los productos
$sql = "SELECT id, nombre_producto, stock, precio
        FROM productos
        ORDER BY nombre_producto ASC";

$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de productos</title>

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
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
        }

        h1 {
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
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #243447;
            color: white;
        }

        .boton {
            display: inline-block;
            padding: 8px 12px;
            margin: 3px;
            background: #2878a7;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 13px;
        }

        .boton:hover {
            background: #1d5c80;
        }

        .boton-compra {
            background: #287a50;
        }

        .mensaje {
            padding: 15px;
            background: #e9ecef;
            border-radius: 5px;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h1>Listado de productos</h1>

    <p>
        Consulta los productos registrados y revisa sus movimientos
        de inventario mediante el Kardex.
    </p>

    <a class="boton boton-compra" href="registrar_compra.php">
        Registrar nueva compra
    </a>

    <div class="tabla-contenedor">

        <?php if ($resultado->num_rows > 0): ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Stock actual</th>
                        <th>Precio de venta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($producto = $resultado->fetch_assoc()): ?>

                        <tr>
                            <td><?= (int)$producto["id"] ?></td>

                            <td>
                                <?= escapar($producto["nombre_producto"]) ?>
                            </td>

                            <td>
                                <?= (int)$producto["stock"] ?>
                            </td>

                            <td>
                                $<?= number_format((float)$producto["precio"], 2) ?>
                            </td>

                            <td>
                                <a
                                    class="boton"
                                    href="kardex_producto.php?id=<?= (int)$producto["id"] ?>"
                                >
                                    Ver Kardex
                                </a>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                </tbody>
            </table>

        <?php else: ?>

            <p class="mensaje">
                No hay productos registrados en el inventario.
            </p>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
