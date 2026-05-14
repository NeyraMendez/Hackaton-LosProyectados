<?php
// 1. CONEXIÓN A LA BASE DE DATOS
$host = "localhost";
$user = "campusgo_user";
$pass = "password123";
$db   = "campusgo";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

session_start();
// ID del vendedor (ajustar según tu sistema de login)
$vendedor_id = $_SESSION['usuario_id'] ?? 1;

// 2. LÓGICA DE ACTUALIZACIÓN (PHP)
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    $producto_id = intval($_POST['producto_id']);
    $nuevo_stock = intval($_POST['nuevo_stock']);
    
    // Si el stock es > 0 está disponible, si es 0 está agotado
    $estado_txt = ($nuevo_stock > 0) ? 'disponible' : 'agotado';

    $stmt = $conn->prepare("UPDATE productos SET inventario = ?, estado = ? WHERE id = ? AND vendedor_id = ?");
    $stmt->bind_param("isii", $nuevo_stock, $estado_txt, $producto_id, $vendedor_id);

    if ($stmt->execute()) {
        $mensaje = "✅ Inventario actualizado correctamente.";
    } else {
        $mensaje = "❌ Error al actualizar.";
    }
    $stmt->close();
}

// 3. CONSULTA DE PRODUCTOS
$query = "SELECT * FROM productos WHERE vendedor_id = $vendedor_id ORDER BY id DESC";
$resultado = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CampusGo - Mis Ventas</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #05050A; --panel: #0E0E18; --neon: #00F5C4; --text: #EEEEF5; --border: rgba(255,255,255,0.1); }
        body { background: var(--bg); color: var(--text); font-family: 'Outfit', sans-serif; margin: 0; padding: 40px; }
        .container { max-width: 1000px; margin: auto; background: var(--panel); border-radius: 20px; border: 1px solid var(--border); padding: 30px; }
        h2 { margin-top: 0; color: var(--neon); }
        .alert { background: rgba(0, 245, 196, 0.1); border: 1px solid var(--neon); color: var(--neon); padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid var(--border); color: #6B6B8A; font-size: 13px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid var(--border); }
        .img-mini { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; }
        .stock-input { background: #13131F; border: 1px solid var(--border); color: var(--neon); padding: 8px; width: 70px; border-radius: 8px; text-align: center; font-weight: bold; }
        .btn-update { background: var(--neon); color: #000; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-update:hover { transform: scale(1.05); box-shadow: 0 0 15px rgba(0, 245, 196, 0.4); }
        .status-tag { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .tag-disponible { background: rgba(0,245,196,0.1); color: var(--neon); }
        .tag-agotado { background: rgba(255,107,107,0.1); color: #FF6B6B; }
    </style>
</head>
<body>

<div class="container">
    <h2>Gestión de Inventario 📊</h2>
    
    <?php if($mensaje != ""): ?>
        <div class="alert"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Precio</th>
                <th>Stock Actual</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $resultado->fetch_assoc()): ?>
            <tr>
                <form method="POST">
                    <input type="hidden" name="producto_id" value="<?php echo $row['id']; ?>">
                    <td style="display: flex; align-items: center; gap: 15px;">
                        <img src="uploads/productos/<?php echo $row['imagen']; ?>" class="img-mini" onerror="this.src='https://via.placeholder.com/50'">
                        <div>
                            <strong><?php echo htmlspecialchars($row['nombre']); ?></strong><br>
                            <small style="color:#6B6B8A"><?php echo $row['categoria']; ?></small>
                        </div>
                    </td>
                    <td>$<?php echo number_format($row['precio'], 2); ?></td>
                    <td>
                        <input type="number" name="nuevo_stock" class="stock-input" value="<?php echo $row['inventario']; ?>" min="0">
                    </td>
                    <td>
                        <?php if($row['inventario'] > 0): ?>
                            <span class="status-tag tag-disponible">Visible</span>
                        <?php else: ?>
                            <span class="status-tag tag-agotado">Oculto</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="submit" name="update_stock" class="btn-update">Guardar</button>
                    </td>
                </form>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>