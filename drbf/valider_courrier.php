<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['drbf']);
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $courrierId = (int)($_POST['courrier_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $courrier = get_courrier($courrierId);
        if (!$courrier || $courrier['statut'] !== 'soumis_drbf') {
            throw new RuntimeException("Ce courrier ne peut pas être validé actuellement.");
        }

        $secretaire = $db->query("SELECT * FROM users WHERE role = 'secretaire' LIMIT 1")->fetch();
        if (!$secretaire) throw new RuntimeException("Aucun compte Secrétaire trouvé.");

        transmettre_courrier([
            'courrier_id' => $courrierId,
            'from_user_id' => $u['id'], 'to_user_id' => $secretaire['id'],
            'from_role' => 'drbf', 'to_role' => 'secretaire',
            'action' => 'validation_finale', 'message' => $message,
            'nouveau_statut' => 'valide',
            'notif_message' => "Le Directeur DRBF a validé le courrier {$courrier['reference']} — le dossier est transmis au Secrétariat pour traitement final.",
            'hist_from' => "Validation finale du courrier {$courrier['reference']} — transmis au Secrétariat.",
            'hist_to' => "Réception du courrier {$courrier['reference']} validé par le Directeur DRBF.",
        ]);

        flash_set('success', "Courrier {$courrier['reference']} validé et transmis au Secrétariat.");
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    header('Location: ' . root_url('/drbf/valider_courrier.php'));
    exit;
}

$aValider = $db->query("SELECT * FROM courriers WHERE statut = 'soumis_drbf' ORDER BY created_at ASC")->fetchAll();

$pageTitle = "Valider les courriers (validation finale)";
$activeNav = 'valider';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers transmis par les Chefs de Service — en attente de validation finale</h3>
  <?php if (empty($aValider)): ?>
    <p style="color:var(--muted);">Aucun courrier en attente de validation finale.</p>
  <?php else: foreach ($aValider as $c):
    $etapes = get_etapes($c['id']);
  ?>
    <div style="border:1px solid var(--border);border-radius:10px;padding:18px 20px;margin-bottom:16px;">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div>
          <strong style="font-size:15px;color:var(--navy-900);"><?= e($c['reference']) ?></strong> — <?= e($c['objet']) ?>
          <span class="badge <?= statut_badge_class($c['statut']) ?>" style="margin-left:8px;"><?= e(statut_label($c['statut'])) ?></span>
        </div>
        <button class="btn btn-green btn-sm" onclick="document.getElementById('v-<?= $c['id'] ?>').style.display='flex'">✅ Valider et transmettre à la Secrétaire</button>
      </div>
      <div style="margin-top:14px;display:flex;gap:12px;flex-wrap:wrap;">
        <?php if ($c['pdf_courant']): ?>
          <a class="pdf-link" href="<?= e(pdf_url($c['pdf_courant'])) ?>" target="_blank">📄 PDF traité (validé Coordonnateur)</a>
        <?php endif; ?>
        <?php if ($c['pdf_complementaire']): ?>
          <a class="pdf-link" href="<?= e(pdf_url($c['pdf_complementaire'])) ?>" target="_blank">📄 PDF complémentaire</a>
        <?php endif; ?>
      </div>
      <?php if ($etapes): ?>
        <div style="margin-top:14px;">
          <button class="btn btn-outline btn-sm" onclick="var x=document.getElementById('et-<?= $c['id'] ?>');x.style.display=(x.style.display==='none'?'':'none');">💬 Voir la conversation</button>
          <div id="et-<?= $c['id'] ?>" style="display:none;margin-top:8px;">
            <?php afficher_etapes($etapes); ?>
          </div>
        </div>
      <?php endif; ?>
      <div class="modal-bg" id="v-<?= $c['id'] ?>" style="display:none;position:fixed;inset:0;background:rgba(11,29,51,.5);z-index:100;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;padding:24px;max-width:460px;width:92%;">
          <h3 style="margin-top:0;">Valider <?= e($c['reference']) ?> ?</h3>
          <p style="color:var(--muted);font-size:13px;margin:0 0 14px;">
            Ce courrier sera transmis au Secrétariat avec les documents PDF pour traitement final (opération de référence départ, etc.).
          </p>
          <form method="post">
            <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
            <div class="form-row">
              <label>Message pour le Secrétariat (optionnel)</label>
              <textarea name="message" placeholder="Note pour la secrétaire..."></textarea>
            </div>
            <button type="submit" class="btn btn-gold">✅ Valider et transmettre</button>
            <button type="button" class="btn btn-outline" onclick="document.getElementById('v-<?= $c['id'] ?>').style.display='none'">Annuler</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
