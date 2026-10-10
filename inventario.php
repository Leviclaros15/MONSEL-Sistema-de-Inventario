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
              <th>Stock Mínimo</th>
              <th>Ubicación</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($materiales)): ?>
              <tr><td colspan="8" class="empty-state">No se encontraron materiales.</td></tr>
            <?php else: ?>
              <?php foreach ($materiales as $m): ?>
                <?php 
                  $esAgotado = ((float)$m['cantidad'] <= 0);
                  $esBajoStock = ((float)$m['cantidad'] <= (float)$m['stock_minimo'] && !$esAgotado);
                ?>
                <tr class="<?= $esAgotado ? 'row-agotado' : ($esBajoStock ? 'row-alerta' : '') ?>">
                  <td><strong><?= htmlspecialchars($m['codigo']) ?></strong></td>
                  <td><?= htmlspecialchars($m['nombre']) ?></td>
                  <td><?= htmlspecialchars($m['nombre_categoria']) ?></td>
                  <td style="font-weight:600; color: <?= $esAgotado ? 'var(--red-text)' : ($esBajoStock ? 'var(--amber-text)' : 'inherit') ?>;">
                    <?= rtrim(rtrim(number_format((float)$m['cantidad'], 2, '.', ''), '0'), '.') ?> <?= htmlspecialchars($m['unidad_medida']) ?>
                  </td>
                  <td><?= rtrim(rtrim(number_format((float)$m['stock_minimo'], 2, '.', ''), '0'), '.') ?> <?= htmlspecialchars($m['unidad_medida']) ?></td>
                  <td><?= htmlspecialchars($m['ubicacion']) ?></td>
                  <td>
                    <?php if ($esAgotado): ?>
                      <span class="badge badge-red">Agotado</span>
                    <?php elseif ($esBajoStock): ?>
                      <span class="badge badge-amber">Stock Bajo</span>
                    <?php else: ?>
                      <span class="badge badge-green">Disponible</span>
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
          <select name="id_categoria" id="f_id_categoria" onchange="alCambiarCategoriaOUnidadModal()" required>
            <option value="">Selecciona una categoría</option>
            <?php foreach ($categorias as $c): ?>
              <option value="<?= $c['id_categoria'] ?>" data-nombre="<?= htmlspecialchars($c['nombre_categoria']) ?>">
                <?= htmlspecialchars($c['nombre_categoria']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label>Unidad de medida *</label>
          <select name="unidad_medida" id="f_unidad_medida" onchange="alCambiarCategoriaOUnidadModal()" required>
            <option value="">Selecciona unidad</option>
            <option value="unidades">Unidades</option>
            <option value="piezas">Piezas</option>
            <option value="metros">Metros</option>
            <option value="rollos">Rollos</option>
            <option value="cajas">Cajas</option>
            <option value="paquetes">Paquetes</option>
            <option value="litros">Litros</option>
            <option value="kilogramos">Kilogramos</option>
            <option value="libras">Libras</option>
            <option value="galones">Galones</option>
          </select>
        </div>
        <div class="form-field">
          <label>Cantidad * <span id="f_modal_cant_hint" style="font-weight:400; color:var(--text-muted); font-size:12px;"></span></label>
          <input type="number" step="0.01" min="0" name="cantidad" id="f_cantidad" required>
        </div>
        <div class="form-field">
          <label>Stock Mínimo (Alerta) *</label>
          <input type="number" step="0.01" min="0" name="stock_minimo" id="f_stock_minimo" placeholder="0" required>
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
