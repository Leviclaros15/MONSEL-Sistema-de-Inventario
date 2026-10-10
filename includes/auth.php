<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protege cualquier página que incluya este archivo:
// si no hay sesión activa, redirige al login.
if (empty($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}
