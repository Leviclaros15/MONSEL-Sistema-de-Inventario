<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: proyectos.php');
    exit;
}

$nombre_proyecto = trim($_POST['nombre_proyecto'] ?? '');
$ubicacion       = trim($_POST['ubicacion'] ?? '');
$fecha           = $_POST['fecha'] ?? date('Y-m-d');
$estado          = $_POST['estado'] ?? 'Pendiente';

if ($nombre_proyecto === '' || $ubicacion === '') {
    flash_set('error', 'Por favor completa todos los campos obligatorios.');
    header('Location: proyectos.php');
    exit;
}

$stmt = $pdo->prepare('INSERT INTO proyectos (nombre_proyecto, ubicacion, fecha, estado) VALUES (?, ?, ?, ?)');
$stmt->execute([$nombre_proyecto, $ubicacion, $fecha, $estado]);

flash_set('success', 'Proyecto agregado correctamente.');
header('Location: proyectos.php');
exit;
