<?php
// ============================================================
// setup.php — Database Initializer
// Run ONCE to create tables and seed admin account.
// DELETE this file after setup!
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$messages = [];
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();

        // Read and execute schema
        $sql = file_get_contents(__DIR__ . '/database/schema.sql');

        // Split by semicolons (skip empty)
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => !empty($s)
        );

        foreach ($statements as $statement) {
            try {
                $db->exec($statement);
            } catch (PDOException $e) {
                // Ignore duplicate index errors
                if (strpos($e->getMessage(), 'Duplicate key name') === false
                 && strpos($e->getMessage(), 'already exists') === false) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        if (empty($errors)) {
            $messages[] = '✅ ' . __('setup_tables_ok');
            $messages[] = '✅ ' . __('setup_admin_seeded');
            $messages[] = '🔒 ' . __('setup_delete_warn');
        }
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
$currentLang = getCurrentLang();
$availableLangs = getAvailableLanguages();
?>
<!DOCTYPE html>
<html lang="<?= e($currentLang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(__('setup_title')) ?> — <?= e(__('app_name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #070b18; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; background-image: radial-gradient(ellipse at 30% 20%, rgba(0,212,170,0.08), transparent 60%); }
        .box { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 2.5rem; width: 100%; max-width: 520px; position: relative; }
        .icon { width: 72px; height: 72px; background: linear-gradient(135deg, #00d4aa, #7c6fff); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.25rem; }
        h1 { font-size: 1.6rem; font-weight: 800; text-align: center; margin-bottom: 0.25rem; }
        p.subtitle { text-align: center; color: #94a3b8; font-size: 0.875rem; margin-bottom: 1.75rem; }
        .warn { background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.3); border-radius: 10px; padding: 1rem; font-size: 0.85rem; color: #fcd34d; margin-bottom: 1.5rem; }
        ul { list-style: none; padding: 0; margin-bottom: 1.5rem; }
        li { padding: 0.6rem 0.9rem; border-radius: 8px; font-size: 0.875rem; margin-bottom: 0.5rem; }
        li.ok  { background: rgba(16,185,129,0.1); color: #6ee7b7; }
        li.err { background: rgba(239,68,68,0.1);  color: #fca5a5; }
        btn, .btn { display: inline-block; padding: 0.75rem 2rem; background: linear-gradient(135deg, #00d4aa, #00a87f); color: #070b18; font-weight: 700; border: none; border-radius: 999px; cursor: pointer; font-size: 0.95rem; text-decoration: none; text-align: center; width: 100%; }
        btn:hover, .btn:hover { filter: brightness(1.1); }
        a.back { display: block; text-align: center; margin-top: 1rem; color: #00d4aa; font-size: 0.85rem; text-decoration: none; }
        a.back:hover { text-decoration: underline; }
        .lang-bar { display: flex; justify-content: center; gap: 0.4rem; margin-bottom: 1.5rem; }
        .lang-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.3rem 0.7rem; border-radius: 999px; font-size: 0.78rem; text-decoration: none; color: #94a3b8; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); transition: all 0.2s; }
        .lang-btn.active { color: #00d4aa; border-color: #00d4aa; background: rgba(0,212,170,0.12); font-weight: 600; }
    </style>
</head>
<body>
<div class="box">
    <div class="lang-bar">
        <?php foreach ($availableLangs as $code => $info): ?>
            <a href="set_lang.php?lang=<?= $code ?>" class="lang-btn <?= $currentLang === $code ? 'active' : '' ?>">
                <span><?= $info['flag'] ?></span>
                <span><?= e($info['name']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="icon">🚽</div>
    <h1><?= e(__('app_name')) ?> <?= e(__('setup_title')) ?></h1>
    <p class="subtitle"><?= e(__('setup_subtitle')) ?></p>

    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
        <div class="warn">
            ⚠️ <?= __('setup_warn') ?>
        </div>
        <form method="POST">
            <button type="submit" class="btn">🚀 <?= e(__('setup_run_btn')) ?></button>
        </form>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li class="err">❌ <?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!empty($messages)): ?>
            <ul>
                <?php foreach ($messages as $msg): ?>
                    <li class="ok"><?= $msg ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="login.php" class="btn"><?= e(__('setup_go_login')) ?> &rarr;</a>
        <?php endif; ?>
    <?php endif; ?>
    <a href="login.php" class="back">&larr; <?= e(__('setup_back_login')) ?></a>
</div>
</body>
</html>

