<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Registrar movimiento';

$materiales = $pdo->query('
    SELECT m.id_material, m.codigo, m.nombre, m.cantidad, m.unidad_medida, c.nombre_categoria
    FROM materiales m
    JOIN categorias c ON c.id_categoria = m.id_categoria
    ORDER BY m.nombre
')->fetchAll();
$proyectos = $pdo->query('SELECT id_proyecto, nombre_proyecto FROM proyectos ORDER BY nombre_proyecto')->fetchAll();

$historial = $pdo->query("
    SELECT m.id_movimiento, m.fecha_movimiento, m.tipo_movimiento, m.cantidad, m.ubicacion, m.observaciones,
           mat.nombre AS material_nombre, mat.unidad_medida, p.nombre_proyecto
    FROM movimientos m
    JOIN materiales mat ON mat.id_material = m.id_material
    LEFT JOIN proyectos p ON p.id_proyecto = m.id_proyecto
    ORDER BY m.fecha_movimiento DESC
    LIMIT 15
")->fetchAll();

$successMsg = flash_get('success');
$errorMsg = flash_get('error');
$tipoActivo = $_GET['tipo'] ?? 'Entrada';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>MONSEL - Registrar movimiento</title>
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

      <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
          <h3>Registrar movimiento</h3>
        </div>

        <div style="padding: 22px 22px 0;">
          <div class="tabs">
            <button type="button" class="tab-btn <?= $tipoActivo === 'Entrada' ? 'active' : '' ?>" data-tipo="Entrada" onclick="cambiarTipo('Entrada')">Entrada</button>
            <button type="button" class="tab-btn <?= $tipoActivo === 'Salida' ? 'active' : '' ?>" data-tipo="Salida" onclick="cambiarTipo('Salida')">Salida</button>
          </div>
        </div>

        <form method="post" action="movimiento_guardar.php">
          <input type="hidden" name="tipo_movimiento" id="tipo_movimiento" value="<?= htmlspecialchars($tipoActivo) ?>">

          <div class="form-grid">
            <div class="form-field">
              <label>Fecha *</label>
              <input type="date" name="fecha" id="f_fecha" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-field">
              <label>Material *</label>
              <select name="id_material" id="f_mov_material" onchange="alCambiarMaterialMovimiento()" required>
                <option value="">Selecciona un material</option>
                <?php foreach ($materiales as $m): ?>
                  <option value="<?= $m['id_material'] ?>"
                          data-categoria="<?= htmlspecialchars($m['nombre_categoria']) ?>"
                          data-unidad="<?= htmlspecialchars($m['unidad_medida']) ?>"
                          data-stock="<?= (float)$m['cantidad'] ?>">
                    <?= htmlspecialchars($m['codigo'] . ' — ' . $m['nombre']) ?> (stock: <?= rtrim(rtrim(number_format((float)$m['cantidad'], 2, '.', ''), '0'), '.') ?> <?= htmlspecialchars($m['unidad_medida']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-field">
              <label>Cantidad * <span id="label_info_unidad" style="font-weight:400; color:var(--text-muted); font-size:12px;"></span></label>
              <input type="number" step="0.01" min="0.01" name="cantidad" id="f_mov_cantidad" placeholder="Ej: 10" required>
            </div>
            <div class="form-field">
              <label>Ubicación *</label>
              <input type="text" name="ubicacion" placeholder="Bodega A / Zona de montaje" required>
            </div>
            <div class="form-field">
              <label>Proyecto relacionado</label>
              <select name="id_proyecto">
                <option value="">Sin proyecto (compra general)</option>
                <?php foreach ($proyectos as $p): ?>
                  <option value="<?= $p['id_proyecto'] ?>"><?= htmlspecialchars($p['nombre_proyecto']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-field full">
              <label>Observaciones</label>
              <textarea name="observaciones" placeholder="Motivo del movimiento (opcional)"></textarea>
            </div>
          </div>

          <div class="form-actions">
            <button type="reset" class="btn btn-outline">Cancelar</button>
            <button type="submit" class="btn btn-blue">Guardar</button>
          </div>
        </form>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Historial de movimientos</h3>
        </div>
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Material</th>
              <th>Cantidad</th>
              <th>Ubicación</th>
              <th>Proyecto</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($historial)): ?>
              <tr><td colspan="6" class="empty-state">Todavía no hay movimientos registrados.</td></tr>
            <?php else: ?>
              <?php foreach ($historial as $h): ?>
                <tr>
                  <td><?= date('d/m/Y', strtotime($h['fecha_movimiento'])) ?></td>
                  <td>
                    <?php if ($h['tipo_movimiento'] === 'Entrada'): ?>
                      <span class="badge badge-green">Entrada</span>
                    <?php else: ?>
                      <span class="badge badge-red">Salida</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($h['material_nombre']) ?></td>
                  <td><?= rtrim(rtrim(number_format((float)$h['cantidad'], 2, '.', ''), '0'), '.') ?> <?= htmlspecialchars($h['unidad_medida']) ?></td>
                  <td><?= htmlspecialchars($h['ubicacion'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($h['nombre_proyecto'] ?? '—') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function cambiarTipo(tipo) {
  document.getElementById('tipo_movimiento').value = tipo;
  document.querySelectorAll('.tab-btn').forEach(function (btn) {
    btn.classList.toggle('active', btn.dataset.tipo === tipo);
  });
  alCambiarMaterialMovimiento();
}

function alCambiarMaterialMovimiento() {
  const sel = document.getElementById('f_mov_material');
  const opt = sel.options[sel.selectedIndex];
  const inputCant = document.getElementById('f_mov_cantidad');
  const labelInfo = document.getElementById('label_info_unidad');
  const tipo = document.getElementById('tipo_movimiento').value;

  if (!opt || !opt.value) {
    inputCant.step = '0.01';
    inputCant.min = '0.01';
    inputCant.removeAttribute('max');
    if (labelInfo) labelInfo.textContent = '';
    return;
  }

  const categoria = (opt.dataset.categoria || '').toLowerCase();
  const unidad = (opt.dataset.unidad || '').toLowerCase();
  const stock = parseFloat(opt.dataset.stock) || 0;

  const esHerramientaOUnidad = categoria.includes('herramienta') || unidad.includes('unidad') || unidad.includes('pieza');

  if (esHerramientaOUnidad) {
    inputCant.step = '1';
    inputCant.min = '1';
    if (inputCant.value && inputCant.value % 1 !== 0) {
      inputCant.value = Math.round(inputCant.value) || 1;
    }
    if (labelInfo) labelInfo.textContent = '(Unidades enteras)';
  } else {
    inputCant.step = '0.01';
    inputCant.min = '0.01';
    if (labelInfo) labelInfo.textContent = '(' + (opt.dataset.unidad || 'decimales permitidos') + ')';
  }

  // Si es salida, no permitir pedir más del stock existente
  if (tipo === 'Salida') {
    inputCant.max = stock;
  } else {
    inputCant.removeAttribute('max');
  }
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', alCambiarMaterialMovimiento);
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
