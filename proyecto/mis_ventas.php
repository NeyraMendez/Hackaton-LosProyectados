<?php
// --- CONFIGURACIÓN DE LA CONEXIÓN ---
$host = "localhost";
$user = "campusgo_user";
$pass = "password123";
$db   = "campusgo";
$conn = new mysqli($host, $user, $pass, $db);

session_start();
$mi_id_vendedor = $_SESSION['usuario_id'] ?? 1;
$vendedor_id = $mi_id_vendedor;

// --- LÓGICA DE ACTUALIZACIÓN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    $producto_id = intval($_POST['producto_id']);
    $nuevo_stock = intval($_POST['nuevo_stock']);
    
    // CAMBIO CLAVE: Al actualizar, si el stock es > 0, el estado pasa a 'disponible'
    // Esto hace que el catálogo lo vuelva a mostrar automáticamente.
    $estado_txt = ($nuevo_stock > 0) ? 'disponible' : 'agotado';

$activo = ($nuevo_stock > 0) ? 1 : 0;
$stmt = $conn->prepare("UPDATE productos SET inventario = ?, estado = ?, activo = ? WHERE id = ? AND vendedor_id = ?");
$stmt->bind_param("isiii", $nuevo_stock, $estado_txt, $activo, $producto_id, $vendedor_id);    
    if ($stmt->execute()) {
        $mensaje = "¡Inventario actualizado con éxito!";
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
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:      #05050A;
      --panel:   #0E0E18;
      --panel2:  #13131F;
      --border:  rgba(255,255,255,0.07);
      --neon:    #00F5C4;
      --neon2:   #FF6B6B;
      --blue:    #00C4FF;
      --text:    #EEEEF5;
      --muted:   #6B6B8A;
      --radius:  16px;
    }
    html, body { height: 100%; font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); }

    /* FONDO ANIMADO */
    .bg-grid {
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background-image: linear-gradient(rgba(0,245,196,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0,245,196,0.03) 1px, transparent 1px);
      background-size: 36px 36px;
      animation: gridMove 20s linear infinite;
    }
    @keyframes gridMove { to { background-position: 36px 36px; } }
    .orb { position: fixed; border-radius: 50%; pointer-events: none; z-index: 0; animation: float 8s ease-in-out infinite alternate; }
    .orb-1 { width:400px; height:400px; background:rgba(0,245,196,0.08); top:-80px; left:-100px; filter:blur(80px); }
    .orb-2 { width:300px; height:300px; background:rgba(255,107,107,0.07); bottom:-60px; right:-80px; filter:blur(70px); animation-delay:-3s; }
    @keyframes float { from{transform:translate(0,0)} to{transform:translate(20px,30px)} }

    .app { position: relative; z-index: 1; display: flex; height: 100vh; overflow: hidden; }

    /* SIDEBAR */
    .sidebar {
      width: 220px; flex-shrink: 0;
      background: rgba(14,14,24,0.9);
      border-right: 1px solid var(--border);
      backdrop-filter: blur(20px);
      display: flex; flex-direction: column;
      padding: 20px 14px; gap: 4px;
    }
    .brand {
      display: flex; align-items: center; gap: 10px;
      font-family: 'Clash Display', sans-serif; font-size: 1.3rem; font-weight: 700;
      color: var(--text); text-decoration: none; margin-bottom: 20px; padding: 0 4px;
    }
    .brand-icon {
      width: 38px; height: 38px; border-radius: 10px;
      background: linear-gradient(135deg, var(--neon), var(--blue));
      display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
      box-shadow: 0 0 18px rgba(0,245,196,0.35);
    }
    .brand span { color: var(--neon); }
    .nav-section { font-size: 10px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); padding: 10px 8px 6px; }
    .nav-item {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 10px; border-radius: 10px;
      font-size: 13.5px; color: var(--muted); text-decoration: none;
      transition: all 0.15s; font-family: 'Outfit', sans-serif;
    }
    .nav-item:hover { background: rgba(255,255,255,0.04); color: var(--text); }
    .nav-item.active { background: rgba(0,245,196,0.1); color: var(--neon); border: 1px solid rgba(0,245,196,0.2); }
    .nav-item .icon { font-size: 1rem; width: 20px; text-align: center; }
    
    .user-card {
      display: flex; align-items: center; gap: 10px;
      padding: 10px; border-radius: 10px;
      background: rgba(255,255,255,0.03); border: 1px solid var(--border); margin-top: auto;
    }
    .avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--neon); color: #000; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; }

    /* MAIN */
    .main { flex: 1; overflow-y: auto; padding: 24px 28px; }
    .topbar { display: flex; align-items: center; gap: 14px; margin-bottom: 26px; }
    .page-title { font-family: 'Clash Display', sans-serif; font-size: 1.6rem; font-weight: 700; flex: 1; }

    /* BOTÓN VOLVER */
    .back-btn {
      display: flex; align-items: center; gap: 8px;
      padding: 9px 16px; border-radius: 10px;
      background: rgba(255,255,255,0.04); border: 1px solid var(--border);
      color: var(--muted); font-size: 13px; font-family: 'Outfit', sans-serif;
      cursor: pointer; text-decoration: none; transition: all 0.15s;
    }
    .back-btn:hover { color: var(--text); border-color: rgba(255,255,255,0.15); }

    /* TABLA */
    .card-table-container {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }
    .sales-table { width: 100%; border-collapse: collapse; }
    .sales-table th {
      background: rgba(255,255,255,0.02);
      padding: 16px; text-align: left;
      font-family: 'Clash Display'; font-size: 11px;
      text-transform: uppercase; color: var(--muted);
      border-bottom: 1px solid var(--border);
    }
    .sales-table td { padding: 16px; border-bottom: 1px solid var(--border); font-size: 14px; vertical-align: middle; }

    .prod-info { display: flex; align-items: center; gap: 12px; }
    .prod-img { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border); background: var(--panel2); }

    .stock-input {
      background: rgba(255,255,255,0.05);
      border: 1px solid var(--border); border-radius: 8px;
      padding: 8px; color: var(--neon); width: 70px; text-align: center;
      font-family: 'Clash Display'; font-weight: 700; outline: none;
    }

    .btn-update {
      background: var(--neon); color: #05050A;
      border: none; padding: 8px 16px; border-radius: 8px;
      font-family: 'Clash Display'; font-weight: 700; font-size: 11px;
      cursor: pointer; transition: 0.2s;
    }
    .btn-update:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,245,196,0.2); }

    .status-badge {
      padding: 4px 10px; border-radius: 99px; font-size: 10px; font-weight: 700;
      text-transform: uppercase;
    }
    .status-disponible { background: rgba(0,245,196,0.1); color: var(--neon); }
    .status-agotado { background: rgba(255,107,107,0.1); color: var(--neon2); }

    .msg-alert {
      padding: 12px 16px; border-radius: 12px;
      background: rgba(0,245,196,0.1); border: 1px solid rgba(0,245,196,0.2);
      color: var(--neon); margin-bottom: 20px;
    }
  </style>
</head>
<body>
<div class="bg-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="app">
  <div class="sidebar">
    <a href="catalogo.php" class="brand">
      <div class="brand-icon">🎓</div>
      Campus<span>Go</span>
    </a>
    <div class="nav-section">Menú</div>
    <a href="catalogo.php" class="nav-item"><span class="icon">🏪</span> Catálogo</a>
    <a href="mapa.php" class="nav-item"><span class="icon">🗺️</span> Mapa</a>
    <a href="mis_compras.php" class="nav-item"><span class="icon">📦</span> Mis Compras</a>
    <div class="nav-section" style="margin-top:10px;">Vender</div>
    <a href="publicar.php" class="nav-item"><span class="icon">➕</span> Publicar producto</a>
    <a href="mis_ventas.php" class="nav-item active"><span class="icon">📊</span> Mis Ventas</a>
    <div class="user-card">
      <div class="avatar">MC</div>
      <div>
        <div class="user-name">María C.</div>
        <div class="user-role">Vendedora</div>
      </div>
    </div>
  </div>

  <div class="main">
    <div class="topbar">
      <div class="page-title">Gestión de Inventario 📊</div>
      <a href="catalogo.php" class="back-btn">← Volver al catálogo</a>
    </div>

    <?php if(isset($mensaje)): ?>
        <div class="msg-alert">✨ <?php echo $mensaje; ?></div>
    <?php endif; ?>

    <div class="card-table-container">
      <table class="sales-table">
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
            <td>
              <div class="prod-info">
                <img src="uploads/<?php echo $row['imagen']; ?>" class="prod-img" onerror="this.src='https://via.placeholder.com/50/13131F/00F5C4?text=📦'">
                <div>
                  <strong style="display:block;"><?php echo $row['nombre']; ?></strong>
                  <span style="font-size:11px;color:var(--muted);"><?php echo $row['categoria']; ?></span>
                </div>
              </div>
            </td>
            <td style="color:var(--neon); font-family:'Clash Display'; font-weight:700;">
              $<?php echo number_format($row['precio'], 2); ?>
            </td>
            <td>
              <form method="POST" style="display:flex; align-items:center; gap:10px;">
                <input type="hidden" name="producto_id" value="<?php echo $row['id']; ?>">
                <input type="number" name="nuevo_stock" class="stock-input" value="<?php echo $row['inventario']; ?>" min="0">
            </td>
            <td>
              <span class="status-badge status-<?php echo ($row['inventario'] > 0) ? 'disponible' : 'agotado'; ?>">
                <?php echo ($row['inventario'] > 0) ? 'En Stock' : 'Agotado'; ?>
              </span>
            </td>
            <td>
                <button type="submit" name="update_stock" class="btn-update">Guardar</button>
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
