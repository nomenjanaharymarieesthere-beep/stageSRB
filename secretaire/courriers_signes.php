<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['secretaire']);
$db = getDB();

$courriers = $db->query("SELECT * FROM courriers WHERE statut = 'archive' ORDER BY updated_at DESC")->fetchAll();

$pageTitle = "Courriers signés & archivés";
$activeNav = 'signes';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Archives — courriers traités, signés et archivés</h3>
  <table>
    <thead><tr><th>Référence</th><th>Objet</th><th>Expéditeur</th><th>Date réception</th><th>Document final</th></tr></thead>
    <tbody>
      <?php if (empty($courriers)): ?>
        <tr class="empty-row"><td colspan="5">Aucun courrier archivé pour le moment.</td></tr>
      <?php else: foreach ($courriers as $c): ?>
        <tr>
          <td><strong><?= e($c['reference']) ?></strong></td>
          <td><?= e($c['objet']) ?></td>
          <td><?= e($c['expediteur']) ?></td>
          <td><?= e($c['date_reception']) ?></td>
          <td><?php if ($c['pdf_courant']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_courant'])) ?>" target="_blank">📄 Ouvrir</a><?php else: ?>—<?php endif; ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
