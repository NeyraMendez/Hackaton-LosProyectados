<?php
session_start();
require_once 'php/config.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: login.php");
    exit;
}
$pdo = conectar();
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    if ($id != $_SESSION['usuario_id']) {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: usuarios.php");
    exit;
}
$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC");
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Usuarios – CampusGo</title>
<link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@700&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root { --bg:#05050A;--panel:#0E0E18;--border:rgba(255,255,255,0.07);--neon:#00F5C4;--neon2:#FF6B6B;--gold:#FFD166;--text:#EEEEF5;--muted:#6B6B8A; }
  body { background:var(--bg);color:var(--text);font-family:'Outfit',sans-serif;padding:2rem 2.5rem;min-height:100vh; }
  .bg-grid { position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(0,245,196,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(0,245,196,0.03) 1px,transparent 1px);background-size:36px 36px; }
  .content { position:relative;z-index:1;max-width:1000px;margin:0 auto; }
  .topbar { display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem; }
  .topbar h1 { font-family:'Clash Display',sans-serif;font-size:1.8rem; }
  .back-btn { padding:0.6rem 1.2rem;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:8px;color:var(--muted);text-decoration:none;font-size:0.88rem;transition:all 0.2s; }
  .back-btn:hover { color:var(--text);border-color:rgba(0,245,196,0.3); }
  .badge-count { display:inline-block;background:rgba(0,245,196,0.12);border:1px solid rgba(0,245,196,0.25);color:var(--neon);padding:0.3rem 0.9rem;border-radius:99px;font-size:0.8rem;margin-left:0.8rem; }
  table { width:100%;border-collapse:collapse;background:var(--panel);border-radius:14px;overflow:hidden;border:1px solid var(--border); }
  thead { background:rgba(0,245,196,0.05);border-bottom:1px solid var(--border); }
  th { padding:14px 18px;font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);text-align:left; }
  td { padding:14px 18px;font-size:0.9rem;border-bottom:1px solid var(--border); }
  tr:last-child td { border-bottom:none; }
  tr:hover td { background:rgba(255,255,255,0.02); }
  .rol-badge { display:inline-block;padding:3px 12px;border-radius:99px;font-size:0.75rem;font-weight:600; }
  .rol-admin    { background:rgba(255,107,107,0.15);color:var(--neon2); }
  .rol-vendedor { background:rgba(255,209,102,0.15);color:var(--gold); }
  .rol-comprador{ background:rgba(0,245,196,0.12);color:var(--neon); }
  .btn-eliminar { padding:5px 14px;background:rgba(255,107,107,0.1);border:1px solid rgba(255,107,107,0.25);color:var(--neon2);border-radius:8px;text-decoration:none;font-size:0.82rem;transition:all 0.2s; }
  .btn-eliminar:hover { background:rgba(255,107,107,0.2); }
  .id-cell { color:var(--muted);font-size:0.82rem; }
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="content">
  <div class="topbar">
    <h1>👥 Panel de Usuarios <span class="badge-count"><?= count($usuarios) ?> usuarios</span></h1>
    <a href="catalogo.php" class="back-btn">← Volver al catálogo</a>
  </div>
  <table>
    <thead>
      <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Acción</th></tr>
    </thead>
    <tbody>
    <?php foreach($usuarios as $u): ?>
    <tr>
      <td class="id-cell">#<?= $u['id'] ?></td>
      <td><?= htmlspecialchars($u['nombre']) ?></td>
      <td style="color:var(--muted)"><?= htmlspecialchars($u['email']) ?></td>
      <td><span class="rol-badge rol-<?= $u['rol'] ?>"><?= ucfirst($u['rol']) ?></span></td>
      <td>
        <?php if($u['id'] != $_SESSION['usuario_id']): ?>
        <a href="?eliminar=<?= $u['id'] ?>" class="btn-eliminar" onclick="return confirm('¿Eliminar usuario?')">🗑 Eliminar</a>
        <?php else: ?>
        <span style="color:var(--muted);font-size:0.8rem;">— tú —</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
