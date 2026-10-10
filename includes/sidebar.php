<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function nav_active(string $file, string $current): string
{
    return $file === $current ? 'active' : '';
}
?>
<aside class="sidebar">
  <div class="brand">
    <div class="bolt">&#9889;</div>
    <div>
      <strong>MONSEL</strong>
      <span>S.A. de C.V.</span>
    </div>
  </div>
  <nav>
    <a href="dashboard.php" class="<?= nav_active('dashboard.php', $currentPage) ?>"><span class="ico">&#8962;</span> Inicio</a>
    <a href="inventario.php" class="<?= nav_active('inventario.php', $currentPage) ?>"><span class="ico">&#128230;</span> Inventario</a>
    <a href="movimiento.php" class="<?= nav_active('movimiento.php', $currentPage) ?>"><span class="ico">&#8646;</span> Entradas / Salidas</a>
    <a href="proyectos.php" class="<?= nav_active('proyectos.php', $currentPage) ?>"><span class="ico">&#128204;</span> Proyectos realizados</a>
  </nav>
  <div class="logout">
    <a href="logout.php"><span class="ico">&#9211;</span> Cerrar sesión</a>
  </div>
</aside>
