
<?php
require_once "config/db.php";

// Solo aceptar envíos mediante POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: clientes.php");
    exit;
}

// Recibir y limpiar los datos
$nombre = trim($_POST['nombre_completo'] ?? '');
$tipoDocumento = trim($_POST['tipo_documento'] ?? '');
$numeroDocumento = trim($_POST['numero_documento'] ?? '');
$tipoCliente = trim($_POST['tipo_cliente'] ?? '');
$limiteCreditoTexto = trim($_POST['limite_credito'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$email = trim($_POST['email'] ?? '');

// Función para regresar mostrando un error
function regresarConError($mensaje) {
    header("Location: clientes.php?error=" . urlencode($mensaje));
    exit;
}

// 1. Validar campos obligatorios
if (
    $nombre === '' ||
    $tipoDocumento === '' ||
    $numeroDocumento === '' ||
    $tipoCliente === '' ||
    $limiteCreditoTexto === ''
) {
    regresarConError("Todos los campos obligatorios deben completarse.");
}

// 2. Validar longitud del nombre
if (mb_strlen($nombre, 'UTF-8') > 150) {
    regresarConError("El nombre no debe superar los 150 caracteres.");
}

// 3. Validar tipo de documento
$tiposDocumentoPermitidos = ['DUI', 'NIT', 'PASAPORTE'];

if (!in_array($tipoDocumento, $tiposDocumentoPermitidos, true)) {
    regresarConError("El tipo de documento seleccionado no es válido.");
}

// 4. Validar tipo de cliente
$tiposClientePermitidos = ['FINAL', 'FISCAL'];

if (!in_array($tipoCliente, $tiposClientePermitidos, true)) {
    regresarConError("El tipo de cliente seleccionado no es válido.");
}

// 5. Validar límite de crédito
if (!is_numeric($limiteCreditoTexto)) {
    regresarConError("El límite de crédito debe ser un número válido.");
}

$limiteCredito = (float)$limiteCreditoTexto;

if (!is_finite($limiteCredito) || $limiteCredito < 0) {
    regresarConError("El límite de crédito no puede ser negativo.");
}

if ($limiteCredito > 99999999.99) {
    regresarConError("El límite de crédito supera el máximo permitido.");
}

// 6. Validar teléfono
if (strlen($telefono) > 20) {
    regresarConError("El teléfono no debe superar los 20 caracteres.");
}

// 7. Validar correo electrónico
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    regresarConError("El correo electrónico no tiene un formato válido.");
}

if (strlen($email) > 100) {
    regresarConError("El correo no debe superar los 100 caracteres.");
}

try {

    // 8. Comprobar si el documento ya está registrado
    $consulta = "SELECT id
                 FROM clientes
                 WHERE numero_documento = :documento
                 LIMIT 1";

    $stmt = $pdo->prepare($consulta);

    $stmt->execute([
        ':documento' => $numeroDocumento
    ]);

    if ($stmt->fetch()) {
        regresarConError("Ya existe un cliente registrado con ese documento.");
    }

    // 9. Insertar el nuevo cliente
    $sql = "INSERT INTO clientes (
                nombre_completo,
                tipo_documento,
                numero_documento,
                telefono,
                email,
                tipo_cliente,
                limite_credito,
                saldo_actual,
                estado
            ) VALUES (
                :nombre,
                :tipo_documento,
                :numero_documento,
                :telefono,
                :email,
                :tipo_cliente,
                :limite_credito,
                0.00,
                'ACTIVO'
            )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':nombre' => $nombre,
        ':tipo_documento' => $tipoDocumento,
        ':numero_documento' => $numeroDocumento,
        ':telefono' => $telefono !== '' ? $telefono : null,
        ':email' => $email !== '' ? $email : null,
        ':tipo_cliente' => $tipoCliente,
        ':limite_credito' => number_format($limiteCredito, 2, '.', '')
    ]);

    // 10. Regresar al listado
    header("Location: clientes.php?mensaje=" . urlencode("Cliente registrado correctamente."));
    exit;

} catch (PDOException $e) {

    // Registrar el error técnico sin mostrar detalles internos al usuario
    error_log("Error al guardar cliente: " . $e->getMessage());

    // También proteger contra duplicados detectados por la restricción UNIQUE
    if ($e->getCode() === '23000') {
        regresarConError("El documento ya está registrado o existe un conflicto de datos.");
    }

    regresarConError("No fue posible guardar el cliente. Verifica los datos e intenta nuevamente.");
}
