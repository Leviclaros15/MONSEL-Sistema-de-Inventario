<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Proyectos realizados';

$busqueda = trim($_GET['q'] ?? '');

if ($busqueda !== '') {
    $stmt = $pdo->prepare('SELECT * FROM proyectos WHERE nombre_proyecto LIKE ? OR ubicacion LIKE ? ORDER BY fecha DESC');
    $like = "%$busqueda%";
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query('SELECT * FROM proyectos ORDER BY fecha DESC');
}
$proyectos = $stmt->fetchAll();

$successMsg = flash_get('success');
$errorMsg = flash_get('error');

function badgeEstadoProyecto(string $estado): string
{
    $clase = match ($estado) {
        'Completado' => 'badge-green',
        'En progreso' => 'badge-amber',
        default => 'badge-red',
    };
    return '<span class="badge ' . $clase . '">' . htmlspecialchars($estado) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>MONSEL - Proyectos realizados</title>
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
          <form method="get" style="display:flex; gap:10px;">
            <input type="text" name="q" class="search-input" placeholder="Buscar proyecto..." value="<?= htmlspecialchars($busqueda) ?>">
            <button type="submit" class="btn btn-outline">Buscar</button>
          </form>
          <button class="btn btn-blue" onclick="abrirModalProyectoAgregar()">+ Nuevo proyecto</button>
        </div>

        <!-- Skeleton loading -->
        <table id="skeletonTable" class="skeleton-hidden">
          <thead>
            <tr>
              <th>Proyecto</th>
              <th>Ubicación</th>
              <th>Fecha</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php for ($i = 0; $i < 5; $i++): ?>
            <tr class="skeleton-row">
              <td><div class="skeleton skeleton-text long"></div></td>
              <td><div class="skeleton skeleton-text medium"></div></td>
              <td><div class="skeleton skeleton-text short"></div></td>
              <td><div class="skeleton skeleton-badge"></div></td>
              <td><div class="skeleton skeleton-text short"></div></td>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>

        <!-- Tabla real -->
        <table id="realTable">
          <thead>
            <tr>
              <th>Proyecto</th>
              <th>Ubicación</th>
              <th>Fecha</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($proyectos)): ?>
              <tr><td colspan="5" class="empty-state">No se encontraron proyectos.</td></tr>
            <?php else: ?>
              <?php foreach ($proyectos as $p): ?>
                <tr>
                  <td><?= htmlspecialchars($p['nombre_proyecto']) ?></td>
                  <td><?= htmlspecialchars($p['ubicacion']) ?></td>
                  <td><?= date('d/m/Y', strtotime($p['fecha'])) ?></td>
                  <td><?= badgeEstadoProyecto($p['estado']) ?></td>
                  <td class="action-icons">
                    <button type="button" class="edit" title="Editar"
                      onclick='abrirModalProyectoEditar(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>&#9998;</button>
                    <form method="post" action="proyecto_eliminar.php" onsubmit="return confirm('¿Eliminar este proyecto?');" style="display:inline;">
                      <input type="hidden" name="id_proyecto" value="<?= $p['id_proyecto'] ?>">
                      <button type="submit" class="delete" title="Eliminar">&#128465;</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <div class="pagination">
          <span>Mostrando <?= count($proyectos) ?> de <?= count($proyectos) ?> proyectos</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Agregar / Editar proyecto -->
<div class="modal-overlay" id="modalProyecto">
  <div class="modal-box">
    <form method="post" action="proyecto_guardar.php">
      <div class="card-header">
        <h3 id="modalTitulo">Nuevo proyecto</h3>
        <button type="button" class="close-x" onclick="cerrarModalProyecto()">&#10005;</button>
      </div>

      <input type="hidden" name="id_proyecto" id="f_id_proyecto" value="">

      <div class="form-grid">
        <div class="form-field full">
          <label>Nombre del proyecto *</label>
          <input type="text" name="nombre_proyecto" id="f_nombre_proyecto" placeholder="PR-005 Residencial Vista Hermosa" required>
        </div>
        <div class="form-field">
          <label>Ubicación *</label>
          <input type="text" name="ubicacion" id="f_ubicacion" placeholder="San Salvador" required>
        </div>
        <div class="form-field">
          <label>Fecha *</label>
          <input type="date" name="fecha" id="f_fecha" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-field full">
          <label>Estado *</label>
          <select name="estado" id="f_estado" required>
            <option value="Pendiente">Pendiente</option>
            <option value="En progreso">En progreso</option>
            <option value="Completado">Completado</option>
          </select>
        </div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="cerrarModalProyecto()">Cancelar</button>
        <button type="submit" class="btn btn-blue">Guardar</button>
      </div>
    </form>
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

function abrirModalProyectoAgregar() {
  document.getElementById('f_id_proyecto').value = '';
  document.getElementById('f_nombre_proyecto').value = '';
  document.getElementById('f_ubicacion').value = '';
  document.getElementById('f_fecha').value = '<?= date('Y-m-d') ?>';
  document.getElementById('f_estado').value = 'Pendiente';
  document.getElementById('modalTitulo').textContent = 'Nuevo proyecto';
  document.getElementById('modalProyecto').classList.add('open');
}

function abrirModalProyectoEditar(p) {
  document.getElementById('f_id_proyecto').value = p.id_proyecto;
  document.getElementById('f_nombre_proyecto').value = p.nombre_proyecto;
  document.getElementById('f_ubicacion').value = p.ubicacion;
  document.getElementById('f_fecha').value = p.fecha;
  document.getElementById('f_estado').value = p.estado;
  document.getElementById('modalTitulo').textContent = 'Editar proyecto';
  document.getElementById('modalProyecto').classList.add('open');
}

function cerrarModalProyecto() {
  document.getElementById('modalProyecto').classList.remove('open');
}

document.addEventListener('click', function (e) {
  const overlay = document.getElementById('modalProyecto');
  if (overlay && e.target === overlay) cerrarModalProyecto();
});
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
