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
   ELIMINAR PRODUCTO
============================================= */

if (isset($_GET['id'])) {

    $id = intval($_GET['id']);

    $pdo = conectar();

    $stmt = $pdo->prepare("
        DELETE FROM productos
        WHERE id = ?
    ");

    if ($stmt->execute([$id])) {

        header("Location: catalogo.php?msg=producto_eliminado");
        exit;

    } else {

        echo "Error al eliminar producto";
    }
}
?>
