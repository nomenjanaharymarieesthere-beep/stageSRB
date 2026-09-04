<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['division']);
$db = getDB();

$stmt = $db->prepare("SELECT COUNT(*) c FROM courriers WHERE division_id = ? AND statut IN ('envoye_division','a_corriger')");
$stmt->execute([$u['id']]); $aTraiter = $stmt->fetch()['c'];

$stmt = $db->prepare("SELECT COUNT(*) c FROM courriers WHERE division_id = ? AND statut = 'envoye_coordonnateur'");
$stmt->execute([$u['id']]); $enVerif = $stmt->fetch()['c'];

$stmt = $db->prepare("SELECT COUNT(*) c FROM courriers WHERE division_id = ? AND statut IN ('valide','en_signature','archive')");
$stmt->execute([$u['id']]); $traites = $stmt->fetch()['c'];

$stmt = $db->prepare("SELECT COUNT(*) c FROM courriers WHERE division_id = ?");
$stmt->execute([$u['id']]); $total = $stmt->fetch()['c'];

$stmt = $db->prepare("SELECT * FROM courriers WHERE division_id = ? ORDER BY updated_at DESC LIMIT 50");
$stmt->execute([$u['id']]); $recents = $stmt->fetchAll();

$pageTitle = "Tableau de bord — " . $u['division_code'];
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
  <div class="stat-card"><div class="num"><?= $total ?></div><div class="lbl">Courriers reçus au total</div></div>
  <div class="stat-card c-amber"><div class="num"><?= $aTraiter ?></div><div class="lbl">À traiter</div></div>
  <div class="stat-card c-indigo"><div class="num"><?= $enVerif ?></div><div class="lbl">En vérification (Coordonnateur)</div></div>
  <div class="stat-card c-green"><div class="num"><?= $traites ?></div><div class="lbl">Traités / validés</div></div>
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
  <a class="btn btn-gold" href="<?= root_url('/division/traiter.php') ?>">🛠️ Traiter un courrier reçu</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
