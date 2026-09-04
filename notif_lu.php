<?php
require_once __DIR__ . '/includes/fonctions.php';
$u = require_login();
mark_notifications_read((int)$u['id']);
header('Content-Type: application/json');
echo json_encode(['ok' => true]);
