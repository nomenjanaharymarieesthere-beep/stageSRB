<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['srpe']);
$db = getDB();

$courriers = $db->query("SELECT * FROM courriers WHERE service_cible = 'SRPE' ORDER BY created_at DESC")->fetchAll();

$pageTitle = "Courriers reçus";
$activeNav = 'recus';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers orientés vers le SRPE</h3>
  <table>
    <thead><tr><th>Référence</th><th>Objet</th><th>PDF</th><th>Statut</th><th>Conversation</th></tr></thead>
    <tbody>
      <?php if (empty($courriers)): ?>
        <tr class="empty-row"><td colspan="5">Aucun courrier reçu pour le SRPE.</td></tr>
      <?php else: foreach ($courriers as $c):
        $etapes = get_etapes($c['id']);
      ?>
        <tr>
          <td><strong><?= e($c['reference']) ?></strong></td>
          <td><?= e($c['objet']) ?></td>
          <td><?php if ($c['pdf_courant']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_courant'])) ?>" target="_blank">📄 Voir</a><?php else: ?>—<?php endif; ?></td>
          <td><span class="badge <?= statut_badge_class($c['statut']) ?>"><?= e(statut_label($c['statut'])) ?></span></td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="var x=document.getElementById('conv-<?= $c['id'] ?>');x.style.display=(x.style.display==='none'?'':'none')">💬 Voir la conversation</button>
          </td>
        </tr>
        <tr id="conv-<?= $c['id'] ?>" style="display:none;">
          <td></td>
          <td colspan="4" style="padding-top:0;">
            <div style="font-weight:700;font-size:12.5px;color:var(--navy-900);margin:2px 0 8px;">Tous les messages &amp; échanges :</div>
            <?php afficher_etapes($etapes); ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
