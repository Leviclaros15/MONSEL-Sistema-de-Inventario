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
$cantidad      = (float) ($_POST['cantidad'] ?? 0);
$stock_minimo  = (float) ($_POST['stock_minimo'] ?? 0);
$ubicacion     = trim($_POST['ubicacion'] ?? '');
$estado        = $_POST['estado'] ?? 'Disponible';
$observaciones = trim($_POST['observaciones'] ?? '');

if ($cantidad <= 0) {
    $estado = 'Agotado';
}

if ($cantidad < 0 || $stock_minimo < 0) {
    flash_set('error', 'La cantidad y el stock mínimo no pueden ser números negativos.');
    header('Location: inventario.php');
    exit;
}

if ($codigo === '' || $nombre === '' || $id_categoria === '' || $unidad_medida === '' || $ubicacion === '') {
    flash_set('error', 'Por favor completa todos los campos obligatorios.');
    header('Location: inventario.php');
    exit;
}

// Validar si la categoría es Herramientas o la unidad es unidades/piezas
$stmtCat = $pdo->prepare('SELECT nombre_categoria FROM categorias WHERE id_categoria = ?');
$stmtCat->execute([$id_categoria]);
$nombreCat = strtolower($stmtCat->fetchColumn() ?: '');
$unidadNom = strtolower($unidad_medida);

if ((strpos($nombreCat, 'herramienta') !== false || strpos($unidadNom, 'unidad') !== false || strpos($unidadNom, 'pieza') !== false)) {
    if (fmod($cantidad, 1) != 0 || fmod($stock_minimo, 1) != 0) {
        flash_set('error', 'Para herramientas o artículos medidos en unidades, la cantidad y stock mínimo deben ser números enteros.');
        header('Location: inventario.php');
        exit;
    }
}

try {
    if ($id_material !== '') {
        // Actualizar material existente
        $stmt = $pdo->prepare("
            UPDATE materiales
            SET id_categoria = ?, codigo = ?, nombre = ?, unidad_medida = ?,
                cantidad = ?, stock_minimo = ?, ubicacion = ?, estado = ?, observaciones = ?
            WHERE id_material = ?
        ");
        $stmt->execute([$id_categoria, $codigo, $nombre, $unidad_medida, $cantidad, $stock_minimo, $ubicacion, $estado, $observaciones, $id_material]);
        flash_set('success', 'Material actualizado correctamente.');
    } else {
        // Insertar nuevo material
        $stmt = $pdo->prepare("
            INSERT INTO materiales (id_categoria, codigo, nombre, unidad_medida, cantidad, stock_minimo, ubicacion, estado, observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id_categoria, $codigo, $nombre, $unidad_medida, $cantidad, $stock_minimo, $ubicacion, $estado, $observaciones]);
        flash_set('success', 'Material agregado correctamente.');
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
