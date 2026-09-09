<?php
/**
 * Attend les variables suivantes définies AVANT l'inclusion :
 *   $pageTitle  (string) - titre de la page
 *   $activeNav  (string) - clé du lien de navigation actif
 * $u (utilisateur courant) doit déjà être défini via require_role()/require_login().
 */
$notifCount = unread_notifications_count($u['id']);
$notifs = recent_notifications($u['id'], 6);

$navByRole = [
    'secretaire' => [
        ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => '/secretaire/dashboard.php', 'icon' => '📊'],
        ['key' => 'enregistrer', 'label' => 'Enregistrer un courrier', 'href' => '/secretaire/enregistrer_courrier.php', 'icon' => '✍️'],
        ['key' => 'envoyer', 'label' => 'Envoyer au Directeur', 'href' => '/secretaire/envoyer_directeur.php', 'icon' => '📤'],
        ['key' => 'recus', 'label' => 'Courriers validés / réf. départ', 'href' => '/secretaire/courriers_recus.php', 'icon' => '📥'],
        ['key' => 'signes', 'label' => 'Courriers signés / Archives', 'href' => '/secretaire/courriers_signes.php', 'icon' => '🗄️'],
        ['key' => 'historique', 'label' => 'Historique', 'href' => '/secretaire/historique.php', 'icon' => '🕘'],
    ],
    'drbf' => [
        ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => '/drbf/dashboard.php', 'icon' => '📊'],
        ['key' => 'recus', 'label' => 'Courriers reçus', 'href' => '/drbf/courriers_recus.php', 'icon' => '📥'],
        ['key' => 'sortie', 'label' => 'Courriers sortants (réf. départ)', 'href' => '/drbf/courriers_sortie.php', 'icon' => '📤'],
        ['key' => 'orienter', 'label' => 'Orienter un courrier', 'href' => '/drbf/orienter_courrier.php', 'icon' => '🧭'],
        ['key' => 'historique', 'label' => 'Historique', 'href' => '/drbf/historique.php', 'icon' => '🕘'],
    ],
    'chef_srb' => [
        ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => '/chef_srb/dashboard.php', 'icon' => '📊'],
        ['key' => 'recus', 'label' => 'Courriers reçus', 'href' => '/chef_srb/courriers_recus.php', 'icon' => '📥'],
        ['key' => 'envoyer', 'label' => 'Envoyer à une division', 'href' => '/chef_srb/envoyer_division.php', 'icon' => '📤'],
        ['key' => 'historique', 'label' => 'Historique', 'href' => '/chef_srb/historique.php', 'icon' => '🕘'],
    ],
    'chef_srsp' => [
        ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => '/chef_srsp/dashboard.php', 'icon' => '📊'],
        ['key' => 'recus', 'label' => 'Courriers reçus', 'href' => '/chef_srsp/courriers_recus.php', 'icon' => '📥'],
        ['key' => 'envoyer', 'label' => 'Envoyer à une division', 'href' => '/chef_srsp/envoyer_division.php', 'icon' => '📤'],
        ['key' => 'historique', 'label' => 'Historique', 'href' => '/chef_srsp/historique.php', 'icon' => '🕘'],
    ],
    'srpe' => [
        ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => '/srpe/dashboard.php', 'icon' => '📊'],
        ['key' => 'recus', 'label' => 'Courriers reçus', 'href' => '/srpe/courriers_recus.php', 'icon' => '📥'],
        ['key' => 'traiter', 'label' => 'Traiter un courrier', 'href' => '/srpe/traiter_courrier.php', 'icon' => '🛠️'],
        ['key' => 'recus_sortie', 'label' => 'Courriers sortants (réf. départ)', 'href' => '/srpe/courriers_sortie.php', 'icon' => '📤'],
        ['key' => 'historique', 'label' => 'Historique', 'href' => '/srpe/historique.php', 'icon' => '🕘'],
    ],
    'division' => [
        ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => '/division/dashboard.php', 'icon' => '📊'],
        ['key' => 'recus', 'label' => 'Courriers reçus', 'href' => '/division/courriers_recus.php', 'icon' => '📥'],
        ['key' => 'traiter', 'label' => 'Traiter le courrier', 'href' => '/division/traiter.php', 'icon' => '🛠️'],
        ['key' => 'recus_sortie', 'label' => 'Courriers sortants (réf. départ)', 'href' => '/division/courriers_sortie.php', 'icon' => '📤'],
        ['key' => 'historique', 'label' => 'Historique', 'href' => '/division/historique.php', 'icon' => '🕘'],
    ],
    'coordonnateur' => [
        ['key' => 'dashboard', 'label' => 'Tableau de bord', 'href' => '/coordonnateur/dashboard.php', 'icon' => '📊'],
        ['key' => 'recus', 'label' => 'Courriers reçus', 'href' => '/coordonnateur/courriers_recus.php', 'icon' => '📥'],
        ['key' => 'verifier', 'label' => 'Vérifier un courrier', 'href' => '/coordonnateur/verifier.php', 'icon' => '✅'],
        ['key' => 'historique', 'label' => 'Historique', 'href' => '/coordonnateur/historique.php', 'icon' => '🕘'],
    ],
];
$nav = $navByRole[$u['role']] ?? [];
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · DRBF</title>
<style>
:root{
  --navy-950:#071224; --navy-900:#0b1e36; --navy-800:#10294a; --navy-700:#15345f;
  --gold-600:#b78f3c; --gold-500:#c7a24e; --gold-400:#d9bd7b; --gold-100:#f6efdd;
  --bg:#f4f6f9; --card:#ffffff; --ink:#1a2436; --muted:#62718a; --border:#e3e8f0;
  --green:#17805a; --red:#b22b20; --amber:#9a6b10; --blue:#1f5d9e; --indigo:#3f3fae; --teal:#0c6b63;
  --shadow-sm:0 1px 2px rgba(15,30,60,.05); --shadow-md:0 6px 20px rgba(15,30,60,.07);
}
*{box-sizing:border-box;}
html{-webkit-font-smoothing:antialiased;}
body{margin:0;font-family:'Segoe UI',Roboto,Arial,sans-serif;color:var(--ink);
  font-size:14px;line-height:1.55;
  background:url('../assets/fond_app.jpg') center/cover no-repeat fixed;
  background-attachment:fixed;}
a{text-decoration:none;color:inherit;}

.layout{display:flex;min-height:100vh;}

/* ============ SIDEBAR ============ */
.sidebar{
  width:268px;flex-shrink:0;
  background:
    linear-gradient(160deg,rgba(8,22,44,.72) 0%,rgba(11,29,51,.68) 100%),
    url('../assets/fond_app.jpg') center/cover no-repeat;
  color:#e8edf5;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;
  box-shadow:2px 0 30px rgba(7,18,36,.30);
  border-right:1px solid rgba(199,162,78,.10);
}
.sidebar .brand{
  padding:28px 22px 22px;position:relative;text-align:center;
  background:linear-gradient(180deg,rgba(255,255,255,.04),transparent);
}
.sidebar .brand::after{content:'';position:absolute;left:22px;right:22px;bottom:0;height:1px;
  background:linear-gradient(90deg,transparent,var(--gold-500),transparent);}
.sidebar .brand .brand-logo{height:74px;width:74px;object-fit:contain;border-radius:50%;
  background:#fff;padding:6px;box-shadow:0 4px 16px rgba(0,0,0,.35),0 0 0 1px rgba(199,162,78,.4);
  margin-bottom:12px;border:2px solid var(--gold-400);}
.sidebar .brand .tag{display:inline-block;background:linear-gradient(135deg,var(--gold-400),var(--gold-600));color:var(--navy-950);
  font-weight:800;font-size:10px;letter-spacing:1.4px;padding:4px 10px;border-radius:4px;margin-top:10px;
  box-shadow:0 2px 8px rgba(199,162,78,.25);}
.sidebar .brand h1{margin:0 0 0;line-height:1.1;font-weight:800;letter-spacing:.3px;font-size:19px;color:#fff;
  text-shadow:0 1px 2px rgba(0,0,0,.3);}
.sidebar .brand p{margin:7px 0 0;font-size:11px;color:#a7b6cf;letter-spacing:.3px;text-transform:uppercase;}

.sidebar nav{flex:1;padding:18px 12px;overflow-y:auto;}
.sidebar nav a{
  display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:8px;
  font-size:13.5px;color:#c9d3e6;margin-bottom:3px;font-weight:500;transition:.16s;
  border-left:2px solid transparent;position:relative;overflow:hidden;
}
.sidebar nav a::before{content:'';position:absolute;left:0;top:0;bottom:0;width:2px;
  background:linear-gradient(180deg,var(--gold-400),var(--gold-600));transform:scaleY(0);transition:.2s;}
.sidebar nav a:hover{background:rgba(255,255,255,.06);color:#fff;}
.sidebar nav a:hover::before{transform:scaleY(1);}
.sidebar nav a.active{background:linear-gradient(90deg,rgba(199,162,78,.14),rgba(255,255,255,.04));color:#fff;font-weight:600;}
.sidebar nav a.active::before{transform:scaleY(1);}
.sidebar nav a.active .icon{opacity:1;}
.sidebar nav .icon{font-size:15px;width:20px;text-align:center;opacity:.85;transition:.16s;}
.sidebar nav a:hover .icon{opacity:1;}

.sidebar .userbox{padding:18px 16px;border-top:1px solid rgba(255,255,255,.09);
  background:linear-gradient(180deg,transparent,rgba(0,0,0,.12));}
.sidebar .userbox .name{font-size:14.5px;font-weight:700;color:#fff;letter-spacing:.4px;}
.sidebar .userbox .role{font-size:11.5px;color:#a7b6cf;font-weight:600;margin-top:2px;line-height:1.4;}
.sidebar .userbox a.logout{
  display:flex;align-items:center;justify-content:center;width:100%;margin-top:13px;padding:9px 14px;
  border-radius:7px;background:rgba(255,255,255,.07);color:#f4f6fb;font-weight:600;font-size:13px;
  border:1px solid rgba(255,255,255,.14);transition:.16s;
}
.sidebar .userbox a.logout:hover{background:var(--red);border-color:var(--red);color:#fff;}

/* ============ MAIN ============ */
.main{flex:1;min-width:0;display:flex;flex-direction:column;}
.topbar{
  background:var(--card);border-bottom:1px solid var(--border);padding:16px 30px;
  display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;
  box-shadow:var(--shadow-sm);
}
.topbar h2{margin:0;font-size:18px;font-weight:700;color:var(--navy-900);letter-spacing:.2px;}
.topbar .sub{font-size:12px;color:var(--muted);margin-top:1px;}

.notif-wrap{position:relative;}
.notif-btn{
  background:var(--navy-900);color:#fff;border:none;border-radius:7px;padding:9px 15px;
  cursor:pointer;font-size:13.5px;position:relative;font-weight:600;transition:.15s;
}
.notif-btn:hover{background:var(--navy-700);}
.notif-dot{
  position:absolute;top:-6px;right:-6px;background:var(--red);color:#fff;font-size:10px;
  border-radius:999px;padding:2px 6px;font-weight:700;box-shadow:0 0 0 2px #fff;
}
.notif-panel{
  display:none;position:absolute;right:0;top:46px;width:340px;background:#fff;
  border:1px solid var(--border);border-radius:10px;box-shadow:0 18px 45px rgba(11,29,51,.18);
  overflow:hidden;z-index:20;
}
.notif-panel.show{display:block;}
.notif-panel .head{padding:13px 18px;background:var(--navy-900);color:#fff;font-size:13px;font-weight:700;letter-spacing:.2px;}
.notif-item{padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;color:var(--ink);}
.notif-item:last-child{border-bottom:none;}
.notif-item .t{color:var(--muted);font-size:11px;margin-top:3px;}
.notif-empty{padding:22px 16px;text-align:center;color:var(--muted);font-size:13px;}

.content{padding:28px 30px;flex:1;max-width:1200px;}

/* ============ FLASH ============ */
.flash{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13.5px;font-weight:600;}
.flash-success{background:#e6f5ee;color:var(--green);border:1px solid #bfe6d3;}
.flash-error{background:#fbebea;color:var(--red);border:1px solid #f3cdca;}

/* ============ CARDS / STATS ============ */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-bottom:26px;}
.stat-card{
  background:var(--card);border:1px solid var(--border);border-left:4px solid var(--gold-500);
  border-radius:10px;padding:20px 22px;box-shadow:var(--shadow-sm);transition:.18s;position:relative;overflow:hidden;
}
.stat-card::after{content:'';position:absolute;right:-18px;top:-18px;width:70px;height:70px;border-radius:50%;
  background:radial-gradient(circle,rgba(201,162,75,.08),transparent 70%);}
.stat-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md);}
.stat-card .num{font-size:30px;font-weight:800;color:var(--navy-900);letter-spacing:-.5px;line-height:1.1;}
.stat-card .lbl{font-size:11.5px;color:var(--muted);margin-top:6px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
.stat-card.c-blue{border-left-color:var(--blue);} .stat-card.c-green{border-left-color:var(--green);}
.stat-card.c-amber{border-left-color:var(--amber);} .stat-card.c-red{border-left-color:var(--red);}
.stat-card.c-indigo{border-left-color:var(--indigo);}

/* ============ CONVERSATION ============ */
.conversation{display:flex;flex-direction:column;gap:10px;margin-top:6px;}
.conv-item{border:1px solid var(--border);border-left:3px solid var(--gold-500);border-radius:8px;padding:11px 15px;background:#fbfcfe;}
.conv-item:nth-child(even){border-left-color:var(--blue);}
.conv-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;}
.conv-sens{font-weight:700;font-size:12.5px;color:var(--navy-900);}
.conv-date{font-size:11.5px;color:var(--muted);}
.conv-msg{font-size:13px;color:#33455e;margin-top:3px;}
.conv-rem{font-size:13px;color:var(--red);margin-top:3px;font-weight:600;}
.conv-lbl{color:var(--muted);font-weight:700;}

.panel{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:24px 26px;margin-bottom:22px;box-shadow:var(--shadow-sm);}
.panel h3{margin:0 0 18px;font-size:15.5px;color:var(--navy-900);font-weight:700;display:flex;align-items:center;gap:9px;}
.panel h3::before{content:'';width:3px;height:16px;background:var(--gold-500);border-radius:2px;display:inline-block;}

/* ============ TABLE ============ */
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:13.5px;}
th{text-align:left;background:#f1f3f7;color:var(--navy-800);padding:12px 13px;font-weight:700;font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;border-bottom:2px solid var(--border);}
td{padding:12px 13px;border-bottom:1px solid var(--border);vertical-align:middle;}
tbody tr{transition:.12s;}
tbody tr:hover td{background:#f7f9fc;}
.empty-row td{text-align:center;color:var(--muted);padding:32px;}

/* ============ BADGES ============ */
.badge{display:inline-block;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;letter-spacing:.2px;border:1px solid transparent;}
.badge-gray{background:#eef0f4;color:#5b6b85;border-color:#dfe3ea;}
.badge-blue{background:#e6eef7;color:var(--blue);border-color:#cfe0f0;}
.badge-indigo{background:#ececf9;color:var(--indigo);border-color:#dcdcf0;}
.badge-amber{background:#faf2dd;color:var(--amber);border-color:#f0e2bb;}
.badge-red{background:#faebe9;color:var(--red);border-color:#f2d4d0;}
.badge-green{background:#e5f4ec;color:var(--green);border-color:#cfe9dc;}
.badge-teal{background:#e0f3f0;color:var(--teal);border-color:#c8e8e3;}
.badge-dark-green{background:#e3efe5;color:#14532d;border-color:#c9e0cd;}

/* ============ RÉFÉRENCES ============ */
.ref-arrivee{display:inline-flex;align-items:center;gap:6px;background:#eef0f4;border:1px solid #dfe3ea;
  color:var(--navy-900);font-weight:700;font-size:12px;padding:4px 10px;border-radius:6px;white-space:nowrap;}
.ref-depart{display:inline-flex;align-items:center;gap:6px;background:var(--gold-100);border:1px solid var(--gold-400);
  color:#8a6515;font-weight:700;font-size:12px;padding:4px 10px;border-radius:6px;white-space:nowrap;}
.ref-empty{color:var(--muted);font-style:italic;}
.ref-set-tag{display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--green);font-weight:600;}
.ref-set-tag::before{content:'✓';}

/* ============ RECHERCHE ============ */
.search-bar{display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;background:#f7f9fc;
  border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:18px;}
.search-bar .field{flex:1;min-width:220px;}
.search-bar label{font-size:11.5px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;font-weight:700;}
.search-bar .clear-btn{display:inline-flex;align-items:center;height:38px;padding:0 14px;border-radius:7px;
  background:#fff;color:var(--navy-900);border:1px solid var(--border);font-size:13px;font-weight:600;transition:.15s;}
.search-bar .clear-btn:hover{background:#f6f8fb;border-color:#c9d2e0;}

/* ============ BUTTONS ============ */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;border-radius:7px;font-size:13px;
  font-weight:600;border:none;cursor:pointer;text-decoration:none;transition:.15s;line-height:1.3;}
.btn:hover{transform:translateY(-1px);}
.btn:active{transform:translateY(0);}
.btn-primary{background:var(--navy-900);color:#fff;} .btn-primary:hover{background:var(--navy-700);box-shadow:0 5px 14px rgba(15,39,68,.25);}
.btn-gold{background:linear-gradient(135deg,var(--gold-400),var(--gold-600));color:var(--navy-950);} .btn-gold:hover{box-shadow:0 5px 14px rgba(199,162,78,.35);}
.btn-green{background:var(--green);color:#fff;} .btn-green:hover{background:#10704d;box-shadow:0 5px 14px rgba(23,128,90,.3);}
.btn-red{background:var(--red);color:#fff;} .btn-red:hover{background:#9b241b;box-shadow:0 5px 14px rgba(178,43,32,.3);}
.btn-outline{background:#fff;color:var(--navy-900);border:1px solid var(--border);}
.btn-outline:hover{background:#f6f8fb;border-color:#c9d2e0;}
.btn-sm{padding:6px 12px;font-size:12px;border-radius:6px;}

/* ============ FORMS ============ */
label{display:block;font-size:12.5px;font-weight:600;color:var(--navy-900);margin-bottom:6px;}
input[type=text],input[type=date],input[type=password],input[type=file],textarea,select{
  width:100%;padding:10px 13px;border:1px solid var(--border);border-radius:7px;font-size:13.5px;
  font-family:inherit;background:#fbfcfe;color:var(--ink);transition:.15s;}
input[type=text]:focus,input[type=date]:focus,input[type=password]:focus,textarea:focus,select:focus{
  outline:none;border-color:var(--gold-500);box-shadow:0 0 0 3px rgba(199,162,78,.15);background:#fff;}
textarea{resize:vertical;min-height:80px;}
.form-row{margin-bottom:16px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.hint{font-size:11.5px;color:var(--muted);margin-top:4px;}

.pdf-link{display:inline-flex;align-items:center;gap:5px;color:var(--blue);font-weight:600;font-size:13px;}
.pdf-link:hover{text-decoration:underline;}

/* ============ TIMELINE ============ */
.timeline{border-left:3px solid var(--gold-500);margin-left:8px;padding-left:20px;}
.timeline-item{position:relative;padding-bottom:20px;}
.timeline-item::before{content:'';position:absolute;left:-27px;top:3px;width:11px;height:11px;
  border-radius:50%;background:var(--navy-900);border:2px solid var(--gold-500);}
.timeline-item .ti-title{font-weight:700;font-size:13.5px;color:var(--navy-900);}
.timeline-item .ti-meta{font-size:12px;color:var(--muted);margin-top:2px;}
.timeline-item .ti-body{font-size:13px;color:var(--ink);margin-top:5px;background:#f7f9fc;padding:8px 12px;border-radius:6px;}

/* MODAL */
.modal-bg{position:fixed;inset:0;background:rgba(7,18,36,.55);z-index:100;align-items:center;justify-content:center;}
.modal-bg>div{background:#fff;border-radius:12px;padding:26px;max-width:440px;width:92%;box-shadow:0 20px 50px rgba(7,18,36,.3);}

@media (max-width: 900px){
  .sidebar{position:fixed;left:-270px;z-index:50;transition:.2s;}
  .form-grid{grid-template-columns:1fr;}
  .content{padding:20px;}
}
</style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="brand">
      <img class="brand-logo" src="<?= root_url('assets/logo_drbf.png') ?>" alt="Logo DRBF">
      <h1>DRBF</h1>
      <span class="tag">DRBF — VATOVAVY</span>
      <p>Gestion des Courriers</p>
    </div>
    <nav>
      <?php foreach ($nav as $item): ?>
        <a href="<?= root_url($item['href']) ?>" class="<?= $activeNav === $item['key'] ? 'active' : '' ?>">
          <span class="icon"><?= $item['icon'] ?></span><?= e($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="userbox">
      <div class="name"><?= e(strtoupper($u['username'])) ?></div>
      <div class="role"><?= e(role_label($u['role'])) ?><?= $u['division_code'] ? ' — ' . e($u['division_code']) : ($u['service'] ? ' — ' . e($u['service']) : '') ?></div>
      <a class="logout" href="<?= root_url('logout.php') ?>">Déconnexion</a>
    </div>
  </aside>

  <div class="main">
    <div class="topbar">
      <div>
        <h2><?= e($pageTitle) ?></h2>
        <div class="sub">Direction Régionale du Budget et des Finances</div>
      </div>
      <div class="notif-wrap">
        <button class="notif-btn" onclick="toggleNotif(this)">
          🔔 Notifications
          <?php if ($notifCount > 0): ?><span class="notif-dot" id="notifDot"><?= $notifCount ?></span><?php endif; ?>
        </button>
        <div class="notif-panel" id="notifPanel">
          <div class="head">Notifications récentes</div>
          <?php if (empty($notifs)): ?>
            <div class="notif-empty">Aucune notification pour le moment.</div>
          <?php else: foreach ($notifs as $n): ?>
            <div class="notif-item">
              <?= e($n['message']) ?>
              <div class="t"><?= format_date($n['created_at']) ?></div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
    <div class="content">
      <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
      <?php endif; ?>
