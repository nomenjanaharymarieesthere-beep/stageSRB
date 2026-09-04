<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['secretaire']);
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $courrierId = (int)($_POST['courrier_id'] ?? 0);
        $action = $_POST['do'] ?? '';
        $courrier = get_courrier($courrierId);
        if (!$courrier) throw new RuntimeException("Courrier introuvable.");

        if ($action === 'signature' && $courrier['statut'] === 'valide') {
            $db->prepare("UPDATE courriers SET statut = 'en_signature', updated_at = NOW() WHERE id = ?")->execute([$courrierId]);
            add_historique($u['id'], $courrierId, 'signature_lancee', "Remise du courrier {$courrier['reference']} pour signature.");
            flash_set('success', "Courrier {$courrier['reference']} envoyé pour signature.");
        } elseif ($action === 'archiver' && $courrier['statut'] === 'en_signature') {
            $pdfSigne = handle_pdf_upload('pdf_signe', 'signe');
            if (!$pdfSigne) throw new RuntimeException("Veuillez téléverser le PDF signé.");
            $db->prepare("UPDATE courriers SET statut = 'archive', pdf_courant = ?, updated_at = NOW() WHERE id = ?")
               ->execute([$pdfSigne, $courrierId]);
            add_historique($u['id'], $courrierId, 'archivage', "Téléversement du document signé et archivage du courrier {$courrier['reference']}.");
            flash_set('success', "Courrier {$courrier['reference']} signé et archivé avec succès.");
        } else {
            throw new RuntimeException("Action invalide pour ce courrier.");
        }
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    header('Location: ' . root_url('/secretaire/courriers_recus.php'));
    exit;
}

$courriers = $db->query("SELECT * FROM courriers WHERE statut IN ('valide','en_signature') ORDER BY updated_at DESC")->fetchAll();

$pageTitle = "Courriers validés par le Coordonnateur";
$activeNav = 'recus';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers validés — traitement final &amp; signature</h3>
  <table>
    <thead><tr><th>Référence</th><th>Objet</th><th>PDF validé</th><th>Statut</th><th>Conversation</th><th>Action</th></tr></thead>
    <tbody>
      <?php if (empty($courriers)): ?>
        <tr class="empty-row"><td colspan="6">Aucun courrier validé en attente.</td></tr>
      <?php else: foreach ($courriers as $c):
        $etapes = get_etapes($c['id']);
      ?>
        <tr>
          <td><strong><?= e($c['reference']) ?></strong></td>
          <td><?= e($c['objet']) ?></td>
          <td><?php if ($c['pdf_courant']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_courant'])) ?>" target="_blank">📄 Télécharger</a><?php else: ?>—<?php endif; ?></td>
          <td><span class="badge <?= statut_badge_class($c['statut']) ?>"><?= e(statut_label($c['statut'])) ?></span></td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="var x=document.getElementById('conv-<?= $c['id'] ?>');x.style.display=(x.style.display==='none'?'':'none')">💬 Voir</button>
          </td>
          <td>
            <?php if ($c['statut'] === 'valide'): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
                <input type="hidden" name="do" value="signature">
                <button type="submit" class="btn btn-primary btn-sm">✒️ Envoyer pour signature</button>
              </form>
            <?php else: ?>
              <button class="btn btn-green btn-sm" onclick="document.getElementById('sig-<?= $c['id'] ?>').style.display='flex'">📤 Téléverser PDF signé</button>
              <div class="modal-bg" id="sig-<?= $c['id'] ?>" style="display:none;position:fixed;inset:0;background:rgba(11,29,51,.5);z-index:100;align-items:center;justify-content:center;">
                <div style="background:#fff;border-radius:12px;padding:24px;max-width:420px;width:92%;">
                  <h3 style="margin-top:0;">Archiver <?= e($c['reference']) ?></h3>
                  <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
                    <input type="hidden" name="do" value="archiver">
                    <div class="form-row">
                      <label>Document signé (PDF) *</label>
                      <input type="file" name="pdf_signe" accept="application/pdf" required>
                    </div>
                    <button type="submit" class="btn btn-gold">Archiver le courrier</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('sig-<?= $c['id'] ?>').style.display='none'">Annuler</button>
                  </form>
                </div>
              </div>
            <?php endif; ?>
          </td>
        </tr>
        <tr id="conv-<?= $c['id'] ?>" style="display:none;">
          <td></td>
          <td colspan="5" style="padding-top:0;">
            <div style="font-weight:700;font-size:12.5px;color:var(--navy-900);margin:2px 0 8px;">Tous les messages &amp; échanges :</div>
            <?php afficher_etapes($etapes); ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
