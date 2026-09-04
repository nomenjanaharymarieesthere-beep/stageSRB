<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['srpe']);
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $courrierId = (int)($_POST['courrier_id'] ?? 0);
        $courrier = get_courrier($courrierId);
        if (!$courrier || !in_array($courrier['statut'], ['oriente_srpe', 'a_corriger'], true) || $courrier['service_cible'] !== 'SRPE') {
            throw new RuntimeException("Ce courrier ne peut pas être traité actuellement.");
        }

        $pdfTraite = handle_pdf_upload('pdf_traite', 'srpe');
        if (!$pdfTraite) throw new RuntimeException("Veuillez téléverser le PDF du document traité.");

        $coordonnateur = $db->query("SELECT * FROM users WHERE role = 'coordonnateur' LIMIT 1")->fetch();
        if (!$coordonnateur) throw new RuntimeException("Aucun compte Coordonnateur trouvé.");

        $estCorrection = $courrier['statut'] === 'a_corriger';

        transmettre_courrier([
            'courrier_id' => $courrierId,
            'from_user_id' => $u['id'], 'to_user_id' => $coordonnateur['id'],
            'from_role' => 'srpe', 'to_role' => 'coordonnateur',
            'action' => $estCorrection ? 'renvoi_apres_correction' : 'envoi_coordonnateur',
            'pdf_file' => $pdfTraite,
            'nouveau_statut' => 'envoye_coordonnateur',
            'pdf_courant' => $pdfTraite,
            'notif_message' => "Le Chef SRPE a " . ($estCorrection ? "renvoyé après correction" : "transmis") . " le courrier {$courrier['reference']} pour vérification.",
            'hist_from' => ($estCorrection ? "Renvoi après correction du courrier {$courrier['reference']} au Coordonnateur." : "Envoi du courrier traité {$courrier['reference']} au Coordonnateur."),
            'hist_to' => "Réception du courrier {$courrier['reference']} traité par le SRPE pour vérification.",
        ]);

        flash_set('success', "Courrier {$courrier['reference']} envoyé au Coordonnateur pour vérification.");
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    header('Location: ' . root_url('/srpe/traiter_courrier.php'));
    exit;
}

$aTraiter = $db->query("SELECT * FROM courriers WHERE service_cible = 'SRPE' AND statut IN ('oriente_srpe','a_corriger') ORDER BY created_at ASC")->fetchAll();

$pageTitle = "Traiter un courrier";
$activeNav = 'traiter';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers à traiter (nouveaux ou retournés pour correction)</h3>
  <table>
    <thead><tr><th>Référence</th><th>Objet</th><th>PDF à traiter</th><th>Statut</th><th>Action</th></tr></thead>
    <tbody>
      <?php if (empty($aTraiter)): ?>
        <tr class="empty-row"><td colspan="5">Aucun courrier à traiter pour le moment.</td></tr>
      <?php else: foreach ($aTraiter as $c): ?>
        <tr>
          <td><strong><?= e($c['reference']) ?></strong></td>
          <td><?= e($c['objet']) ?></td>
          <td><?php if ($c['pdf_courant']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_courant'])) ?>" target="_blank">📄 Voir / imprimer</a><?php else: ?>—<?php endif; ?></td>
          <td><span class="badge <?= statut_badge_class($c['statut']) ?>"><?= e(statut_label($c['statut'])) ?></span></td>
          <td><button class="btn btn-primary btn-sm" onclick="document.getElementById('m-<?= $c['id'] ?>').style.display='flex'">🛠️ Traiter</button></td>
        </tr>
        <?php
          $etapes = get_etapes($c['id']);
          if ($etapes):
        ?>
        <tr>
          <td></td>
          <td colspan="4" style="padding-top:0;">
            <div style="font-weight:700;font-size:12.5px;color:var(--navy-900);margin:2px 0 8px;">📩 Tous les messages &amp; échanges reçus :</div>
            <?php afficher_etapes($etapes); ?>
          </td>
        </tr>
        <?php endif; ?>
        <div class="modal-bg" id="m-<?= $c['id'] ?>" style="display:none;position:fixed;inset:0;background:rgba(11,29,51,.5);z-index:100;align-items:center;justify-content:center;">
          <div style="background:#fff;border-radius:12px;padding:24px;max-width:440px;width:92%;">
            <h3 style="margin-top:0;">Traiter <?= e($c['reference']) ?></h3>
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
              <div class="form-row">
                <label>Document traité (PDF) *</label>
                <input type="file" name="pdf_traite" accept="application/pdf" required>
                <div class="hint">Téléversez le document une fois le traitement physique terminé.</div>
              </div>
              <button type="submit" class="btn btn-gold">Envoyer au Coordonnateur</button>
              <button type="button" class="btn btn-outline" onclick="document.getElementById('m-<?= $c['id'] ?>').style.display='none'">Annuler</button>
            </form>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
