<?php
// ============================================================
// set_lang.php — Switch language endpoint
// ============================================================

require_once __DIR__ . '/config/config.php';

$lang = $_GET['lang'] ?? 'en';
$returnUrl = $_GET['return'] ?? ($_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/index.php'));

// Validate and set language
setAppLanguage($lang);

// Clean redirect to avoid open redirect vulnerability
if (empty($returnUrl) || !str_starts_with($returnUrl, BASE_URL) && !str_starts_with($returnUrl, '/')) {
    $returnUrl = BASE_URL . '/index.php';
}

header('Location: ' . $returnUrl);
exit;
