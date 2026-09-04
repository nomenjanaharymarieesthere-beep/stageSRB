<?php
require_once __DIR__ . '/includes/fonctions.php';
logout();
header('Location: ' . root_url('login.php'));
exit;
