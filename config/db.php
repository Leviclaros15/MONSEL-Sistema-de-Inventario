<?php
// =========================================================
// Conexión a la base de datos - MONSEL S.A. de C.V.
// Ajusta estos datos según tu configuración local (XAMPP/WAMP/etc.)
// =========================================================

$host    = 'localhost';
$dbname  = 'monsel_inventario';
$dbuser  = 'root';
$dbpass  = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (PDOException $e) {
    die(
        '<div style="font-family: Arial; max-width:600px; margin:60px auto; padding:24px; border:1px solid #f3c2c2; background:#fdeaea; color:#7a1f1f; border-radius:8px;">' .
        '<h2 style="margin-top:0;">No se pudo conectar a la base de datos</h2>' .
        '<p>Verifica que:</p>' .
        '<ul>' .
        '<li>El servicio de MySQL esté iniciado (por ejemplo, en el panel de XAMPP).</li>' .
        '<li>Hayas importado el archivo <code>database/monsel_inventario.sql</code>.</li>' .
        '<li>Los datos de conexión en <code>config/db.php</code> sean correctos.</li>' .
        '</ul>' .
        '<p style="font-size:13px; color:#a04a4a;">Detalle técnico: ' . htmlspecialchars($e->getMessage()) . '</p>' .
        '</div>'
    );
}
