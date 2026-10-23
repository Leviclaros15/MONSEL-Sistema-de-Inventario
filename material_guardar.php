<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inventario.php');
    exit;
}

$id_material   = trim($_POST['id_material'] ?? '');
$codigo        = trim($_POST['codigo'] ?? '');
$nombre        = trim($_POST['nombre'] ?? '');
$id_categoria  = $_POST['id_categoria'] ?? '';
$unidad_medida = trim($_POST['unidad_medida'] ?? '');
$stock_minimo  = (float) ($_POST['stock_minimo'] ?? 0);
$ubicacion     = trim($_POST['ubicacion'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

if ($stock_minimo < 0) {
    flash_set('error', 'El stock mínimo no puede ser negativo.');
    header('Location: inventario.php');
    exit;
}

if ($codigo === '' || $nombre === '' || $id_categoria === '' || $unidad_medida === '' || $ubicacion === '') {
    flash_set('error', 'Por favor completa todos los campos obligatorios.');
    header('Location: inventario.php');
    exit;
}

try {
    if ($id_material !== '') {
        // Actualizar material existente (sin modificar cantidad - se hace por movimientos)
        $stmt = $pdo->prepare("
            UPDATE materiales
            SET id_categoria = ?, codigo = ?, nombre = ?, unidad_medida = ?,
                stock_minimo = ?, ubicacion = ?, observaciones = ?
            WHERE id_material = ?
        ");
        $stmt->execute([$id_categoria, $codigo, $nombre, $unidad_medida, $stock_minimo, $ubicacion, $observaciones, $id_material]);
        flash_set('success', 'Material actualizado correctamente. Nota: El stock solo se modifica mediante entradas/salidas.');
    } else {
        // Insertar nuevo material con cantidad 0 (se abastece por movimientos)
        $stmt = $pdo->prepare("
            INSERT INTO materiales (id_categoria, codigo, nombre, unidad_medida, cantidad, stock_minimo, ubicacion, estado, observaciones)
            VALUES (?, ?, ?, ?, 0, ?, ?, 'Agotado', ?)
        ");
        $stmt->execute([$id_categoria, $codigo, $nombre, $unidad_medida, $stock_minimo, $ubicacion, $observaciones]);
        flash_set('success', 'Material agregado correctamente. Registra una entrada para agregar stock.');
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        flash_set('error', 'Ya existe un material con ese código.');
    } else {
        flash_set('error', 'Ocurrió un error al guardar el material.');
    }
}

header('Location: inventario.php');
exit;
