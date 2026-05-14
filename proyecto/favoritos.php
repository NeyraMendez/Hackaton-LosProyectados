<?php
session_start();
require_once 'php/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
$nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$rol    = $_SESSION['usuario_rol']    ?? 'comprador';
$siglas = strtoupper(substr($nombre, 0, 2));
$uid    = $_SESSION['usuario_id'];
$pdo    = conectar();

// Agregar favorito
if (isset($_GET['agregar'])) {
    $pid = intval($_GET['agregar']);
    $pdo->prepare("INSERT IGNORE INTO favoritos (usuario_id, producto_id) VALUES (?,?)")->execute([$uid, $pid]);
    header("Location: catalogo.php");
    exit;
}

// Quitar favorito
if (isset($_GET['quitar'])) {
    $pid = intval($_GET['quitar']);
    $pdo->prepare("DELETE FROM favoritos WHERE usuario_id=? AND producto_id=?")->execute([$uid, $pid]);
    header("Location: favoritos.php");
    exit;
}

// Obtener favoritos
$stmt = $pdo->prepare("
    SELECT p.*, u.nombre AS vendedor_nombre
    FROM favoritos f
    JOIN productos p ON f.producto_id = p.id
    JOIN usuarios u ON p.vendedor_id = u.id
    WHERE f.usuario_id = ?
    ORDER BY f.creado_en DESC
");
$stmt->execute([$uid]);
$favs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>CampusGo – Favoritos</title>
<link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@700&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{--bg:#05050A;--panel:#0E0E18;--panel2:#13131F;--border:rgba(255,255,255,0.07);--neon:#00F5C4;--neon2:#FF6B6B;--gold:#FFD166;--blue:#00C4FF;--text:#EEEEF5;--muted:#6B6B8A;--radius:16px}
  html,body{height:100%;font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text)}
  .bg-grid{position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(0,245,196,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(0,245,196,0.03) 1px,transparent 1px);background-size:36px 36px}
  .orb{position:fixed;border-radius:50%;pointer-events:none;z-index:0;animation:float 8s ease-in-out infinite alternate}
  .orb-1{width:400px;height:400px;background:rgba(0,245,196,0.08);top:-80px;left:-100px;filter:blur(80px)}
  .orb-2{width:300px;height:300px;background:rgba(255,107,107,0.07);bottom:-60px;right:-80px;filter:blur(70px);animation-delay:-3s}
  @keyframes float{from{transform:translate(0,0)}to{transform:translate(20px,30px)}}
  .app{position:relative;z-index:1;display:flex;height:100vh;overflow:hidden}
  .sidebar{width:220px;flex-shrink:0;background:rgba(14,14,24,0.9);border-right:1px solid var(--border);backdrop-filter:blur(20px);display:flex;flex-direction:column;padding:20px 14px;gap:4px}
  .brand{display:flex;align-items:center;gap:10px;font-family:'Clash Display',sans-serif;font-size:1.3rem;font-weight:700;color:var(--text);text-decoration:none;margin-bottom:20px;padding:0 4px}
  .brand-icon{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--neon),var(--blue));display:flex;align-items:center;justify-content:center;font-size:1.1rem;box-shadow:0 0 18px rgba(0,245,196,0.35)}
  .brand span{color:var(--neon)}
  .nav-section{font-size:10px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);padding:10px 8px 6px}
  .nav-item{display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;font-size:13.5px;color:var(--muted);cursor:pointer;border:none;background:transparent;width:100%;text-align:left;text-decoration:none;transition:all 0.15s;font-family:'Outfit',sans-serif}
  .nav-item:hover{background:rgba(255,255,255,0.04);color:var(--text)}
  .nav-item.active{background:rgba(0,245,196,0.1);color:var(--neon);border:1px solid rgba(0,245,196,0.2)}
  .nav-item .icon{font-size:1rem;width:20px;text-align:center}
  .spacer{flex:1}
  .divider{border:none;border-top:1px solid var(--border);margin:8px 0}
  .user-card{display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;background:rgba(255,255,255,0.03);border:1px solid var(--border)}
  .avatar{width:36px;height:36px;border-radius:50%;background:rgba(0,245,196,0.12);border:1px solid rgba(0,245,196,0.25);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--neon);flex-shrink:0}
  .user-name{font-size:13px;font-weight:600}
  .user-role{font-size:11px;color:var(--muted)}
  .main{flex:1;overflow-y:auto;padding:24px 28px}
  .main::-webkit-scrollbar{width:5px}
  .main::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.07);border-radius:99px}
  .topbar{display:flex;align-items:center;gap:14px;margin-bottom:26px}
  .page-title{font-family:'Clash Display',sans-serif;font-size:1.6rem;font-weight:700;flex:1}
  .empty-state{text-align:center;padding:80px 0;color:var(--muted)}
  .empty-state span{display:block;font-size:4rem;margin-bottom:1rem}
  .empty-state a{display:inline-block;margin-top:1.5rem;padding:0.8rem 1.8rem;background:var(--neon);color:#05050A;border-radius:10px;text-decoration:none;font-weight:700;font-family:'Clash Display',sans-serif}
  .products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:14px}
  .prod-card{background:var(--panel);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;transition:all 0.2s}
  .prod-card:hover{border-color:rgba(0,245,196,0.3);transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,0.4)}
  .prod-img{width:100%;aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;font-size:48px;position:relative;overflow:hidden}
  .prod-img img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0}
  .prod-body{padding:12px 14px 14px}
  .prod-cat{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px}
  .prod-name{font-size:13.5px;font-weight:500;margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .prod-seller{font-size:11px;color:var(--muted);margin-bottom:10px}
  .prod-footer{display:flex;align-items:center;justify-content:space-between}
  .prod-price{font-family:'Clash Display',sans-serif;font-size:1.15rem;font-weight:700;color:var(--neon)}
  .btn-quitar{width:32px;height:32px;border-radius:8px;background:rgba(255,107,107,0.12);border:1px solid rgba(255,107,107,0.25);color:var(--neon2);font-size:1rem;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.2s;flex-shrink:0;text-decoration:none}
  .btn-quitar:hover{background:var(--neon2);color:white}
  .badge-count{display:inline-block;background:rgba(255,107,107,0.12);border:1px solid rgba(255,107,107,0.25);color:var(--neon2);padding:0.3rem 0.9rem;border-radius:99px;font-size:0.8rem;margin-left:0.8rem}
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
    <a href="favoritos.php" class="nav-item active"><span class="icon">❤️</span> Favoritos</a>
    <?php if($rol==='vendedor'||$rol==='admin'): ?>
    <div class="nav-section" style="margin-top:10px;">Vender</div>
    <a href="publicar.php" class="nav-item"><span class="icon">➕</span> Publicar producto</a>
    <a href="mis_ventas.php" class="nav-item"><span class="icon">📊</span> Mis Ventas</a>
    <?php else: ?>
    <div class="nav-section" style="margin-top:10px;">¿Quieres vender?</div>
    <a href="registro.php" class="nav-item" style="color:var(--gold);border:1px solid rgba(255,209,102,0.2);background:rgba(255,209,102,0.05);">
      <span class="icon">🏪</span> Crear cuenta vendedor
    </a>
    <?php endif; ?>
    <?php if($rol==='admin'): ?>
    <div class="nav-section" style="margin-top:10px;">Admin</div>
    <a href="usuarios.php" class="nav-item"><span class="icon">👥</span> Gestionar usuarios</a>
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

  <div class="main">
    <div class="topbar">
      <div class="page-title">❤️ Favoritos <span class="badge-count"><?= count($favs) ?></span></div>
      <a href="catalogo.php" style="color:var(--muted);text-decoration:none;font-size:0.9rem;">← Ver catálogo</a>
    </div>

    <?php if(empty($favs)): ?>
    <div class="empty-state">
      <span>❤️</span>
      <p>No tienes productos favoritos aún.</p>
      <p style="font-size:0.9rem;margin-top:0.5rem;">Agrega productos desde el catálogo.</p>
      <a href="catalogo.php">Explorar catálogo →</a>
    </div>
    <?php else: ?>
    <div class="products-grid">
      <?php
      $emojiCat = ['tecnologia'=>'💻','libros'=>'📚','comida'=>'🍕','ropa'=>'👕','servicios'=>'🔧'];
      $bgColors = ['tecnologia'=>'rgba(0,196,255,0.07)','libros'=>'rgba(255,209,102,0.07)','comida'=>'rgba(255,107,107,0.07)','ropa'=>'rgba(255,209,102,0.06)','servicios'=>'rgba(0,245,196,0.06)'];
      foreach($favs as $p):
        $cat   = $p['categoria'] ?? '';
        $emoji = $emojiCat[$cat] ?? '📦';
        $bg    = $bgColors[$cat] ?? 'rgba(255,255,255,0.04)';
      ?>
      <div class="prod-card">
        <div class="prod-img" style="background:<?= $bg ?>">
          <?php if($p['imagen']): ?>
          <img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" onerror="this.remove()">
          <?php endif; ?>
          <?= $emoji ?>
        </div>
        <div class="prod-body">
          <div class="prod-cat"><?= htmlspecialchars($cat) ?></div>
          <div class="prod-name"><?= htmlspecialchars($p['nombre']) ?></div>
          <div class="prod-seller">👤 @<?= htmlspecialchars($p['vendedor_nombre']) ?></div>
          <div class="prod-footer">
            <div class="prod-price">$<?= number_format($p['precio'],0,'.',',') ?></div>
            <a href="favoritos.php?quitar=<?= $p['id'] ?>" class="btn-quitar"
               onclick="return confirm('¿Quitar de favoritos?')">🗑</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
