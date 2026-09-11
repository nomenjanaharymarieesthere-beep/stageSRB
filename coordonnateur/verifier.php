<?php
require_once __DIR__ . '/../includes/fonctions.php';
$u = require_role(['coordonnateur']);
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $courrierId = (int)($_POST['courrier_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        $remarque = trim($_POST['remarque'] ?? '');
        $courrier = get_courrier($courrierId);
        if (!$courrier || $courrier['statut'] !== 'envoye_coordonnateur') {
            throw new RuntimeException("Ce courrier ne peut pas être vérifié actuellement.");
        }

        if ($decision === 'valider') {
            // Trouver le Chef de Service correspondant au service cible
            $chefMap = [
                'SRB'  => ['role' => 'chef_srb',  'label' => 'Chef SRB'],
                'SRSP' => ['role' => 'chef_srsp', 'label' => 'Chef SRSP'],
                'SRPE' => ['role' => 'srpe',      'label' => 'Chef SRPE'],
            ];
            $t = $chefMap[$courrier['service_cible']] ?? null;
            if (!$t) throw new RuntimeException("Service cible du courrier introuvable.");

            $chef = $db->prepare("SELECT * FROM users WHERE role = ? LIMIT 1");
            $chef->execute([$t['role']]);
            $chef = $chef->fetch();
            if (!$chef) throw new RuntimeException("Aucun compte {$t['label']} trouvé.");

            transmettre_courrier([
                'courrier_id' => $courrierId,
                'from_user_id' => $u['id'], 'to_user_id' => $chef['id'],
                'from_role' => 'coordonnateur', 'to_role' => $t['role'],
                'action' => 'validation', 'remarque' => $remarque,
                'nouveau_statut' => 'valide_chef',
                'notif_message' => "Le Coordonnateur a validé le courrier {$courrier['reference']} — consultez le dossier et transmettez-le au DRBF pour validation finale.",
                'hist_from' => "Validation du courrier {$courrier['reference']} — renvoyé au {$t['label']}.",
                'hist_to' => "Réception du courrier {$courrier['reference']} validé par le Coordonnateur.",
            ]);
            flash_set('success', "Courrier {$courrier['reference']} validé et renvoyé au {$t['label']} pour transmission au DRBF.");

        } elseif ($decision === 'retourner') {
            if ($remarque === '') throw new RuntimeException("Veuillez indiquer une remarque expliquant les erreurs à corriger.");

            if ($courrier['division_id']) {
                $destId = $courrier['division_id'];
                $destRole = 'division';
                $destLabel = $courrier['division_cible'];
            } elseif ($courrier['service_cible'] === 'SRPE') {
                $srpe = $db->query("SELECT * FROM users WHERE role = 'srpe' LIMIT 1")->fetch();
                if (!$srpe) throw new RuntimeException("Aucun compte SRPE trouvé.");
                $destId = $srpe['id'];
                $destRole = 'srpe';
                $destLabel = 'SRPE';
            } else {
                throw new RuntimeException("Impossible de déterminer le destinataire de la correction.");
            }

            transmettre_courrier([
                'courrier_id' => $courrierId,
                'from_user_id' => $u['id'], 'to_user_id' => $destId,
                'from_role' => 'coordonnateur', 'to_role' => $destRole,
                'action' => 'retour_correction', 'remarque' => $remarque,
                'nouveau_statut' => 'a_corriger',
                'notif_message' => "Le Coordonnateur a retourné le courrier {$courrier['reference']} pour correction.",
                'hist_from' => "Retour pour correction du courrier {$courrier['reference']} à $destLabel.",
                'hist_to' => "Le courrier {$courrier['reference']} a été retourné pour correction par le Coordonnateur.",
            ]);
            flash_set('success', "Courrier {$courrier['reference']} retourné pour correction.");
        } else {
            throw new RuntimeException("Décision invalide.");
        }
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    header('Location: ' . root_url('/coordonnateur/verifier.php'));
    exit;
}

$aVerifier = $db->query("SELECT * FROM courriers WHERE statut = 'envoye_coordonnateur' ORDER BY created_at ASC")->fetchAll();

$pageTitle = "Vérifier un courrier";
$activeNav = 'verifier';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <h3>Courriers à vérifier</h3>
  <table>
    <thead><tr><th>Référence</th><th>Objet</th><th>Provenance</th><th>PDF</th><th>Action</th></tr></thead>
    <tbody>
      <?php if (empty($aVerifier)): ?>
        <tr class="empty-row"><td colspan="5">Aucun courrier en attente de vérification.</td></tr>
      <?php else: foreach ($aVerifier as $c): ?>
        <tr>
          <td><strong><?= e($c['reference']) ?></strong></td>
          <td><?= e($c['objet']) ?></td>
          <td><?= e($c['division_cible'] ?: $c['service_cible']) ?></td>
          <td><?php if ($c['pdf_courant']): ?><a class="pdf-link" href="<?= e(pdf_url($c['pdf_courant'])) ?>" target="_blank">📄 Voir</a><?php else: ?>—<?php endif; ?></td>
          <td><button class="btn btn-primary btn-sm" onclick="document.getElementById('m-<?= $c['id'] ?>').style.display='flex'">✅ Vérifier</button></td>
        </tr>
        <div class="modal-bg" id="m-<?= $c['id'] ?>" style="display:none;position:fixed;inset:0;background:rgba(11,29,51,.5);z-index:100;align-items:center;justify-content:center;">
          <div style="background:#fff;border-radius:12px;padding:24px;max-width:460px;width:92%;">
            <h3 style="margin-top:0;">Vérification de <?= e($c['reference']) ?></h3>
            <p style="color:var(--muted);font-size:13.5px;"><?= e($c['objet']) ?></p>
            <form method="post">
              <input type="hidden" name="courrier_id" value="<?= $c['id'] ?>">
              <div class="form-row">
                <label>Remarque (obligatoire en cas de retour pour correction)</label>
                <textarea name="remarque" placeholder="Expliquez les erreurs à corriger..."></textarea>
              </div>
              <button type="submit" name="decision" value="valider" class="btn btn-green">✅ Valider le travail</button>
              <button type="submit" name="decision" value="retourner" class="btn btn-red">↩️ Retourner pour correction</button>
              <button type="button" class="btn btn-outline" onclick="document.getElementById('m-<?= $c['id'] ?>').style.display='none'">Annuler</button>
            </form>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
