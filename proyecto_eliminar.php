<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_proyecto'])) {
    try {
        $stmt = $pdo->prepare('DELETE FROM proyectos WHERE id_proyecto = ?');
        $stmt->execute([$_POST['id_proyecto']]);
        flash_set('success', 'Proyecto eliminado correctamente.');
    } catch (PDOException $e) {
        flash_set('error', 'No se puede eliminar: este proyecto tiene movimientos asociados.');
    }
}

header('Location: proyectos.php');
exit;
