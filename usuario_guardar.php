<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

// Verificar que sea administrador
if ($_SESSION['rol'] !== 'administrador') {
    flash_set('error', 'No tienes permisos para realizar esta acción.');
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}

$id_usuario = (int) ($_POST['id_usuario'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$contrasena = trim($_POST['contrasena'] ?? '');
$rol = $_POST['rol'] ?? 'tecnico';
$estado = $_POST['estado'] ?? 'activo';

if ($nombre === '' || $usuario === '' || $correo === '') {
    flash_set('error', 'Por favor completa todos los campos obligatorios.');
    header('Location: usuarios.php');
    exit;
}

// Validar formato de correo
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    flash_set('error', 'El correo electrónico no es válido.');
    header('Location: usuarios.php');
    exit;
}

try {
    if ($id_usuario > 0) {
        // Editar usuario existente
        if ($contrasena !== '') {
            // Actualizar con nueva contraseña
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
                UPDATE usuarios
                SET nombre = ?, usuario = ?, correo = ?, contrasena = ?, rol = ?, estado = ?
                WHERE id_usuario = ?
            ');
            $stmt->execute([$nombre, $usuario, $correo, $hash, $rol, $estado, $id_usuario]);
        } else {
            // Actualizar sin cambiar contraseña
            $stmt = $pdo->prepare('
                UPDATE usuarios
                SET nombre = ?, usuario = ?, correo = ?, rol = ?, estado = ?
                WHERE id_usuario = ?
            ');
            $stmt->execute([$nombre, $usuario, $correo, $rol, $estado, $id_usuario]);
        }
        flash_set('success', 'Usuario actualizado correctamente.');
    } else {
        // Crear nuevo usuario
        if ($contrasena === '') {
            flash_set('error', 'La contraseña es obligatoria para nuevos usuarios.');
            header('Location: usuarios.php');
            exit;
        }
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('
            INSERT INTO usuarios (nombre, usuario, correo, contrasena, rol, estado)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$nombre, $usuario, $correo, $hash, $rol, $estado]);
        flash_set('success', 'Usuario creado correctamente.');
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        flash_set('error', 'El usuario o correo ya está registrado.');
    } else {
        flash_set('error', 'Error al guardar el usuario.');
    }
}

header('Location: usuarios.php');
exit;
