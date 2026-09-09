<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['division']);
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $courrierId = (int)($_POST['courrier_id'] ?? 0);
        $courrier = get_courrier($courrierId);
        if (!$courrier || $courrier['division_id'] != $u['id'] || !in_array($courrier['statut'], ['envoye_division', 'a_corriger'], true)) {
            throw new RuntimeException("Ce courrier ne peut pas être traité actuellement.");
        }

        $pdfTraite = handle_pdf_upload('pdf_traite', 'div_' . $u['division_code']);
        if (!$pdfTraite) throw new RuntimeException("Veuillez téléverser le PDF du document traité.");

        // SRSP : les divisions envoient directement à la Secrétaire (sans Coordonnateur)
        if ($u['service'] === 'SRSP') {
            $secretaire = $db->query("SELECT * FROM users WHERE role = 'secretaire' LIMIT 1")->fetch();
            if (!$secretaire) throw new RuntimeException("Aucun compte Secrétaire trouvé.");

            transmettre_courrier([
                'courrier_id' => $courrierId,
                'from_user_id' => $u['id'], 'to_user_id' => $secretaire['id'],
                'from_role' => 'division', 'to_role' => 'secretaire',
                'action' => 'envoi_secretaire',
                'pdf_file' => $pdfTraite,
                'nouveau_statut' => 'envoye_secretaire',
                'pdf_courant' => $pdfTraite,
                'notif_message' => "La division {$u['division_code']} (SRSP) a transmis le courrier {$courrier['reference']} (réf. arrivée) au Secrétariat.",
                'hist_from' => "Envoi du courrier traité {$courrier['reference']} directement au Secrétariat.",
                'hist_to' => "Réception du courrier {$courrier['reference']} traité par la {$u['division_code']} (SRSP).",
            ]);

            flash_set('success', "Courrier {$courrier['reference']} envoyé au Secrétariat (réf. arrivée : {$courrier['reference']}).");
        } else {
            // SRB (et autres) : envoi au Coordonnateur
            $coordonnateur = $db->query("SELECT * FROM users WHERE role = 'coordonnateur' LIMIT 1")->fetch();
            if (!$coordonnateur) throw new RuntimeException("Aucun compte Coordonnateur trouvé.");

            $estCorrection = $courrier['statut'] === 'a_corriger';

            transmettre_courrier([
                'courrier_id' => $courrierId,
                'from_user_id' => $u['id'], 'to_user_id' => $coordonnateur['id'],
                'from_role' => 'division', 'to_role' => 'coordonnateur',
                'action' => $estCorrection ? 'renvoi_apres_correction' : 'envoi_coordonnateur',
                'pdf_file' => $pdfTraite,
                'nouveau_statut' => 'envoye_coordonnateur',
                'pdf_courant' => $pdfTraite,
                'notif_message' => "La {$u['division_code']} a " . ($estCorrection ? "renvoyé après correction" : "transmis") . " le courrier {$courrier['reference']} pour vérification.",
                'hist_from' => ($estCorrection ? "Renvoi après correction du courrier {$courrier['reference']} au Coordonnateur." : "Envoi du courrier traité {$courrier['reference']} au Coordonnateur."),
                'hist_to' => "Réception du courrier {$courrier['reference']} traité par la {$u['division_code']} pour vérification.",
            ]);

            flash_set('success', "Courrier {$courrier['reference']} envoyé au Coordonnateur pour vérification.");
        }
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    header('Location: ' . root_url('/division/traiter.php'));
    exit;
}

$courrierId = (int)($_GET['courrier_id'] ?? 0);
$courrier = get_courrier($courrierId);
if (!$courrier || $courrier['division_id'] != $u['id'] || !in_array($courrier['statut'], ['envoye_division', 'a_corriger'], true)) {
    flash_set('error', "Courrier introuvable ou déjà traité.");
    header('Location: ' . root_url('/division/traiter.php'));
    exit;
}

$estSrsp = ($u['service'] === 'SRSP');
$destinataire = $estSrsp ? 'la Secrétaire' : 'le Coordonnateur';

$pageTitle = $estSrsp ? "Envoyer au Secrétariat" : "Envoyer au Coordonnateur";
$activeNav = 'traiter';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel" style="max-width:520px;">
  <h3>Finaliser le traitement de <?= e($courrier['reference']) ?></h3>
  <p style="color:var(--muted);font-size:13.5px;"><?= e($courrier['objet']) ?></p>
  <?php if ($estSrsp): ?>
    <p style="background:var(--gold-100);border:1px solid var(--gold-400);border-radius:8px;padding:10px 14px;font-size:13px;color:#8a6515;">
      Votre division (SRSP) enverra ce dossier <strong>directement à la Secrétaire</strong>, sans passer par le Coordonnateur.
    </p>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="courrier_id" value="<?= $courrier['id'] ?>">
    <div class="form-row">
      <label>Document traité (PDF numérisé) *</label>
      <input type="file" name="pdf_traite" accept="application/pdf" required>
      <div class="hint">Une fois le dossier traité physiquement, numérisez-le et téléversez-le ici.</div>
    </div>
    <button type="submit" class="btn btn-gold">📤 Envoyer à <?= e($destinataire) ?></button>
    <a class="btn btn-outline" href="<?= root_url('/division/traiter.php') ?>">Annuler</a>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
