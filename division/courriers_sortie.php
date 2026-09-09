<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['division']);
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $courrierId = (int)($_POST['courrier_id'] ?? 0);
        $action = $_POST['do'] ?? '';
        $courrier = get_courrier($courrierId);
        if (!$courrier || $courrier['division_id'] != $u['id']) {
            throw new RuntimeException("Courrier introuvable pour cette division.");
        }
        if (empty($courrier['reference_depart'])) {
            throw new RuntimeException("Ce courrier n'a pas de référence de départ.");
        }

        if ($action === 'stocker' && $courrier['statut'] === 'sortie_division') {
            $db->prepare("UPDATE courriers SET statut = 'sortie_stockee', updated_at = NOW() WHERE id = ?")->execute([$courrierId]);
            add_historique($u['id'], $courrierId, 'stockage_sortie', "Stockage du courrier sortant {$courrier['reference']} (réf. départ {$courrier['reference_depart']}) dans la division {$u['division_code']}.");
            flash_set('success', "Courrier sortant {$courrier['reference']} stocké dans la division {$u['division_code']}.");
        } else {
            throw new RuntimeException("Action invalide pour ce courrier.");
        }
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    header('Location: ' . root_url('/division/courriers_sortie.php'));
    exit;
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $like = "%$search%";
    $stmt = $db->prepare("SELECT * FROM courriers WHERE division_id = ? AND statut IN ('sortie_division','sortie_stockee') AND (reference LIKE ? OR reference_depart LIKE ? OR objet LIKE ? OR expediteur LIKE ?) ORDER BY updated_at DESC");
    $stmt->execute([$u['id'], $like, $like, $like, $like]);
    $courriers = $stmt->fetchAll();
} else {
    $stmt = $db->prepare("SELECT * FROM courriers WHERE division_id = ? AND statut IN ('sortie_division','sortie_stockee') ORDER BY updated_at DESC");
    $stmt->execute([$u['id']]);
    $courriers = $stmt->fetchAll();
}

$pageTitle = "Courriers sortants — " . $u['division_code'];
$activeNav = 'recus_sortie';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers sortants (réf. départ) — stockage &amp; impression</h3>

  <form method="get" class="search-bar">
    <div class="field">
      <label>🔍 Rechercher un courrier sortant</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Référence arrivée, référence départ, objet ou expéditeur...">
    </div>
    <button type="submit" class="btn btn-primary">Rechercher</button>
    <?php if ($search !== ''): ?>
      <a href="<?= root_url('/division/courriers_sortie.php') ?>" class="clear-btn">✕ Effacer</a>
    <?php endif; ?>
  </form>

  <div class="table-wrap">
  <table>
    <thead><tr><th>Référence Arrivée</th><th>Référence Départ</th><th>Objet</th><th>PDF</th><th>Statut</th><th>Impression</th><th>Action</th></tr></thead>
    <tbody>
      <?php if (empty($courriers)): ?>
        <tr class="empty-row"><td colspan="7">Aucun courrier sortant pour cette division.</td></tr>
      <?php else: foreach ($courriers as $c): ?>
        <tr>
          <td><span class="ref-arrivee"><?= e($c['reference']) ?></span></td>
          <td><span class="ref-depart"><?= e($c['reference_depart']) ?></span></td>
          <td><?= e($c['objet']) ?></td>
          <td><?php if ($c['pdf_original']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_original'])) ?>" target="_blank">📄 Voir</a><?php else: ?>—<?php endif; ?></td>
          <td><span class="badge <?= statut_badge_class($c['statut']) ?>"><?= e(statut_label($c['statut'])) ?></span></td>
          <td>
            <a class="btn btn-outline btn-sm" href="<?= root_url('/division/fiche_imprimable.php?id=' . $c['id']) ?>" target="_blank">🖨️ Fiche</a>
          </td>
          <td>
            <?php if ($c['statut'] === 'sortie_division'): ?>
              <form method="post" style="display:inline;">
                <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
                <input type="hidden" name="do" value="stocker">
                <button type="submit" class="btn btn-green btn-sm">📦 Stocker</button>
              </form>
            <?php else: ?>
              <span class="ref-set-tag">Stocké</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
