<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CampusGo – Iniciar Sesión</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    /* ── RESET ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:        #05050A;
      --panel:     #0E0E18;
      --border:    rgba(255,255,255,0.07);
      --neon:      #00F5C4;
      --neon2:     #FF6B6B;
      --gold:      #FFD166;
      --text:      #EEEEF5;
      --muted:     #6B6B8A;
      --radius:    18px;
    }

    html, body {
      height: 100%;
      font-family: 'Outfit', sans-serif;
      background: var(--bg);
      color: var(--text);
      overflow: hidden;
    }

    /* ── FONDO ANIMADO ── */
    .bg-grid {
      position: fixed; inset: 0; z-index: 0;
      background-image:
        linear-gradient(rgba(0,245,196,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,245,196,0.04) 1px, transparent 1px);
      background-size: 40px 40px;
      animation: gridMove 20s linear infinite;
    }
    @keyframes gridMove { to { background-position: 40px 40px; } }

    .orb {
      position: fixed; border-radius: 50%; filter: blur(80px); z-index: 0;
      animation: float 8s ease-in-out infinite alternate;
    }
    .orb-1 { width:420px; height:420px; background:rgba(0,245,196,0.12); top:-80px; left:-100px; }
    .orb-2 { width:320px; height:320px; background:rgba(255,107,107,0.10); bottom:-60px; right:-80px; animation-delay:-3s; }
    .orb-3 { width:200px; height:200px; background:rgba(255,209,102,0.08); top:40%; left:60%; animation-delay:-5s; }
    @keyframes float { from { transform: translate(0,0) scale(1); } to { transform: translate(20px,30px) scale(1.05); } }

    /* ── LAYOUT ── */
    .page {
      position: relative; z-index: 1;
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 480px;
    }

    /* ── LADO IZQUIERDO ── */
    .left {
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 4rem 5rem;
    }

    .brand {
      display: flex; align-items: center; gap: 0.8rem;
      font-family: 'Clash Display', sans-serif;
      font-size: 2rem; font-weight: 700;
      margin-bottom: 3rem;
    }
    .brand-icon {
      width: 48px; height: 48px;
      background: linear-gradient(135deg, var(--neon), #00C4FF);
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem;
      box-shadow: 0 0 24px rgba(0,245,196,0.4);
    }
    .brand span { color: var(--neon); }

    .left h1 {
      font-family: 'Clash Display', sans-serif;
      font-size: 3.8rem; font-weight: 700;
      line-height: 1.05;
      margin-bottom: 1.2rem;
    }
    .left h1 em { font-style: normal; color: var(--neon); }

    .left p { color: var(--muted); font-size: 1.1rem; line-height: 1.7; max-width: 440px; margin-bottom: 3rem; }

    /* ROL CARDS */
    .roles { display: flex; flex-direction: column; gap: 0.9rem; }
    .role-card {
      display: flex; align-items: center; gap: 1rem;
      padding: 1rem 1.3rem;
      background: rgba(255,255,255,0.03);
      border: 1px solid var(--border);
      border-radius: 12px;
      transition: all 0.25s;
    }
    .role-card:hover { border-color: rgba(0,245,196,0.3); background: rgba(0,245,196,0.05); }
    .role-icon {
      width: 40px; height: 40px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
      flex-shrink: 0;
    }
    .ri-comprador { background: rgba(0,245,196,0.15); }
    .ri-vendedor  { background: rgba(255,209,102,0.15); }
    .ri-admin     { background: rgba(255,107,107,0.15); }
    .role-info h4 { font-size: 0.95rem; font-weight: 600; margin-bottom: 2px; }
    .role-info p  { font-size: 0.8rem; color: var(--muted); }

    /* ── PANEL DERECHO ── */
    .right {
      display: flex; align-items: center; justify-content: center;
      padding: 2rem;
      border-left: 1px solid var(--border);
      background: rgba(14,14,24,0.7);
      backdrop-filter: blur(20px);
    }

    .card {
      width: 100%;
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 2.5rem;
      box-shadow: 0 32px 80px rgba(0,0,0,0.5);
    }

    .card-title {
      font-family: 'Clash Display', sans-serif;
      font-size: 1.8rem; font-weight: 600;
      margin-bottom: 0.4rem;
    }
    .card-sub { color: var(--muted); font-size: 0.9rem; margin-bottom: 2rem; }

    /* TABS ROLES */
    .role-tabs {
      display: grid; grid-template-columns: 1fr 1fr 1fr;
      gap: 0.5rem; margin-bottom: 2rem;
      background: rgba(255,255,255,0.03);
      padding: 5px; border-radius: 12px;
      border: 1px solid var(--border);
    }
    .rtab {
      padding: 0.6rem 0.3rem;
      border: none; background: transparent;
      color: var(--muted); font-family: 'Outfit', sans-serif;
      font-size: 0.82rem; font-weight: 500;
      border-radius: 8px; cursor: pointer;
      display: flex; flex-direction: column; align-items: center; gap: 3px;
      transition: all 0.2s;
    }
    .rtab span { font-size: 1.1rem; }
    .rtab.active { background: var(--neon); color: #050510; font-weight: 600; }
    .rtab.active.vendedor { background: var(--gold); }
    .rtab.active.admin { background: var(--neon2); color: white; }

    /* ALERTA */
    .alert {
      padding: 0.8rem 1rem;
      border-radius: 10px;
      font-size: 0.88rem;
      margin-bottom: 1.5rem;
      display: none;
    }
    .alert.show { display: block; }
    .alert.error { background: rgba(255,107,107,0.15); border: 1px solid rgba(255,107,107,0.3); color: #ff8e8e; }
    .alert.success { background: rgba(0,245,196,0.12); border: 1px solid rgba(0,245,196,0.3); color: var(--neon); }

    /* FORM */
    .field { margin-bottom: 1.2rem; }
    .field label {
      display: block; font-size: 0.82rem; font-weight: 500;
      color: var(--muted); margin-bottom: 0.5rem; letter-spacing: 0.5px;
      text-transform: uppercase;
    }
    .field-wrap { position: relative; }
    .field-icon {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      font-size: 1rem; pointer-events: none;
    }
    .field input {
      width: 100%;
      padding: 0.85rem 1rem 0.85rem 2.8rem;
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--border);
      border-radius: 10px;
      color: var(--text);
      font-family: 'Outfit', sans-serif;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .field input::placeholder { color: var(--muted); }
    .field input:focus {
      border-color: var(--neon);
      box-shadow: 0 0 0 3px rgba(0,245,196,0.12);
    }

    /* DEMO HINT */
    .demo-hint {
      background: rgba(0,245,196,0.06);
      border: 1px solid rgba(0,245,196,0.15);
      border-radius: 10px;
      padding: 0.8rem 1rem;
      margin-bottom: 1.4rem;
      font-size: 0.8rem;
      color: rgba(0,245,196,0.8);
    }
    .demo-hint strong { color: var(--neon); }
    .demo-hint .hint-row { margin-top: 4px; }
    .demo-fill {
      background: none; border: none; color: var(--neon);
      font-size: 0.78rem; cursor: pointer; text-decoration: underline;
      padding: 0; font-family: 'Outfit', sans-serif; margin-top: 0.4rem;
    }

    /* SUBMIT */
    .btn-submit {
      width: 100%;
      padding: 0.95rem;
      background: var(--neon);
      color: #05050A;
      font-family: 'Clash Display', sans-serif;
      font-size: 1rem; font-weight: 600;
      border: none; border-radius: 10px;
      cursor: pointer;
      letter-spacing: 0.5px;
      transition: all 0.2s;
      position: relative; overflow: hidden;
      margin-top: 0.5rem;
    }
    .btn-submit::after {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
      transform: translateX(-100%);
      transition: transform 0.4s;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,245,196,0.35); }
    .btn-submit:hover::after { transform: translateX(100%); }
    .btn-submit.vendedor { background: var(--gold); }
    .btn-submit.admin    { background: var(--neon2); color: white; }

    .register-link {
      text-align: center; margin-top: 1.4rem;
      font-size: 0.85rem; color: var(--muted);
    }
    .register-link a { color: var(--neon); text-decoration: none; font-weight: 500; }
    .register-link a:hover { text-decoration: underline; }

    /* RESPONSIVE */
    @media (max-width: 900px) {
      .page { grid-template-columns: 1fr; overflow: auto; }
      .left { display: none; }
      html, body { overflow: auto; }
      .right { min-height: 100vh; border-left: none; }
    }
  </style>
</head>
<body>

<div class="bg-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="page">

  <!-- IZQUIERDA -->
  <div class="left">
    <div class="brand">
      <div class="brand-icon">🎓</div>
      Campus<span>Go</span>
    </div>

    <h1>El mercado<br>de tu <em>campus</em></h1>
    <p>Compra, vende y descubre productos de tus compañeros universitarios en un solo lugar, con mapa en tiempo real e IA.</p>

    <div class="roles">
      <div class="role-card">
        <div class="role-icon ri-comprador">🛒</div>
        <div class="role-info">
          <h4>Comprador</h4>
          <p>Explora productos y añádelos al carrito</p>
        </div>
      </div>
      <div class="role-card">
        <div class="role-icon ri-vendedor">🏪</div>
        <div class="role-info">
          <h4>Vendedor</h4>
          <p>Publica productos y gestiona tus ventas</p>
        </div>
      </div>
      <div class="role-card">
        <div class="role-icon ri-admin">⚡</div>
        <div class="role-info">
          <h4>Admin</h4>
          <p>Panel completo, usuarios, métricas y control total</p>
        </div>
      </div>
    </div>
  </div>

  <!-- DERECHA -->
  <div class="right">
    <div class="card">
      <div class="card-title">Bienvenido 👋</div>
      <div class="card-sub">Inicia sesión en tu cuenta</div>

      <!-- TABS -->
      <div class="role-tabs">
        <button class="rtab active" data-rol="comprador" onclick="setRol('comprador', this)">
          <span>🛒</span> Comprador
        </button>
        <button class="rtab vendedor" data-rol="vendedor" onclick="setRol('vendedor', this)">
          <span>🏪</span> Vendedor
        </button>
        <button class="rtab admin" data-rol="admin" onclick="setRol('admin', this)">
          <span>⚡</span> Admin
        </button>
      </div>

      <!-- ALERTA -->
      <?php
        $error = $_GET['error'] ?? '';
        $msg   = $_GET['msg'] ?? '';
        if ($error === 'credenciales') echo '<div class="alert error show">❌ Correo o contraseña incorrectos.</div>';
        elseif ($error === 'campos')   echo '<div class="alert error show">⚠️ Completa todos los campos.</div>';
        elseif ($msg === 'logout')     echo '<div class="alert success show">✅ Sesión cerrada correctamente.</div>';
      ?>
      <div class="alert" id="js-alert"></div>

      <!-- HINT CREDENCIALES -->
      <div class="demo-hint" id="demo-hint">
        <strong>🔑 Credenciales de prueba:</strong>
        <div class="hint-row" id="hint-text">comprador@campusgo.com · campusgo123</div>
        <button class="demo-fill" onclick="fillDemo()">→ Autocompletar</button>
      </div>

      <!-- FORM -->
      <form method="POST" action="php/login_handler.php" id="loginForm" onsubmit="return validateForm()">
        <input type="hidden" name="rol" id="rol-hidden" value="comprador">

        <div class="field">
          <label>Correo electrónico</label>
          <div class="field-wrap">
            <span class="field-icon">✉️</span>
            <input type="email" name="email" id="email" placeholder="tucorreo@campus.edu" required autocomplete="email">
          </div>
        </div>

        <div class="field">
          <label>Contraseña</label>
          <div class="field-wrap">
            <span class="field-icon">🔒</span>
            <input type="password" name="password" id="password" placeholder="••••••••" required autocomplete="current-password">
          </div>
        </div>

        <button type="submit" class="btn-submit" id="btn-submit">
          Iniciar Sesión →
        </button>
      </form>

      <div class="register-link">
        ¿Sin cuenta? <a href="registro.php">Regístrate gratis</a>
      </div>
    </div>
  </div>

</div>

<script>
  // Datos por rol
  const rolData = {
    comprador: { email: 'comprador@campusgo.com', hint: 'comprador@campusgo.com · campusgo123', btn: '', tab: '' },
    vendedor:  { email: 'vendedor@campusgo.com',  hint: 'vendedor@campusgo.com · campusgo123',  btn: 'vendedor', tab: 'vendedor' },
    admin:     { email: 'admin@campusgo.com',      hint: 'admin@campusgo.com · campusgo123',     btn: 'admin',    tab: 'admin' },
  };

  let rolActual = 'comprador';

  function setRol(rol, btn) {
    rolActual = rol;
    document.getElementById('rol-hidden').value = rol;

    // tabs
    document.querySelectorAll('.rtab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');

    // botón color
    const submitBtn = document.getElementById('btn-submit');
    submitBtn.className = 'btn-submit ' + rolData[rol].btn;

    // hint
    document.getElementById('hint-text').textContent = rolData[rol].hint;

    // limpiar campos
    document.getElementById('email').value    = '';
    document.getElementById('password').value = '';
  }

  function fillDemo() {
    document.getElementById('email').value    = rolData[rolActual].email;
    document.getElementById('password').value = 'campusgo123';
  }

  function validateForm() {
    const e = document.getElementById('email').value;
    const p = document.getElementById('password').value;
    if (!e || !p) {
      const alert = document.getElementById('js-alert');
      alert.textContent = '⚠️ Por favor completa todos los campos.';
      alert.className = 'alert error show';
      return false;
    }
    return true;
  }
</script>
</body>
</html>
