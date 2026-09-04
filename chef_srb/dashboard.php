<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['chef_srb']);
$db = getDB();

$totalRecus = $db->query("SELECT COUNT(*) c FROM courriers WHERE service_cible = 'SRB'")->fetch()['c'];
$enAttente = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'oriente_srb'")->fetch()['c'];
$enDivision = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'envoye_division' AND service_cible = 'SRB'")->fetch()['c'];
$enVerif = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'envoye_coordonnateur' AND service_cible = 'SRB'")->fetch()['c'];
$aCorriger = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'a_corriger' AND service_cible = 'SRB'")->fetch()['c'];
$traites = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut IN ('valide','en_signature','archive') AND service_cible = 'SRB'")->fetch()['c'];

$recents = $db->query("SELECT * FROM courriers WHERE service_cible = 'SRB' ORDER BY updated_at DESC LIMIT 50")->fetchAll();

$pageTitle = "Tableau de bord — Chef SRB";
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
  <div class="stat-card c-blue"><div class="num"><?= $totalRecus ?></div><div class="lbl">Courriers reçus du Directeur</div></div>
  <div class="stat-card c-amber"><div class="num"><?= $enAttente ?></div><div class="lbl">À orienter vers une division</div></div>
  <div class="stat-card c-indigo"><div class="num"><?= $enDivision ?></div><div class="lbl">En traitement par division</div></div>
  <div class="stat-card c-red"><div class="num"><?= $aCorriger ?></div><div class="lbl">Retournés pour correction</div></div>
  <div class="stat-card c-green"><div class="num"><?= $traites ?></div><div class="lbl">Traités / validés</div></div>
</div>

<div class="panel">
  <h3>Activité récente du service SRB</h3>
  <table>
    <thead><tr><th>Référence</th><th>Objet</th><th>Division</th><th>Statut</th><th>Mise à jour</th></tr></thead>
    <tbody>
      <?php if (empty($recents)): ?>
        <tr class="empty-row"><td colspan="5">Aucun courrier pour le moment.</td></tr>
      <?php else: foreach ($recents as $c): ?>
        <tr>
          <td><strong><?= e($c['reference']) ?></strong></td>
          <td><?= e($c['objet']) ?></td>
          <td><?= e($c['division_cible'] ?: '—') ?></td>
          <td><span class="badge <?= statut_badge_class($c['statut']) ?>"><?= e(statut_label($c['statut'])) ?></span></td>
          <td><?= format_date($c['updated_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="panel">
  <h3>Action rapide</h3>
  <a class="btn btn-gold" href="<?= root_url('/chef_srb/envoyer_division.php') ?>">📤 Envoyer un courrier à une division</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
