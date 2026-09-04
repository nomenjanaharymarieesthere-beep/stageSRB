<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['chef_srb']);

$hist = get_historique_utilisateur($u['id']);

$pageTitle = "Mon historique";
$activeNav = 'historique';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Historique de mes actions</h3>
  <?php if (empty($hist)): ?>
    <p style="color:var(--muted);">Aucune action enregistrée pour le moment.</p>
  <?php else: ?>
    <div class="timeline">
      <?php foreach ($hist as $h): ?>
        <div class="timeline-item">
          <div class="ti-title"><?= e($h['reference'] ? $h['reference'] . ' — ' . $h['objet'] : ucfirst($h['action'])) ?></div>
          <div class="ti-meta"><?= format_date($h['created_at']) ?></div>
          <div class="ti-body"><?= e($h['description']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
