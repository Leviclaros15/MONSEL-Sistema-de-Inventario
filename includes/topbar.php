<?php
// Espera que $pageTitle esté definido en la página que lo incluye
$nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';
$iniciales = '';
foreach (explode(' ', trim($nombreUsuario)) as $palabra) {
    if ($palabra !== '') {
        $iniciales .= substr($palabra, 0, 1);
    }
}
$iniciales = strtoupper(substr($iniciales, 0, 2));
?>
<header class="topbar">
  <h1><?= htmlspecialchars($pageTitle ?? '') ?></h1>
  <div class="user">
    <span><?= htmlspecialchars($nombreUsuario) ?></span>
    <div class="avatar"><?= htmlspecialchars($iniciales) ?></div>
  </div>
</header>
