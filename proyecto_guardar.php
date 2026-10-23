<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: proyectos.php');
    exit;
}

$id_proyecto = (int) ($_POST['id_proyecto'] ?? 0);
$nombre_proyecto = trim($_POST['nombre_proyecto'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');
$fecha = $_POST['fecha'] ?? date('Y-m-d');
$estado = $_POST['estado'] ?? 'Pendiente';

if ($nombre_proyecto === '' || $ubicacion === '') {
    flash_set('error', 'Por favor completa todos los campos obligatorios.');
    header('Location: proyectos.php');
    exit;
}

try {
    if ($id_proyecto > 0) {
        // Editar proyecto existente
        $stmt = $pdo->prepare('
            UPDATE proyectos
            SET nombre_proyecto = ?, ubicacion = ?, fecha = ?, estado = ?
            WHERE id_proyecto = ?
        ');
        $stmt->execute([$nombre_proyecto, $ubicacion, $fecha, $estado, $id_proyecto]);
        flash_set('success', 'Proyecto actualizado correctamente.');
    } else {
        // Crear nuevo proyecto
        $stmt = $pdo->prepare('INSERT INTO proyectos (nombre_proyecto, ubicacion, fecha, estado) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nombre_proyecto, $ubicacion, $fecha, $estado]);
        flash_set('success', 'Proyecto agregado correctamente.');
    }
} catch (PDOException $e) {
    flash_set('error', 'Error al guardar el proyecto.');
}

header('Location: proyectos.php');
exit;
