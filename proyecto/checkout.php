<?php
session_start();
require_once 'php/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id    = $_SESSION['usuario_id'];
$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Usuario';
$rolUsuario    = $_SESSION['usuario_rol']    ?? '';
$iniciales     = strtoupper(substr($nombreUsuario, 0, 1) . (strpos($nombreUsuario, ' ') !== false ? substr($nombreUsuario, strpos($nombreUsuario,' ')+1, 1) : ''));

// Cargar dirección guardada del usuario
$pdo_pre = conectar();
$stmtDir = $pdo_pre->prepare("SELECT direccion FROM usuarios WHERE id = ?");
$stmtDir->execute([$usuario_id]);
$direccionGuardada = $stmtDir->fetchColumn() ?: '';

// ── CONFIRMAR COMPRA (POST desde JS) ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data      = json_decode(file_get_contents('php://input'), true);
    $items     = $data['items']     ?? [];
    $envio     = $data['envio']     ?? '';
    $direccion = trim($data['direccion'] ?? '');

    if (empty($items)) {
        echo json_encode(['ok' => false, 'error' => 'carrito_vacio']);
        exit;
    }

    $pdo = conectar();

    $pdo->beginTransaction();
    try {
        // Guardar dirección en el perfil del usuario si eligió envío a domicilio
        if ($envio === 'domicilio' && $direccion !== '') {
            $stmtDir = $pdo->prepare("UPDATE usuarios SET direccion = ? WHERE id = ?");
            $stmtDir->execute([$direccion, $usuario_id]);
        }

        $ins = $pdo->prepare("INSERT INTO ventas (comprador_id, producto_id, vendedor_id, precio_final, estado) VALUES (?,?,?,?,'pendiente')");

        foreach ($items as $item) {
            $pid = (int)$item['producto_id'];
            $qty = max(1, (int)$item['cantidad']);

            // Bloquear la fila para leer stock actual de forma segura
            $stmtP = $pdo->prepare("SELECT id, precio, vendedor_id, inventario FROM productos WHERE id = ? AND activo = 1 FOR UPDATE");
            $stmtP->execute([$pid]);
            $p = $stmtP->fetch(PDO::FETCH_ASSOC);

            // Producto no encontrado o inactivo
            if (!$p) {
                $pdo->rollBack();
                echo json_encode(['ok' => false, 'error' => 'producto_no_disponible', 'producto_id' => $pid]);
                exit;
            }

            // Stock insuficiente
            if ($p['inventario'] < $qty) {
                $pdo->rollBack();
                echo json_encode([
                    'ok'           => false,
                    'error'        => 'stock_insuficiente',
                    'producto_id'  => $pid,
                    'disponibles'  => (int)$p['inventario'],
                    'solicitados'  => $qty
                ]);
                exit;
            }

            // Insertar venta
            $ins->execute([$usuario_id, $pid, $p['vendedor_id'], $p['precio'] * $qty]);

            // Registrar en historial de compras del comprador
            $stmtCR = $pdo->prepare("
                INSERT INTO compra_realizada
                    (comprador_id, vendedor_id, nombre, descripcion, categoria, imagen, precio_pagado, cantidad)
                SELECT
                    :comprador_id,
                    vendedor_id,
                    nombre,
                    descripcion,
                    categoria,
                    imagen,
                    precio * :cantidad,
                    :cantidad2
                FROM productos
                WHERE id = :producto_id
            ");
            $stmtCR->execute([
                ':comprador_id' => $usuario_id,
                ':cantidad'     => $qty,
                ':cantidad2'    => $qty,
                ':producto_id'  => $pid,
            ]);

            // Descontar inventario; si llega a 0 también desactivar el producto
            $nuevoInventario = $p['inventario'] - $qty;
            $nuevoActivo     = $nuevoInventario > 0 ? 1 : 0;

            $stmtU = $pdo->prepare("UPDATE productos SET inventario = ?, activo = ? WHERE id = ?");
            $stmtU->execute([$nuevoInventario, $nuevoActivo, $pid]);
        }

        $pdo->commit();
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => 'db_error']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CampusGo – Checkout</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:#05050A; --panel:#0E0E18; --panel2:#13131F;
      --border:rgba(255,255,255,0.07); --neon:#00F5C4; --neon2:#FF6B6B;
      --gold:#FFD166; --blue:#00C4FF; --text:#EEEEF5; --muted:#6B6B8A; --radius:16px;
    }
    html,body{height:100%;font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);}
    .bg-grid{position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(0,245,196,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(0,245,196,0.03) 1px,transparent 1px);background-size:36px 36px;animation:gridMove 20s linear infinite;}
    @keyframes gridMove{to{background-position:36px 36px;}}
    .orb{position:fixed;border-radius:50%;pointer-events:none;z-index:0;animation:float 8s ease-in-out infinite alternate;}
    .orb-1{width:400px;height:400px;background:rgba(0,245,196,0.08);top:-80px;left:-100px;filter:blur(80px);}
    .orb-2{width:300px;height:300px;background:rgba(255,107,107,0.07);bottom:-60px;right:-80px;filter:blur(70px);animation-delay:-3s;}
    @keyframes float{from{transform:translate(0,0)}to{transform:translate(20px,30px)}}
    .app{position:relative;z-index:1;display:flex;height:100vh;overflow:hidden;}
    .sidebar{width:220px;flex-shrink:0;background:rgba(14,14,24,0.9);border-right:1px solid var(--border);backdrop-filter:blur(20px);display:flex;flex-direction:column;padding:20px 14px;gap:4px;}
    .brand{display:flex;align-items:center;gap:10px;font-family:'Clash Display',sans-serif;font-size:1.3rem;font-weight:700;color:var(--text);text-decoration:none;margin-bottom:20px;padding:0 4px;}
    .brand-icon{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--neon),var(--blue));display:flex;align-items:center;justify-content:center;font-size:1.1rem;box-shadow:0 0 18px rgba(0,245,196,0.35);}
    .brand span{color:var(--neon);}
    .nav-section{font-size:10px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);padding:10px 8px 6px;}
    .nav-item{display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;font-size:13.5px;color:var(--muted);cursor:pointer;border:none;background:transparent;width:100%;text-align:left;text-decoration:none;transition:all 0.15s;font-family:'Outfit',sans-serif;}
    .nav-item:hover{background:rgba(255,255,255,0.04);color:var(--text);}
    .nav-item.active{background:rgba(0,245,196,0.1);color:var(--neon);border:1px solid rgba(0,245,196,0.2);}
    .nav-item .icon{font-size:1rem;width:20px;text-align:center;}
    .spacer{flex:1;} .divider{border:none;border-top:1px solid var(--border);margin:8px 0;}
    .user-card{display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;background:rgba(255,255,255,0.03);border:1px solid var(--border);}
    .avatar{width:36px;height:36px;border-radius:50%;background:rgba(0,245,196,0.12);border:1px solid rgba(0,245,196,0.25);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--neon);flex-shrink:0;}
    .user-name{font-size:13px;font-weight:600;} .user-role{font-size:11px;color:var(--muted);}
    .main{flex:1;overflow-y:auto;padding:24px 28px;}
    .main::-webkit-scrollbar{width:5px;} .main::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.07);border-radius:99px;}
    .topbar{display:flex;align-items:center;gap:14px;margin-bottom:26px;}
    .page-title{font-family:'Clash Display',sans-serif;font-size:1.6rem;font-weight:700;flex:1;}
    .back-btn{display:flex;align-items:center;gap:7px;padding:9px 16px;border-radius:10px;font-size:13px;background:rgba(255,255,255,0.04);border:1px solid var(--border);color:var(--muted);text-decoration:none;transition:all 0.15s;font-family:'Outfit',sans-serif;}
    .back-btn:hover{color:var(--text);border-color:rgba(255,255,255,0.15);}
    .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:26px;}
    .stat-card{background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:16px 18px;}
    .stat-label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;}
    .stat-value{font-family:'Clash Display',sans-serif;font-size:1.6rem;font-weight:700;color:var(--neon);}
    .stat-sub{font-size:11px;color:var(--muted);margin-top:2px;}
    .checkout-grid{display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;}
    .section-title{font-family:'Clash Display',sans-serif;font-size:1rem;font-weight:600;color:var(--muted);margin-bottom:16px;text-transform:uppercase;letter-spacing:0.06em;}
    .items-panel{background:var(--panel);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;}
    .items-head{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
    .items-head h3{font-family:'Clash Display',sans-serif;font-size:1rem;font-weight:600;}
    .vaciar-btn{font-size:12px;color:var(--neon2);background:rgba(255,107,107,0.08);border:1px solid rgba(255,107,107,0.2);border-radius:8px;padding:5px 12px;cursor:pointer;font-family:'Outfit',sans-serif;transition:all 0.15s;}
    .vaciar-btn:hover{background:rgba(255,107,107,0.15);}
    .checkout-item{display:flex;align-items:center;gap:14px;padding:16px 20px;border-bottom:1px solid var(--border);transition:background 0.15s;}
    .checkout-item:last-child{border-bottom:none;}
    .checkout-item:hover{background:rgba(255,255,255,0.02);}
    .item-emoji{width:54px;height:54px;border-radius:12px;background:rgba(0,245,196,0.06);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:1.8rem;flex-shrink:0;}
    .item-info{flex:1;}
    .item-cat{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:3px;}
    .item-name{font-size:14px;font-weight:500;margin-bottom:3px;}
    .item-seller{font-size:11px;color:var(--muted);}
    .item-controls{display:flex;align-items:center;gap:10px;flex-shrink:0;}
    .qty-wrap{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;padding:4px 8px;}
    .qty-btn{width:22px;height:22px;border-radius:6px;background:rgba(255,255,255,0.06);border:1px solid var(--border);color:var(--text);cursor:pointer;font-size:0.85rem;display:flex;align-items:center;justify-content:center;transition:all 0.15s;}
    .qty-btn:hover{background:rgba(0,245,196,0.12);border-color:rgba(0,245,196,0.3);color:var(--neon);}
    .qty-num{font-size:13px;font-weight:600;min-width:18px;text-align:center;}
    .item-price{font-family:'Clash Display',sans-serif;font-size:1.1rem;font-weight:700;color:var(--neon);min-width:80px;text-align:right;flex-shrink:0;}
    .remove-btn{background:none;border:none;color:var(--muted);cursor:pointer;font-size:1rem;padding:6px;border-radius:8px;transition:all 0.15s;flex-shrink:0;}
    .remove-btn:hover{color:var(--neon2);background:rgba(255,107,107,0.1);}
    .empty-cart{text-align:center;padding:60px 20px;color:var(--muted);}
    .empty-cart .big-emoji{font-size:3.5rem;margin-bottom:14px;}
    .empty-cart p{font-size:0.95rem;margin-bottom:18px;}
    .go-catalog{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;background:rgba(0,245,196,0.1);border:1px solid rgba(0,245,196,0.25);color:var(--neon);text-decoration:none;font-size:13px;font-weight:500;transition:all 0.2s;}
    .go-catalog:hover{background:rgba(0,245,196,0.18);transform:translateY(-2px);}
    .summary-panel{background:var(--panel);border:1px solid var(--border);border-radius:var(--radius);padding:22px;position:sticky;top:0;}
    .summary-title{font-family:'Clash Display',sans-serif;font-size:1rem;font-weight:700;margin-bottom:20px;}
    .summary-row{display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:10px;}
    .summary-row.highlight{color:var(--text);font-weight:500;}
    .summary-divider{border:none;border-top:1px solid var(--border);margin:14px 0;}
    .summary-total{display:flex;justify-content:space-between;font-family:'Clash Display',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:20px;}
    .summary-total span:last-child{color:var(--neon);}
    .free-shipping{display:flex;align-items:center;gap:8px;padding:10px 14px;background:rgba(0,245,196,0.06);border:1px solid rgba(0,245,196,0.15);border-radius:10px;font-size:12px;color:var(--neon);margin-bottom:16px;}
    .confirm-btn{width:100%;padding:15px;border:none;border-radius:12px;background:var(--neon);color:#05050A;font-family:'Clash Display',sans-serif;font-size:1rem;font-weight:700;cursor:pointer;transition:all 0.2s;letter-spacing:0.5px;}
    .confirm-btn:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,245,196,0.35);}
    .confirm-btn:disabled{opacity:0.35;cursor:not-allowed;transform:none;}
    .secure-note{display:flex;align-items:center;justify-content:center;gap:6px;font-size:11px;color:var(--muted);margin-top:12px;}
    .success-banner{display:flex;align-items:center;gap:14px;background:rgba(0,245,196,0.08);border:1px solid rgba(0,245,196,0.25);border-radius:14px;padding:18px 22px;margin-bottom:24px;animation:popIn 0.35s cubic-bezier(.4,0,.2,1);}
    @keyframes popIn{from{opacity:0;transform:scale(.97)}to{opacity:1;transform:scale(1)}}
    .success-icon{font-size:2rem;flex-shrink:0;}
    .success-text h4{font-family:'Clash Display',sans-serif;font-size:1.05rem;color:var(--neon);margin-bottom:4px;}
    .success-text p{font-size:13px;color:var(--muted);}
    .toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--panel2);border:1px solid rgba(0,245,196,0.3);color:var(--neon);padding:12px 22px;border-radius:99px;font-size:13px;font-weight:500;z-index:200;transition:transform 0.35s cubic-bezier(.4,0,.2,1);white-space:nowrap;}
    .toast.show{transform:translateX(-50%) translateY(0);}
    @media(max-width:900px){.checkout-grid{grid-template-columns:1fr;}.summary-panel{position:static;}}
    @media(max-width:768px){.sidebar{display:none;}.stats-row{grid-template-columns:repeat(2,1fr);}}

    /* ── Método de pago & Envío ── */
    .option-section{margin-top:18px;}
    .option-panel{background:var(--panel);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;}
    .option-head{padding:16px 20px;border-bottom:1px solid var(--border);}
    .option-head h3{font-family:'Clash Display',sans-serif;font-size:1rem;font-weight:600;}
    .option-head p{font-size:12px;color:var(--muted);margin-top:3px;}
    .option-list{display:flex;flex-direction:column;gap:0;}
    .option-row{display:flex;align-items:center;gap:16px;padding:16px 20px;cursor:pointer;border-bottom:1px solid var(--border);transition:background 0.15s;user-select:none;}
    .option-row:last-child{border-bottom:none;}
    .option-row:hover{background:rgba(255,255,255,0.02);}
    .option-row.selected{background:rgba(0,245,196,0.06);}
    .option-radio{width:18px;height:18px;border-radius:50%;border:2px solid var(--muted);flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all 0.15s;}
    .option-row.selected .option-radio{border-color:var(--neon);}
    .option-radio-dot{width:8px;height:8px;border-radius:50%;background:var(--neon);display:none;}
    .option-row.selected .option-radio-dot{display:block;}
    .option-icon{font-size:1.5rem;width:42px;height:42px;border-radius:10px;background:rgba(255,255,255,0.04);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all 0.15s;}
    .option-row.selected .option-icon{background:rgba(0,245,196,0.08);border-color:rgba(0,245,196,0.25);}
    .option-text{}
    .option-label{font-size:14px;font-weight:500;}
    .option-sub{font-size:11px;color:var(--muted);margin-top:2px;}
    /* Dirección extra */
    .address-box{padding:14px 20px 18px;border-top:1px solid var(--border);background:rgba(0,245,196,0.03);display:none;}
    .address-box.show{display:block;}
    .address-box label{font-size:12px;color:var(--muted);display:block;margin-bottom:6px;}
    .address-input{width:100%;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:10px;padding:10px 14px;color:var(--text);font-family:'Outfit',sans-serif;font-size:13px;outline:none;transition:border-color 0.15s;}
    .address-input:focus{border-color:rgba(0,245,196,0.4);}
    .address-input::placeholder{color:var(--muted);}
    /* Datos de tarjeta */
    .card-box{padding:18px 20px 20px;border-top:1px solid var(--border);background:rgba(0,196,255,0.03);display:none;}
    .card-box.show{display:block;}
    .card-fields{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .card-field-full{grid-column:1/-1;}
    .card-field label{font-size:12px;color:var(--muted);display:block;margin-bottom:6px;}
    .card-input{width:100%;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:10px;padding:10px 14px;color:var(--text);font-family:'Outfit',sans-serif;font-size:13px;outline:none;transition:border-color 0.15s;letter-spacing:0.04em;}
    .card-input:focus{border-color:rgba(0,196,255,0.45);}
    .card-input::placeholder{color:var(--muted);}
  </style>
</head>
<body>
<div class="bg-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="app">
  <div class="sidebar">
    <a href="index.php" class="brand"><div class="brand-icon">🎓</div>Campus<span>Go</span></a>
    <div class="nav-section">Menú</div>
    <a href="catalogo.php" class="nav-item"><span class="icon">🏪</span> Catálogo</a>
    <a href="mapa.php" class="nav-item"><span class="icon">🗺️</span> Mapa</a>
    <a href="mis_compras.php" class="nav-item"><span class="icon">📦</span> Mis Compras</a>
    <a href="favoritos.php" class="nav-item"><span class="icon">❤️</span> Favoritos</a>
    <div class="nav-section" style="margin-top:10px;">Vender</div>
    <a href="publicar.php" class="nav-item"><span class="icon">➕</span> Publicar producto</a>
    <a href="mis_ventas.php" class="nav-item"><span class="icon">📊</span> Mis Ventas</a>
    <div class="spacer"></div>
    <hr class="divider">
    <div class="user-card">
      <div class="avatar"><?= htmlspecialchars($iniciales) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars(explode(' ', $nombreUsuario)[0]) ?></div>
        <div class="user-role"><?= htmlspecialchars($rolUsuario) ?></div>
      </div>
    </div>
    <a href="php/logout.php" class="nav-item" style="color:var(--neon2);margin-top:6px;"><span class="icon">🚪</span> Cerrar sesión</a>
  </div>

  <div class="main">
    <div class="topbar">
      <div class="page-title">Checkout 🧾</div>
      <a href="catalogo.php" class="back-btn">← Seguir comprando</a>
    </div>

    <div id="success-banner" style="display:none;" class="success-banner">
      <div class="success-icon">🎉</div>
      <div class="success-text">
        <h4>¡Pedido confirmado!</h4>
        <p>Tu orden fue registrada. Los vendedores se pondrán en contacto contigo pronto.</p>
      </div>
    </div>

    <div class="stats-row">
      <div class="stat-card"><div class="stat-label">Productos</div><div class="stat-value" id="stat-items">0</div><div class="stat-sub">en tu carrito</div></div>
      <div class="stat-card"><div class="stat-label">Vendedores</div><div class="stat-value" style="color:var(--gold);" id="stat-sellers">0</div><div class="stat-sub">distintos</div></div>
      <div class="stat-card"><div class="stat-label">Envío</div><div class="stat-value" style="color:var(--neon);font-size:1.1rem;">Gratis</div><div class="stat-sub">🎉 sin costo</div></div>
      <div class="stat-card"><div class="stat-label">Total</div><div class="stat-value" style="color:var(--neon2);" id="stat-total">$0</div><div class="stat-sub">MXN</div></div>
    </div>

    <div class="checkout-grid">
      <div>
        <div class="section-title">✦ Artículos en tu carrito</div>
        <div class="items-panel" id="items-panel">
          <div class="empty-cart"><div class="big-emoji">🛒</div><p>Tu carrito está vacío</p><a href="catalogo.php" class="go-catalog">🏪 Ir al catálogo</a></div>
        </div>

        <!-- ── Método de pago ── -->
        <div class="option-section">
          <div class="section-title">💳 Método de pago</div>
          <div class="option-panel">
            <div class="option-head">
              <h3>¿Cómo vas a pagar?</h3>
              <p>Selecciona una opción para continuar</p>
            </div>
            <div class="option-list" id="pago-list">
              <div class="option-row" data-value="efectivo" onclick="selectOption('pago','efectivo',this)">
                <div class="option-radio"><div class="option-radio-dot"></div></div>
                <div class="option-icon">💵</div>
                <div class="option-text">
                  <div class="option-label">Efectivo</div>
                  <div class="option-sub">Pago directo al vendedor en el momento de la entrega</div>
                </div>
              </div>
              <div class="option-row" data-value="tarjeta" onclick="selectOption('pago','tarjeta',this)">
                <div class="option-radio"><div class="option-radio-dot"></div></div>
                <div class="option-icon">💳</div>
                <div class="option-text">
                  <div class="option-label">Tarjeta de crédito / débito</div>
                  <div class="option-sub">Visa, Mastercard, American Express</div>
                </div>
              </div>
            </div>
            <!-- Datos de tarjeta -->
            <div class="card-box" id="card-box">
              <div class="card-fields">
                <div class="card-field card-field-full">
                  <label for="card-name">Nombre del titular</label>
                  <input id="card-name" class="card-input" type="text" maxlength="60" placeholder="Como aparece en la tarjeta" style="text-transform:uppercase;" oninput="this.value=this.value.toUpperCase()">
                </div>
                <div class="card-field card-field-full">
                  <label for="card-number">Número de tarjeta</label>
                  <input id="card-number" class="card-input" type="text" inputmode="numeric" maxlength="19" placeholder="1234 5678 9012 3456" oninput="fmtCardNumber(this)">
                </div>
                <div class="card-field">
                  <label for="card-expiry">Fecha de expiración</label>
                  <input id="card-expiry" class="card-input" type="text" inputmode="numeric" maxlength="5" placeholder="MM/AA" oninput="fmtExpiry(this)">
                </div>
                <div class="card-field">
                  <label for="card-cvv">CVV</label>
                  <input id="card-cvv" class="card-input" type="password" inputmode="numeric" maxlength="4" placeholder="•••">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Detalles del envío ── -->
        <div class="option-section">
          <div class="section-title">🚚 Detalles del envío</div>
          <div class="option-panel">
            <div class="option-head">
              <h3>¿Cómo quieres recibirlo?</h3>
              <p>Elige la modalidad de entrega</p>
            </div>
            <div class="option-list" id="envio-list">
              <div class="option-row" data-value="persona" onclick="selectOption('envio','persona',this)">
                <div class="option-radio"><div class="option-radio-dot"></div></div>
                <div class="option-icon">🤝</div>
                <div class="option-text">
                  <div class="option-label">Entrega en persona</div>
                  <div class="option-sub">Coordina punto de encuentro con el vendedor en campus</div>
                </div>
              </div>
              <div class="option-row" data-value="domicilio" onclick="selectOption('envio','domicilio',this)">
                <div class="option-radio"><div class="option-radio-dot"></div></div>
                <div class="option-icon">📦</div>
                <div class="option-text">
                  <div class="option-label">Envío a domicilio</div>
                  <div class="option-sub">El vendedor te lo hace llegar a tu dirección</div>
                </div>
              </div>
            </div>
            <div class="address-box" id="address-box">
              <label for="address-input">Dirección de entrega</label>
              <input id="address-input" class="address-input" type="text" placeholder="Calle, número, colonia, ciudad…" value="<?= htmlspecialchars($direccionGuardada) ?>">
            </div>
          </div>
        </div>
      </div>
      <div>
        <div class="section-title">✦ Resumen del pedido</div>
        <div class="summary-panel">
          <div class="summary-title">🧾 Detalle de pago</div>
          <div id="summary-lines"></div>
          <hr class="summary-divider">
          <div class="summary-row highlight"><span>Subtotal</span><span id="summary-subtotal">$0.00</span></div>
          <div class="summary-row"><span>Envío</span><span style="color:var(--neon)">Gratis</span></div>
          <hr class="summary-divider">
          <div class="summary-total"><span>Total</span><span id="summary-total">$0.00</span></div>
          <div class="free-shipping">🚚 Entrega en campus sin costo adicional</div>
          <button class="confirm-btn" id="confirm-btn" onclick="confirmarCompra()" disabled>Confirmar pedido →</button>
          <div class="secure-note">🔒 Pago seguro en campus · CampusGo</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>

const seleccion = { pago: null, envio: null };

function selectOption(group, value, el) {
  seleccion[group] = value;
  const list = document.getElementById(group + '-list');
  list.querySelectorAll('.option-row').forEach(r => r.classList.remove('selected'));
  el.classList.add('selected');

  // Mostrar/ocultar campo de dirección
  if (group === 'envio') {
    document.getElementById('address-box').classList.toggle('show', value === 'domicilio');
  }
  // Mostrar/ocultar datos de tarjeta
  if (group === 'pago') {
    document.getElementById('card-box').classList.toggle('show', value === 'tarjeta');
  }
}

const emojis = { tecnologia:'💻', libros:'📚', comida:'🌮', ropa:'👕', servicios:'🔧', otros:'📦' };

function cartLoad() {
  try { return JSON.parse(sessionStorage.getItem('campusgo_cart') || '[]'); } catch { return []; }
}
function cartSave(c) { sessionStorage.setItem('campusgo_cart', JSON.stringify(c)); }
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2800);
}

let cart = cartLoad();

function renderCheckout() {
  const total      = cart.reduce((a,c) => a + c.precio * c.qty, 0);
  const totalItems = cart.reduce((a,c) => a + c.qty, 0);

  document.getElementById('stat-items').textContent   = totalItems;
  document.getElementById('stat-sellers').textContent = new Set(cart.map(c => c.vendedor)).size;
  document.getElementById('stat-total').textContent   = '$' + total.toLocaleString('es-MX');
  document.getElementById('summary-subtotal').textContent = '$' + total.toLocaleString('es-MX', {minimumFractionDigits:2});
  document.getElementById('summary-total').textContent    = '$' + total.toLocaleString('es-MX', {minimumFractionDigits:2});
  document.getElementById('confirm-btn').disabled = cart.length === 0;

  // Summary lines
  document.getElementById('summary-lines').innerHTML = cart.map(c =>
    `<div class="summary-row"><span>${c.nombre} ×${c.qty}</span><span>$${(c.precio*c.qty).toLocaleString()}</span></div>`
  ).join('');

  // Items panel
  const panel = document.getElementById('items-panel');
  if (!cart.length) {
    panel.innerHTML = `<div class="empty-cart"><div class="big-emoji">🛒</div><p>Tu carrito está vacío</p><a href="catalogo.php" class="go-catalog">🏪 Ir al catálogo</a></div>`;
    return;
  }

  const head = `<div class="items-head"><h3>🛒 Mi Carrito (${totalItems} ${totalItems===1?'artículo':'artículos'})</h3><button class="vaciar-btn" onclick="vaciarCarrito()">🗑️ Vaciar todo</button></div>`;
  const rows = cart.map(c => `
    <div class="checkout-item">
      <div class="item-emoji">${emojis[c.cat]||'📦'}</div>
      <div class="item-info">
        <div class="item-cat">${c.cat}</div>
        <div class="item-name">${c.nombre}</div>
        <div class="item-seller">👤 ${c.vendedor}</div>
      </div>
      <div class="item-controls">
        <div class="qty-wrap">
          <button class="qty-btn" onclick="changeQty(${c.id},-1)">−</button>
          <span class="qty-num">${c.qty}</span>
          <button class="qty-btn" onclick="changeQty(${c.id},1)">+</button>
        </div>
        <div class="item-price">$${(c.precio*c.qty).toLocaleString()}</div>
        <button class="remove-btn" onclick="removeItem(${c.id})">🗑️</button>
      </div>
    </div>`).join('');
  panel.innerHTML = head + rows;
}

function changeQty(id, delta) {
  const item = cart.find(c => c.id === id);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) cart = cart.filter(c => c.id !== id);
  cartSave(cart);
  renderCheckout();
}
function removeItem(id) {
  cart = cart.filter(c => c.id !== id);
  cartSave(cart);
  renderCheckout();
}
function vaciarCarrito() {
  if (!confirm('¿Vaciar todo el carrito?')) return;
  cart = [];
  cartSave(cart);
  renderCheckout();
}

function confirmarCompra() {
  if (!cart.length) return;

  if (!seleccion.pago) {
    showToast('💳 Elige un método de pago');
    return;
  }
  if (!seleccion.envio) {
    showToast('🚚 Elige una opción de envío');
    return;
  }
  if (seleccion.pago === 'tarjeta') {
    const name = document.getElementById('card-name').value.trim();
    const num = document.getElementById('card-number').value.replace(/\s/g,'');
    const exp = document.getElementById('card-expiry').value.trim();
    const cvv = document.getElementById('card-cvv').value.trim();
    if (!name)            { showToast('👤 Ingresa el nombre del titular'); return; }
    if (num.length < 13) { showToast('💳 Ingresa un número de tarjeta válido'); return; }
    if (!/^\d{2}\/\d{2}$/.test(exp)) { showToast('📅 Ingresa la fecha de expiración (MM/AA)'); return; }
    if (cvv.length < 3)  { showToast('🔒 Ingresa el CVV'); return; }
  }
  if (seleccion.envio === 'domicilio') {
    const dir = document.getElementById('address-input').value.trim();
    if (!dir) {
      showToast('📍 Escribe tu dirección de entrega');
      return;
    }
  }

  const btn = document.getElementById('confirm-btn');
  btn.disabled = true;
  btn.textContent = 'Procesando...';

  const direccion = seleccion.envio === 'domicilio'
    ? document.getElementById('address-input').value.trim()
    : null;

  fetch('checkout.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      items:    cart.map(c => ({ producto_id: c.id, cantidad: c.qty })),
      pago:     seleccion.pago,
      envio:    seleccion.envio,
      direccion: direccion
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.ok) {
      // Limpiar sessionStorage solo al confirmar
      sessionStorage.removeItem('campusgo_cart');
      cart = [];
      document.getElementById('success-banner').style.display = 'flex';
      renderCheckout();
      showToast('🎉 ¡Pedido registrado con éxito!');
    } else {
      let msg = '❌ Error al procesar el pedido';
      if (data.error === 'stock_insuficiente') {
        const disp = data.disponibles;
        const sol  = data.solicitados;
        msg = `⚠️ Stock insuficiente: pediste ${sol} pero solo hay ${disp} disponible${disp !== 1 ? 's' : ''}`;
      } else if (data.error === 'producto_no_disponible') {
        msg = '⚠️ Uno de los productos ya no está disponible';
      } else if (data.error === 'carrito_vacio') {
        msg = '🛒 El carrito está vacío';
      }
      showToast(msg);
      btn.disabled = false;
      btn.textContent = 'Confirmar pedido →';
    }
  })
  .catch(() => {
    showToast('❌ Error de conexión');
    btn.disabled = false;
    btn.textContent = 'Confirmar pedido →';
  });
}

renderCheckout();
</script>
</body>
</html>
