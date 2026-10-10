<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Inventario';

$categorias = $pdo->query('SELECT * FROM categorias ORDER BY nombre_categoria')->fetchAll();

$busqueda = trim($_GET['q'] ?? '');

if ($busqueda !== '') {
    $stmt = $pdo->prepare("
        SELECT mat.*, c.nombre_categoria
        FROM materiales mat
        JOIN categorias c ON c.id_categoria = mat.id_categoria
        WHERE mat.codigo LIKE ? OR mat.nombre LIKE ?
        ORDER BY mat.id_material DESC
    ");
    $like = "%$busqueda%";
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query("
        SELECT mat.*, c.nombre_categoria
        FROM materiales mat
        JOIN categorias c ON c.id_categoria = mat.id_categoria
        ORDER BY mat.id_material DESC
    ");
}
$materiales = $stmt->fetchAll();

$successMsg = flash_get('success');
$errorMsg = flash_get('error');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>MONSEL - Inventario</title>
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
            <input type="text" name="q" class="search-input" placeholder="Buscar material..." value="<?= htmlspecialchars($busqueda) ?>">
            <button type="submit" class="btn btn-outline">Buscar</button>
          </form>
          <button class="btn btn-blue" onclick="abrirModalAgregar()">+ Agregar material</button>
        </div>

        <table>
          <thead>
            <tr>
              <th>Código</th>
              <th>Nombre</th>
              <th>Categoría</th>
              <th>Cantidad</th>
              <th>Ubicación</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($materiales)): ?>
              <tr><td colspan="7" class="empty-state">No se encontraron materiales.</td></tr>
            <?php else: ?>
              <?php foreach ($materiales as $m): ?>
                <tr>
                  <td><?= htmlspecialchars($m['codigo']) ?></td>
                  <td><?= htmlspecialchars($m['nombre']) ?></td>
                  <td><?= htmlspecialchars($m['nombre_categoria']) ?></td>
                  <td><?= rtrim(rtrim(number_format((float)$m['cantidad'], 2, '.', ''), '0'), '.') ?> <?= htmlspecialchars($m['unidad_medida']) ?></td>
                  <td><?= htmlspecialchars($m['ubicacion']) ?></td>
                  <td>
                    <?php if ($m['estado'] === 'Disponible'): ?>
                      <span class="badge badge-green">Disponible</span>
                    <?php else: ?>
                      <span class="badge badge-red">Agotado</span>
                    <?php endif; ?>
                  </td>
                  <td class="action-icons">
                    <button type="button" class="edit" title="Editar"
                      onclick='abrirModalEditar(<?= json_encode($m, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>&#9998;</button>
                    <form method="post" action="material_eliminar.php" onsubmit="return confirm('¿Eliminar este material del inventario?');" style="display:inline;">
                      <input type="hidden" name="id_material" value="<?= $m['id_material'] ?>">
                      <button type="submit" class="delete" title="Eliminar">&#128465;</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <div class="pagination">
          <span>Mostrando <?= count($materiales) ?> de <?= count($materiales) ?> materiales</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Agregar / Editar material -->
<div class="modal-overlay" id="modalMaterial">
  <div class="modal-box">
    <form method="post" action="material_guardar.php">
      <div class="card-header">
        <h3 id="modalTitulo">Agregar material</h3>
        <button type="button" class="close-x" onclick="cerrarModal()">&#10005;</button>
      </div>

      <input type="hidden" name="id_material" id="f_id_material" value="">

      <div class="form-grid">
        <div class="form-field">
          <label>Código *</label>
          <input type="text" name="codigo" id="f_codigo" placeholder="MAT-006" required>
        </div>
        <div class="form-field">
          <label>Nombre del material *</label>
          <input type="text" name="nombre" id="f_nombre" placeholder="Cable THHN #12" required>
        </div>
        <div class="form-field">
          <label>Categoría *</label>
          <select name="id_categoria" id="f_id_categoria" required>
            <option value="">Selecciona una categoría</option>
            <?php foreach ($categorias as $c): ?>
              <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label>Unidad de medida *</label>
          <input type="text" name="unidad_medida" id="f_unidad_medida" placeholder="metros, unidades..." required>
        </div>
        <div class="form-field">
          <label>Cantidad *</label>
          <input type="number" step="0.01" min="0" name="cantidad" id="f_cantidad" required>
        </div>
        <div class="form-field">
          <label>Ubicación *</label>
          <input type="text" name="ubicacion" id="f_ubicacion" placeholder="Bodega A" required>
        </div>
        <div class="form-field">
          <label>Estado *</label>
          <select name="estado" id="f_estado" required>
            <option value="Disponible">Disponible</option>
            <option value="Agotado">Agotado</option>
          </select>
        </div>
        <div class="form-field full">
          <label>Observaciones</label>
          <textarea name="observaciones" id="f_observaciones" placeholder="Observaciones (opcional)"></textarea>
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" class="btn btn-blue">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
