<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Inventario';

// Operaciones de Categorías (Crear y Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_categoria'])) {
    $actionCat = $_POST['action_categoria'];
    
    if ($actionCat === 'crear') {
        $nombreCat = trim($_POST['nombre_categoria'] ?? '');
        $descCat   = trim($_POST['descripcion'] ?? '');
        if ($nombreCat !== '') {
            try {
                $stmtNew = $pdo->prepare('INSERT INTO categorias (nombre_categoria, descripcion) VALUES (?, ?)');
                $stmtNew->execute([$nombreCat, $descCat]);
                flash_set('success', 'Categoría "' . $nombreCat . '" agregada exitosamente.');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    flash_set('error', 'Ya existe una categoría con ese nombre.');
                } else {
                    flash_set('error', 'Error al guardar la categoría.');
                }
            }
        } else {
            flash_set('error', 'El nombre de la categoría es obligatorio.');
        }
    } elseif ($actionCat === 'eliminar') {
        $idCat = (int) ($_POST['id_categoria'] ?? 0);
        $check = $pdo->prepare('SELECT COUNT(*) FROM materiales WHERE id_categoria = ?');
        $check->execute([$idCat]);
        $totalAsociados = (int) $check->fetchColumn();
        
        if ($totalAsociados > 0) {
            flash_set('error', "No se puede eliminar la categoría: tiene $totalAsociados material(es) asociado(s).");
        } else {
            try {
                $stmtDel = $pdo->prepare('DELETE FROM categorias WHERE id_categoria = ?');
                $stmtDel->execute([$idCat]);
                flash_set('success', 'Categoría eliminada correctamente.');
            } catch (PDOException $e) {
                flash_set('error', 'No se pudo eliminar la categoría.');
            }
        }
    }
    header('Location: inventario.php');
    exit;
}

$categorias = $pdo->query('
    SELECT c.*, COUNT(m.id_material) AS total_materiales 
    FROM categorias c 
    LEFT JOIN materiales m ON m.id_categoria = c.id_categoria 
    GROUP BY c.id_categoria 
    ORDER BY c.nombre_categoria
')->fetchAll();

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
          <div style="display:flex; gap:10px;">
            <button type="button" class="btn btn-outline" onclick="abrirModalGestionCategorias()">
              <img src="assets/icons/category.svg" alt="" style="width:16px; height:16px; object-fit:contain; opacity:0.75;">
              Categorías
            </button>
            <button type="button" class="btn btn-blue" onclick="abrirModalAgregar()">+ Agregar material</button>
          </div>
        </div>

        <!-- Skeleton loading -->
        <table id="skeletonTable" class="skeleton-hidden">
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
            <?php for ($i = 0; $i < 5; $i++): ?>
            <tr class="skeleton-row">
              <td><div class="skeleton skeleton-text short"></div></td>
              <td><div class="skeleton skeleton-text medium"></div></td>
              <td><div class="skeleton skeleton-text short"></div></td>
              <td><div class="skeleton skeleton-text short"></div></td>
              <td><div class="skeleton skeleton-text short"></div></td>
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
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <label style="margin-bottom:0;">Categoría *</label>
            <a href="javascript:void(0)" onclick="abrirModalGestionCategorias()" style="font-size:12px; color:var(--blue-600); font-weight:600;">+ Nueva categoría</a>
          </div>
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
          <label>Stock Mínimo (Alerta) *</label>
          <input type="number" step="0.01" min="0" name="stock_minimo" id="f_stock_minimo" placeholder="0" required>
        </div>
        <div class="form-field">
          <label>Ubicación *</label>
          <input type="text" name="ubicacion" id="f_ubicacion" placeholder="Bodega A" required>
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

<!-- Modal Gestión de Categorías -->
<div class="modal-overlay" id="modalGestionCategorias">
  <div class="modal-box" style="max-width: 650px;">
    <div class="card-header">
      <h3>Gestión de Categorías</h3>
      <button type="button" class="close-x" onclick="cerrarModalGestionCategorias()">&#10005;</button>
    </div>

    <div style="padding: 20px;">
      <!-- Formulario para agregar nueva categoría -->
      <form method="post" action="inventario.php" style="background:#f8fafc; border:1px solid var(--border); border-radius:8px; padding:16px; margin-bottom:20px;">
        <input type="hidden" name="action_categoria" value="crear">
        <h4 style="margin:0 0 12px; font-size:14px; font-weight:700;">+ Registrar nueva categoría</h4>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px;">Nombre *</label>
            <input type="text" name="nombre_categoria" placeholder="Ej: Iluminación, Tubería" required style="width:100%; padding:8px 10px; border:1px solid var(--border); border-radius:6px; font-size:13px;">
          </div>
          <div>
            <label style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px;">Descripción</label>
            <input type="text" name="descripcion" placeholder="Opcional..." style="width:100%; padding:8px 10px; border:1px solid var(--border); border-radius:6px; font-size:13px;">
          </div>
        </div>
        <div style="display:flex; justify-content:flex-end;">
          <button type="submit" class="btn btn-blue" style="padding:7px 14px; font-size:13px;">Guardar categoría</button>
        </div>
      </form>

      <!-- Tabla de categorías existentes -->
      <h4 style="margin:0 0 10px; font-size:14px; font-weight:700;">Categorías existentes</h4>
      <div style="border:1px solid var(--border); border-radius:8px; overflow:hidden;">
        <table style="font-size:13px;">
          <thead>
            <tr>
              <th style="padding:8px 14px;">Categoría</th>
              <th style="padding:8px 14px;">Descripción</th>
              <th style="padding:8px 14px;">Materiales</th>
              <th style="padding:8px 14px; text-align:center;">Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categorias as $cat): ?>
              <tr>
                <td style="padding:9px 14px;"><strong><?= htmlspecialchars($cat['nombre_categoria']) ?></strong></td>
                <td style="padding:9px 14px; color:var(--text-muted);"><?= htmlspecialchars($cat['descripcion'] ?: '—') ?></td>
                <td style="padding:9px 14px;">
                  <span class="badge <?= $cat['total_materiales'] > 0 ? 'badge-green' : 'badge-amber' ?>" style="font-size:11px;">
                    <?= $cat['total_materiales'] ?>
                  </span>
                </td>
                <td style="padding:9px 14px; text-align:center;">
                  <?php if ($cat['total_materiales'] == 0): ?>
                    <form method="post" action="inventario.php" onsubmit="return confirm('¿Eliminar la categoría <?= htmlspecialchars($cat['nombre_categoria']) ?>?');" style="display:inline;">
                      <input type="hidden" name="action_categoria" value="eliminar">
                      <input type="hidden" name="id_categoria" value="<?= $cat['id_categoria'] ?>">
                      <button type="submit" class="delete" title="Eliminar categoría" style="background:none; border:none; cursor:pointer; color:var(--red-text); font-size:14px;">&#128465;</button>
                    </form>
                  <?php else: ?>
                    <span title="No se puede eliminar: tiene materiales asignados" style="color:var(--text-muted); font-size:12px; cursor:not-allowed;">🔒</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
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

    // Simular tiempo de carga muy corto (en producción esto sería async)
    setTimeout(function() {
      skeletonTable.classList.add('skeleton-hidden');
      realTable.classList.remove('skeleton-hidden');
    }, 300);
  }
});

function abrirModalGestionCategorias() {
  document.getElementById('modalGestionCategorias').classList.add('open');
}

function cerrarModalGestionCategorias() {
  document.getElementById('modalGestionCategorias').classList.remove('open');
}

document.addEventListener('click', function (e) {
  const overlayCat = document.getElementById('modalGestionCategorias');
  if (overlayCat && e.target === overlayCat) {
    cerrarModalGestionCategorias();
  }
});
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
