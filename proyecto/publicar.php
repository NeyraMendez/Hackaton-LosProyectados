<?php
session_start();
require_once 'php/config.php'; // Asegúrate de que este archivo tenga tu función conectar()

// 1. Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$pdo = conectar();
$success = false;

// 2. Lógica de Procesamiento al hacer POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_publicar'])) {
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = floatval($_POST['precio'] ?? 0);
    $categoria   = trim($_POST['categoria'] ?? 'otros');
    $inventario  = intval($_POST['inventario'] ?? 1);
    $estado      = trim($_POST['estado'] ?? 'disponible');

    if (!empty($nombre) && $precio > 0) {
        $imagen_url = null;
        
        // Manejo de la primera imagen cargada
        if (!empty($_FILES['imagenes']['tmp_name'][0])) {
            $upload_dir = 'uploads/productos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $ext = strtolower(pathinfo($_FILES['imagenes']['name'][0], PATHINFO_EXTENSION));
            $nuevo_nombre = uniqid('prod_') . '.' . $ext;
            $destino = $upload_dir . $nuevo_nombre;

            if (move_uploaded_file($_FILES['imagenes']['tmp_name'][0], $destino)) {
                $imagen_url = $destino;
            }
        }

        try {
            $sql = "INSERT INTO productos (nombre, descripcion, precio, categoria, vendedor_id, imagen, inventario, activo, estado) 
                    VALUES (:nom, :desc, :pre, :cat, :vid, :img, :inv, 1, :est)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom'  => $nombre,
                ':desc' => $descripcion,
                ':pre'  => $precio,
                ':cat'  => $categoria,
                ':vid'  => $usuario_id,
                ':img'  => $imagen_url,
                ':inv'  => $inventario,
                ':est'  => $estado
            ]);
            $success = true;
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CampusGo – Publicar Producto</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    /* AQUÍ VA TODO TU CSS LITERALMENTE IGUAL */
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
    .bg-grid { position: fixed; inset: 0; z-index: 0; pointer-events: none; background-image: linear-gradient(rgba(0,245,196,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0,245,196,0.03) 1px, transparent 1px); background-size: 36px 36px; animation: gridMove 20s linear infinite; }
    @keyframes gridMove { to { background-position: 36px 36px; } }
    .orb { position: fixed; border-radius: 50%; pointer-events: none; z-index: 0; animation: float 8s ease-in-out infinite alternate; }
    .orb-1 { width:400px; height:400px; background:rgba(0,245,196,0.08); top:-80px; left:-100px; filter:blur(80px); }
    .orb-2 { width:300px; height:300px; background:rgba(255,107,107,0.07); bottom:-60px; right:-80px; filter:blur(70px); animation-delay:-3s; }
    @keyframes float { from{transform:translate(0,0)} to{transform:translate(20px,30px)} }
    .app { position: relative; z-index: 1; display: flex; height: 100vh; overflow: hidden; }
    .sidebar { width: 220px; flex-shrink: 0; background: rgba(14,14,24,0.9); border-right: 1px solid var(--border); backdrop-filter: blur(20px); display: flex; flex-direction: column; padding: 20px 14px; gap: 4px; }
    .brand { display: flex; align-items: center; gap: 10px; font-family: 'Clash Display', sans-serif; font-size: 1.3rem; font-weight: 700; color: var(--text); text-decoration: none; margin-bottom: 20px; padding: 0 4px; }
    .brand-icon { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, var(--neon), var(--blue)); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 0 18px rgba(0,245,196,0.35); }
    .brand span { color: var(--neon); }
    .nav-section { font-size: 10px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); padding: 10px 8px 6px; }
    .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 10px; border-radius: 10px; font-size: 13.5px; color: var(--muted); cursor: pointer; border: none; background: transparent; width: 100%; text-align: left; text-decoration: none; transition: all 0.15s; font-family: 'Outfit', sans-serif; }
    .nav-item:hover { background: rgba(255,255,255,0.04); color: var(--text); }
    .nav-item.active { background: rgba(0,245,196,0.1); color: var(--neon); border: 1px solid rgba(0,245,196,0.2); }
    .nav-item .icon { font-size: 1rem; width: 20px; text-align: center; }
    .spacer { flex: 1; }
    .divider { border: none; border-top: 1px solid var(--border); margin: 8px 0; }
    .user-card { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); }
    .avatar { width: 36px; height: 36px; border-radius: 50%; background: rgba(0,245,196,0.12); border: 1px solid rgba(0,245,196,0.25); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: var(--neon); flex-shrink: 0; }
    .user-name { font-size: 13px; font-weight: 600; }
    .user-role { font-size: 11px; color: var(--muted); }
    .main { flex: 1; overflow-y: auto; padding: 24px 28px; }
    .topbar { display: flex; align-items: center; gap: 14px; margin-bottom: 26px; }
    .page-title { font-family: 'Clash Display', sans-serif; font-size: 1.6rem; font-weight: 700; flex: 1; }
    .back-btn { display: flex; align-items: center; gap: 8px; padding: 9px 16px; border-radius: 10px; background: rgba(255,255,255,0.04); border: 1px solid var(--border); color: var(--muted); font-size: 13px; font-family: 'Outfit', sans-serif; cursor: pointer; text-decoration: none; transition: all 0.15s; }
    .pub-grid { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
    .card { background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius); padding: 22px 24px; margin-bottom: 16px; }
    .card-title { font-family: 'Clash Display', sans-serif; font-size: 0.95rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
    .field { margin-bottom: 18px; }
    .field label { display: block; font-size: 12.5px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
    .req { color: var(--neon); margin-left: 2px; }
    .input, .textarea { width: 100%; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 10px; padding: 11px 14px; color: var(--text); font-family: 'Outfit', sans-serif; font-size: 14px; outline: none; transition: 0.2s; }
    .input:focus, .textarea:focus { border-color: rgba(0,245,196,0.4); box-shadow: 0 0 0 3px rgba(0,245,196,0.07); }
    .input-group { display: flex; align-items: center; }
    .input-prefix { background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-right: none; border-radius: 10px 0 0 10px; padding: 11px 14px; font-family: 'Clash Display', sans-serif; font-size: 15px; font-weight: 700; color: var(--neon); }
    .input-group .input { border-radius: 0 10px 10px 0; }
    .cat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .cat-option { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 14px 8px; border-radius: 10px; border: 1px solid var(--border); background: rgba(255,255,255,0.02); cursor: pointer; transition: all 0.15s; font-size: 12px; color: var(--muted); text-align: center; }
    .cat-option.selected { border-color: rgba(0,245,196,0.5); color: var(--neon); background: rgba(0,245,196,0.08); }
    .cat-emoji { font-size: 1.6rem; }
    .estado-options { display: flex; gap: 8px; }
    .estado-opt { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; border-radius: 10px; border: 1px solid var(--border); cursor: pointer; font-size: 13px; color: var(--muted); transition: 0.15s; }
    .estado-opt.selected { border-color: rgba(0,245,196,0.4); color: var(--neon); background: rgba(0,245,196,0.07); }
    .img-drop { border: 2px dashed rgba(255,255,255,0.1); border-radius: 12px; padding: 32px 20px; text-align: center; cursor: pointer; transition: 0.2s; position: relative; }
    .img-drop.drag { border-color: rgba(0,245,196,0.4); background: rgba(0,245,196,0.04); }
    .img-thumb { aspect-ratio: 1/1; border-radius: 10px; overflow: hidden; background: rgba(255,255,255,0.05); border: 1px solid var(--border); position: relative; }
    .img-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .inv-control { display: flex; align-items: center; gap: 12px; }
    .inv-btn { width: 38px; height: 38px; border-radius: 9px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--text); font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .inv-num { font-family: 'Clash Display', sans-serif; font-size: 1.8rem; font-weight: 700; color: var(--neon); min-width: 40px; text-align: center; }
    .preview-card { background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; transition: 0.2s; }
    .preview-img { width: 100%; aspect-ratio: 1/1; display: flex; align-items: center; justify-content: center; font-size: 72px; position: relative; background-size: cover; background-position: center; }
    .preview-body { padding: 16px; }
    .preview-price { font-family: 'Clash Display', sans-serif; font-size: 1.2rem; font-weight: 700; color: var(--neon); }
    .btn-pub { flex: 1; padding: 15px; border: none; border-radius: 12px; background: var(--neon); color: #05050A; font-family: 'Clash Display', sans-serif; font-size: 1.05rem; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .success-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 200; display: none; align-items: center; justify-content: center; backdrop-filter: blur(8px); }
    .success-overlay.show { display: flex; }
    .success-modal { background: var(--panel2); border: 1px solid rgba(0,245,196,0.2); border-radius: 20px; padding: 40px; text-align: center; max-width: 380px; width: 90%; }
    .field-error { font-size: 11px; color: var(--neon2); margin-top: 5px; display: none; }
    .field-error.show { display: block; }
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
    <a href="publicar.php" class="nav-item active"><span class="icon">➕</span> Publicar producto</a>
    <div class="spacer"></div>
    <div class="user-card">
      <div class="avatar">U</div>
      <div><div class="user-name">Usuario</div><div class="user-role">Vendedor</div></div>
    </div>
  </div>

  <div class="main">
    <div class="topbar">
      <div class="page-title">Publicar Producto ➕</div>
      <a href="catalogo.php" class="back-btn">← Volver al catálogo</a>
    </div>

    <form action="publicar.php" method="POST" enctype="multipart/form-data" id="mainForm">
        <input type="hidden" name="action_publicar" value="1">
        
        <div class="pub-grid">
          <div>
            <div class="card">
              <div class="card-title">📝 Información básica</div>
              <div class="field">
                <label>Nombre del producto <span class="req">*</span></label>
                <input type="text" name="nombre" class="input" id="nombre" required oninput="updatePreview()">
                <div class="field-error" id="err-nombre">El nombre es obligatorio</div>
              </div>
              <div class="field">
                <label>Descripción <span class="req">*</span></label>
                <textarea name="descripcion" class="textarea" id="descripcion" required oninput="updatePreview()"></textarea>
              </div>
            </div>

            <div class="card">
              <div class="card-title">🏷️ Categoría *</div>
              <input type="hidden" name="categoria" id="cat-value" required>
              <div class="cat-grid">
                <div class="cat-option" onclick="selectCat('tecnologia', this)"><span class="cat-emoji">💻</span>Tecnología</div>
                <div class="cat-option" onclick="selectCat('libros', this)"><span class="cat-emoji">📚</span>Libros</div>
                <div class="cat-option" onclick="selectCat('comida', this)"><span class="cat-emoji">🍕</span>Comida</div>
                <div class="cat-option" onclick="selectCat('ropa', this)"><span class="cat-emoji">👕</span>Ropa</div>
                <div class="cat-option" onclick="selectCat('servicios', this)"><span class="cat-emoji">🔧</span>Servicios</div>
                <div class="cat-option" onclick="selectCat('otros', this)"><span class="cat-emoji">📦</span>Otros</div>
              </div>
              <div class="field-error" id="err-cat">Selecciona una categoría</div>
            </div>

            <div class="card">
              <div class="card-title">💰 Precio e inventario</div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="field">
                  <label>Precio <span class="req">*</span></label>
                  <div class="input-group">
                    <span class="input-prefix">$</span>
                    <input type="number" name="precio" class="input" id="precio" step="0.01" required oninput="updatePreview()">
                  </div>
                </div>
                <div class="field">
                  <label>Inventario</label>
                  <div class="inv-control">
                    <button type="button" class="inv-btn" onclick="changeInv(-1)">−</button>
                    <span class="inv-num" id="inv-num-display">1</span>
                    <button type="button" class="inv-btn" onclick="changeInv(1)">+</button>
                    <input type="hidden" name="inventario" id="inventario-hidden" value="1">
                  </div>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-title">📋 Estado del producto</div>
              <div class="estado-options">
                <label class="estado-opt selected" id="est-disponible">
                  <input type="radio" name="estado" value="disponible" checked onchange="selectEstado('disponible')"> ✅ Disponible
                </label>
                <label class="estado-opt" id="est-pausado">
                  <input type="radio" name="estado" value="pausado" onchange="selectEstado('pausado')"> ⏸️ Pausado
                </label>
              </div>
            </div>

            <div class="card">
              <div class="card-title">🖼️ Imagen</div>
              <div class="img-drop" onclick="document.getElementById('img-input').click()">
                <input type="file" name="imagenes[]" id="img-input" accept="image/*" onchange="handleFiles(this.files)">
                <div class="drop-icon">📸</div>
                <div class="drop-text">Haz clic para subir foto principal</div>
              </div>
              <div id="img-preview" class="img-preview-grid" style="margin-top:10px"></div>
            </div>

            <button type="submit" class="btn-pub">🚀 Publicar producto</button>
          </div>

          <div class="preview-sticky">
            <div class="preview-label">✦ Vista previa</div>
            <div class="preview-card">
              <div class="preview-img" id="prev-img" style="background:rgba(0,196,255,0.07)">
                <span id="prev-emoji">📦</span>
              </div>
              <div class="preview-body">
                <div class="preview-cat" id="prev-cat">categoría</div>
                <div class="preview-name" id="prev-name">Nombre del producto</div>
                <div class="preview-price" id="prev-price">$0</div>
              </div>
            </div>
          </div>
        </div>
    </form>
  </div>
</div>

<div class="success-overlay <?php if($success) echo 'show'; ?>" id="success-overlay">
  <div class="success-modal">
    <div class="success-icon">🎉</div>
    <div class="success-title">¡Publicado!</div>
    <div class="success-sub">Tu producto ya está en el catálogo.</div>
    <button class="btn-pub" onclick="window.location.href='catalogo.php'">Ir al catálogo</button>
  </div>
</div>

<script>
// TODO TU JS ORIGINAL INTEGRADO
let inv = 1;
const catEmojis = { tecnologia:'💻', libros:'📚', comida:'🍕', ropa:'👕', servicios:'🔧', otros:'📦' };

function selectCat(cat, el) {
  document.getElementById('cat-value').value = cat;
  document.querySelectorAll('.cat-option').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  updatePreview();
}

function changeInv(n) {
  inv = Math.max(1, inv + n);
  document.getElementById('inv-num-display').innerText = inv;
  document.getElementById('inventario-hidden').value = inv;
}

function selectEstado(val) {
  document.querySelectorAll('.estado-opt').forEach(o => o.classList.remove('selected'));
  document.getElementById('est-'+val).classList.add('selected');
}

function updatePreview() {
  const nom = document.getElementById('nombre').value || 'Nombre del producto';
  const pre = document.getElementById('precio').value || '0';
  const cat = document.getElementById('cat-value').value;
  
  document.getElementById('prev-name').innerText = nom;
  document.getElementById('prev-price').innerText = '$' + pre;
  document.getElementById('prev-cat').innerText = cat.toUpperCase() || 'categoría';
  if(cat) document.getElementById('prev-emoji').innerText = catEmojis[cat];
}

function handleFiles(files) {
  if (files[0]) {
    const reader = new FileReader();
    reader.onload = (e) => {
      const p = document.getElementById('prev-img');
      p.style.backgroundImage = `url(${e.target.result})`;
      document.getElementById('prev-emoji').style.display = 'none';
      
      document.getElementById('img-preview').innerHTML = `<div class="img-thumb"><img src="${e.target.result}"></div>`;
    };
    reader.readAsDataURL(files[0]);
  }
}
</script>
</body>
</html>