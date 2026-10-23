<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

// Verificar que sea administrador
if ($_SESSION['rol'] !== 'administrador') {
    flash_set('error', 'No tienes permisos para acceder a esta sección.');
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Gestión de usuarios';

$busqueda = trim($_GET['q'] ?? '');

if ($busqueda !== '') {
    $stmt = $pdo->prepare("
        SELECT id_usuario, nombre, correo, usuario, rol, estado, fecha_registro
        FROM usuarios
        WHERE nombre LIKE ? OR correo LIKE ? OR usuario LIKE ?
        ORDER BY fecha_registro DESC
    ");
    $like = "%$busqueda%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("
        SELECT id_usuario, nombre, correo, usuario, rol, estado, fecha_registro
        FROM usuarios
        ORDER BY fecha_registro DESC
    ");
}
$usuarios = $stmt->fetchAll();

$successMsg = flash_get('success');
$errorMsg = flash_get('error');

function badgeRol(string $rol): string
{
    $clase = $rol === 'administrador' ? 'badge-green' : 'badge-amber';
    return '<span class="badge ' . $clase . '">' . htmlspecialchars($rol) . '</span>';
}

function badgeEstado(string $estado): string
{
    $clase = $estado === 'activo' ? 'badge-green' : 'badge-red';
    return '<span class="badge ' . $clase . '">' . htmlspecialchars($estado) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>MONSEL - Gestión de usuarios</title>
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
            <input type="text" name="q" class="search-input" placeholder="Buscar usuario..." value="<?= htmlspecialchars($busqueda) ?>">
            <button type="submit" class="btn btn-outline">Buscar</button>
          </form>
          <button class="btn btn-blue" onclick="abrirModalUsuarioAgregar()">+ Nuevo usuario</button>
        </div>

        <!-- Skeleton loading -->
        <table id="skeletonTable" class="skeleton-hidden">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Usuario</th>
              <th>Correo</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Fecha registro</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php for ($i = 0; $i < 5; $i++): ?>
            <tr class="skeleton-row">
              <td><div class="skeleton skeleton-text medium"></div></td>
              <td><div class="skeleton skeleton-text short"></div></td>
              <td><div class="skeleton skeleton-text medium"></div></td>
              <td><div class="skeleton skeleton-badge"></div></td>
              <td><div class="skeleton skeleton-badge"></div></td>
              <td><div class="skeleton skeleton-text short"></div></td>
              <td><div class="skeleton skeleton-text short"></div></td>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>

        <!-- Tabla real -->
        <table id="realTable">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Usuario</th>
              <th>Correo</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Fecha registro</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($usuarios)): ?>
              <tr><td colspan="7" class="empty-state">No se encontraron usuarios.</td></tr>
            <?php else: ?>
              <?php foreach ($usuarios as $u): ?>
                <tr>
                  <td><?= htmlspecialchars($u['nombre']) ?></td>
                  <td><?= htmlspecialchars($u['usuario']) ?></td>
                  <td><?= htmlspecialchars($u['correo']) ?></td>
                  <td><?= badgeRol($u['rol']) ?></td>
                  <td><?= badgeEstado($u['estado']) ?></td>
                  <td><?= date('d/m/Y', strtotime($u['fecha_registro'])) ?></td>
                  <td class="action-icons">
                    <button type="button" class="edit" title="Editar"
                      onclick='abrirModalUsuarioEditar(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>&#9998;</button>
                    <?php if ($u['id_usuario'] !== $_SESSION['id_usuario']): ?>
                      <form method="post" action="usuario_eliminar.php" onsubmit="return confirm('¿Eliminar este usuario?');" style="display:inline;">
                        <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                        <button type="submit" class="delete" title="Eliminar">&#128465;</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <div class="pagination">
          <span>Mostrando <?= count($usuarios) ?> de <?= count($usuarios) ?> usuarios</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Agregar / Editar usuario -->
<div class="modal-overlay" id="modalUsuario">
  <div class="modal-box">
    <form method="post" action="usuario_guardar.php">
      <div class="card-header">
        <h3 id="modalTitulo">Nuevo usuario</h3>
        <button type="button" class="close-x" onclick="cerrarModalUsuario()">&#10005;</button>
      </div>

      <input type="hidden" name="id_usuario" id="f_id_usuario" value="">

      <div class="form-grid">
        <div class="form-field full">
          <label>Nombre completo *</label>
          <input type="text" name="nombre" id="f_nombre" placeholder="Juan Pérez" required>
        </div>
        <div class="form-field">
          <label>Usuario *</label>
          <input type="text" name="usuario" id="f_usuario" placeholder="juanperez" required>
        </div>
        <div class="form-field">
          <label>Correo electrónico *</label>
          <input type="email" name="correo" id="f_correo" placeholder="juan@monsel.com" required>
        </div>
        <div class="form-field">
          <label>Contraseña *</label>
          <input type="password" name="contrasena" id="f_contrasena" placeholder="Mínimo 6 caracteres" required minlength="6">
        </div>
        <div class="form-field">
          <label>Rol *</label>
          <select name="rol" id="f_rol" required>
            <option value="tecnico">Técnico</option>
            <option value="administrador">Administrador</option>
          </select>
        </div>
        <div class="form-field">
          <label>Estado *</label>
          <select name="estado" id="f_estado" required>
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
          </select>
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="cerrarModalUsuario()">Cancelar</button>
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

function abrirModalUsuarioAgregar() {
  document.getElementById('f_id_usuario').value = '';
  document.getElementById('f_nombre').value = '';
  document.getElementById('f_usuario').value = '';
  document.getElementById('f_correo').value = '';
  document.getElementById('f_contrasena').value = '';
  document.getElementById('f_contrasena').required = true;
  document.getElementById('f_contrasena').placeholder = 'Mínimo 6 caracteres';
  document.getElementById('f_rol').value = 'tecnico';
  document.getElementById('f_estado').value = 'activo';
  document.getElementById('modalTitulo').textContent = 'Nuevo usuario';
  document.getElementById('modalUsuario').classList.add('open');
}

function abrirModalUsuarioEditar(u) {
  document.getElementById('f_id_usuario').value = u.id_usuario;
  document.getElementById('f_nombre').value = u.nombre;
  document.getElementById('f_usuario').value = u.usuario;
  document.getElementById('f_correo').value = u.correo;
  document.getElementById('f_contrasena').value = '';
  document.getElementById('f_contrasena').required = false;
  document.getElementById('f_contrasena').placeholder = 'Dejar vacío para mantener la actual';
  document.getElementById('f_rol').value = u.rol;
  document.getElementById('f_estado').value = u.estado;
  document.getElementById('modalTitulo').textContent = 'Editar usuario';
  document.getElementById('modalUsuario').classList.add('open');
}

function cerrarModalUsuario() {
  document.getElementById('modalUsuario').classList.remove('open');
}

document.addEventListener('click', function (e) {
  const overlay = document.getElementById('modalUsuario');
  if (overlay && e.target === overlay) cerrarModalUsuario();
});
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
