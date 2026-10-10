<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_material'])) {
    try {
        $stmt = $pdo->prepare('DELETE FROM materiales WHERE id_material = ?');
        $stmt->execute([$_POST['id_material']]);
        flash_set('success', 'Material eliminado correctamente.');
    } catch (PDOException $e) {
        flash_set('error', 'No se puede eliminar: este material tiene movimientos registrados.');
    }
}

header('Location: inventario.php');
exit;
