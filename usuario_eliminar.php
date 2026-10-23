<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

// Verificar que sea administrador
if ($_SESSION['rol'] !== 'administrador') {
    flash_set('error', 'No tienes permisos para realizar esta acción.');
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_usuario'])) {
    $id = (int) $_POST['id_usuario'];

    // No permitir eliminar el propio usuario
    if ($id === $_SESSION['id_usuario']) {
        flash_set('error', 'No puedes eliminar tu propio usuario.');
    } else {
        try {
            $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id_usuario = ?');
            $stmt->execute([$id]);
            flash_set('success', 'Usuario eliminado correctamente.');
        } catch (PDOException $e) {
            flash_set('error', 'No se pudo eliminar el usuario. Es posible que tenga movimientos asociados.');
        }
    }
}

header('Location: usuarios.php');
exit;
