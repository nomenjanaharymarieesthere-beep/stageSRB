<?php
require_once __DIR__ . '/includes/fonctions.php';

$u = current_user();
if ($u) { header('Location: ' . root_url(role_dashboard_path($u['role']))); exit; }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $u = login_attempt($username, $password);
    if ($u) {
        header('Location: ' . root_url(role_dashboard_path($u['role'])));
        exit;
    }
    $error = "Identifiant ou mot de passe incorrect.";
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion · DRBF</title>
<style>
:root{--green:#12805c;--green-d:#0d6b4b;--red:#b52b1e;--ink:#173a2e;--gold-500:#c7a24e;--gold-400:#d9bd7b;}
*{box-sizing:border-box;}
body{margin:0;min-height:100vh;font-family:'Segoe UI',Roboto,Arial,sans-serif;
  background:url('assets/fond_login.jpg') center/cover no-repeat fixed;
  display:flex;align-items:center;justify-content:center;padding:20px;}
.card{width:100%;max-width:380px;border-radius:18px;overflow:hidden;
  background:rgba(255,255,255,.16);
  backdrop-filter:blur(10px) saturate(140%);-webkit-backdrop-filter:blur(10px) saturate(140%);
  box-shadow:0 24px 60px rgba(0,0,0,.45),inset 0 1px 0 rgba(255,255,255,.4);
  border:1px solid rgba(255,255,255,.35);
  position:relative;}
.card::before{content:'';position:absolute;inset:0;border-radius:18px;padding:1px;pointer-events:none;
  background:linear-gradient(160deg,rgba(255,255,255,.65),rgba(47,174,126,.35),rgba(181,43,30,.3),rgba(255,255,255,.5));
  -webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);
  -webkit-mask-composite:xor;mask-composite:exclude;}
.card .top{background:linear-gradient(170deg,rgba(255,255,255,.22),rgba(255,255,255,.08));
  padding:30px 28px 24px;color:#fff;text-align:center;position:relative;border-bottom:1px solid rgba(255,255,255,.2);}
.card .top::after{content:'';position:absolute;bottom:0;left:18%;right:18%;height:3px;border-radius:3px;
  background:linear-gradient(90deg,rgba(23,128,92,0),#2fae7e,rgba(181,43,30,0));}
.card .top .tag{display:inline-block;background:rgba(255,255,255,.92);color:var(--green-d);font-weight:800;
  font-size:10px;letter-spacing:1.5px;padding:5px 11px;border-radius:4px;margin-bottom:12px;box-shadow:0 2px 8px rgba(0,0,0,.18);}
.card .top h1{margin:7px 0 0;font-size:26px;font-weight:800;letter-spacing:.5px;color:#fff;text-shadow:0 2px 6px rgba(0,0,0,.4);}
.card .top p{margin:7px 0 0;font-size:12px;color:rgba(255,255,255,.92);line-height:1.55;text-shadow:0 1px 3px rgba(0,0,0,.4);}
.card .top .login-logo{height:84px;width:84px;object-fit:contain;border-radius:50%;background:#fff;
  padding:7px;border:2px solid rgba(47,174,126,.6);box-shadow:0 10px 26px rgba(0,0,0,.4);
  margin:0 auto 12px;display:block;transition:.3s;}
.card .top .login-logo:hover{transform:scale(1.05);}
.card .body{padding:24px 28px 26px;}
label{display:block;font-size:12.5px;font-weight:700;color:#fff;margin-bottom:7px;text-shadow:0 1px 2px rgba(0,0,0,.4);}
input{width:100%;padding:12px 14px;border:1px solid rgba(255,255,255,.65);border-radius:9px;font-size:14px;margin-bottom:16px;
  background:rgba(255,255,255,.82);color:var(--ink);transition:.15s;}
input:focus{outline:none;border-color:#fff;box-shadow:0 0 0 3px rgba(47,174,126,.4);background:#fff;}
button{width:100%;padding:13px;border:none;border-radius:9px;cursor:pointer;transition:.16s;font-weight:800;font-size:14px;
  background:linear-gradient(135deg,#2fae7e,var(--green-d));color:#fff;
  box-shadow:0 8px 20px rgba(13,107,75,.35);}
button:hover{transform:translateY(-1px);box-shadow:0 12px 26px rgba(13,107,75,.45);}
button:active{transform:translateY(0);}
.error{background:rgba(181,43,30,.9);color:#fff;padding:10px 13px;border-radius:8px;font-size:13px;margin-bottom:16px;font-weight:600;border:1px solid rgba(255,255,255,.25);}
.demo{margin-top:20px;padding-top:18px;border-top:1px solid rgba(255,255,255,.22);}
.demo h4{margin:0 0 10px;font-size:11px;color:rgba(255,255,255,.9);text-transform:uppercase;letter-spacing:.5px;}
.demo-grid{display:flex;flex-wrap:wrap;gap:6px;}
.demo-grid span{background:rgba(255,255,255,.92);color:var(--green-d);font-size:11px;padding:4px 9px;border-radius:5px;font-weight:700;}
.demo p.pwd{margin-top:10px;font-size:11px;color:rgba(255,255,255,.9);}
</style>
</head>
<body>
<div class="card">
  <div class="top">
    <img class="login-logo" src="assets/logo_drbf.png" alt="Logo DRBF">
    <span class="tag">DRBF — VATOVAVY</span>
    <h1>DRBF VATOVAVY</h1>
    <p>Direction Régionale du Budget et des Finances<br>Application de Gestion des Courriers</p>
  </div>
  <div class="body">
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <label>Identifiant</label>
      <input type="text" name="username" required autofocus autocomplete="off" placeholder="ex : SECRETAIRE">
      <label>Mot de passe</label>
      <input type="password" name="password" required autocomplete="new-password" placeholder="••••••••">
      <button type="submit">Se connecter</button>
    </form>
  </div>
</div>
</body>
</html>
