<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CampusGo – Marketplace Universitario</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="nav-brand">
    <span class="logo-icon">🎓</span>
    <span class="logo-text">Campus<strong>Go</strong></span>
  </div>
  <div class="nav-links">
    <a href="index.php" class="active">Inicio</a>
    <a href="productos.php">Productos</a>
    <a href="mapa.php">Mapa</a>
    <?php if(isset($_SESSION['usuario_id'])): ?>
      <a href="dashboard.php">Mi Perfil</a>
      <a href="php/logout.php" class="btn-nav">Salir</a>
    <?php else: ?>
      <a href="login.php" class="btn-nav">Entrar</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-content">
    <div class="badge">🔥 Marketplace del Campus</div>
    <h1>Compra y vende<br><span class="highlight">dentro de tu uni</span></h1>
    <p>Encuentra productos de tus compañeros, ve a los vendedores en el mapa y recibe recomendaciones inteligentes.</p>
    <div class="hero-btns">
      <a href="productos.php" class="btn-primary">Ver Productos</a>
      <a href="mapa.php" class="btn-secondary">Ver Mapa 🗺️</a>
    </div>
    <div class="hero-stats">
      <div class="stat"><strong id="total-productos">--</strong><span>Productos</span></div>
      <div class="stat"><strong id="total-vendedores">--</strong><span>Vendedores</span></div>
      <div class="stat"><strong id="total-ventas">--</strong><span>Ventas hoy</span></div>
    </div>
  </div>
  <div class="hero-visual">
    <div id="mini-map"></div>
  </div>
</section>

<!-- CATEGORÍAS -->
<section class="categorias">
  <h2>Categorías</h2>
  <div class="cat-grid">
    <a href="productos.php?cat=tecnologia" class="cat-card">💻<span>Tecnología</span></a>
    <a href="productos.php?cat=libros" class="cat-card">📚<span>Libros</span></a>
    <a href="productos.php?cat=comida" class="cat-card">🍕<span>Comida</span></a>
    <a href="productos.php?cat=ropa" class="cat-card">👕<span>Ropa</span></a>
    <a href="productos.php?cat=servicios" class="cat-card">🔧<span>Servicios</span></a>
    <a href="productos.php?cat=otros" class="cat-card">📦<span>Otros</span></a>
  </div>
</section>

<!-- PRODUCTOS RECIENTES -->
<section class="productos-home">
  <div class="section-header">
    <h2>Productos recientes</h2>
    <a href="productos.php">Ver todos →</a>
  </div>
  <div class="productos-grid" id="productos-recientes">
    <div class="loading">Cargando productos...</div>
  </div>
</section>

<!-- IA CHATBOT -->
<div class="chatbot-btn" id="chatbot-toggle">🤖 IA</div>
<div class="chatbot-box" id="chatbot-box">
  <div class="chatbot-header">
    <span>🤖 Asistente CampusGo</span>
    <button onclick="document.getElementById('chatbot-box').classList.remove('open')">✕</button>
  </div>
  <div class="chatbot-messages" id="chatbot-messages">
    <div class="msg-bot">¡Hola! ¿Qué estás buscando hoy? 😊</div>
  </div>
  <div class="chatbot-input">
    <input type="text" id="chat-input" placeholder="Buscar producto..." onkeydown="if(event.key==='Enter') sendChat()">
    <button onclick="sendChat()">➤</button>
  </div>
</div>

<footer class="footer">
  <p>CampusGo © 2025 – Hecho con ❤️ para estudiantes</p>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="js/main.js"></script>
<script src="js/chatbot.js"></script>
<script>
  // Mini mapa en hero
  const miniMap = L.map('mini-map', { zoomControl: false, dragging: false }).setView([19.4326, -99.1332], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);
  // Vendedores de ejemplo
  const vendedores = [
    [19.434, -99.134, "Juan - Libros"],
    [19.431, -99.131, "María - Comida"],
    [19.436, -99.132, "Carlos - Tech"],
    [19.433, -99.136, "Ana - Ropa"],
  ];
  vendedores.forEach(v => {
    L.marker([v[0], v[1]]).addTo(miniMap).bindPopup(v[2]);
  });

  // Stats dinámicas
  fetch('api/stats.php').then(r=>r.json()).then(d=>{
    document.getElementById('total-productos').textContent = d.productos || '0';
    document.getElementById('total-vendedores').textContent = d.vendedores || '0';
    document.getElementById('total-ventas').textContent = d.ventas || '0';
  }).catch(()=>{
    document.getElementById('total-productos').textContent = '12';
    document.getElementById('total-vendedores').textContent = '5';
    document.getElementById('total-ventas').textContent = '3';
  });

  // Productos recientes
  fetch('api/productos.php?limit=6').then(r=>r.json()).then(data=>{
    const grid = document.getElementById('productos-recientes');
    if(!data.length){ grid.innerHTML='<p class="empty">Sin productos aún. ¡Sé el primero!</p>'; return; }
    grid.innerHTML = data.map(p=>`
      <div class="producto-card">
        <div class="prod-img">${p.imagen ? `<img src="uploads/${p.imagen}" alt="${p.nombre}">` : '<div class="no-img">📦</div>'}</div>
        <div class="prod-info">
          <h3>${p.nombre}</h3>
          <p class="prod-precio">$${p.precio}</p>
          <p class="prod-vendedor">👤 ${p.vendedor}</p>
          <a href="producto.php?id=${p.id}" class="btn-ver">Ver</a>
        </div>
      </div>
    `).join('');
  }).catch(()=>{
    document.getElementById('productos-recientes').innerHTML='<p class="empty">Conecta la base de datos para ver productos.</p>';
  });
</script>
</body>
</html>
