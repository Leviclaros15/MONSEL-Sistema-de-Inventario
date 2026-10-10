<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: movimiento.php');
    exit;
}

$tipo          = $_POST['tipo_movimiento'] ?? '';
$fecha         = $_POST['fecha'] ?? date('Y-m-d');
$id_material   = $_POST['id_material'] ?? '';
$cantidad      = (float) ($_POST['cantidad'] ?? 0);
$ubicacion     = trim($_POST['ubicacion'] ?? '');
$id_proyecto   = $_POST['id_proyecto'] ?? '';
$observaciones = trim($_POST['observaciones'] ?? '');
$id_usuario    = $_SESSION['id_usuario'];

$hoy = date('Y-m-d');
if ($fecha < $hoy) {
    flash_set('error', 'No puedes registrar movimientos con una fecha pasada.');
    header('Location: movimiento.php');
    exit;
}

if (!in_array($tipo, ['Entrada', 'Salida'], true) || $id_material === '' || $cantidad <= 0 || $ubicacion === '') {
    flash_set('error', 'Por favor completa todos los campos obligatorios con valores válidos.');
    header('Location: movimiento.php');
    exit;
}

$id_proyecto = $id_proyecto === '' ? null : $id_proyecto;

try {
    $pdo->beginTransaction();

    // Bloquea la fila del material para evitar condiciones de carrera
    $stmt = $pdo->prepare('
        SELECT m.cantidad, m.unidad_medida, c.nombre_categoria
        FROM materiales m
        JOIN categorias c ON c.id_categoria = m.id_categoria
        WHERE m.id_material = ?
        FOR UPDATE
    ');
    $stmt->execute([$id_material]);
    $material = $stmt->fetch();

    if (!$material) {
        throw new Exception('El material seleccionado ya no existe.');
    }

    $categoriaNom = strtolower($material['nombre_categoria'] ?? '');
    $unidadNom    = strtolower($material['unidad_medida'] ?? '');
    if ((strpos($categoriaNom, 'herramienta') !== false || strpos($unidadNom, 'unidad') !== false || strpos($unidadNom, 'pieza') !== false) && fmod($cantidad, 1) != 0) {
        throw new Exception('Para herramientas o artículos medidos en unidades, la cantidad debe ser un número entero (sin decimales).');
    }

    $stockActual = (float) $material['cantidad'];

    if ($tipo === 'Salida' && $cantidad > $stockActual) {
        throw new Exception("No hay suficiente stock disponible (stock actual: $stockActual).");
    }

    $nuevoStock = $tipo === 'Entrada' ? $stockActual + $cantidad : $stockActual - $cantidad;
    $nuevoEstado = $nuevoStock <= 0 ? 'Agotado' : 'Disponible';

    $update = $pdo->prepare('UPDATE materiales SET cantidad = ?, estado = ? WHERE id_material = ?');
    $update->execute([$nuevoStock, $nuevoEstado, $id_material]);

    $insert = $pdo->prepare("
        INSERT INTO movimientos (id_material, id_proyecto, id_usuario, tipo_movimiento, cantidad, ubicacion, observaciones, fecha_movimiento)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->execute([$id_material, $id_proyecto, $id_usuario, $tipo, $cantidad, $ubicacion, $observaciones, $fecha . ' ' . date('H:i:s')]);

    $pdo->commit();
    flash_set('success', "Movimiento de $tipo registrado correctamente. Stock actualizado a $nuevoStock.");
} catch (Exception $e) {
    $pdo->rollBack();
    flash_set('error', $e->getMessage());
}

header('Location: movimiento.php');
exit;
