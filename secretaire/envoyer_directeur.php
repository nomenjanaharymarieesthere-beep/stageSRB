<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['secretaire']);
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $courrierId = (int)($_POST['courrier_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $courrier = get_courrier($courrierId);
        if (!$courrier || $courrier['statut'] !== 'nouveau') {
            throw new RuntimeException("Ce courrier ne peut pas être envoyé (statut invalide).");
        }

        $directeur = $db->query("SELECT * FROM users WHERE role = 'drbf' LIMIT 1")->fetch();
        if (!$directeur) throw new RuntimeException("Aucun compte Directeur DRBF trouvé.");

        transmettre_courrier([
            'courrier_id' => $courrierId,
            'from_user_id' => $u['id'], 'to_user_id' => $directeur['id'],
            'from_role' => 'secretaire', 'to_role' => 'drbf',
            'action' => 'envoi_drbf', 'message' => $message,
            'nouveau_statut' => 'envoye_drbf',
            'notif_message' => "Nouveau courrier {$courrier['reference']} reçu du Secrétariat : {$courrier['objet']}.",
            'hist_from' => "Envoi du courrier {$courrier['reference']} au Directeur DRBF.",
            'hist_to' => "Réception du courrier {$courrier['reference']} envoyé par le Secrétariat.",
        ]);

        flash_set('success', "Courrier {$courrier['reference']} envoyé au Directeur avec succès.");
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    header('Location: ' . root_url('/secretaire/envoyer_directeur.php'));
    exit;
}

$enAttente = $db->query("SELECT * FROM courriers WHERE statut = 'nouveau' ORDER BY created_at ASC")->fetchAll();

$pageTitle = "Envoyer au Directeur";
$activeNav = 'envoyer';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers en attente d'envoi au Directeur DRBF</h3>
  <table>
    <thead><tr><th>Référence</th><th>Objet</th><th>Expéditeur</th><th>Date</th><th>PDF</th><th>Action</th></tr></thead>
    <tbody>
      <?php if (empty($enAttente)): ?>
        <tr class="empty-row"><td colspan="6">Aucun courrier en attente d'envoi.</td></tr>
      <?php else: foreach ($enAttente as $c): ?>
        <tr>
          <td><strong><?= e($c['reference']) ?></strong></td>
          <td><?= e($c['objet']) ?></td>
          <td><?= e($c['expediteur']) ?></td>
          <td><?= e($c['date_reception']) ?></td>
          <td><?php if ($c['pdf_original']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_original'])) ?>" target="_blank">📄 Voir</a><?php else: ?>—<?php endif; ?></td>
          <td>
            <button class="btn btn-primary btn-sm" onclick="document.getElementById('modal-<?= $c['id'] ?>').style.display='flex'">📤 Envoyer au Directeur</button>
          </td>
        </tr>
        <div class="modal-bg" id="modal-<?= $c['id'] ?>" style="display:none;position:fixed;inset:0;background:rgba(11,29,51,.5);z-index:100;align-items:center;justify-content:center;">
          <div style="background:#fff;border-radius:12px;padding:24px;max-width:420px;width:92%;">
            <h3 style="margin-top:0;">Envoyer <?= e($c['reference']) ?> au Directeur</h3>
            <form method="post">
              <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
              <div class="form-row">
                <label>Message d'accompagnement (optionnel)</label>
                <textarea name="message" placeholder="Note pour le Directeur..."></textarea>
              </div>
              <button type="submit" class="btn btn-gold">Confirmer l'envoi</button>
              <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-<?= $c['id'] ?>').style.display='none'">Annuler</button>
            </form>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
