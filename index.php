<?php
session_start();
if (!empty($_SESSION['id_usuario'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $error = 'Por favor completa usuario y contraseña.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ? AND estado = "activo" LIMIT 1');
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['contrasena'])) {
            $_SESSION['id_usuario'] = $user['id_usuario'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>MONSEL - Iniciar sesión</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-wrap">
  <div class="login-visual">
    <svg viewBox="0 0 500 700" preserveAspectRatio="xMidYMid slice">
      <polyline points="80,0 60,220 110,220 40,500" fill="none" stroke="#3a5079" stroke-width="4"/>
      <polyline points="40,500 30,700" fill="none" stroke="#3a5079" stroke-width="4"/>
      <line x1="0" y1="150" x2="160" y2="150" stroke="#3a5079" stroke-width="3"/>
      <line x1="0" y1="260" x2="160" y2="260" stroke="#3a5079" stroke-width="3"/>
      <polyline points="260,60 240,300 300,300 210,620" fill="none" stroke="#2b3c5c" stroke-width="4"/>
      <line x1="180" y1="180" x2="340" y2="180" stroke="#2b3c5c" stroke-width="3"/>
      <line x1="180" y1="320" x2="340" y2="320" stroke="#2b3c5c" stroke-width="3"/>
    </svg>
    <div class="tagline">
      <h2>Control de inventario para montajes eléctricos</h2>
      <p>Registra entradas, salidas y sobrantes de material en tiempo real, sin hojas de cálculo.</p>
    </div>
  </div>

  <div class="login-panel">
    <div class="login-box">
      <a href="dashboard.php" class="brand">
        <img src="assets/imgs/logo_monsel.png" alt="MONSEL" class="brand-logo">
        <div>
          <strong>MONSEL</strong>
          <span>Sistema de inventario</span>
        </div>
      </a>

      <h1>Inicio de sesión</h1>
      <p class="subtitle">Sistema de Inventario</p>

      <?php if ($error): ?>
        <div class="login-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="field">
          <label for="usuario">Usuario</label>
          <input type="text" id="usuario" name="usuario" placeholder="Usuario" value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" required autofocus>
        </div>
        <div class="field">
          <label for="password">Contraseña</label>
          <div class="input-wrap">
            <input type="password" id="password" name="password" placeholder="••••••••" required>
            <button type="button" class="toggle-pass" onclick="togglePassword()">Ver</button>
          </div>
        </div>
        <button type="submit" class="btn-primary">Iniciar sesión</button>
      </form>

    </div>
  </div>
</div>

<script>
function togglePassword() {
  const input = document.getElementById('password');
  const btn = document.querySelector('.toggle-pass');
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = 'Ocultar';
  } else {
    input.type = 'password';
    btn.textContent = 'Ver';
  }
}
</script>
</body>
</html>
