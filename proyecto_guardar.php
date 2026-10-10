<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: proyectos.php');
    exit;
}

$nombre_proyecto = trim($_POST['nombre_proyecto'] ?? '');
$ubicacion       = trim($_POST['ubicacion'] ?? '');
$fecha           = $_POST['fecha'] ?? date('Y-m-d');
$estado          = $_POST['estado'] ?? 'Pendiente';

if ($nombre_proyecto === '' || $ubicacion === '') {
    flash_set('error', 'Por favor completa todos los campos obligatorios.');
    header('Location: proyectos.php');
    exit;
}

$stmt = $pdo->prepare('INSERT INTO proyectos (nombre_proyecto, ubicacion, fecha, estado) VALUES (?, ?, ?, ?)');
$stmt->execute([$nombre_proyecto, $ubicacion, $fecha, $estado]);

flash_set('success', 'Proyecto agregado correctamente.');
header('Location: proyectos.php');
exit;
__halt_compiler();
    $eliminarPhp = <<<'CODE'
<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_categoria'])) {
    $id = (int) $_POST['id_categoria'];
    
    // Verificar si hay materiales asociados
    $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM materiales WHERE id_categoria = ?');
    $stmtCheck->execute([$id]);
    $totalAsociados = (int) $stmtCheck->fetchColumn();

    if ($totalAsociados > 0) {
        flash_set('error', "No se puede eliminar: hay $totalAsociados material(es) registrados bajo esta categoría.");
    } else {
        try {
            $stmt = $pdo->prepare('DELETE FROM categorias WHERE id_categoria = ?');
            $stmt->execute([$id]);
            flash_set('success', 'Categoría eliminada correctamente.');
        } catch (PDOException $e) {
            flash_set('error', 'No se pudo eliminar la categoría.');
        }
    }
}

header('Location: categorias.php');
exit;
CODE;
    file_put_contents(__DIR__ . '/categoria_eliminar.php', $eliminarPhp);

    // 4. categorias.php
    $categoriasView = <<<'CODE'
<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Gestión de categorías';

$busqueda = trim($_GET['q'] ?? '');

if ($busqueda !== '') {
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(m.id_material) AS total_materiales
        FROM categorias c
        LEFT JOIN materiales m ON m.id_categoria = c.id_categoria
        WHERE c.nombre_categoria LIKE ? OR c.descripcion LIKE ?
        GROUP BY c.id_categoria
        ORDER BY c.nombre_categoria ASC
    ");
    $like = "%$busqueda%";
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(m.id_material) AS total_materiales
        FROM categorias c
        LEFT JOIN materiales m ON m.id_categoria = c.id_categoria
        GROUP BY c.id_categoria
        ORDER BY c.nombre_categoria ASC
    ");
}
$categorias = $stmt->fetchAll();

$successMsg = flash_get('success');
$errorMsg   = flash_get('error');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>MONSEL - Categorías</title>
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
            <input type="text" name="q" class="search-input" placeholder="Buscar categoría..." value="<?= htmlspecialchars($busqueda) ?>">
            <button type="submit" class="btn btn-outline">Buscar</button>
          </form>
          <button class="btn btn-blue" onclick="abrirModalCategoriaAgregar()">+ Nueva categoría</button>
        </div>

        <table>
          <thead>
            <tr>
              <th>Nombre de categoría</th>
              <th>Descripción</th>
              <th>Materiales asociados</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($categorias)): ?>
              <tr><td colspan="4" class="empty-state">No se encontraron categorías.</td></tr>
            <?php else: ?>
              <?php foreach ($categorias as $c): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($c['nombre_categoria']) ?></strong></td>
                  <td><?= htmlspecialchars($c['descripcion'] ?: '—') ?></td>
                  <td>
                    <span class="badge <?= $c['total_materiales'] > 0 ? 'badge-green' : 'badge-amber' ?>">
                      <?= $c['total_materiales'] ?> <?= $c['total_materiales'] == 1 ? 'material' : 'materiales' ?>
                    </span>
                  </td>
                  <td class="action-icons">
                    <button type="button" class="edit" title="Editar"
                      onclick='abrirModalCategoriaEditar(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>&#9998;</button>
                    <form method="post" action="categoria_eliminar.php" onsubmit="return confirm('¿Eliminar esta categoría?');" style="display:inline;">
                      <input type="hidden" name="id_categoria" value="<?= $c['id_categoria'] ?>">
                      <button type="submit" class="delete" title="Eliminar">&#128465;</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <div class="pagination">
          <span>Mostrando <?= count($categorias) ?> categorías</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Agregar / Editar Categoría -->
<div class="modal-overlay" id="modalCategoria">
  <div class="modal-box">
    <form method="post" action="categoria_guardar.php">
      <div class="card-header">
        <h3 id="modalCatTitulo">Nueva categoría</h3>
        <button type="button" class="close-x" onclick="cerrarModalCategoria()">&#10005;</button>
      </div>

      <input type="hidden" name="id_categoria" id="f_cat_id" value="">

      <div class="form-grid">
        <div class="form-field full">
          <label>Nombre de la categoría *</label>
          <input type="text" name="nombre_categoria" id="f_cat_nombre" placeholder="Ej: Herramientas, Tubería, Iluminación" required>
        </div>
        <div class="form-field full">
          <label>Descripción</label>
          <textarea name="descripcion" id="f_cat_descripcion" placeholder="Descripción breve del tipo de materiales (opcional)"></textarea>
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="cerrarModalCategoria()">Cancelar</button>
        <button type="submit" class="btn btn-blue">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModalCategoriaAgregar() {
  document.getElementById('f_cat_id').value = '';
  document.getElementById('f_cat_nombre').value = '';
  document.getElementById('f_cat_descripcion').value = '';
  document.getElementById('modalCatTitulo').textContent = 'Nueva categoría';
  document.getElementById('modalCategoria').classList.add('open');
}

function abrirModalCategoriaEditar(cat) {
  document.getElementById('f_cat_id').value = cat.id_categoria;
  document.getElementById('f_cat_nombre').value = cat.nombre_categoria;
  document.getElementById('f_cat_descripcion').value = cat.descripcion || '';
  document.getElementById('modalCatTitulo').textContent = 'Editar categoría';
  document.getElementById('modalCategoria').classList.add('open');
}

function cerrarModalCategoria() {
  document.getElementById('modalCategoria').classList.remove('open');
}

document.addEventListener('click', function(e) {
  const overlay = document.getElementById('modalCategoria');
  if (overlay && e.target === overlay) {
    cerrarModalCategoria();
  }
});
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
CODE;
    file_put_contents(__DIR__ . '/categorias.php', $categoriasView);

flash_set('success', 'Proyecto agregado correctamente.');
header('Location: proyectos.php');
exit;
