<?php
// =============================================
//  CAMPUSGO - Login Handler
//  Archivo: php/login_handler.php
// =============================================

session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: ../login.php?error=campos');
    exit;
}

$pdo  = conectar();
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    header('Location: ../login.php?error=credenciales');
    exit;
}

// Guardar sesión
$_SESSION['usuario_id']  = $user['id'];
$_SESSION['usuario_nombre'] = $user['nombre'];
$_SESSION['usuario_email']  = $user['email'];
$_SESSION['usuario_rol']    = $user['rol'];

// Redirigir según rol
switch ($user['rol']) {
    case 'admin':
        header('Location: ../dashboard_admin.php');
        break;
    case 'vendedor':
        header('Location: ../dashboard_vendedor.php');
        break;
    default:
        header('Location: ../dashboard_comprador.php');
        break;
}
exit;
?>
