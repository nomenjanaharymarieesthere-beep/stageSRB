<?php
require_once __DIR__ . '/../includes/fonctions.php';
require_role(['srpe']);
header('Location: ' . root_url('/division/fiche_imprimable.php?id=' . (int)($_GET['id'] ?? 0)));
exit;
