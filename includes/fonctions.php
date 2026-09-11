<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

/* =========================================================
   BASE URL / CHEMIN
========================================================= */
function base_url(): string {
    if (!function_exists('__drbf_base_url')) {
        function __drbf_base_url(): string {
            // Premier segment de SCRIPT_NAME = dossier d'installation (ex: /DRBF)
            $segments = explode('/', trim($_SERVER['SCRIPT_NAME'] ?? '', '/'));
            return '/' . ($segments[0] ?? '');
        }
    }
    return __drbf_base_url();
}
// Racine du site (dossier DRBF/), utile depuis les sous-dossiers de rôle.
function root_url(string $path = ''): string {
    return base_url() . '/' . ltrim($path, '/');
}

/* =========================================================
   AUTHENTIFICATION
========================================================= */
function current_user(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    static $u = null;
    if ($u !== null) return $u;
    $stmt = getDB()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch() ?: null;
    return $u;
}

function require_login(): array {
    $u = current_user();
    if (!$u) {
        header('Location: ' . root_url('login.php'));
        exit;
    }
    return $u;
}

function require_role(array $roles): array {
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        die("Accès refusé : vous n'avez pas l'autorisation d'accéder à cette page.");
    }
    return $u;
}

function login_attempt(string $username, string $password): ?array
{
    $db = getDB();

    $stmt = $db->prepare("
        SELECT *
        FROM users
        WHERE username = :username
          AND actif = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':username' => $username
    ]);

    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        return null;
    }

    if (!password_verify($password, $u['password'])) {
        return null;
    }

    // Sécurité : nouvelle session après connexion
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$u['id'];
    $_SESSION['user_role'] = $u['role'];

    return $u;
}

function logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

/* =========================================================
   RÔLES : libellés, couleurs, dashboard associé
========================================================= */
function role_label(string $role): string {
    $labels = [
        'secretaire'    => 'Secrétaire',
        'drbf'          => 'Directeur Régional du Budget et des Finances',
        'chef_srb'      => 'Chef Service Régional du Budget',
        'chef_srsp'     => 'Chef Service Régional du Solde et Pension',
        'srpe'          => 'Chef Service Régional du Patrimoine de l\'État',
        'division'      => 'Division',
        'coordonnateur' => 'Coordonnateur',
    ];
    return $labels[$role] ?? $role;
}

function role_dashboard_path(string $role): string {
    $map = [
        'secretaire'    => '/secretaire/dashboard.php',
        'drbf'          => '/drbf/dashboard.php',
        'chef_srb'      => '/chef_srb/dashboard.php',
        'chef_srsp'     => '/chef_srsp/dashboard.php',
        'srpe'          => '/srpe/dashboard.php',
        'division'      => '/division/dashboard.php',
        'coordonnateur' => '/coordonnateur/dashboard.php',
    ];
    return $map[$role] ?? '/login.php';
}

/* =========================================================
   STATUTS DE COURRIER : libellés + badges
========================================================= */
function statut_label(string $statut): string {
    $labels = [
        'nouveau'               => 'Nouveau (non envoyé)',
        'envoye_drbf'           => 'Envoyé au Directeur',
        'oriente_srb'           => 'Orienté vers SRB',
        'oriente_srsp'          => 'Orienté vers SRSP',
        'oriente_srpe'          => 'Orienté vers SRPE',
        'envoye_division'       => 'Envoyé à la division',
        'envoye_coordonnateur'  => 'En vérification (Coordonnateur)',
        'envoye_secretaire'     => 'Traité - envoyé au Secrétaire',
        'a_corriger'            => 'Retourné pour correction',
        'valide_chef'           => 'Validé par le Coordonnateur — chez le Chef de Service',
        'soumis_drbf'           => 'Transmis au DRBF pour validation finale',
        'valide'                => 'Validé par le DRBF - transmis à la Secrétaire',
        'en_signature'          => 'En attente de signature',
        'archive'               => 'Traité, signé et archivé',
        // Circuit de sortie (courrier avec référence départ)
        'sortie_drbf'           => 'Sortie - envoyé au Directeur',
        'sortie_service'        => 'Sortie - envoyé au service',
        'sortie_division'       => 'Sortie - envoyé à la division',
        'sortie_stockee'        => 'Sortie - stocké dans la division',
    ];
    return $labels[$statut] ?? $statut;
}

function statut_badge_class(string $statut): string {
    $map = [
        'nouveau'              => 'badge-gray',
        'envoye_drbf'          => 'badge-blue',
        'oriente_srb'          => 'badge-blue',
        'oriente_srsp'         => 'badge-blue',
        'oriente_srpe'         => 'badge-blue',
        'envoye_division'      => 'badge-indigo',
        'envoye_coordonnateur' => 'badge-amber',
        'envoye_secretaire'    => 'badge-green',
        'a_corriger'           => 'badge-red',
        'valide_chef'          => 'badge-teal',
        'soumis_drbf'          => 'badge-blue',
        'valide'               => 'badge-green',
        'en_signature'         => 'badge-teal',
        'archive'              => 'badge-dark-green',
        'sortie_drbf'          => 'badge-blue',
        'sortie_service'       => 'badge-indigo',
        'sortie_division'      => 'badge-amber',
        'sortie_stockee'       => 'badge-dark-green',
    ];
    return $map[$statut] ?? 'badge-gray';
}

/* =========================================================
   HISTORIQUE / NOTIFICATIONS
========================================================= */
function add_historique(int $userId, ?int $courrierId, string $action, string $description): void {
    $stmt = getDB()->prepare("INSERT INTO historique (user_id, courrier_id, action, description) VALUES (?,?,?,?)");
    $stmt->execute([$userId, $courrierId, $action, $description]);
}

function add_notification(int $userId, ?int $courrierId, string $message): void {
    $stmt = getDB()->prepare("INSERT INTO notifications (user_id, courrier_id, message) VALUES (?,?,?)");
    $stmt->execute([$userId, $courrierId, $message]);
}

function unread_notifications_count(int $userId): int {
    $stmt = getDB()->prepare("SELECT COUNT(*) c FROM notifications WHERE user_id = ? AND lu = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetch()['c'];
}

function recent_notifications(int $userId, int $limit = 8): array {
    $stmt = getDB()->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function mark_notifications_read(int $userId): void {
    $stmt = getDB()->prepare("UPDATE notifications SET lu = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
}

/* =========================================================
   UPLOAD PDF
========================================================= */
function handle_pdf_upload(string $inputName, ?string $prefix = 'courrier'): ?string {
    if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) return null;
    $file = $_FILES[$inputName];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        throw new RuntimeException("Seuls les fichiers PDF sont acceptés.");
    }
    $dir = __DIR__ . '/../uploads/courriers/';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $filename = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        throw new RuntimeException("Échec du téléversement du fichier.");
    }
    return $filename;
}

function pdf_url(?string $filename): ?string {
    if (!$filename) return null;
    return root_url('uploads/courriers/' . $filename);
}

/* =========================================================
   GÉNÉRATION DE RÉFÉRENCE COURRIER
========================================================= */
function generer_reference(): string {
    $annee = date('Y');
    $stmt = getDB()->prepare("SELECT COUNT(*) c FROM courriers WHERE reference LIKE ?");
    $stmt->execute(["DRBF-$annee-%"]);
    $count = (int)$stmt->fetch()['c'] + 1;
    return sprintf("DRBF-%s-%04d", $annee, $count);
}

function set_reference_depart(int $courrierId, string $referenceDepart): void {
    $db = getDB();
    $check = $db->prepare("SELECT id FROM courriers WHERE reference_depart = ? AND id != ?");
    $check->execute([$referenceDepart, $courrierId]);
    if ($check->fetch()) {
        throw new RuntimeException("Cette référence de départ existe déjà.");
    }
    $db->prepare("UPDATE courriers SET reference_depart = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$referenceDepart, $courrierId]);
}

/* =========================================================
    DONNÉES STRUCTURE (services / divisions)
========================================================= */
function divisions_srb(): array {
    return ['PE' => 'DIV PE', 'FL-EPN' => 'DIV FL-EPN', 'Execution-RFM' => 'DIV Exécution-RFM', 'CIR' => 'DIV CIR'];
}
function divisions_srsp(): array {
    return ['BAG' => 'DIV BAG', 'VISA' => 'DIV VISA', 'SOLDE' => 'DIV SOLDE', 'PENSION' => 'DIV PENSION', 'SECOUR' => 'DIV SECOUR'];
}

/* =========================================================
   UTILITAIRES D'AFFICHAGE
========================================================= */
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function format_date(string $dt): string {
    $t = strtotime($dt);
    return $t ? date('d/m/Y à H:i', $t) : $dt;
}

function flash_set(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function flash_get(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

/* =========================================================
   REQUETES COURRIER COMMUNES
========================================================= */
function get_courrier(int $id): ?array {
    $stmt = getDB()->prepare("SELECT * FROM courriers WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function get_etapes(int $courrierId): array {
    $stmt = getDB()->prepare("
        SELECT e.*
        FROM etapes e
        WHERE e.courrier_id = ?
        ORDER BY e.created_at ASC, e.id ASC
    ");
    $stmt->execute([$courrierId]);
    return $stmt->fetchAll();
}

function get_historique_utilisateur(int $userId, int $limit = 200): array {
    $stmt = getDB()->prepare("
        SELECT h.*, c.reference, c.objet
        FROM historique h
        LEFT JOIN courriers c ON c.id = h.courrier_id
        WHERE h.user_id = ?
        ORDER BY h.created_at DESC, h.id DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Enregistre une étape de transmission d'un courrier (audit trail complet),
 * met à jour son statut, notifie le destinataire, journalise pour les deux parties.
 */
function transmettre_courrier(array $params): void {
    $db = getDB();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO etapes
            (courrier_id, from_user_id, to_user_id, from_role, to_role, action, message, remarque, delai, pdf_file)
            VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $params['courrier_id'],
            $params['from_user_id'] ?? null,
            $params['to_user_id'] ?? null,
            $params['from_role'] ?? null,
            $params['to_role'] ?? null,
            $params['action'],
            $params['message'] ?? null,
            $params['remarque'] ?? null,
            $params['delai'] ?? null,
            $params['pdf_file'] ?? null,
        ]);

        $fields = ['statut = ?', 'updated_at = NOW()'];
        $values = [$params['nouveau_statut']];
        foreach (['service_cible', 'division_cible', 'chef_service_id', 'division_id', 'pdf_courant', 'pdf_complementaire'] as $optField) {
            if (array_key_exists($optField, $params)) {
                $fields[] = "$optField = ?";
                $values[] = $params[$optField];
            }
        }
        $values[] = $params['courrier_id'];
        $db->prepare("UPDATE courriers SET " . implode(', ', $fields) . " WHERE id = ?")->execute($values);

        if (!empty($params['to_user_id'])) {
            add_notification((int)$params['to_user_id'], (int)$params['courrier_id'], $params['notif_message'] ?? 'Nouveau courrier reçu.');
        }
        if (!empty($params['from_user_id'])) {
            add_historique((int)$params['from_user_id'], (int)$params['courrier_id'], $params['action'], $params['hist_from'] ?? $params['notif_message'] ?? '');
        }
        if (!empty($params['to_user_id'])) {
            add_historique((int)$params['to_user_id'], (int)$params['courrier_id'], 'reception', $params['hist_to'] ?? $params['notif_message'] ?? '');
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

/* =========================================================
   AFFICHAGE : conversation / étapes d'un courrier
   Affiche TOUS les messages, remarques et délais échangés.
========================================================= */
function afficher_etapes(array $etapes, string $serviceCible = ''): void {
    if (empty($etapes)) return;
    echo '<div class="conversation">';
    foreach ($etapes as $et) {
        $fromLabel = role_label($et['from_role'] ?? '');
        $toLabel   = role_label($et['to_role'] ?? '');
        $sens = ($et['from_role'] && $et['to_role'])
            ? $fromLabel . ' → ' . $toLabel
            : e(statut_label($et['action'] ?? ''));
        echo '<div class="conv-item">';
        echo '<div class="conv-head">';
        echo '<span class="conv-sens">' . e($sens) . '</span>';
        echo '<span class="conv-date">' . format_date($et['created_at']) . '</span>';
        echo '</div>';
        if (!empty($et['message']))  echo '<div class="conv-msg"><span class="conv-lbl">Message :</span> ' . e($et['message']) . '</div>';
        if (!empty($et['remarque'])) echo '<div class="conv-rem"><span class="conv-lbl">Remarque :</span> ' . e($et['remarque']) . '</div>';
        if (!empty($et['delai']))    echo '<div class="conv-msg"><span class="conv-lbl">Délai :</span> ' . e($et['delai']) . '</div>';
        if (!empty($et['pdf_file'])) echo '<div class="conv-msg"><span class="conv-lbl">Pièce jointe :</span> <a class="pdf-link" href="' . e(pdf_url($et['pdf_file'])) . '" target="_blank">📄 Télécharger</a></div>';
        echo '</div>';
    }
    echo '</div>';
}

function afficher_conversation(int $courrierId): void {
    afficher_etapes(get_etapes($courrierId));
}
