<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

// Verificar que sea administrador
if ($_SESSION['rol'] !== 'administrador') {
    flash_set('error', 'No tienes permisos para acceder a esta sección.');
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Auditoría';

// Filtros
$filtroTipo = $_GET['tipo'] ?? '';
$filtroUsuario = $_GET['usuario'] ?? '';
$filtroFechaDesde = $_GET['fecha_desde'] ?? '';
$filtroFechaHasta = $_GET['fecha_hasta'] ?? '';

// Construir consulta con filtros
$where = ['1=1'];
$params = [];

if ($filtroTipo !== '') {
    $where[] = 'm.tipo_movimiento = ?';
    $params[] = $filtroTipo;
}

if ($filtroUsuario !== '') {
    $where[] = 'u.id_usuario = ?';
    $params[] = $filtroUsuario;
}

if ($filtroFechaDesde !== '') {
    $where[] = 'DATE(m.fecha_movimiento) >= ?';
    $params[] = $filtroFechaDesde;
}

if ($filtroFechaHasta !== '') {
    $where[] = 'DATE(m.fecha_movimiento) <= ?';
    $params[] = $filtroFechaHasta;
}

$whereClause = implode(' AND ', $where);

// Obtener historial de movimientos
$query = "
    SELECT
        m.id_movimiento,
        m.fecha_movimiento,
        m.tipo_movimiento,
        m.cantidad,
        m.ubicacion,
        m.observaciones,
        mat.nombre AS material_nombre,
        mat.unidad_medida,
        mat.codigo AS material_codigo,
        p.nombre_proyecto,
        u.nombre AS usuario_nombre,
        u.usuario AS usuario_username
    FROM movimientos m
    JOIN materiales mat ON mat.id_material = m.id_material
    JOIN usuarios u ON u.id_usuario = m.id_usuario
    LEFT JOIN proyectos p ON p.id_proyecto = m.id_proyecto
    WHERE $whereClause
    ORDER BY m.fecha_movimiento DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$historial = $stmt->fetchAll();

// Obtener lista de usuarios para el filtro
$usuarios = $pdo->query('SELECT id_usuario, nombre, usuario FROM usuarios ORDER BY nombre')->fetchAll();

$successMsg = flash_get('success');
$errorMsg = flash_get('error');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>MONSEL - Auditoría</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <div class="content">
      <?php if ($successMsg): ?><div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
      <?php if ($errorMsg): ?><div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h3>Historial de movimientos</h3>
          <button class="btn btn-outline" onclick="document.getElementById('filtroForm').reset(); window.location.href='auditoria.php';">Limpiar filtros</button>
        </div>

        <!-- Formulario de filtros -->
        <form method="get" id="filtroForm" style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid var(--border);">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
            <div>
              <label style="display: block; font-size: 12.5px; font-weight: 600; margin-bottom: 4px;">Tipo de movimiento</label>
              <select name="tipo" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
                <option value="">Todos</option>
                <option value="Entrada" <?= $filtroTipo === 'Entrada' ? 'selected' : '' ?>>Entrada</option>
                <option value="Salida" <?= $filtroTipo === 'Salida' ? 'selected' : '' ?>>Salida</option>
              </select>
            </div>
            <div>
              <label style="display: block; font-size: 12.5px; font-weight: 600; margin-bottom: 4px;">Usuario</label>
              <select name="usuario" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
                <option value="">Todos</option>
                <?php foreach ($usuarios as $u): ?>
                  <option value="<?= $u['id_usuario'] ?>" <?= $filtroUsuario == $u['id_usuario'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['nombre']) ?> (<?= htmlspecialchars($u['usuario']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label style="display: block; font-size: 12.5px; font-weight: 600; margin-bottom: 4px;">Fecha desde</label>
              <input type="date" name="fecha_desde" value="<?= htmlspecialchars($filtroFechaDesde) ?>" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
            </div>
            <div>
              <label style="display: block; font-size: 12.5px; font-weight: 600; margin-bottom: 4px;">Fecha hasta</label>
              <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($filtroFechaHasta) ?>" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px;">
            </div>
          </div>
          <div style="margin-top: 12px;">
            <button type="submit" class="btn btn-blue" style="padding: 8px 16px; font-size: 13px;">Aplicar filtros</button>
          </div>
        </form>

        <!-- Skeleton loading -->
        <table id="skeletonTable" class="skeleton-hidden">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Material</th>
              <th>Cantidad</th>
              <th>Usuario</th>
              <th>Proyecto</th>
              <th>Ubicación</th>
              <th>Observaciones</th>
            </tr>
          </thead>
          <tbody>
            <?php for ($i = 0; $i < 5; $i++): ?>
            <tr class="skeleton-row">
              <td><div class="skeleton skeleton-text short"></div></td>
              <td><div class="skeleton skeleton-badge"></div></td>
              <td><div class="skeleton skeleton-text medium"></div></td>
              <td><div class="skeleton skeleton-text short"></div></td>
              <td><div class="skeleton skeleton-text medium"></div></td>
              <td><div class="skeleton skeleton-text short"></div></td>
              <td><div class="skeleton skeleton-text short"></div></td>
              <td><div class="skeleton skeleton-text medium"></div></td>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>

        <!-- Tabla real -->
        <table id="realTable">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Material</th>
              <th>Cantidad</th>
              <th>Usuario</th>
              <th>Proyecto</th>
              <th>Ubicación</th>
              <th>Observaciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($historial)): ?>
              <tr><td colspan="8" class="empty-state">No se encontraron movimientos con los filtros aplicados.</td></tr>
            <?php else: ?>
              <?php foreach ($historial as $h): ?>
                <tr>
                  <td>
                    <div style="font-weight: 600;"><?= date('d/m/Y', strtotime($h['fecha_movimiento'])) ?></div>
                    <div style="font-size: 11.5px; color: var(--text-muted);"><?= date('H:i', strtotime($h['fecha_movimiento'])) ?></div>
                  </td>
                  <td>
                    <?php if ($h['tipo_movimiento'] === 'Entrada'): ?>
                      <span class="badge badge-green">Entrada</span>
                    <?php else: ?>
                      <span class="badge badge-red">Salida</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="font-weight: 600;"><?= htmlspecialchars($h['material_codigo']) ?></div>
                    <div style="font-size: 12px;"><?= htmlspecialchars($h['material_nombre']) ?></div>
                  </td>
                  <td style="font-weight: 600;">
                    <?= rtrim(rtrim(number_format((float)$h['cantidad'], 2, '.', ''), '0'), '.') ?> <?= htmlspecialchars($h['unidad_medida']) ?>
                  </td>
                  <td>
                    <div style="font-weight: 600;"><?= htmlspecialchars($h['usuario_nombre']) ?></div>
                    <div style="font-size: 11.5px; color: var(--text-muted);">@<?= htmlspecialchars($h['usuario_username']) ?></div>
                  </td>
                  <td><?= htmlspecialchars($h['nombre_proyecto'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($h['ubicacion'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($h['observaciones'] ?? '—') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <div class="pagination">
          <span>Mostrando <?= count($historial) ?> movimiento(s)</span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Skeleton loading
document.addEventListener('DOMContentLoaded', function() {
  const skeletonTable = document.getElementById('skeletonTable');
  const realTable = document.getElementById('realTable');

  if (skeletonTable && realTable) {
    skeletonTable.classList.remove('skeleton-hidden');
    realTable.classList.add('skeleton-hidden');

    setTimeout(function() {
      skeletonTable.classList.add('skeleton-hidden');
      realTable.classList.remove('skeleton-hidden');
    }, 300);
  }
});
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
