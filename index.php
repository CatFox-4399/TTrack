<?php
// Root index.php — redirect to login
require_once __DIR__ . '/config/config.php';
header('Location: ' . BASE_URL . '/login.php');
exit;
