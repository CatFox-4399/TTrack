<?php
// ============================================================
// setup.php — Database Initializer
// Run ONCE to create tables and seed admin account.
// DELETE this file after setup!
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';

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
            $messages[] = '✅ Database tables created successfully.';
            $messages[] = '✅ Default admin account seeded: <strong>username: admin / password: Admin@123</strong>';
            $messages[] = '🔒 Please delete <code>setup.php</code> after setup is complete.';
        }
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup — ToiletTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #070b18; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; background-image: radial-gradient(ellipse at 30% 20%, rgba(0,212,170,0.08), transparent 60%); }
        .box { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 2.5rem; width: 100%; max-width: 520px; }
        .icon { width: 72px; height: 72px; background: linear-gradient(135deg, #00d4aa, #7c6fff); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.25rem; }
        h1 { font-size: 1.6rem; font-weight: 800; text-align: center; margin-bottom: 0.25rem; }
        p.subtitle { text-align: center; color: #94a3b8; font-size: 0.875rem; margin-bottom: 2rem; }
        .warn { background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.3); border-radius: 10px; padding: 1rem; font-size: 0.85rem; color: #fcd34d; margin-bottom: 1.5rem; }
        ul { list-style: none; padding: 0; margin-bottom: 1.5rem; }
        li { padding: 0.6rem 0.9rem; border-radius: 8px; font-size: 0.875rem; margin-bottom: 0.5rem; }
        li.ok  { background: rgba(16,185,129,0.1); color: #6ee7b7; }
        li.err { background: rgba(239,68,68,0.1);  color: #fca5a5; }
        btn, .btn { display: inline-block; padding: 0.75rem 2rem; background: linear-gradient(135deg, #00d4aa, #00a87f); color: #070b18; font-weight: 700; border: none; border-radius: 999px; cursor: pointer; font-size: 0.95rem; text-decoration: none; text-align: center; width: 100%; }
        btn:hover, .btn:hover { filter: brightness(1.1); }
        a.back { display: block; text-align: center; margin-top: 1rem; color: #00d4aa; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="box">
    <div class="icon">🚽</div>
    <h1>ToiletTrack Setup</h1>
    <p class="subtitle">Database Initializer</p>

    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
        <div class="warn">
            ⚠️ This will create the database schema and seed a default admin account.<br>
            Make sure your database credentials in <code>config/database.php</code> are correct.
        </div>
        <form method="POST">
            <button type="submit" class="btn">🚀 Run Setup</button>
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
            <a href="login.php" class="btn">Go to Login →</a>
        <?php endif; ?>
    <?php endif; ?>
    <a href="login.php" class="back">← Back to Login</a>
</div>
</body>
</html>
