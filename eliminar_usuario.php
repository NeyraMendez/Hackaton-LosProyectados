<?php
session_start();
require_once 'php/config.php';

/* =============================================
   SOLO ADMIN
============================================= */

if (
    !isset($_SESSION['usuario_rol']) ||
    $_SESSION['usuario_rol'] !== 'admin'
) {
    die("Acceso denegado");
}

/* =============================================
   ELIMINAR USUARIO
============================================= */

if (isset($_GET['id'])) {

    $id = intval($_GET['id']);

    $pdo = conectar();

    /* NO eliminar admins */
    $verificar = $pdo->prepare("
        SELECT rol
        FROM usuarios
        WHERE id = ?
    ");

    $verificar->execute([$id]);

    $usuario = $verificar->fetch();

    if ($usuario && $usuario['rol'] === 'admin') {

        die("No puedes eliminar administradores");
    }

    $stmt = $pdo->prepare("
        DELETE FROM usuarios
        WHERE id = ?
    ");

    if ($stmt->execute([$id])) {

        header("Location: admin.php?msg=usuario_eliminado");
        exit;

    } else {

        echo "Error eliminando usuario";
    }
}
?>
