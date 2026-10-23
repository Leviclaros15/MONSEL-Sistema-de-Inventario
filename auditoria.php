<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Auditoria';


?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>MONSEL - Auditoria</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

      <div class="card">
        <div class="card-header">
          <h3>Historial de acciones realizadas</h3>
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
