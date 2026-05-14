<?php
// --- CONFIGURACIÓN DE LA CONEXIÓN ---
$host = "localhost"; $user = "tu_usuario"; $pass = "tu_password"; $db = "nombre_de_tu_bd";
$conn = new mysqli($host, $user, $pass, $db);

session_start();
$vendedor_id = $_SESSION['user_id'] ?? 1;

// --- LÓGICA DE ACTUALIZACIÓN DE STOCK ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    $producto_id = intval($_POST['producto_id']);
    $nuevo_stock = intval($_POST['nuevo_stock']);
    
    $estado_txt = ($nuevo_stock > 0) ? 'disponible' : 'agotado';
    $stmt = $conn->prepare("UPDATE productos SET inventario = ?, estado = ? WHERE id = ? AND vendedor_id = ?");
    $stmt->bind_param("isii", $nuevo_stock, $estado_txt, $producto_id, $vendedor_id);
    
    if ($stmt->execute()) {
        $mensaje = "¡Stock actualizado!";
    }
}

// --- CONSULTA DE PRODUCTOS ---
$query = "SELECT * FROM productos WHERE vendedor_id = $vendedor_id ORDER BY id DESC";
$resultado = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CampusGo – Mis Ventas</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    /* REUTILIZACIÓN DE ESTILOS DE PUBLICAR.HTML */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:     #05050A;
      --panel:  #0E0E18;
      --panel2: #13131F;
      --border: rgba(255,255,255,0.07);
      --neon:   #00F5C4;
      --neon2:  #FF6B6B;
      --blue:   #00C4FF;
      --text:   #EEEEF5;
      --muted:  #6B6B8A;
      --radius: 16px;
    }
    html, body { height: 100%; font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); }

    .bg-grid {
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background-image: linear-gradient(rgba(0,245,196,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0,245,196,0.03) 1px, transparent 1px);
      background-size: 36px 36px;
    }

    .app { position: relative; z-index: 1; display: flex; height: 100vh; overflow: hidden; }

    /* SIDEBAR */
    .sidebar {
      width: 220px; flex-shrink: 0; background: rgba(14,14,24,0.9);
      border-right: 1px solid var(--border); backdrop-filter: blur(20px);
      display: flex; flex-direction: column; padding: 20px 14px; gap: 4px;
    }
    .brand {
      display: flex; align-items: center; gap: 10px; font-family: 'Clash Display', sans-serif;
      font-size: 1.3rem; font-weight: 700; color: var(--text); text-decoration: none; margin-bottom: 20px;
    }
    .brand span { color: var(--neon); }
    .nav-section { font-size: 10px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); padding: 10px 8px 6px; }
    .nav-item {
      display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px;
      font-size: 13.5px; color: var(--muted); text-decoration: none; transition: all 0.15s;
    }
    .nav-item:hover { background: rgba(255,255,255,0.04); color: var(--text); }
    .nav-item.active { background: rgba(0,245,196,0.1); color: var(--neon); border: 1px solid rgba(0,245,196,0.2); }

    /* MAIN */
    .main { flex: 1; overflow-y: auto; padding: 24px 28px; }
    .page-title { font-family: 'Clash Display', sans-serif; font-size: 1.6rem; font-weight: 700; margin-bottom: 26px; }

    /* TABLA DE VENTAS */
    .card-table {
      background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius);
      overflow: hidden; backdrop-filter: blur(10px);
    }
    table { width: 100%; border-collapse: collapse; }
    th {
      text-align: left; padding: 16px 20px; background: rgba(255,255,255,0.02);
      font-family: 'Clash Display', sans-serif; font-size: 12px; color: var(--muted);
      text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid var(--border);
    }
    td { padding: 16px 20px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    
    /* PRODUCT INFO */
    .prod-cell { display: flex; align-items: center; gap: 14px; }
    .prod-img {
      width: 50px; height: 50px; border-radius: 10px; object-fit: cover;
      border: 1px solid var(--border); background: var(--panel2);
    }
    .prod-name { font-weight: 600; font-size: 14px; display: block; }
    .prod-cat { font-size: 11px; color: var(--muted); text-transform: uppercase; }

    /* INPUTS Y BADGES */
    .stock-input {
      background: rgba(255,255,255,0.04); border: 1px solid var(--border);
      color: var(--text); padding: 8px; width: 70px; border-radius: 8px;
      text-align: center; font-family: 'Outfit', sans-serif; outline: none;
    }
    .stock-input:focus { border-color: var(--neon); }

    .status-badge {
      padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 700;
      text-transform: uppercase; display: inline-block;
    }
    .status-active { background: rgba(0,245,196,0.1); color: var(--neon); border: 1px solid rgba(0,245,196,0.2); }
    .status-out { background: rgba(255,107,107,0.1); color: var(--neon2); border: 1px solid rgba(255,107,107,0.2); }

    .btn-update {
      background: var(--neon); color: #05050A; border: none; padding: 8px 16px;
      border-radius: 8px; font-family: 'Clash Display', sans-serif; font-weight: 700;
      font-size: 12px; cursor: pointer; transition: 0.2s;
    }
    .btn-update:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,245,196,0.3); }

    .toast {
      padding: 12px 20px; background: rgba(0,245,196,0.1); border: 1px solid var(--neon);
      color: var(--neon); border-radius: 10px; margin-bottom: 20px; font-size: 14px;
    }
  </style>
</head>
<body>
<div class="bg-grid"></div>

<div class="app">
  <div class="sidebar">
    <a href="catalogo.php" class="brand">🎓 Campus<span>Go</span></a>
    <div class="nav-section">Menú</div>
    <a href="catalogo.php" class="nav-item">🏪 Catálogo</a>
    <a href="publicar.php" class="nav-item">➕ Publicar producto</a>
    <a href="mis_ventas.php" class="nav-item active">📊 Mis Ventas</a>
    <div class="nav-section" style="margin-top:10px;">Cuenta</div>
    <a href="php/logout.php" class="nav-item" style="color:var(--neon2);">🚪 Cerrar sesión</a>
  </div>

  <div class="main">
    <h1 class="page-title">Panel de Ventas 📊</h1>

    <?php if(isset($mensaje)): ?>
      <div class="toast"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <div class="card-table">
      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $resultado->fetch_assoc()): ?>
          <tr>
            <td>
              <div class="prod-cell">
                <?php 
                  $img_path = "uploads/" . $row['imagen'];
                  $display_img = (!empty($row['imagen']) && file_exists($img_path)) ? $img_path : 'https://via.placeholder.com/150?text=No+Image';
                ?>
                <img src="<?php echo $display_img; ?>" class="prod-img" alt="Producto">
                <div>
                  <span class="prod-name"><?php echo htmlspecialchars($row['nombre']); ?></span>
                  <span class="prod-cat"><?php echo htmlspecialchars($row['categoria']); ?></span>
                </div>
              </div>
            </td>
            <td style="color: var(--neon); font-weight: 700; font-family: 'Clash Display';">
              $<?php echo number_format($row['precio'], 2); ?>
            </td>
            <td>
              <form method="POST" style="display: flex; align-items: center; gap: 10px;">
                <input type="hidden" name="producto_id" value="<?php echo $row['id']; ?>">
                <input type="number" name="nuevo_stock" class="stock-input" value="<?php echo $row['inventario']; ?>" min="0">
            </td>
            <td>
              <?php if($row['inventario'] > 0): ?>
                <span class="status-badge status-active">Activo</span>
              <?php else: ?>
                <span class="status-badge status-out">Agotado</span>
              <?php endif; ?>
            </td>
            <td>
                <button type="submit" name="update_stock" class="btn-update">Actualizar</button>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>