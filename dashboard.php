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

// Alertas de stock bajo o agotado
$stmtAlertas = $pdo->query("
    SELECT mat.id_material, mat.codigo, mat.nombre, mat.cantidad, mat.stock_minimo, mat.unidad_medida, c.nombre_categoria
    FROM materiales mat
    JOIN categorias c ON c.id_categoria = mat.id_categoria
    WHERE mat.cantidad <= mat.stock_minimo
    ORDER BY mat.cantidad ASC
");
$materialesBajoStock = $stmtAlertas->fetchAll();
$totalBajoStock = count($materialesBajoStock);

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
        <div class="kpi-card <?= $totalBajoStock > 0 ? 'kpi-card-alert' : '' ?>">
          <div class="label">Stock bajo / Crítico</div>
          <div class="value <?= $totalBajoStock > 0 ? 'text-red' : '' ?>">
            <?= $totalBajoStock ?>
          </div>
        </div>
      </div>

      <?php if ($totalBajoStock > 0): ?>
      <div class="card mb-4" style="border-left: 4px solid var(--red-text);">
        <div class="card-header">
          <div style="display:flex; align-items:center; gap:8px;">
            <span class="badge badge-red">Alerta</span>
            <h3 style="color: var(--red-text);">Materiales que requieren reabastecimiento</h3>
          </div>
          <a href="inventario.php" class="btn btn-outline">Ir a inventario</a>
        </div>
        <table>
          <thead>
            <tr>
              <th>Código</th>
              <th>Material</th>
              <th>Categoría</th>
              <th>Stock Actual</th>
              <th>Stock Mínimo</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($materialesBajoStock as $m): ?>
              <tr>
                <td><strong><?= htmlspecialchars($m['codigo']) ?></strong></td>
                <td><?= htmlspecialchars($m['nombre']) ?></td>
                <td><?= htmlspecialchars($m['nombre_categoria']) ?></td>
                <td style="font-weight:700; color: <?= $m['cantidad'] <= 0 ? 'var(--red-text)' : 'var(--amber-text)' ?>;">
                  <?= rtrim(rtrim(number_format((float)$m['cantidad'], 2, '.', ''), '0'), '.') ?> <?= htmlspecialchars($m['unidad_medida']) ?>
                </td>
                <td><?= rtrim(rtrim(number_format((float)$m['stock_minimo'], 2, '.', ''), '0'), '.') ?> <?= htmlspecialchars($m['unidad_medida']) ?></td>
                <td>
                  <?php if ($m['cantidad'] <= 0): ?>
                    <span class="badge badge-red">Agotado</span>
                  <?php else: ?>
                    <span class="badge badge-amber">Stock Bajo</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

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
