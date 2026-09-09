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

        if ($action === 'signature' && in_array($courrier['statut'], ['valide', 'envoye_secretaire'], true)) {
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
        } elseif ($action === 'ajouter_reference_depart') {
            $referenceDepart = trim($_POST['reference_depart'] ?? '');
            if ($referenceDepart === '') {
                throw new RuntimeException("Veuillez saisir une référence de départ.");
            }
            if (!in_array($courrier['statut'], ['valide', 'en_signature', 'envoye_secretaire'], true)) {
                throw new RuntimeException("Impossible d'ajouter une référence de départ pour ce courrier.");
            }
            set_reference_depart($courrierId, $referenceDepart);
            add_historique($u['id'], $courrierId, 'ajout_ref_depart', "Ajout de la référence de départ $referenceDepart au courrier {$courrier['reference']}.");
            flash_set('success', "Référence de départ $referenceDepart ajoutée au courrier {$courrier['reference']}.");
        } elseif ($action === 'envoyer_sortie_drbf') {
            if (empty($courrier['reference_depart'])) {
                throw new RuntimeException("Ce courrier n'a pas de référence de départ. Ajoutez-la d'abord.");
            }
            if ($courrier['statut'] === 'sortie_drbf') {
                throw new RuntimeException("Ce courrier a déjà été envoyé au Directeur pour la sortie.");
            }
            $directeur = $db->query("SELECT * FROM users WHERE role = 'drbf' LIMIT 1")->fetch();
            if (!$directeur) throw new RuntimeException("Aucun compte Directeur DRBF trouvé.");
            transmettre_courrier([
                'courrier_id' => $courrierId,
                'from_user_id' => $u['id'], 'to_user_id' => $directeur['id'],
                'from_role' => 'secretaire', 'to_role' => 'drbf',
                'action' => 'envoi_sortie_drbf',
                'nouveau_statut' => 'sortie_drbf',
                'notif_message' => "Courrier sortant {$courrier['reference']} (réf. départ {$courrier['reference_depart']}) envoyé au Directeur par le Secrétariat.",
                'hist_from' => "Envoi du courrier sortant {$courrier['reference']} (réf. départ {$courrier['reference_depart']}) au Directeur.",
                'hist_to' => "Réception du courrier sortant {$courrier['reference']} (réf. départ {$courrier['reference_depart']}).",
            ]);
            flash_set('success', "Courrier sortant {$courrier['reference']} (réf. départ) envoyé au Directeur.");
        } else {
            throw new RuntimeException("Action invalide pour ce courrier.");
        }
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    header('Location: ' . root_url('/secretaire/courriers_recus.php'));
    exit;
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $like = "%$search%";
    $stmt = $db->prepare("SELECT * FROM courriers WHERE statut IN ('valide','en_signature','envoye_secretaire') AND (reference LIKE ? OR reference_depart LIKE ? OR objet LIKE ? OR expediteur LIKE ?) ORDER BY updated_at DESC");
    $stmt->execute([$like, $like, $like, $like]);
    $courriers = $stmt->fetchAll();
} else {
    $courriers = $db->query("SELECT * FROM courriers WHERE statut IN ('valide','en_signature','envoye_secretaire') ORDER BY updated_at DESC")->fetchAll();
}

$pageTitle = "Courriers validés et reçus des divisions";
$activeNav = 'recus';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers validés / reçus — traitement final &amp; signature</h3>

  <form method="get" class="search-bar">
    <div class="field">
      <label>🔍 Rechercher un courrier</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Référence arrivée, référence départ, objet ou expéditeur...">
    </div>
    <button type="submit" class="btn btn-primary">Rechercher</button>
    <?php if ($search !== ''): ?>
      <a href="<?= root_url('/secretaire/courriers_recus.php') ?>" class="clear-btn">✕ Effacer</a>
    <?php endif; ?>
  </form>

  <div class="table-wrap">
  <table>
    <thead><tr><th>Référence Arrivée</th><th>Référence Départ</th><th>Objet</th><th>PDF validé</th><th>Statut</th><th>Conversation</th><th>Action</th></tr></thead>
    <tbody>
      <?php if (empty($courriers)): ?>
        <tr class="empty-row"><td colspan="7">Aucun courrier validé en attente.</td></tr>
      <?php else: foreach ($courriers as $c):
        $etapes = get_etapes($c['id']);
      ?>
        <tr>
          <td><span class="ref-arrivee"><?= e($c['reference']) ?></span></td>
          <td>
            <?php if ($c['reference_depart']): ?>
              <span class="ref-depart"><?= e($c['reference_depart']) ?></span>
            <?php else: ?>
              <span class="ref-empty">Non définie</span>
            <?php endif; ?>
          </td>
          <td><?= e($c['objet']) ?></td>
          <td><?php if ($c['pdf_courant']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_courant'])) ?>" target="_blank">📄 Télécharger</a><?php else: ?>—<?php endif; ?></td>
          <td><span class="badge <?= statut_badge_class($c['statut']) ?>"><?= e(statut_label($c['statut'])) ?></span></td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="var x=document.getElementById('conv-<?= $c['id'] ?>');x.style.display=(x.style.display==='none'?'':'none')">💬 Voir</button>
          </td>
          <td>
            <?php if (!$c['reference_depart'] && in_array($c['statut'], ['valide', 'en_signature', 'envoye_secretaire'], true)): ?>
              <button class="btn btn-gold btn-sm" onclick="document.getElementById('refd-<?= $c['id'] ?>').style.display='flex'">📤 Ajouter Réf. Départ</button>
              <div class="modal-bg" id="refd-<?= $c['id'] ?>" style="display:none;position:fixed;inset:0;background:rgba(11,29,51,.5);z-index:100;align-items:center;justify-content:center;">
                <div style="background:#fff;border-radius:12px;padding:24px;max-width:420px;width:92%;">
                  <h3 style="margin-top:0;">Ajouter une référence de départ</h3>
                  <p style="color:var(--muted);font-size:13px;margin:0 0 14px;">
                    Courrier : <strong><?= e($c['reference']) ?></strong><br><?= e($c['objet']) ?>
                  </p>
                  <form method="post">
                    <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
                    <input type="hidden" name="do" value="ajouter_reference_depart">
                    <div class="form-row">
                      <label>Référence de départ *</label>
                      <input type="text" name="reference_depart" required placeholder="Ex : DEP-2026-0001">
                      <div class="hint">Attribuée lorsque le courrier est envoyé/remis à un destinataire externe.</div>
                    </div>
                    <button type="submit" class="btn btn-gold">Enregistrer la référence</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('refd-<?= $c['id'] ?>').style.display='none'">Annuler</button>
                  </form>
                </div>
              </div>
            <?php elseif ($c['reference_depart']): ?>
              <span class="ref-set-tag">Définie</span>
            <?php endif; ?>
            <?php if ($c['reference_depart'] && !in_array($c['statut'], ['sortie_drbf', 'sortie_service', 'sortie_division', 'sortie_stockee'], true)): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
                <input type="hidden" name="do" value="envoyer_sortie_drbf">
                <button type="submit" class="btn btn-primary btn-sm" title="Envoyer ce courrier sortant au Directeur (circuit de sortie)">📤 Envoyer au Directeur</button>
              </form>
            <?php endif; ?>
            <?php if (in_array($c['statut'], ['valide', 'envoye_secretaire'], true)): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
                <input type="hidden" name="do" value="signature">
                <button type="submit" class="btn btn-primary btn-sm">✒️ Envoyer pour signature</button>
              </form>
            <?php elseif ($c['statut'] === 'en_signature'): ?>
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
          <td colspan="6" style="padding-top:0;">
            <div style="font-weight:700;font-size:12.5px;color:var(--navy-900);margin:2px 0 8px;">Tous les messages &amp; échanges :</div>
            <?php afficher_etapes($etapes); ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
