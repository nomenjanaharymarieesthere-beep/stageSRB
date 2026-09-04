<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['drbf']);
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $courrierId = (int)($_POST['courrier_id'] ?? 0);
        $service = $_POST['service'] ?? '';
        $message = trim($_POST['message'] ?? '');
        $courrier = get_courrier($courrierId);
        if (!$courrier || $courrier['statut'] !== 'envoye_drbf') {
            throw new RuntimeException("Ce courrier ne peut pas être orienté (statut invalide).");
        }
        if (!in_array($service, ['SRB', 'SRSP', 'SRPE'], true)) {
            throw new RuntimeException("Veuillez choisir un service valide.");
        }

        $roleTarget = ['SRB' => 'chef_srb', 'SRSP' => 'chef_srsp', 'SRPE' => 'srpe'][$service];
        $chef = $db->prepare("SELECT * FROM users WHERE role = ? LIMIT 1");
        $chef->execute([$roleTarget]);
        $chef = $chef->fetch();
        if (!$chef) throw new RuntimeException("Aucun responsable trouvé pour le service $service.");

        $statutMap = ['SRB' => 'oriente_srb', 'SRSP' => 'oriente_srsp', 'SRPE' => 'oriente_srpe'];

        transmettre_courrier([
            'courrier_id' => $courrierId,
            'from_user_id' => $u['id'], 'to_user_id' => $chef['id'],
            'from_role' => 'drbf', 'to_role' => $roleTarget,
            'action' => 'orientation', 'message' => $message,
            'nouveau_statut' => $statutMap[$service],
            'service_cible' => $service,
            'chef_service_id' => $chef['id'],
            'notif_message' => "Le Directeur a orienté le courrier {$courrier['reference']} vers votre service.",
            'hist_from' => "Orientation du courrier {$courrier['reference']} vers $service.",
            'hist_to' => "Réception du courrier {$courrier['reference']} orienté par le Directeur vers $service.",
        ]);

        flash_set('success', "Courrier {$courrier['reference']} orienté vers $service.");
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    header('Location: ' . root_url('/drbf/orienter_courrier.php'));
    exit;
}

$enAttente = $db->query("SELECT * FROM courriers WHERE statut = 'envoye_drbf' ORDER BY created_at ASC")->fetchAll();

$pageTitle = "Orienter un courrier";
$activeNav = 'orienter';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers en attente d'orientation</h3>
  <table>
    <thead><tr><th>Référence</th><th>Objet</th><th>Expéditeur</th><th>PDF</th><th>Action</th></tr></thead>
    <tbody>
      <?php if (empty($enAttente)): ?>
        <tr class="empty-row"><td colspan="5">Aucun courrier en attente d'orientation.</td></tr>
      <?php else: foreach ($enAttente as $c): ?>
        <tr>
          <td><strong><?= e($c['reference']) ?></strong></td>
          <td><?= e($c['objet']) ?></td>
          <td><?= e($c['expediteur']) ?></td>
          <td><?php if ($c['pdf_original']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_original'])) ?>" target="_blank">📄 Voir</a><?php else: ?>—<?php endif; ?></td>
          <td>
            <button class="btn btn-primary btn-sm" onclick="document.getElementById('m-<?= $c['id'] ?>').style.display='flex'">🧭 Orienter</button>
          </td>
        </tr>
        <div class="modal-bg" id="m-<?= $c['id'] ?>" style="display:none;position:fixed;inset:0;background:rgba(11,29,51,.5);z-index:100;align-items:center;justify-content:center;">
          <div style="background:#fff;border-radius:12px;padding:24px;max-width:420px;width:92%;">
            <h3 style="margin-top:0;">Orienter <?= e($c['reference']) ?></h3>
            <form method="post">
              <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
              <div class="form-row">
                <label>Service compétent *</label>
                <select name="service" required>
                  <option value="">— Choisir un service —</option>
                  <option value="SRB">Service Régional du Budget (SRB)</option>
                  <option value="SRSP">Service Régional du Solde et Pension (SRSP)</option>
                  <option value="SRPE">Service Régional du Patrimoine de l'État (SRPE)</option>
                </select>
              </div>
              <div class="form-row">
                <label>Instruction / message (optionnel)</label>
                <textarea name="message" placeholder="Instructions pour le service..."></textarea>
              </div>
              <button type="submit" class="btn btn-gold">Confirmer l'orientation</button>
              <button type="button" class="btn btn-outline" onclick="document.getElementById('m-<?= $c['id'] ?>').style.display='none'">Annuler</button>
            </form>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
