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
        if (!$courrier || $courrier['statut'] !== 'sortie_drbf') {
            throw new RuntimeException("Ce courrier sortant ne peut pas être orienté (statut invalide).");
        }
        if (empty($courrier['reference_depart'])) {
            throw new RuntimeException("Ce courrier n'a pas de référence de départ.");
        }
        if (!in_array($service, ['SRB', 'SRSP', 'SRPE'], true)) {
            throw new RuntimeException("Veuillez choisir un service valide.");
        }

        $roleTarget = ['SRB' => 'chef_srb', 'SRSP' => 'chef_srsp', 'SRPE' => 'srpe'][$service];
        $chef = $db->prepare("SELECT * FROM users WHERE role = ? LIMIT 1");
        $chef->execute([$roleTarget]);
        $chef = $chef->fetch();
        if (!$chef) throw new RuntimeException("Aucun responsable trouvé pour le service $service.");

        transmettre_courrier([
            'courrier_id' => $courrierId,
            'from_user_id' => $u['id'], 'to_user_id' => $chef['id'],
            'from_role' => 'drbf', 'to_role' => $roleTarget,
            'action' => 'orientation_sortie', 'message' => $message,
            'nouveau_statut' => 'sortie_service',
            'service_cible' => $service,
            'chef_service_id' => $chef['id'],
            'notif_message' => "Le Directeur a orienté le courrier sortant {$courrier['reference']} (réf. départ {$courrier['reference_depart']}) vers votre service.",
            'hist_from' => "Orientation du courrier sortant {$courrier['reference']} vers $service.",
            'hist_to' => "Réception du courrier sortant {$courrier['reference']} orienté par le Directeur vers $service.",
        ]);

        flash_set('success', "Courrier sortant {$courrier['reference']} orienté vers $service.");
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    header('Location: ' . root_url('/drbf/courriers_sortie.php'));
    exit;
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $like = "%$search%";
    $stmt = $db->prepare("SELECT * FROM courriers WHERE statut IN ('sortie_drbf','sortie_service','sortie_division','sortie_stockee') AND (reference LIKE ? OR reference_depart LIKE ? OR objet LIKE ? OR expediteur LIKE ?) ORDER BY updated_at DESC");
    $stmt->execute([$like, $like, $like, $like]);
    $enAttente = $stmt->fetchAll();
} else {
    $enAttente = $db->query("SELECT * FROM courriers WHERE statut IN ('sortie_drbf','sortie_service','sortie_division','sortie_stockee') ORDER BY updated_at DESC")->fetchAll();
}

$pageTitle = "Courriers sortants (réf. départ)";
$activeNav = 'sortie';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers sortants — orienter vers un service</h3>

  <form method="get" class="search-bar">
    <div class="field">
      <label>🔍 Rechercher un courrier sortant</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Référence arrivée, référence départ, objet ou expéditeur...">
    </div>
    <button type="submit" class="btn btn-primary">Rechercher</button>
    <?php if ($search !== ''): ?>
      <a href="<?= root_url('/drbf/courriers_sortie.php') ?>" class="clear-btn">✕ Effacer</a>
    <?php endif; ?>
  </form>

  <div class="table-wrap">
  <table>
    <thead><tr><th>Référence Arrivée</th><th>Référence Départ</th><th>Objet</th><th>PDF</th><th>Statut</th><th>Action</th></tr></thead>
    <tbody>
      <?php if (empty($enAttente)): ?>
        <tr class="empty-row"><td colspan="6">Aucun courrier sortant à orienter.</td></tr>
      <?php else: foreach ($enAttente as $c): ?>
        <tr>
          <td><span class="ref-arrivee"><?= e($c['reference']) ?></span></td>
          <td><span class="ref-depart"><?= e($c['reference_depart']) ?></span></td>
          <td><?= e($c['objet']) ?></td>
          <td><?php if ($c['pdf_original']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_original'])) ?>" target="_blank">📄 Voir</a><?php else: ?>—<?php endif; ?></td>
          <td><span class="badge <?= statut_badge_class($c['statut']) ?>"><?= e(statut_label($c['statut'])) ?></span></td>
          <td>
            <?php if ($c['statut'] === 'sortie_drbf'): ?>
              <button class="btn btn-primary btn-sm" onclick="document.getElementById('m-<?= $c['id'] ?>').style.display='flex'">🧭 Orienter</button>
            <?php else: ?>
              <span class="ref-set-tag">En circuit</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php if ($c['statut'] === 'sortie_drbf'): ?>
        <div class="modal-bg" id="m-<?= $c['id'] ?>" style="display:none;position:fixed;inset:0;background:rgba(11,29,51,.5);z-index:100;align-items:center;justify-content:center;">
          <div style="background:#fff;border-radius:12px;padding:24px;max-width:420px;width:92%;">
            <h3 style="margin-top:0;">Orienter <?= e($c['reference']) ?> (sortie)</h3>
            <p style="color:var(--muted);font-size:13px;margin:0 0 14px;">
              Réf. départ : <strong><?= e($c['reference_depart']) ?></strong><br><?= e($c['objet']) ?>
            </p>
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
        <?php endif; ?>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
