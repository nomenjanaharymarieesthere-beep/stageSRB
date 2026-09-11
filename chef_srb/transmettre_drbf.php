<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['chef_srb']);
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $courrierId = (int)($_POST['courrier_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $courrier = get_courrier($courrierId);
        if (!$courrier || $courrier['service_cible'] !== 'SRB' || $courrier['statut'] !== 'valide_chef') {
            throw new RuntimeException("Ce courrier ne peut pas être transmis actuellement.");
        }

        $pdfComp = handle_pdf_upload('pdf_complementaire', 'comp_srb_' . $u['id']);
        if ($courrier['pdf_complementaire'] && !$pdfComp) {
            $pdfComp = $courrier['pdf_complementaire'];
        }

        $drbf = $db->query("SELECT * FROM users WHERE role = 'drbf' LIMIT 1")->fetch();
        if (!$drbf) throw new RuntimeException("Aucun compte Directeur DRBF trouvé.");

        transmettre_courrier([
            'courrier_id' => $courrierId,
            'from_user_id' => $u['id'], 'to_user_id' => $drbf['id'],
            'from_role' => 'chef_srb', 'to_role' => 'drbf',
            'action' => 'transmission_drbf', 'message' => $message,
            'nouveau_statut' => 'soumis_drbf',
            'pdf_complementaire' => $pdfComp,
            'notif_message' => "Le Chef SRB a transmis le courrier {$courrier['reference']}" . ($pdfComp && $pdfComp !== $courrier['pdf_complementaire'] ? " (avec PDF complémentaire)" : "") . " au DRBF pour validation finale.",
            'hist_from' => "Transmission du courrier {$courrier['reference']} au DRBF pour validation finale.",
            'hist_to' => "Réception du courrier {$courrier['reference']} du Chef SRB pour validation finale.",
        ]);

        flash_set('success', "Courrier {$courrier['reference']} transmis au DRBF pour validation finale.");
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    header('Location: ' . root_url('/chef_srb/transmettre_drbf.php'));
    exit;
}

$aValider = $db->prepare("SELECT * FROM courriers WHERE service_cible = 'SRB' AND statut = 'valide_chef' ORDER BY created_at ASC");
$aValider->execute();
$aValider = $aValider->fetchAll();

$pageTitle = "Transmettre au DRBF (validation finale)";
$activeNav = 'transmettre';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers validés par le Coordonnateur — prêts pour le DRBF</h3>
  <p style="color:var(--muted);font-size:13px;margin-bottom:16px;">
    Consultez le PDF validé par le Coordonnateur. Si nécessaire, ajoutez un PDF complémentaire au dossier, puis transmettez le tout au Directeur DRBF pour validation finale.
  </p>
  <?php if (empty($aValider)): ?>
    <p style="color:var(--muted);">Aucun courrier à transmettre au DRBF pour le moment.</p>
  <?php else: foreach ($aValider as $c):
    $etapes = get_etapes($c['id']);
  ?>
    <div style="border:1px solid var(--border);border-radius:10px;padding:18px 20px;margin-bottom:16px;">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div>
          <strong style="font-size:15px;color:var(--navy-900);"><?= e($c['reference']) ?></strong> — <?= e($c['objet']) ?>
          <span class="badge <?= statut_badge_class($c['statut']) ?>" style="margin-left:8px;"><?= e(statut_label($c['statut'])) ?></span>
        </div>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('t-<?= $c['id'] ?>').style.display='flex'">📤 Transmettre au DRBF</button>
      </div>
      <?php if ($etapes): ?>
        <div style="margin-top:14px;">
          <div style="font-weight:700;font-size:13px;color:var(--navy-900);margin-bottom:10px;">📩 Messages &amp; échanges reçus :</div>
          <?php afficher_etapes($etapes); ?>
        </div>
      <?php endif; ?>
      <div style="margin-top:14px;display:flex;gap:12px;flex-wrap:wrap;">
        <?php if ($c['pdf_courant']): ?>
          <a class="pdf-link" href="<?= e(pdf_url($c['pdf_courant'])) ?>" target="_blank">📄 PDF traité (validé par Coordonnateur)</a>
        <?php endif; ?>
        <?php if ($c['pdf_complementaire']): ?>
          <a class="pdf-link" href="<?= e(pdf_url($c['pdf_complementaire'])) ?>" target="_blank">📄 PDF complémentaire</a>
        <?php endif; ?>
      </div>
      <div class="modal-bg" id="t-<?= $c['id'] ?>" style="display:none;position:fixed;inset:0;background:rgba(11,29,51,.5);z-index:100;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;padding:24px;max-width:460px;width:92%;">
          <h3 style="margin-top:0;">Transmettre <?= e($c['reference']) ?> au DRBF</h3>
          <p style="color:var(--muted);font-size:13px;margin:0 0 14px;">
            Le dossier contient déjà le <strong>PDF validé par le Coordonnateur</strong>. Vous pouvez y ajouter un <strong>PDF complémentaire</strong> (facultatif) avant de transmettre au Directeur.
          </p>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
            <div class="form-row">
              <label>PDF complémentaire (facultatif)</label>
              <input type="file" name="pdf_complementaire" accept="application/pdf">
              <div class="hint">Document complémentaire ajouté par vos soins avant transmission au DRBF.</div>
            </div>
            <div class="form-row">
              <label>Message au Directeur (optionnel)</label>
              <textarea name="message" placeholder="Note pour le Directeur DRBF..."></textarea>
            </div>
            <button type="submit" class="btn btn-gold">📤 Confirmer la transmission</button>
            <button type="button" class="btn btn-outline" onclick="document.getElementById('t-<?= $c['id'] ?>').style.display='none'">Annuler</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
