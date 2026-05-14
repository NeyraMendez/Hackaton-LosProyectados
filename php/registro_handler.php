<?php
// =============================================
//  CAMPUSGO - Registro Handler
//  Archivo: php/registro_handler.php
// =============================================
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../registro.php');
    exit;
}

$nombre   = trim($_POST['nombre']    ?? '');
$email    = trim($_POST['email']     ?? '');
$password = $_POST['password']       ?? '';
$password2= $_POST['password2']      ?? '';
$rol      = $_POST['rol']            ?? 'comprador';

// Validaciones
if (empty($nombre) || empty($email) || empty($password)) {
    header('Location: ../registro.php?error=campos');
    exit;
}
if ($password !== $password2) {
    header('Location: ../registro.php?error=pass');
    exit;
}
if (!in_array($rol, ['comprador', 'vendedor'])) {
    $rol = 'comprador';
}

$pdo = conectar();

// Verificar si ya existe
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    header('Location: ../registro.php?error=existe');
    exit;
}

// Insertar usuario
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
$stmt->execute([$nombre, $email, $hash, $rol]);

header('Location: ../registro.php?ok=1');
exit;
?>
