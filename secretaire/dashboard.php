<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['secretaire']);
$db = getDB();

$total = $db->query("SELECT COUNT(*) c FROM courriers")->fetch()['c'];
$nonEnvoyes = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'nouveau'")->fetch()['c'];
$enCours = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut NOT IN ('nouveau','archive')")->fetch()['c'];
$valides = $db->query("SELECT COUNT(*) c FROM courriers WHERE statut = 'valide'")->fetch()['c'];

$recents = $db->query("SELECT * FROM courriers ORDER BY created_at DESC LIMIT 50")->fetchAll();

$pageTitle = "Tableau de bord — Secrétaire";
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
  <div class="stat-card"><div class="num"><?= $total ?></div><div class="lbl">Courriers enregistrés</div></div>
  <div class="stat-card c-amber"><div class="num"><?= $nonEnvoyes ?></div><div class="lbl">En attente d'envoi au DRBF</div></div>
  <div class="stat-card c-blue"><div class="num"><?= $enCours ?></div><div class="lbl">En circuit de traitement</div></div>
  <div class="stat-card c-green"><div class="num"><?= $valides ?></div><div class="lbl">Validés — à traiter</div></div>
</div>

<div class="panel">
  <h3>Derniers courriers enregistrés</h3>
  <table>
    <thead><tr><th>Réf. d'arrivée</th><th>Réf. d'origine</th><th>Objet</th><th>Expéditeur</th><th>Date réception</th><th>Statut</th></tr></thead>
    <tbody>
      <?php if (empty($recents)): ?>
        <tr class="empty-row"><td colspan="6">Aucun courrier enregistré pour le moment.</td></tr>
      <?php else: foreach ($recents as $c): ?>
        <tr>
          <td><strong><?= e($c['reference']) ?></strong></td>
          <td><span class="ref-empty"><?= $c['reference_origine'] ? e($c['reference_origine']) : '—' ?></span></td>
          <td><?= e($c['objet']) ?></td>
          <td><?= e($c['expediteur']) ?></td>
          <td><?= e($c['date_reception']) ?></td>
          <td><span class="badge <?= statut_badge_class($c['statut']) ?>"><?= e(statut_label($c['statut'])) ?></span></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="panel">
  <h3>Actions rapides</h3>
  <a class="btn btn-gold" href="<?= root_url('/secretaire/enregistrer_courrier.php') ?>">✍️ Enregistrer un nouveau courrier</a>
  &nbsp;
  <a class="btn btn-outline" href="<?= root_url('/secretaire/envoyer_directeur.php') ?>">📤 Envoyer au Directeur</a>
  &nbsp;
  <a class="btn btn-outline" href="<?= root_url('/secretaire/courriers_recus.php') ?>">📥 Courriers validés à traiter</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
