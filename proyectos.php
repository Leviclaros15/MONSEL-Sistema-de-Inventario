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
          <button class="btn btn-blue" onclick="document.getElementById('modalProyecto').classList.add('open')">+ Nuevo proyecto</button>
        </div>

        <table>
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

<!-- Modal Nuevo proyecto -->
<div class="modal-overlay" id="modalProyecto">
  <div class="modal-box">
    <form method="post" action="proyecto_guardar.php">
      <div class="card-header">
        <h3>Nuevo proyecto</h3>
        <button type="button" class="close-x" onclick="document.getElementById('modalProyecto').classList.remove('open')">&#10005;</button>
      </div>
      <div class="form-grid">
        <div class="form-field full">
          <label>Nombre del proyecto *</label>
          <input type="text" name="nombre_proyecto" placeholder="PR-005 Residencial Vista Hermosa" required>
        </div>
        <div class="form-field">
          <label>Ubicación *</label>
          <input type="text" name="ubicacion" placeholder="San Salvador" required>
        </div>
        <div class="form-field">
          <label>Fecha *</label>
          <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-field full">
          <label>Estado *</label>
          <select name="estado" required>
            <option value="Pendiente">Pendiente</option>
            <option value="En progreso">En progreso</option>
            <option value="Completado">Completado</option>
          </select>
        </div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('modalProyecto').classList.remove('open')">Cancelar</button>
        <button type="submit" class="btn btn-blue">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('click', function (e) {
  const overlay = document.getElementById('modalProyecto');
  if (overlay && e.target === overlay) overlay.classList.remove('open');
});
</script>
</body>
</html>
