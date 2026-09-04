<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['coordonnateur']);
$db = getDB();

$totalRecus = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut NOT IN ('nouveau','envoye_drbf')")->fetch()['c'];
$enVerif = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'envoye_coordonnateur'")->fetch()['c'];
$aCorriger = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'a_corriger'")->fetch()['c'];
$valides = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut IN ('valide','en_signature','archive')")->fetch()['c'];
$archives = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'archive'")->fetch()['c'];

$recents = $db->query("SELECT * FROM courriers WHERE statut IN ('envoye_coordonnateur','a_corriger','valide') ORDER BY updated_at DESC LIMIT 50")->fetchAll();

$pageTitle = "Tableau de bord — Coordonnateur";
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
  <div class="stat-card c-blue"><div class="num"><?= $totalRecus ?></div><div class="lbl">Courriers reçus au total</div></div>
  <div class="stat-card c-amber"><div class="num"><?= $enVerif ?></div><div class="lbl">En attente de vérification</div></div>
  <div class="stat-card c-red"><div class="num"><?= $aCorriger ?></div><div class="lbl">Retournés pour correction</div></div>
  <div class="stat-card c-green"><div class="num"><?= $valides ?></div><div class="lbl">Validés</div></div>
  <div class="stat-card c-indigo"><div class="num"><?= $archives ?></div><div class="lbl">Archivés</div></div>
</div>

<div class="panel">
  <h3>Courriers en circuit de vérification</h3>
  <table>
    <thead><tr><th>Référence</th><th>Objet</th><th>Service</th><th>Statut</th><th>Mise à jour</th></tr></thead>
    <tbody>
      <?php if (empty($recents)): ?>
        <tr class="empty-row"><td colspan="5">Aucun courrier pour le moment.</td></tr>
      <?php else: foreach ($recents as $c): ?>
        <tr>
          <td><strong><?= e($c['reference']) ?></strong></td>
          <td><?= e($c['objet']) ?></td>
          <td><?= e($c['service_cible'] ?: '—') ?><?= $c['division_cible'] ? ' / ' . e($c['division_cible']) : '' ?></td>
          <td><span class="badge <?= statut_badge_class($c['statut']) ?>"><?= e(statut_label($c['statut'])) ?></span></td>
          <td><?= format_date($c['updated_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="panel">
  <h3>Action rapide</h3>
  <a class="btn btn-gold" href="<?= root_url('/coordonnateur/verifier.php') ?>">✅ Vérifier les courriers reçus</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
