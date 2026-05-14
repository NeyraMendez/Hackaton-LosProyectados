<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CampusGo – Crear Cuenta</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:      #05050A;
      --panel:   #0E0E18;
      --border:  rgba(255,255,255,0.07);
      --neon:    #00F5C4;
      --neon2:   #FF6B6B;
      --gold:    #FFD166;
      --text:    #EEEEF5;
      --muted:   #6B6B8A;
      --radius:  18px;
    }

    html, body {
      height: 100%;
      font-family: 'Outfit', sans-serif;
      background: var(--bg);
      color: var(--text);
      overflow: hidden;
    }

    .bg-grid {
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background-image:
        linear-gradient(rgba(0,245,196,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,245,196,0.04) 1px, transparent 1px);
      background-size: 40px 40px;
      animation: gridMove 20s linear infinite;
    }
    @keyframes gridMove { to { background-position: 40px 40px; } }

    .orb { position: fixed; border-radius: 50%; filter: blur(80px); z-index: 0; animation: float 8s ease-in-out infinite alternate; pointer-events: none; }
    .orb-1 { width:420px; height:420px; background:rgba(0,245,196,0.10); top:-80px; left:-100px; }
    .orb-2 { width:320px; height:320px; background:rgba(255,107,107,0.08); bottom:-60px; right:-80px; animation-delay:-3s; }
    .orb-3 { width:200px; height:200px; background:rgba(255,209,102,0.07); top:40%; left:55%; animation-delay:-5s; }
    @keyframes float { from{transform:translate(0,0) scale(1)} to{transform:translate(20px,30px) scale(1.05)} }

    .page {
      position: relative; z-index: 1;
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 480px;
    }

    /* IZQUIERDA */
    .left {
      display: flex; flex-direction: column; justify-content: center;
      padding: 4rem 5rem;
    }

    .brand {
      display: flex; align-items: center; gap: 0.8rem;
      font-family: 'Clash Display', sans-serif;
      font-size: 2rem; font-weight: 700;
      margin-bottom: 3rem; text-decoration: none; color: var(--text);
    }
    .brand-icon {
      width: 48px; height: 48px;
      background: linear-gradient(135deg, var(--neon), #00C4FF);
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
      box-shadow: 0 0 24px rgba(0,245,196,0.4);
    }
    .brand span { color: var(--neon); }

    .left h1 {
      font-family: 'Clash Display', sans-serif;
      font-size: 3.6rem; font-weight: 700; line-height: 1.05; margin-bottom: 1.2rem;
    }
    .left h1 em { font-style: normal; color: var(--neon); }
    .left p { color: var(--muted); font-size: 1.05rem; line-height: 1.7; max-width: 420px; margin-bottom: 3rem; }

    .perks { display: flex; flex-direction: column; gap: 1rem; }
    .perk {
      display: flex; align-items: center; gap: 1rem;
      padding: 1rem 1.2rem;
      background: rgba(255,255,255,0.03);
      border: 1px solid var(--border); border-radius: 12px;
      font-size: 0.9rem; color: var(--text);
      transition: border-color 0.2s;
    }
    .perk:hover { border-color: rgba(0,245,196,0.25); }
    .perk-icon { font-size: 1.4rem; flex-shrink: 0; }
    .perk p { color: var(--muted); font-size: 0.8rem; margin-top: 2px; }

    /* DERECHA */
    .right {
      display: flex; align-items: center; justify-content: center;
      padding: 2rem;
      border-left: 1px solid var(--border);
      background: rgba(14,14,24,0.7);
      backdrop-filter: blur(20px);
      overflow-y: auto;
    }

    .card {
      width: 100%;
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 2.2rem;
      box-shadow: 0 32px 80px rgba(0,0,0,0.5);
    }

    .card-title { font-family: 'Clash Display', sans-serif; font-size: 1.7rem; font-weight: 600; margin-bottom: 0.3rem; }
    .card-sub { color: var(--muted); font-size: 0.88rem; margin-bottom: 1.8rem; }

    /* TABS ROL */
    .rol-tabs {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 0.5rem; margin-bottom: 1.6rem;
      background: rgba(255,255,255,0.03);
      padding: 5px; border-radius: 12px;
      border: 1px solid var(--border);
    }
    .rtab {
      padding: 0.7rem;
      border: none; background: transparent;
      color: var(--muted); font-family: 'Outfit', sans-serif;
      font-size: 0.85rem; font-weight: 500;
      border-radius: 8px; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 6px;
      transition: all 0.2s;
    }
    .rtab.active { background: var(--neon); color: #050510; font-weight: 600; }
    .rtab.active.vendedor { background: var(--gold); }

    /* ALERTA */
    .alert {
      padding: 0.75rem 1rem; border-radius: 10px;
      font-size: 0.85rem; margin-bottom: 1.2rem; display: none;
    }
    .alert.show { display: block; }
    .alert.error   { background: rgba(255,107,107,0.12); border: 1px solid rgba(255,107,107,0.3); color: #ff8e8e; }
    .alert.success { background: rgba(0,245,196,0.10);  border: 1px solid rgba(0,245,196,0.3);   color: var(--neon); }

    /* FIELDS */
    .field { margin-bottom: 1rem; }
    .field label {
      display: block; font-size: 0.78rem; font-weight: 500;
      color: var(--muted); margin-bottom: 0.45rem;
      letter-spacing: 0.5px; text-transform: uppercase;
    }
    .field-wrap { position: relative; }
    .field-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); font-size: 0.95rem; pointer-events: none; }
    .field input {
      width: 100%;
      padding: 0.8rem 1rem 0.8rem 2.7rem;
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--border);
      border-radius: 10px;
      color: var(--text);
      font-family: 'Outfit', sans-serif; font-size: 0.92rem;
      outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    }
    .field input::placeholder { color: var(--muted); }
    .field input:focus { border-color: var(--neon); box-shadow: 0 0 0 3px rgba(0,245,196,0.10); }
    .field input.error-field { border-color: var(--neon2); }

    /* PASSWORD STRENGTH */
    .strength-bar { display: flex; gap: 4px; margin-top: 6px; }
    .sb { height: 3px; flex: 1; border-radius: 99px; background: rgba(255,255,255,0.08); transition: background 0.3s; }
    .sb.weak   { background: var(--neon2); }
    .sb.medium { background: var(--gold); }
    .sb.strong { background: var(--neon); }
    .strength-text { font-size: 0.75rem; color: var(--muted); margin-top: 4px; }

    /* TÉRMINOS */
    .terms {
      display: flex; align-items: flex-start; gap: 10px;
      margin: 1rem 0; font-size: 0.82rem; color: var(--muted);
    }
    .terms input[type=checkbox] { margin-top: 2px; accent-color: var(--neon); width: 15px; height: 15px; flex-shrink: 0; }
    .terms a { color: var(--neon); text-decoration: none; }

    /* BOTÓN */
    .btn-submit {
      width: 100%; padding: 0.9rem;
      background: var(--neon); color: #05050A;
      font-family: 'Clash Display', sans-serif; font-size: 1rem; font-weight: 600;
      border: none; border-radius: 10px; cursor: pointer;
      letter-spacing: 0.5px; transition: all 0.2s;
      position: relative; overflow: hidden;
    }
    .btn-submit::after {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
      transform: translateX(-100%); transition: transform 0.4s;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,245,196,0.3); }
    .btn-submit:hover::after { transform: translateX(100%); }
    .btn-submit.vendedor { background: var(--gold); }

    .login-link { text-align: center; margin-top: 1.2rem; font-size: 0.83rem; color: var(--muted); }
    .login-link a { color: var(--neon); text-decoration: none; font-weight: 500; }

    @media(max-width:900px) {
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
    <a href="login.php" class="brand">
      <div class="brand-icon">🎓</div>
      Campus<span>Go</span>
    </a>

    <h1>Únete a la<br>comunidad <em>estudiantil</em></h1>
    <p>Crea tu perfil en segundos y empieza a intercambiar productos con seguridad dentro de tu universidad.</p>

    <div class="perks">
      <div class="perk">
        <span class="perk-icon">🛒</span>
        <div>
          <strong>Compra fácil</strong>
          <p>Encuentra productos de tus compañeros con precios accesibles</p>
        </div>
      </div>
      <div class="perk">
        <span class="perk-icon">🏪</span>
        <div>
          <strong>Vende sin comisión</strong>
          <p>Publica tus productos gratis y llega a toda la comunidad</p>
        </div>
      </div>
      <div class="perk">
        <span class="perk-icon">🗺️</span>
        <div>
          <strong>Mapa en tiempo real</strong>
          <p>Encuentra vendedores cerca de ti dentro del campus</p>
        </div>
      </div>
    </div>
  </div>

  <!-- DERECHA -->
  <div class="right">
    <div class="card">
      <div class="card-title">Crear cuenta ✨</div>
      <div class="card-sub">Regístrate para comenzar</div>

      <!-- TABS ROL -->
      <div class="rol-tabs">
        <button class="rtab active" onclick="setRol('comprador', this)">🛒 Comprador</button>
        <button class="rtab vendedor" onclick="setRol('vendedor', this)">🏪 Vendedor</button>
      </div>

      <!-- ALERTAS -->
      <?php
        $error = $_GET['error'] ?? '';
        $ok    = $_GET['ok'] ?? '';
        if ($error === 'existe')   echo '<div class="alert error show">❌ Ese correo ya está registrado.</div>';
        elseif ($error === 'pass') echo '<div class="alert error show">⚠️ Las contraseñas no coinciden.</div>';
        elseif ($error === 'campos') echo '<div class="alert error show">⚠️ Completa todos los campos.</div>';
        elseif ($ok === '1')       echo '<div class="alert success show">✅ Cuenta creada. <a href="login.php" style="color:var(--neon)">Inicia sesión →</a></div>';
      ?>
      <div class="alert" id="js-alert"></div>

      <form method="POST" action="php/registro_handler.php" id="regForm" onsubmit="return validateForm()">
        <input type="hidden" name="rol" id="rol-input" value="comprador">

        <div class="field">
          <label>Nombre completo</label>
          <div class="field-wrap">
            <span class="field-icon">👤</span>
            <input type="text" name="nombre" id="nombre" placeholder="Ej. Juan Pérez" required>
          </div>
        </div>

        <div class="field">
          <label>Correo institucional</label>
          <div class="field-wrap">
            <span class="field-icon">✉️</span>
            <input type="email" name="email" id="email" placeholder="nombre@uabc.edu.mx" required>
          </div>
        </div>

        <div class="field">
          <label>Contraseña</label>
          <div class="field-wrap">
            <span class="field-icon">🔒</span>
            <input type="password" name="password" id="password" placeholder="Mínimo 8 caracteres" required oninput="checkStrength()">
          </div>
          <div class="strength-bar">
            <div class="sb" id="sb1"></div>
            <div class="sb" id="sb2"></div>
            <div class="sb" id="sb3"></div>
            <div class="sb" id="sb4"></div>
          </div>
          <div class="strength-text" id="strength-text"></div>
        </div>

        <div class="field">
          <label>Confirmar contraseña</label>
          <div class="field-wrap">
            <span class="field-icon">🔒</span>
            <input type="password" name="password2" id="password2" placeholder="Repite tu contraseña" required>
          </div>
        </div>

        <div class="terms">
          <input type="checkbox" id="terminos" required>
          <label for="terminos">Acepto los <a href="#">términos de uso</a> y la <a href="#">política de privacidad</a> de CampusGo</label>
        </div>

        <button type="submit" class="btn-submit" id="btn-submit">Crear mi cuenta →</button>
      </form>

      <div class="login-link">
        ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
      </div>
    </div>
  </div>

</div>

<script>
  let rolActual = 'comprador';

  function setRol(rol, btn) {
    rolActual = rol;
    document.getElementById('rol-input').value = rol;
    document.querySelectorAll('.rtab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('btn-submit').className = 'btn-submit ' + (rol === 'vendedor' ? 'vendedor' : '');
  }

  function checkStrength() {
    const val = document.getElementById('password').value;
    const bars = [document.getElementById('sb1'), document.getElementById('sb2'), document.getElementById('sb3'), document.getElementById('sb4')];
    const txt  = document.getElementById('strength-text');
    bars.forEach(b => b.className = 'sb');
    if (val.length === 0) { txt.textContent = ''; return; }
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const cls = score <= 1 ? 'weak' : score <= 2 ? 'medium' : 'strong';
    const label = score <= 1 ? '⚠️ Débil' : score <= 2 ? '👌 Media' : '✅ Fuerte';
    for (let i = 0; i < score; i++) bars[i].classList.add(cls);
    txt.textContent = label;
    txt.style.color = score <= 1 ? 'var(--neon2)' : score <= 2 ? 'var(--gold)' : 'var(--neon)';
  }

  function validateForm() {
    const n  = document.getElementById('nombre').value.trim();
    const e  = document.getElementById('email').value.trim();
    const p  = document.getElementById('password').value;
    const p2 = document.getElementById('password2').value;
    const al = document.getElementById('js-alert');

    if (!n || !e || !p || !p2) {
      al.textContent = '⚠️ Completa todos los campos.';
      al.className = 'alert error show'; return false;
    }
    if (p !== p2) {
      al.textContent = '❌ Las contraseñas no coinciden.';
      al.className = 'alert error show';
      document.getElementById('password2').classList.add('error-field');
      return false;
    }
    if (p.length < 8) {
      al.textContent = '⚠️ La contraseña debe tener mínimo 8 caracteres.';
      al.className = 'alert error show'; return false;
    }
    return true;
  }
</script>
</body>
</html>
