<?php
// =============================================
//  CAMPUSGO - Login Handler
//  Archivo: php/login_handler.php
// =============================================

session_start();
require_once 'config.php';

/* Verificar método POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

/* Obtener datos */
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

/* Validar campos vacíos */
if (empty($email) || empty($password)) {

    header('Location: ../login.php?error=campos');
    exit;
}

/* Conectar BD */
$pdo = conectar();

/* Buscar usuario activo */
$stmt = $pdo->prepare("
    SELECT * 
    FROM usuarios 
    WHERE email = ? 
    AND activo = 1
");

$stmt->execute([$email]);

$user = $stmt->fetch();

/* Validar credenciales */
if (!$user || !password_verify($password, $user['password'])) {

    header('Location: ../login.php?error=credenciales');
    exit;
}

/* =============================================
   GUARDAR SESIÓN
============================================= */

$_SESSION['usuario_id']     = $user['id'];
$_SESSION['usuario_nombre'] = $user['nombre'];
$_SESSION['usuario_email']  = $user['email'];
$_SESSION['usuario_rol']    = $user['rol'];

/* =============================================
   REDIRECCIÓN SEGÚN ROL
============================================= */

switch ($user['rol']) {

    case 'admin':

        header('Location: ../dashboard_admin.php');
        break;

    case 'vendedor':

        header('Location: ../dashboard_vendedor.php');
        break;

    case 'comprador':

        header('Location: ../dashboard_comprador.php');
        break;

    default:

        session_destroy();

        header('Location: ../login.php?error=rol');
        break;
}

exit;
?>
