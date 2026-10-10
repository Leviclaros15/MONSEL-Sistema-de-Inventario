<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function nav_active(string $file, string $current): string
{
    return $file === $current ? 'active' : '';
}
?>
<aside class="sidebar">
  <a href="dashboard.php" class="brand">
    <img src="assets/imgs/logo_monsel.png" alt="MONSEL" class="brand-logo">
    <div>
      <strong>MONSEL</strong>
      <span>Sistema de inventario</span>
    </div>
  </a>
  <nav>
    <a href="dashboard.php" class="<?= nav_active('dashboard.php', $currentPage) ?>"><img src="assets/icons/home.svg" alt="" class="ico"> Inicio</a>
    <a href="inventario.php" class="<?= nav_active('inventario.php', $currentPage) ?>"><img src="assets/icons/inventory.svg" alt="" class="ico"> Inventario</a>
    <a href="movimiento.php" class="<?= nav_active('movimiento.php', $currentPage) ?>"><img src="assets/icons/transfer.svg" alt="" class="ico"> Entradas / Salidas</a>
    <a href="proyectos.php" class="<?= nav_active('proyectos.php', $currentPage) ?>"><img src="assets/icons/projects.svg" alt="" class="ico"> Proyectos realizados</a>
  </nav>
  <div class="logout">
    <a href="logout.php"><img src="assets/icons/logout.svg" alt="" class="ico"> Cerrar sesión</a>
  </div>
</aside>
