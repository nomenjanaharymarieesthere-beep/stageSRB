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

        if ($action === 'ajouter_reference_depart') {
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

$pageTitle = "Courriers validés par le DRBF";
$activeNav = 'recus';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers validés par le DRBF — traitement final &amp; référence départ</h3>

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
    <thead><tr><th>Réf. Arrivée</th><th>Réf. Origine</th><th>Référence Départ</th><th>Objet</th><th>Documents PDF</th><th>Statut</th><th>Conversation</th><th>Action</th></tr></thead>
    <tbody>
      <?php if (empty($courriers)): ?>
        <tr class="empty-row"><td colspan="8">Aucun courrier validé en attente.</td></tr>
      <?php else: foreach ($courriers as $c):
        $etapes = get_etapes($c['id']);
      ?>
        <tr>
          <td><span class="ref-arrivee"><?= e($c['reference']) ?></span></td>
          <td><span class="ref-empty"><?= $c['reference_origine'] ? e($c['reference_origine']) : '—' ?></span></td>
          <td>
            <?php if ($c['reference_depart']): ?>
              <span class="ref-depart"><?= e($c['reference_depart']) ?></span>
            <?php else: ?>
              <span class="ref-empty">Non définie</span>
            <?php endif; ?>
          </td>
          <td><?= e($c['objet']) ?></td>
          <td>
            <?php if ($c['pdf_courant']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_courant'])) ?>" target="_blank">📄 PDF validé</a><?php else: ?><span style="color:var(--muted);">—</span><?php endif; ?>
            <?php if ($c['pdf_complementaire']): ?><br><a class="pdf-link" href="<?= e(pdf_url($c['pdf_complementaire'])) ?>" target="_blank">📄 PDF complémentaire</a><?php endif; ?>
          </td>
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
          </td>
        </tr>
        <tr id="conv-<?= $c['id'] ?>" style="display:none;">
          <td></td>
          <td colspan="7" style="padding-top:0;">
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
