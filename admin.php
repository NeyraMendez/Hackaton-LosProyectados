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

$pdo = conectar();

$stmt = $pdo->query("
    SELECT *
    FROM usuarios
    ORDER BY id ASC
");

$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<title>Panel Admin - CampusGo</title>

<style>

body{
    font-family: Arial;
    background:#f5f5f5;
    padding:30px;
}

table{
    width:100%;
    border-collapse: collapse;
    background:white;
}

th, td{
    border:1px solid #ccc;
    padding:12px;
    text-align:center;
}

th{
    background:#222;
    color:white;
}

a{
    text-decoration:none;
}

.eliminar{
    color:red;
    font-weight:bold;
}

</style>

</head>

<body>

<h1>Panel Administrador</h1>

<table>

<tr>
    <th>ID</th>
    <th>Nombre</th>
    <th>Email</th>
    <th>Rol</th>
    <th>Acción</th>
</tr>

<?php foreach($usuarios as $usuario): ?>

<tr>

    <td><?= $usuario['id'] ?></td>
    <td><?= $usuario['nombre'] ?></td>
    <td><?= $usuario['email'] ?></td>
    <td><?= $usuario['rol'] ?></td>

    <td>

        <?php if($usuario['rol'] !== 'admin'): ?>

        <a class="eliminar"
           href="eliminar_usuario.php?id=<?= $usuario['id'] ?>"
           onclick="return confirm('¿Eliminar usuario?')">

           🗑 Eliminar

        </a>

        <?php else: ?>

        ADMIN PRINCIPAL

        <?php endif; ?>

    </td>

</tr>

<?php endforeach; ?>

</table>

</body>
</html>
