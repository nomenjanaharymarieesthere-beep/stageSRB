<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['division', 'srpe', 'drbf', 'chef_srb', 'chef_srsp', 'secretaire', 'coordonnateur']);
$db = getDB();

$courrierId = (int)($_GET['id'] ?? 0);
$courrier = get_courrier($courrierId);
if (!$courrier || empty($courrier['reference_depart'])) {
    die("Fiche introuvable pour ce courrier.");
}
$etapes = get_etapes($courrierId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Fiche courrier — <?= e($courrier['reference']) ?></title>
<style>
*{box-sizing:border-box;}
body{font-family:'Segoe UI',Roboto,Arial,sans-serif;color:#1a2436;font-size:13px;margin:30px;line-height:1.5;}
.print-header{display:flex;justify-content:space-between;align-items:center;border-bottom:3px solid #c7a24e;padding-bottom:14px;margin-bottom:20px;}
.print-header .title{font-size:20px;font-weight:800;color:#0b1e36;text-transform:uppercase;letter-spacing:1px;}
.print-header .sub{font-size:12px;color:#62718a;}
.fiche-table{width:100%;border-collapse:collapse;}
.fiche-table td,.fiche-table th{border:1px solid #e3e8f0;padding:9px 12px;vertical-align:top;}
.fiche-table th{background:#f1f3f7;width:200px;font-size:12px;color:#10294a;font-weight:700;text-transform:uppercase;letter-spacing:.3px;}
.ref-pill{display:inline-block;font-weight:700;font-size:13px;padding:4px 12px;border-radius:6px;}
.ref-arrivee{background:#eef0f4;color:#0b1e36;}
.ref-depart{background:#f6efdd;color:#8a6515;}
.badge{display:inline-block;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;border:1px solid transparent;}
.badge-green{background:#e5f4ec;color:#17805a;border-color:#cfe9dc;}
.badge-dark-green{background:#e3efe5;color:#14532d;border-color:#c9e0cd;}
.section-title{font-size:14px;font-weight:700;color:#0b1e36;margin:24px 0 10px;border-left:3px solid #c7a24e;padding-left:10px;}
.print-foot{margin-top:34px;border-top:1px solid #e3e8f0;padding-top:14px;font-size:11px;color:#62718a;display:flex;justify-content:space-between;}
@media print{
  body{margin:10mm;}
  .no-print{display:none !important;}
}
</style>
</head>
<body>

<div class="print-header">
  <div>
    <div class="title">Direction Régionale du Budget et des Finances</div>
    <div class="sub">Fiche de courrier — <?= e(statut_label($courrier['statut'])) ?></div>
  </div>
  <div style="text-align:right;">
    <div style="font-size:26px;">DRBF</div>
    <div style="font-size:11px;color:#62718a;">Vatovavy</div>
  </div>
</div>

<div style="margin-bottom:14px;text-align:right;" class="no-print">
  <button onclick="window.print()" class="btn" style="background:#0b1e36;color:#fff;border:none;padding:10px 18px;border-radius:7px;font-size:13px;cursor:pointer;font-weight:600;">🖨️ Imprimer / PDF</button>
</div>

<table class="fiche-table">
  <tr>
    <th>Référence Arrivée</th>
    <td><span class="ref-pill ref-arrivee"><?= e($courrier['reference']) ?></span></td>
    <th>Référence Départ</th>
    <td><span class="ref-pill ref-depart"><?= e($courrier['reference_depart']) ?></span></td>
  </tr>
  <tr>
    <th>Objet</th>
    <td colspan="3"><?= e($courrier['objet']) ?></td>
  </tr>
  <tr>
    <th>Expéditeur</th>
    <td><?= e($courrier['expediteur']) ?></td>
    <th>Date de réception</th>
    <td><?= e(date('d/m/Y', strtotime($courrier['date_reception']))) ?></td>
  </tr>
  <tr>
    <th>Service cible</th>
    <td><?= e($courrier['service_cible'] ?: '—') ?></td>
    <th>Division cible</th>
    <td><?= e($courrier['division_cible'] ?: '—') ?></td>
  </tr>
  <tr>
    <th>Statut</th>
    <td colspan="3"><span class="badge badge-green"><?= e(statut_label($courrier['statut'])) ?></span></td>
  </tr>
  <tr>
    <th>Description</th>
    <td colspan="3"><?= nl2br(e($courrier['description'] ?? '')) ?: '—' ?></td>
  </tr>
</table>

<?php if (!empty($etapes)): ?>
<div class="section-title">Historique / suivi</div>
<table class="fiche-table">
  <tr><th style="width:120px;">Date</th><th>Étape</th></tr>
  <?php foreach ($etapes as $et): ?>
    <tr>
      <td><?= e(date('d/m/Y H:i', strtotime($et['created_at']))) ?></td>
      <td>
        <?php
          $from = role_label($et['from_role'] ?? '');
          $to = role_label($et['to_role'] ?? '');
          if ($et['from_role'] && $et['to_role']) {
              echo e($from . ' → ' . $to);
          } else {
              echo e(ucfirst(str_replace('_', ' ', $et['action'])));
          }
          if (!empty($et['message'])) echo ' — ' . e($et['message']);
          if (!empty($et['remarque'])) echo ' <em>(Remarque : ' . e($et['remarque']) . ')</em>';
        ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<div class="print-foot">
  <span>Imprimé le <?= e(date('d/m/Y à H:i')) ?> par <?= e($u['username']) ?></span>
  <span>DRBF — Gestion des Courriers</span>
</div>

</body>
</html>
