<?php
session_start();
$nombre = $_SESSION['usuario_nombre'] ?? 'Invitado';
$rol    = $_SESSION['usuario_rol']    ?? 'comprador';
$siglas = strtoupper(substr($nombre, 0, 2));
require_once 'php/config.php';

$pdo = conectar();

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Saber si es admin
$esAdmin = ($_SESSION['usuario_rol'] === 'admin');

// BORRAR PRODUCTO
if ($esAdmin && isset($_GET['eliminar_producto'])) {

    $id = intval($_GET['eliminar_producto']);

    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: catalogo.php");
    exit;
}

// BORRAR USUARIO
if ($esAdmin && isset($_GET['eliminar_usuario'])) {

    $id = intval($_GET['eliminar_usuario']);

    // evitar borrar al admin principal
    if ($id != $_SESSION['usuario_id']) {

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
    }

    header("Location: catalogo.php");
    exit;
}

// ── NUEVO: Consultar productos activos desde la BD ──
$stmt = $pdo->prepare("
    SELECT p.id, p.nombre, p.descripcion, p.precio, p.categoria, p.imagen,
           u.nombre AS vendedor_nombre
    FROM productos p
    LEFT JOIN usuarios u ON p.vendedor_id = u.id
    WHERE p.activo = 1
    ORDER BY p.creado_en DESC
");
$stmt->execute();
$productos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
$productos_json = json_encode($productos_db, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CampusGo – Catálogo</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:     #05050A;
      --panel:  #0E0E18;
      --panel2: #13131F;
      --border: rgba(255,255,255,0.07);
      --neon:   #00F5C4;
      --neon2:  #FF6B6B;
      --gold:   #FFD166;
      --blue:   #00C4FF;
      --text:   #EEEEF5;
      --muted:  #6B6B8A;
      --radius: 16px;
    }
    html, body { height: 100%; font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); }

    /* FONDO */
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

    /* LAYOUT */
    .app { position: relative; z-index: 1; display: flex; height: 100vh; overflow: hidden; }

    /* SIDEBAR */
    .sidebar {
      width: 220px; flex-shrink: 0;
      background: rgba(14,14,24,0.9);
      border-right: 1px solid var(--border);
      backdrop-filter: blur(20px);
      display: flex; flex-direction: column;
      padding: 20px 14px;
      gap: 4px;
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
      font-size: 13.5px; color: var(--muted);
      cursor: pointer; border: none; background: transparent;
      width: 100%; text-align: left; text-decoration: none;
      transition: all 0.15s; font-family: 'Outfit', sans-serif;
    }
    .nav-item:hover { background: rgba(255,255,255,0.04); color: var(--text); }
    .nav-item.active { background: rgba(0,245,196,0.1); color: var(--neon); border: 1px solid rgba(0,245,196,0.2); }
    .nav-item .icon { font-size: 1rem; width: 20px; text-align: center; }

    .spacer { flex: 1; }
    .divider { border: none; border-top: 1px solid var(--border); margin: 8px 0; }

    .user-card {
      display: flex; align-items: center; gap: 10px;
      padding: 10px; border-radius: 10px;
      background: rgba(255,255,255,0.03); border: 1px solid var(--border);
    }
    .avatar {
      width: 36px; height: 36px; border-radius: 50%;
      background: rgba(0,245,196,0.12); border: 1px solid rgba(0,245,196,0.25);
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 700; color: var(--neon); flex-shrink: 0;
    }
    .user-name { font-size: 13px; font-weight: 600; }
    .user-role { font-size: 11px; color: var(--muted); }

    /* MAIN */
    .main { flex: 1; overflow-y: auto; padding: 24px 28px; }
    .main::-webkit-scrollbar { width: 5px; }
    .main::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.07); border-radius: 99px; }

    /* TOPBAR */
    .topbar { display: flex; align-items: center; gap: 14px; margin-bottom: 26px; }
    .page-title { font-family: 'Clash Display', sans-serif; font-size: 1.6rem; font-weight: 700; flex: 1; }

    .search-wrap {
      display: flex; align-items: center; gap: 10px;
      background: rgba(255,255,255,0.04); border: 1px solid var(--border);
      border-radius: 10px; padding: 9px 14px; width: 240px;
      transition: border-color 0.2s;
    }
    .search-wrap:focus-within { border-color: rgba(0,245,196,0.35); }
    .search-wrap input { background: transparent; border: none; outline: none; color: var(--text); font-family: 'Outfit', sans-serif; font-size: 13px; width: 100%; }
    .search-wrap input::placeholder { color: var(--muted); }

    .cart-btn {
      position: relative; background: rgba(0,245,196,0.1); border: 1px solid rgba(0,245,196,0.25);
      border-radius: 10px; padding: 9px 14px; cursor: pointer; font-size: 1.2rem;
      transition: all 0.2s; color: var(--text);
    }
    .cart-btn:hover { background: rgba(0,245,196,0.18); }
    .cart-count {
      position: absolute; top: -6px; right: -6px;
      background: var(--neon2); color: white; font-size: 10px; font-weight: 700;
      width: 18px; height: 18px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
    }

    /* FILTROS */
    .filters { display: flex; gap: 8px; margin-bottom: 22px; flex-wrap: wrap; }
    .filter-btn {
      padding: 7px 16px; border-radius: 99px; font-size: 12px; font-weight: 500;
      border: 1px solid var(--border); background: transparent; color: var(--muted);
      cursor: pointer; font-family: 'Outfit', sans-serif; transition: all 0.15s;
    }
    .filter-btn:hover { border-color: rgba(0,245,196,0.3); color: var(--text); }
    .filter-btn.active { background: var(--neon); color: #05050A; border-color: var(--neon); font-weight: 600; }

    /* STATS ROW */
    .stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 26px; }
    .stat-card {
      background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 16px 18px;
    }
    .stat-label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
    .stat-value { font-family: 'Clash Display', sans-serif; font-size: 1.6rem; font-weight: 700; color: var(--neon); }
    .stat-sub { font-size: 11px; color: var(--muted); margin-top: 2px; }

    /* GRID PRODUCTOS */
    .section-title { font-family: 'Clash Display', sans-serif; font-size: 1rem; font-weight: 600; color: var(--muted); margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.06em; }
    .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(185px, 1fr)); gap: 14px; margin-bottom: 32px; }

    .prod-card {
      background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius);
      overflow: hidden; cursor: pointer; transition: all 0.2s;
    }
    .prod-card:hover { border-color: rgba(0,245,196,0.3); transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,0.4); }

    .prod-img {
      width: 100%; aspect-ratio: 1/1;
      display: flex; align-items: center; justify-content: center; font-size: 48px;
      position: relative; overflow: hidden;
    }
    .prod-img img { width:100%; height:100%; object-fit:cover; position:absolute; inset:0; }
    .prod-badge {
      position: absolute; top: 10px; left: 10px; z-index: 1;
      font-size: 10px; font-weight: 600; padding: 3px 10px; border-radius: 99px;
      letter-spacing: 0.04em;
    }
    .pb-dest  { background: rgba(0,245,196,0.2);  color: var(--neon); }
    .pb-nuevo { background: rgba(0,196,255,0.2);  color: var(--blue); }
    .pb-oferta{ background: rgba(255,209,102,0.2); color: var(--gold); }
    .pb-agotado{ background: rgba(255,107,107,0.2); color: var(--neon2); }

    .prod-body { padding: 12px 14px 14px; }
    .prod-cat { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px; }
    .prod-name { font-size: 13.5px; font-weight: 500; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .prod-seller { font-size: 11px; color: var(--muted); margin-bottom: 10px; }
    .prod-footer { display: flex; align-items: center; justify-content: space-between; }
    .prod-price { font-family: 'Clash Display', sans-serif; font-size: 1.15rem; font-weight: 700; color: var(--neon); }
    .prod-price.agotado { color: var(--muted); }

    .add-btn {
      width: 32px; height: 32px; border-radius: 8px;
      background: rgba(0,245,196,0.12); border: 1px solid rgba(0,245,196,0.25);
      color: var(--neon); font-size: 1.2rem; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all 0.2s; flex-shrink: 0;
    }
    .add-btn:hover { background: var(--neon); color: #05050A; transform: scale(1.1); }
    .add-btn:disabled { opacity: 0.3; cursor: not-allowed; }

    /* CARRITO MODAL */
    .cart-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100;
      display: none; backdrop-filter: blur(4px);
    }
    .cart-overlay.open { display: block; }
    .cart-panel {
      position: fixed; right: 0; top: 0; bottom: 0; width: 380px;
      background: var(--panel2); border-left: 1px solid var(--border);
      z-index: 101; display: flex; flex-direction: column;
      transform: translateX(100%); transition: transform 0.3s cubic-bezier(.4,0,.2,1);
    }
    .cart-overlay.open .cart-panel { transform: translateX(0); }

    .cart-head {
      display: flex; align-items: center; justify-content: space-between;
      padding: 20px 22px; border-bottom: 1px solid var(--border);
    }
    .cart-head h3 { font-family: 'Clash Display', sans-serif; font-size: 1.2rem; }
    .close-btn { background: none; border: none; color: var(--muted); font-size: 1.3rem; cursor: pointer; }
    .close-btn:hover { color: var(--text); }

    .cart-items { flex: 1; overflow-y: auto; padding: 16px 22px; }
    .cart-empty { text-align: center; color: var(--muted); padding: 60px 0; font-size: 0.95rem; }
    .cart-empty span { display: block; font-size: 3rem; margin-bottom: 12px; }

    .cart-item {
      display: flex; align-items: center; gap: 12px;
      padding: 12px 0; border-bottom: 1px solid var(--border);
    }
    .ci-img { font-size: 2rem; width: 50px; height: 50px; background: rgba(255,255,255,0.04); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ci-info { flex: 1; }
    .ci-name { font-size: 13px; font-weight: 500; margin-bottom: 3px; }
    .ci-price { font-family: 'Clash Display', sans-serif; font-size: 14px; color: var(--neon); }
    .ci-qty { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
    .qty-btn { width: 24px; height: 24px; border-radius: 6px; background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: var(--text); cursor: pointer; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
    .qty-btn:hover { background: rgba(0,245,196,0.1); border-color: rgba(0,245,196,0.3); }
    .qty-num { font-size: 13px; font-weight: 600; min-width: 20px; text-align: center; }
    .ci-remove { background: none; border: none; color: var(--muted); cursor: pointer; font-size: 1rem; padding: 4px; }
    .ci-remove:hover { color: var(--neon2); }

    .cart-foot { padding: 18px 22px; border-top: 1px solid var(--border); }
    .cart-subtotal { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; color: var(--muted); }
    .cart-total { display: flex; justify-content: space-between; font-family: 'Clash Display', sans-serif; font-size: 1.3rem; font-weight: 700; margin-bottom: 16px; }
    .cart-total span:last-child { color: var(--neon); }

    .checkout-btn {
      width: 100%; padding: 14px; border: none; border-radius: 12px;
      background: var(--neon); color: #05050A;
      font-family: 'Clash Display', sans-serif; font-size: 1rem; font-weight: 700;
      cursor: pointer; transition: all 0.2s; letter-spacing: 0.5px;
    }
    .checkout-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,245,196,0.3); }
    .checkout-btn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }

    /* TOAST */
    .toast {
      position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%) translateY(80px);
      background: var(--panel2); border: 1px solid rgba(0,245,196,0.3);
      color: var(--neon); padding: 12px 22px; border-radius: 99px;
      font-size: 13px; font-weight: 500; z-index: 200;
      transition: transform 0.35s cubic-bezier(.4,0,.2,1);
      white-space: nowrap;
    }
    .toast.show { transform: translateX(-50%) translateY(0); }

    /* MODAL DETALLE */
    .detail-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 110;
      display: none; align-items: center; justify-content: center;
      backdrop-filter: blur(6px);
    }
    .detail-overlay.open { display: flex; }
    .detail-modal {
      background: var(--panel2); border: 1px solid var(--border); border-radius: 20px;
      width: 480px; max-width: 95vw; padding: 28px;
      animation: popIn 0.25s cubic-bezier(.4,0,.2,1);
    }
    @keyframes popIn { from{opacity:0;transform:scale(.95)} to{opacity:1;transform:scale(1)} }
    .dm-img { font-size: 80px; text-align: center; margin-bottom: 18px; padding: 20px; background: rgba(255,255,255,0.03); border-radius: 14px; min-height:120px; display:flex; align-items:center; justify-content:center; overflow:hidden; }
    .dm-img img { width:100%; max-height:180px; object-fit:contain; border-radius:10px; }
    .dm-cat { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
    .dm-name { font-family: 'Clash Display', sans-serif; font-size: 1.5rem; font-weight: 700; margin-bottom: 8px; }
    .dm-desc { color: var(--muted); font-size: 14px; line-height: 1.6; margin-bottom: 16px; }
    .dm-seller { display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: rgba(255,255,255,0.03); border-radius: 10px; font-size: 13px; margin-bottom: 18px; }
    .dm-seller span { color: var(--muted); font-size: 1.1rem; }
    .dm-footer { display: flex; align-items: center; justify-content: space-between; }
    .dm-price { font-family: 'Clash Display', sans-serif; font-size: 2rem; font-weight: 700; color: var(--neon); }
    .dm-add { padding: 12px 24px; background: var(--neon); color: #05050A; border: none; border-radius: 10px; font-family: 'Clash Display', sans-serif; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: all 0.2s; }
    .dm-add:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,245,196,0.3); }
    .dm-close { position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.06); border: none; border-radius: 8px; width: 32px; height: 32px; color: var(--muted); cursor: pointer; font-size: 1rem; }

    @media(max-width:768px) {
      .sidebar { display: none; }
      .stats-row { grid-template-columns: repeat(2,1fr); }
      .cart-panel { width: 100%; }
    }
  </style>
</head>
<body>
<div class="bg-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="app">

  <!-- SIDEBAR — sin cambios -->
  <div class="sidebar">
    <a href="catalogo.php" class="brand">
      <div class="brand-icon">🎓</div>
      Campus<span>Go</span>
    </a>

    <div class="nav-section">Menú</div>
    <a href="catalogo.php" class="nav-item active">
      <span class="icon">🏪</span> Catálogo
    </a>
    <a href="mapa.php" class="nav-item">
      <span class="icon">🗺️</span> Mapa
    </a>
    <a href="mis_compras.php" class="nav-item">
      <span class="icon">📦</span> Mis Compras
    </a>
    <a href="favoritos.php" class="nav-item">
      <span class="icon">❤️</span> Favoritos
    </a>
   <?php if($rol === 'vendedor' || $rol === 'admin'): ?>
<div class="nav-section" style="margin-top:10px;">Vender</div>
<a href="publicar.php" class="nav-item">
  <span class="icon">➕</span> Publicar producto
</a>
<a href="mis_ventas.php" class="nav-item">
  <span class="icon">📊</span> Mis Ventas
</a>
<?php else: ?>
<div class="nav-section" style="margin-top:10px;">¿Quieres vender?</div>
<a href="registro.php" class="nav-item" style="color:var(--gold);border:1px solid rgba(255,209,102,0.2);background:rgba(255,209,102,0.05);">
  <span class="icon">🏪</span> Crear cuenta vendedor
</a>
<?php endif; ?>

<?php if($esAdmin): ?>
<div class="nav-section" style="margin-top:10px;">Admin</div>
<a href="usuarios.php" class="nav-item">
  <span class="icon">👥</span> Gestionar usuarios
</a>
<?php endif; ?>

    <div class="spacer"></div>
    <hr class="divider">
<div class="user-card">
      <div class="avatar"><?php echo $siglas; ?></div>
      <div>
        <div class="user-name"><?php echo htmlspecialchars($nombre); ?></div>
        <div class="user-role"><?php echo ucfirst($rol); ?></div>
      </div>
    </div>

    <a href="php/logout.php" class="nav-item" style="color:var(--neon2);margin-top:6px;">
      <span class="icon">🚪</span> Cerrar sesión
    </a>
  </div>

  <!-- MAIN — sin cambios en topbar, stats, filtros -->
  <div class="main">

    <div class="topbar">
      <div class="page-title">Catálogo 🛍️</div>
      <div class="search-wrap">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" id="search-input" placeholder="Buscar productos..." oninput="filterProducts()">
      </div>
      <button class="cart-btn" onclick="toggleCart()">
        🛒
        <span class="cart-count" id="cart-count">0</span>
      </button>
    </div>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Productos</div>
        <div class="stat-value" id="total-prods">12</div>
        <div class="stat-sub">disponibles</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Vendedores</div>
        <div class="stat-value" style="color:var(--gold);">6</div>
        <div class="stat-sub">activos hoy</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Categorías</div>
        <div class="stat-value" style="color:var(--blue);">6</div>
        <div class="stat-sub">disponibles</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">En tu carrito</div>
        <div class="stat-value" style="color:var(--neon2);" id="stat-cart">$0</div>
        <div class="stat-sub">total acumulado</div>
      </div>
    </div>

    <div class="filters">
      <button class="filter-btn active" onclick="setFilter('todos', this)">Todos</button>
      <button class="filter-btn" onclick="setFilter('tecnologia', this)">💻 Tecnología</button>
      <button class="filter-btn" onclick="setFilter('libros', this)">📚 Libros</button>
      <button class="filter-btn" onclick="setFilter('comida', this)">🍕 Comida</button>
      <button class="filter-btn" onclick="setFilter('ropa', this)">👕 Ropa</button>
      <button class="filter-btn" onclick="setFilter('servicios', this)">🔧 Servicios</button>
    </div>

    <div class="section-title">✦ Productos disponibles</div>
    <div class="products-grid" id="products-grid"></div>

  </div>
</div>

<!-- CARRITO — sin cambios -->
<div class="cart-overlay" id="cart-overlay" onclick="closeCartOutside(event)">
  <div class="cart-panel">
    <div class="cart-head">
      <h3>🛒 Mi Carrito</h3>
      <button class="close-btn" onclick="toggleCart()">✕</button>
    </div>
    <div class="cart-items" id="cart-items">
      <div class="cart-empty"><span>🛒</span>Tu carrito está vacío</div>
    </div>
    <div class="cart-foot">
      <div class="cart-subtotal"><span>Subtotal</span><span id="subtotal">$0.00</span></div>
      <div class="cart-subtotal"><span>Envío</span><span style="color:var(--neon)">Gratis 🎉</span></div>
      <div class="cart-total"><span>Total</span><span id="total-price">$0.00</span></div>
      <button class="checkout-btn" id="checkout-btn" onclick="checkout()" disabled>Confirmar pedido →</button>
    </div>
  </div>
</div>

<!-- MODAL DETALLE — sin cambios -->
<div class="detail-overlay" id="detail-overlay" onclick="closeDetailOutside(event)">
  <div class="detail-modal" style="position:relative">
    <button class="dm-close" onclick="closeDetail()">✕</button>
    <div class="dm-img" id="dm-img">📦</div>
    <div class="dm-cat" id="dm-cat">categoría</div>
    <div class="dm-name" id="dm-name">Nombre</div>
    <div class="dm-desc" id="dm-desc">Descripción del producto.</div>
    <div class="dm-seller"><span>👤</span><span id="dm-seller">vendedor</span></div>
    <div class="dm-footer">
      <div class="dm-price" id="dm-price">$0</div>
      <button class="dm-add" id="dm-add-btn" onclick="addFromDetail()">+ Agregar al carrito</button>
    </div>
  </div>
</div>

<div class="toast" id="toast">✅ Agregado al carrito</div>

<script>
// ── CAMBIO: productos desde PHP/BD (activo=1) en lugar del array hardcodeado ──
const productos = <?= $productos_json ?>;

const emojiCat = {
  tecnologia: '💻', libros: '📚', comida: '🍕', ropa: '👕',
  servicios: '🔧', electronica: '🔌', deportes: '⚽', hogar: '🏠'
};
const bgColors = {
  tecnologia: "rgba(0,196,255,0.07)",
  libros:     "rgba(255,209,102,0.07)",
  comida:     "rgba(255,107,107,0.07)",
  ropa:       "rgba(255,209,102,0.06)",
  servicios:  "rgba(0,245,196,0.06)",
};

function cartLoad() {
  try { return JSON.parse(sessionStorage.getItem('campusgo_cart') || '[]'); } catch { return []; }
}
function cartSave(c) {
  sessionStorage.setItem('campusgo_cart', JSON.stringify(c));
}

let cart = cartLoad();
let filtroActual = "todos";
let searchActual = "";
let detailProd = null;

// ── CAMBIO: renderProducts usa campos de la BD (nombre, categoria, vendedor_nombre, precio, imagen) ──
function renderProducts() {
  const grid = document.getElementById("products-grid");
  let lista = productos.filter(p => {
    const cat = (p.categoria || '').toLowerCase();
    const matchCat    = filtroActual === "todos" || cat === filtroActual;
    const matchSearch = (p.nombre || '').toLowerCase().includes(searchActual.toLowerCase()) ||
                        (p.vendedor_nombre || '').toLowerCase().includes(searchActual.toLowerCase());
    return matchCat && matchSearch;
  });

  document.getElementById("total-prods").textContent = lista.length;

  if (!lista.length) {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;color:var(--muted);padding:60px 0;font-size:1rem;">😕 No se encontraron productos</div>`;
    return;
  }

  grid.innerHTML = lista.map(p => {
    const cat    = (p.categoria || '').toLowerCase();
    const emoji  = emojiCat[cat] || '📦';
    const bg     = bgColors[cat] || 'rgba(255,255,255,0.04)';
    const precio = parseFloat(p.precio) || 0;
    const vendedor = p.vendedor_nombre ? `@${p.vendedor_nombre}` : '—';

    // Imagen real si existe, si no emoji
    const imgInner = p.imagen
      ? `<img src="${p.imagen}" alt="" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;" onerror="this.remove()">${emoji}`
      : emoji;

    return `
    <div class="prod-card" onclick="openDetail(${p.id})">
      <div class="prod-img" style="background:${bg}">
        ${imgInner}
      </div>
      <div class="prod-body">
        <div class="prod-cat">${p.categoria || ''}</div>
        <div class="prod-name">${p.nombre || ''}</div>
        <div class="prod-seller">👤 ${vendedor}</div>
        <div class="prod-footer">
          <div class="prod-price">$${precio.toLocaleString('es-MX')}</div>
          <div style="display:flex;gap:6px;">
            <button class="add-btn"
              onclick="event.stopPropagation(); addToCart(${p.id})">
              +
            </button>
<a href="favoritos.php?agregar=${p.id}" class="add-btn" style="background:rgba(255,107,107,0.12);border-color:rgba(255,107,107,0.25);color:var(--neon2);text-decoration:none;" onclick="event.stopPropagation();">❤</a>
            <?php if($esAdmin): ?>
            <button class="add-btn"
              style="background:rgba(255,0,0,0.15);color:#ff6b6b;border-color:#ff6b6b;"
              onclick="event.stopPropagation();
              if(confirm('¿Eliminar producto?')){
                window.location='catalogo.php?eliminar_producto=${p.id}';
              }">
              🗑
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>`;
  }).join("");
}

// ── Sin cambios a partir de aquí ──
function setFilter(cat, btn) {
  filtroActual = cat;
  document.querySelectorAll(".filter-btn").forEach(b => b.classList.remove("active"));
  btn.classList.add("active");
  renderProducts();
}
function filterProducts() {
  searchActual = document.getElementById("search-input").value;
  renderProducts();
}

function addToCart(id) {
  const prod = productos.find(p => p.id == id);
  if (!prod) return;
  const existing = cart.find(c => c.id == id);
  if (existing) existing.qty++;
  else cart.push({ ...prod, qty: 1 });
  cartSave(cart);
  updateCart();
  showToast(`✅ ${prod.nombre} agregado`);
}

function updateCart() {
  const count = cart.reduce((a,c) => a + c.qty, 0);
  const total = cart.reduce((a,c) => a + parseFloat(c.precio) * c.qty, 0);

  document.getElementById("cart-count").textContent = count;
  document.getElementById("total-price").textContent = `$${total.toLocaleString('es-MX', {minimumFractionDigits:2})}`;
  document.getElementById("subtotal").textContent    = `$${total.toLocaleString('es-MX', {minimumFractionDigits:2})}`;
  document.getElementById("stat-cart").textContent   = `$${total.toLocaleString('es-MX')}`;
  document.getElementById("checkout-btn").disabled   = cart.length === 0;

  const container = document.getElementById("cart-items");
  if (!cart.length) {
    container.innerHTML = `<div class="cart-empty"><span>🛒</span>Tu carrito está vacío</div>`;
    return;
  }
  container.innerHTML = cart.map(c => `
    <div class="cart-item">
      <div class="ci-img">${emojiCat[(c.categoria||'').toLowerCase()] || '📦'}</div>
      <div class="ci-info">
        <div class="ci-name">${c.nombre}</div>
        <div class="ci-price">$${(parseFloat(c.precio) * c.qty).toLocaleString('es-MX')}</div>
        <div class="ci-qty">
          <button class="qty-btn" onclick="changeQty(${c.id}, -1)">−</button>
          <span class="qty-num">${c.qty}</span>
          <button class="qty-btn" onclick="changeQty(${c.id}, 1)">+</button>
        </div>
      </div>
      <button class="ci-remove" onclick="removeFromCart(${c.id})">🗑️</button>
    </div>
  `).join("");
}

function changeQty(id, delta) {
  const item = cart.find(c => c.id == id);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) cart = cart.filter(c => c.id != id);
  cartSave(cart);
  updateCart();
}
function removeFromCart(id) {
  cart = cart.filter(c => c.id != id);
  cartSave(cart);
  updateCart();
}

function toggleCart() {
  document.getElementById("cart-overlay").classList.toggle("open");
}
function closeCartOutside(e) {
  if (e.target === document.getElementById("cart-overlay")) toggleCart();
}

function checkout() {
  window.location.href = 'checkout.php';
}

function openDetail(id) {
  detailProd = productos.find(p => p.id == id);
  if (!detailProd) return;

  const dmImg = document.getElementById("dm-img");
  if (detailProd.imagen) {
    dmImg.innerHTML = `<img src="${detailProd.imagen}" alt="${detailProd.nombre}" onerror="this.outerHTML='${emojiCat[(detailProd.categoria||'').toLowerCase()]||'📦'}'">`;
  } else {
    dmImg.textContent = emojiCat[(detailProd.categoria||'').toLowerCase()] || '📦';
  }

  document.getElementById("dm-cat").textContent    = detailProd.categoria || '';
  document.getElementById("dm-name").textContent   = detailProd.nombre || '';
  document.getElementById("dm-desc").textContent   = detailProd.descripcion || 'Sin descripción.';
  document.getElementById("dm-seller").textContent = detailProd.vendedor_nombre ? `@${detailProd.vendedor_nombre}` : '—';
  document.getElementById("dm-price").textContent  = `$${parseFloat(detailProd.precio).toLocaleString('es-MX')}`;
  document.getElementById("detail-overlay").classList.add("open");
}
function closeDetail() { document.getElementById("detail-overlay").classList.remove("open"); }
function closeDetailOutside(e) { if (e.target === document.getElementById("detail-overlay")) closeDetail(); }
function addFromDetail() {
  addToCart(detailProd.id);
  closeDetail();
}

function showToast(msg) {
  const t = document.getElementById("toast");
  t.textContent = msg;
  t.classList.add("show");
  setTimeout(() => t.classList.remove("show"), 2500);
}

// INIT
renderProducts();
updateCart();
</script>
</body>
</html>
