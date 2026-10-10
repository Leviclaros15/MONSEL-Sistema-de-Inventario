<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Panel principal';

$totalMateriales = (int) $pdo->query('SELECT COUNT(*) FROM materiales')->fetchColumn();

$mesActual = date('Y-m');
$stmtEntradas = $pdo->prepare("SELECT COALESCE(SUM(cantidad),0) FROM movimientos WHERE tipo_movimiento = 'Entrada' AND DATE_FORMAT(fecha_movimiento, '%Y-%m') = ?");
$stmtEntradas->execute([$mesActual]);
$entradasMes = (float) $stmtEntradas->fetchColumn();

$stmtSalidas = $pdo->prepare("SELECT COALESCE(SUM(cantidad),0) FROM movimientos WHERE tipo_movimiento = 'Salida' AND DATE_FORMAT(fecha_movimiento, '%Y-%m') = ?");
$stmtSalidas->execute([$mesActual]);
$salidasMes = (float) $stmtSalidas->fetchColumn();

$proyectosActivos = (int) $pdo->query("SELECT COUNT(*) FROM proyectos WHERE estado IN ('En progreso','Pendiente')")->fetchColumn();

$recientes = $pdo->query("
    SELECT m.fecha_movimiento, m.tipo_movimiento, m.cantidad, mat.nombre AS material_nombre
    FROM movimientos m
    JOIN materiales mat ON mat.id_material = m.id_material
    ORDER BY m.fecha_movimiento DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>MONSEL - Panel principal</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <div class="content">
      <div class="page-header">
        <div>
          <h2>¡Hola, <?= htmlspecialchars(explode(' ', $_SESSION['nombre'])[0]) ?>!</h2>
          <p>Bienvenido al sistema de inventario</p>
        </div>
      </div>

      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="label">Total de materiales</div>
          <div class="value"><?= $totalMateriales ?></div>
        </div>
        <div class="kpi-card">
          <div class="label">Entradas (este mes)</div>
          <div class="value"><?= (int) $entradasMes ?></div>
        </div>
        <div class="kpi-card">
          <div class="label">Salidas (este mes)</div>
          <div class="value"><?= (int) $salidasMes ?></div>
        </div>
        <div class="kpi-card">
          <div class="label">Proyectos activos</div>
          <div class="value"><?= $proyectosActivos ?></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Movimientos recientes</h3>
          <a href="movimiento.php" class="btn btn-outline">Ver todos</a>
        </div>
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Material</th>
              <th>Cantidad</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recientes)): ?>
              <tr><td colspan="4" class="empty-state">Todavía no hay movimientos registrados.</td></tr>
            <?php else: ?>
              <?php foreach ($recientes as $r): ?>
                <tr>
                  <td><?= date('d/m/Y', strtotime($r['fecha_movimiento'])) ?></td>
                  <td>
                    <?php if ($r['tipo_movimiento'] === 'Entrada'): ?>
                      <span class="badge badge-green">Entrada</span>
                    <?php else: ?>
                      <span class="badge badge-red">Salida</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($r['material_nombre']) ?></td>
                  <td><?= rtrim(rtrim(number_format((float)$r['cantidad'], 2, '.', ''), '0'), '.') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>
