<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['chef_srsp']);
$db = getDB();
$divisions = divisions_srsp();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $courrierId = (int)($_POST['courrier_id'] ?? 0);
        $divCode = $_POST['division'] ?? '';
        $message = trim($_POST['message'] ?? '');
        $remarque = trim($_POST['remarque'] ?? '');
        $delai = trim($_POST['delai'] ?? '');

        $courrier = get_courrier($courrierId);
        if (!$courrier || !in_array($courrier['statut'], ['oriente_srsp', 'sortie_service'], true)) {
            throw new RuntimeException("Ce courrier ne peut pas être envoyé (statut invalide).");
        }
        if ($courrier['statut'] === 'sortie_service' && $courrier['service_cible'] !== 'SRSP') {
            throw new RuntimeException("Ce courrier sortant n'est pas destiné au SRSP.");
        }
        if (!array_key_exists($divCode, $divisions)) {
            throw new RuntimeException("Division invalide. Vous ne pouvez envoyer qu'aux divisions du SRSP.");
        }

        $divUser = $db->prepare("SELECT * FROM users WHERE role = 'division' AND service = 'SRSP' AND division_code = ? LIMIT 1");
        $divUser->execute([$divCode]);
        $divUser = $divUser->fetch();
        if (!$divUser) throw new RuntimeException("Compte de la division introuvable.");

        $estSortie = $courrier['statut'] === 'sortie_service';

        transmettre_courrier([
            'courrier_id' => $courrierId,
            'from_user_id' => $u['id'], 'to_user_id' => $divUser['id'],
            'from_role' => 'chef_srsp', 'to_role' => 'division',
            'action' => $estSortie ? 'envoi_division_sortie' : 'envoi_division',
            'message' => $message, 'remarque' => $remarque, 'delai' => $delai,
            'nouveau_statut' => $estSortie ? 'sortie_division' : 'envoye_division',
            'division_cible' => $divCode,
            'division_id' => $divUser['id'],
            'notif_message' => $estSortie
                ? "Le Chef SRSP vous a transmis le courrier sortant {$courrier['reference']} (réf. départ {$courrier['reference_depart']}) pour stockage."
                : "Le Chef SRSP vous a transmis le courrier {$courrier['reference']}.",
            'hist_from' => $estSortie
                ? "Envoi du courrier sortant {$courrier['reference']} à la {$divisions[$divCode]} pour stockage."
                : "Envoi du courrier {$courrier['reference']} à la {$divisions[$divCode]}.",
            'hist_to' => $estSortie
                ? "Réception du courrier sortant {$courrier['reference']} envoyé par le Chef SRSP pour stockage."
                : "Réception du courrier {$courrier['reference']} envoyé par le Chef SRSP.",
        ]);

        flash_set('success', "Courrier {$courrier['reference']} envoyé à {$divisions[$divCode]}.");
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    header('Location: ' . root_url('/chef_srsp/envoyer_division.php'));
    exit;
}

$entrants = $db->query("SELECT * FROM courriers WHERE statut = 'oriente_srsp' ORDER BY created_at ASC")->fetchAll();
$sortants = $db->query("SELECT * FROM courriers WHERE statut = 'sortie_service' AND service_cible = 'SRSP' ORDER BY created_at ASC")->fetchAll();

$pageTitle = "Envoyer à une division";
$activeNav = 'envoyer';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers reçus du Directeur — à répartir (entrants)</h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Référence Arrivée</th><th>Objet</th><th>PDF</th><th>Action</th></tr></thead>
    <tbody>
      <?php if (empty($entrants)): ?>
        <tr class="empty-row"><td colspan="4">Aucun courrier entrant en attente de répartition.</td></tr>
      <?php else: foreach ($entrants as $c): ?>
        <tr>
          <td><span class="ref-arrivee"><?= e($c['reference']) ?></span></td>
          <td><?= e($c['objet']) ?></td>
          <td><?php if ($c['pdf_original']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_original'])) ?>" target="_blank">📄 Voir</a><?php else: ?>—<?php endif; ?></td>
          <td><button class="btn btn-primary btn-sm" onclick="document.getElementById('m-<?= $c['id'] ?>').style.display='flex'">📤 Envoyer</button></td>
        </tr>
        <div class="modal-bg" id="m-<?= $c['id'] ?>" style="display:none;position:fixed;inset:0;background:rgba(11,29,51,.5);z-index:100;align-items:center;justify-content:center;">
          <div style="background:#fff;border-radius:12px;padding:24px;max-width:440px;width:92%;">
            <h3 style="margin-top:0;">Envoyer <?= e($c['reference']) ?> à une division</h3>
            <form method="post">
              <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
              <div class="form-row">
                <label>Division *</label>
                <select name="division" required>
                  <option value="">— Choisir une division —</option>
                  <?php foreach ($divisions as $code => $label): ?>
                    <option value="<?= e($code) ?>"><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-row"><label>Message</label><textarea name="message" placeholder="Instructions pour la division..."></textarea></div>
              <div class="form-row"><label>Remarque</label><textarea name="remarque" placeholder="Remarque particulière..."></textarea></div>
              <div class="form-row"><label>Délai de traitement</label><input type="text" name="delai" placeholder="Ex : 5 jours ouvrables"></div>
              <button type="submit" class="btn btn-gold">Confirmer l'envoi</button>
              <button type="button" class="btn btn-outline" onclick="document.getElementById('m-<?= $c['id'] ?>').style.display='none'">Annuler</button>
            </form>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="panel">
  <h3>Courriers sortants (réf. départ) — à répartir pour stockage</h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Référence Arrivée</th><th>Référence Départ</th><th>Objet</th><th>PDF</th><th>Action</th></tr></thead>
    <tbody>
      <?php if (empty($sortants)): ?>
        <tr class="empty-row"><td colspan="5">Aucun courrier sortant en attente de répartition.</td></tr>
      <?php else: foreach ($sortants as $c): ?>
        <tr>
          <td><span class="ref-arrivee"><?= e($c['reference']) ?></span></td>
          <td><span class="ref-depart"><?= e($c['reference_depart']) ?></span></td>
          <td><?= e($c['objet']) ?></td>
          <td><?php if ($c['pdf_original']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_original'])) ?>" target="_blank">📄 Voir</a><?php else: ?>—<?php endif; ?></td>
          <td><button class="btn btn-gold btn-sm" onclick="document.getElementById('s-<?= $c['id'] ?>').style.display='flex'">📤 Envoyer pour stockage</button></td>
        </tr>
        <div class="modal-bg" id="s-<?= $c['id'] ?>" style="display:none;position:fixed;inset:0;background:rgba(11,29,51,.5);z-index:100;align-items:center;justify-content:center;">
          <div style="background:#fff;border-radius:12px;padding:24px;max-width:440px;width:92%;">
            <h3 style="margin-top:0;">Envoyer <?= e($c['reference']) ?> (sortie) à une division</h3>
            <p style="color:var(--muted);font-size:13px;margin:0 0 14px;">Réf. départ : <strong><?= e($c['reference_depart']) ?></strong></p>
            <form method="post">
              <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
              <div class="form-row">
                <label>Division *</label>
                <select name="division" required>
                  <option value="">— Choisir une division —</option>
                  <?php foreach ($divisions as $code => $label): ?>
                    <option value="<?= e($code) ?>"><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-row"><label>Message</label><textarea name="message" placeholder="Instructions pour la division..."></textarea></div>
              <div class="form-row"><label>Remarque</label><textarea name="remarque" placeholder="Remarque particulière..."></textarea></div>
              <button type="submit" class="btn btn-gold">Confirmer l'envoi</button>
              <button type="button" class="btn btn-outline" onclick="document.getElementById('s-<?= $c['id'] ?>').style.display='none'">Annuler</button>
            </form>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
