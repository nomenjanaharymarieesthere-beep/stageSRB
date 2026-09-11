<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['secretaire']);
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $referenceOrigine = trim($_POST['reference_origine'] ?? '');
        $reference = trim($_POST['reference'] ?? '');
        $objet = trim($_POST['objet'] ?? '');
        $expediteur = trim($_POST['expediteur'] ?? '');
        $dateReception = trim($_POST['date_reception'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($referenceOrigine === '' || $reference === '' || $objet === '' || $expediteur === '' || $dateReception === '') {
            throw new RuntimeException("Veuillez remplir tous les champs obligatoires.");
        }

        $check = $db->prepare("SELECT id FROM courriers WHERE reference = ?");
        $check->execute([$reference]);
        if ($check->fetch()) {
            throw new RuntimeException("Cette référence d'arrivée existe déjà. Veuillez utiliser une référence unique.");
        }

        $pdfFile = handle_pdf_upload('pdf_courrier', 'original');

        $stmt = $db->prepare("INSERT INTO courriers
            (reference, reference_origine, objet, expediteur, date_reception, description, pdf_original, pdf_courant, statut, created_by)
            VALUES (?,?,?,?,?,?,?,?, 'nouveau', ?)");
        $stmt->execute([$reference, $referenceOrigine, $objet, $expediteur, $dateReception, $description, $pdfFile, $pdfFile, $u['id']]);
        $courrierId = (int)$db->lastInsertId();

        add_historique($u['id'], $courrierId, 'enregistrement', "Enregistrement du courrier $reference (réf. origine $referenceOrigine).");

        flash_set('success', "Courrier $reference enregistré avec succès (réf. origine : $referenceOrigine). Vous pouvez maintenant l'envoyer au Directeur.");
        header('Location: ' . root_url('/secretaire/enregistrer_courrier.php'));
        exit;
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        header('Location: ' . root_url('/secretaire/enregistrer_courrier.php'));
        exit;
    }
}

$courriers = $db->query("SELECT * FROM courriers ORDER BY created_at DESC")->fetchAll();

$pageTitle = "Enregistrer un courrier";
$activeNav = 'enregistrer';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Nouveau courrier reçu</h3>
  <form method="post" enctype="multipart/form-data">
    <div class="form-grid">
      <div class="form-row">
        <label>Référence d'origine (expéditeur) *</label>
        <input type="text" name="reference_origine" required placeholder="Ex : MINFIN/SG/2026-0142">
        <div class="hint">Référence attribuée par l'organisme ou la personne qui a envoyé le courrier.</div>
      </div>
      <div class="form-row">
        <label>Référence d'arrivée *</label>
        <input type="text" name="reference" required placeholder="Ex : DRBF-2026-0001">
        <div class="hint">Référence d'arrivée attribuée par la secrétaire lors de la réception et de l'enregistrement à la DRBF.</div>
      </div>
      <div class="form-row">
        <label>Objet du courrier *</label>
        <input type="text" name="objet" required placeholder="Ex : Demande de crédits supplémentaires">
      </div>
      <div class="form-row">
        <label>Expéditeur *</label>
        <input type="text" name="expediteur" required placeholder="Ex : Ministère des Finances">
      </div>
    </div>
    <div class="form-grid">
      <div class="form-row">
        <label>Date de réception *</label>
        <input type="date" name="date_reception" required value="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-row">
        <label>Fichier PDF</label>
        <input type="file" name="pdf_courrier" accept="application/pdf">
        <div class="hint">Optionnel — vous pouvez ajouter le PDF numérisé du courrier.</div>
      </div>
    </div>
    <div class="form-row">
      <label>Description / Remarques</label>
      <textarea name="description" placeholder="Détails complémentaires sur le courrier..."></textarea>
    </div>
    <button type="submit" class="btn btn-gold">✍️ Enregistrer le courrier</button>
  </form>
</div>

<div class="panel">
  <h3>Liste générale des courriers enregistrés</h3>
  <table>
    <thead><tr><th>Réf. d'arrivée</th><th>Réf. d'origine</th><th>Objet</th><th>Expéditeur</th><th>Date réception</th><th>PDF</th><th>Statut</th></tr></thead>
    <tbody>
      <?php if (empty($courriers)): ?>
        <tr class="empty-row"><td colspan="7">Aucun courrier enregistré pour le moment.</td></tr>
      <?php else: foreach ($courriers as $c): ?>
        <tr>
          <td><strong><?= e($c['reference']) ?></strong></td>
          <td><span class="ref-empty"><?= $c['reference_origine'] ? e($c['reference_origine']) : '—' ?></span></td>
          <td><?= e($c['objet']) ?></td>
          <td><?= e($c['expediteur']) ?></td>
          <td><?= e($c['date_reception']) ?></td>
          <td><?php if ($c['pdf_original']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_original'])) ?>" target="_blank">📄 Voir</a><?php else: ?>—<?php endif; ?></td>
          <td><span class="badge <?= statut_badge_class($c['statut']) ?>"><?= e(statut_label($c['statut'])) ?></span></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
