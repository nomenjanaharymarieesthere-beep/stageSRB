<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['division']);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM courriers WHERE division_id = ? AND statut IN ('envoye_division','a_corriger') ORDER BY created_at ASC");
$stmt->execute([$u['id']]);
$aTraiter = $stmt->fetchAll();

$pageTitle = "Traiter un courrier";
$activeNav = 'traiter';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers à traiter</h3>
  <?php if (empty($aTraiter)): ?>
    <p style="color:var(--muted);">Aucun courrier à traiter pour le moment.</p>
  <?php else: foreach ($aTraiter as $c):
    $etapes = get_etapes($c['id']);
  ?>
    <div style="border:1px solid var(--border);border-radius:10px;padding:18px 20px;margin-bottom:16px;">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div>
          <strong style="font-size:15px;color:var(--navy-900);"><?= e($c['reference']) ?></strong> — <?= e($c['objet']) ?>
        </div>
        <span class="badge <?= statut_badge_class($c['statut']) ?>"><?= e(statut_label($c['statut'])) ?></span>
      </div>
      <?php if ($etapes): ?>
        <div style="margin-top:14px;">
          <div style="font-weight:700;font-size:13px;color:var(--navy-900);margin-bottom:10px;">📩 Messages &amp; échanges reçus :</div>
          <?php afficher_etapes($etapes); ?>
        </div>
      <?php endif; ?>
      <div style="margin-top:14px;">
        <?php if ($c['pdf_courant']): ?>
          <a class="pdf-link" href="<?= e(pdf_url($c['pdf_courant'])) ?>" target="_blank">📄 Télécharger / Imprimer le PDF</a>
        <?php endif; ?>
      </div>
      <div style="margin-top:14px;">
        <a class="btn btn-gold btn-sm" href="<?= root_url('/division/envoyer_coordonnateur.php?courrier_id=' . $c['id']) ?>">🛠️ Enregistrer le traitement &amp; envoyer</a>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
