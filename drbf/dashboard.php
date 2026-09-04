<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['drbf']);
$db = getDB();

$totalRecus = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut != 'nouveau'")->fetch()['c'];
$enAttente = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'envoye_drbf'")->fetch()['c'];
$versSRB = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'oriente_srb'")->fetch()['c'];
$versSRSP = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'oriente_srsp'")->fetch()['c'];
$versSRPE = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'oriente_srpe'")->fetch()['c'];
$archives = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'archive'")->fetch()['c'];

$recents = $db->query("SELECT * FROM courriers WHERE statut != 'nouveau' ORDER BY updated_at DESC LIMIT 50")->fetchAll();

$pageTitle = "Tableau de bord — Directeur DRBF";
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
  <div class="stat-card c-blue"><div class="num"><?= $totalRecus ?></div><div class="lbl">Courriers reçus du Secrétariat</div></div>
  <div class="stat-card c-amber"><div class="num"><?= $enAttente ?></div><div class="lbl">Courriers à orienter</div></div>
  <div class="stat-card c-blue"><div class="num"><?= $versSRB ?></div><div class="lbl">Orientés vers SRB</div></div>
  <div class="stat-card c-indigo"><div class="num"><?= $versSRSP ?></div><div class="lbl">Orientés vers SRSP</div></div>
  <div class="stat-card c-green"><div class="num"><?= $versSRPE ?></div><div class="lbl">Orientés vers SRPE</div></div>
  <div class="stat-card"><div class="num"><?= $archives ?></div><div class="lbl">Dossiers archivés</div></div>
</div>

<div class="panel">
  <h3>Activité récente</h3>
  <table>
    <thead><tr><th>Référence</th><th>Objet</th><th>Statut</th><th>Mise à jour</th></tr></thead>
    <tbody>
      <?php if (empty($recents)): ?>
        <tr class="empty-row"><td colspan="4">Aucun courrier reçu pour le moment.</td></tr>
      <?php else: foreach ($recents as $c): ?>
        <tr>
          <td><strong><?= e($c['reference']) ?></strong></td>
          <td><?= e($c['objet']) ?></td>
          <td><span class="badge <?= statut_badge_class($c['statut']) ?>"><?= e(statut_label($c['statut'])) ?></span></td>
          <td><?= format_date($c['updated_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="panel">
  <h3>Action rapide</h3>
  <a class="btn btn-gold" href="<?= root_url('/drbf/orienter_courrier.php') ?>">🧭 Orienter un courrier reçu</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
