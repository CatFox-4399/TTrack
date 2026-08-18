<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// If session claims to be logged in, verify the user actually exists in DB
// This prevents ERR_TOO_MANY_REDIRECTS from stale/corrupt sessions
if (isLoggedIn()) {
    try {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, role FROM users WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $verifiedUser = $stmt->fetch();

        if ($verifiedUser) {
            // Valid — send to appropriate dashboard
            if ($verifiedUser['role'] === ROLE_ADMIN) {
                header('Location: ' . BASE_URL . '/admin/index.php');
            } else {
                header('Location: ' . BASE_URL . '/user/dashboard.php');
            }
            exit;
        } else {
            // User no longer exists or is inactive — destroy stale session
            logoutUser();
        }
    } catch (Exception $e) {
        // DB not set up yet — destroy session and show login/setup prompt
        logoutUser();
    }
}

$error = '';

$dbReady = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter your username and password.';
    } else {
        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                loginUser($user);
                if ($user['role'] === ROLE_ADMIN) {
                    header('Location: ' . BASE_URL . '/admin/index.php');
                } else {
                    if ($user['must_change_password']) {
                        header('Location: ' . BASE_URL . '/change_password.php');
                    } else {
                        header('Location: ' . BASE_URL . '/user/dashboard.php');
                    }
                }
                exit;
            } else {
                sleep(1); // brute-force delay
                $error = 'Invalid username or password. Please try again.';
            }
        } catch (Exception $e) {
            $dbReady = false;
        }
    }
} else {
    // Check DB availability silently on GET
    try { getDB(); } catch (Exception $e) { $dbReady = false; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <meta name="description" content="Login to <?= APP_NAME ?> — College Toilet Cleanliness Monitoring">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .login-features { display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1.75rem; }
        .login-feature { display: flex; align-items: center; gap: 0.6rem; font-size: 0.82rem; color: #94a3b8; }
        .login-feature i { color: #00d4aa; width: 16px; }
        .login-divider { height: 1px; background: rgba(255,255,255,0.08); margin: 1.5rem 0; }
        .college-name { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.1em; color: #64748b; text-align: center; margin-top: 1.5rem; }
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-box">

        <!-- Brand -->
        <div class="login-brand">
            <div class="login-brand-icon"><i class="fas fa-toilet"></i></div>
            <h1><?= APP_NAME ?></h1>
            <p><?= APP_SUBTITLE ?></p>
        </div>

        <!-- Features -->
        <div class="login-features">
            <div class="login-feature"><i class="fas fa-camera"></i> Photo Evidence Check-In / Check-Out</div>
            <div class="login-feature"><i class="fas fa-clock-rotate-left"></i> Complete Cleanliness History</div>
            <div class="login-feature"><i class="fas fa-shield-halved"></i> Role-Based Access Control</div>
        </div>

        <!-- DB not ready warning -->
        <?php if (!$dbReady): ?>
        <div class="alert alert-warning" style="flex-direction:column;align-items:flex-start;gap:0.5rem">
            <strong>⚠️ Database not set up yet!</strong>
            <span style="font-size:0.82rem">Please run the setup first before logging in.</span>
            <a href="<?= BASE_URL ?>/setup.php" class="btn btn-warning btn-sm" style="margin-top:0.25rem">
                <i class="fas fa-database"></i> Run Setup Now
            </a>
        </div>
        <?php endif; ?>


        <!-- Error -->
        <?php if ($error): ?>
            <div class="alert alert-error auto-dismiss">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" id="loginForm" novalidate>
            <div class="form-group">
                <label class="form-label" for="username">
                    <i class="fas fa-user" style="margin-right:0.3rem"></i> Username
                </label>
                <input type="text" id="username" name="username" class="form-control"
                       placeholder="Enter your username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       autocomplete="username" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">
                    <i class="fas fa-lock" style="margin-right:0.3rem"></i> Password
                </label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Enter your password"
                           autocomplete="current-password" required>
                    <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:0.5rem">
                <i class="fas fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div class="login-divider"></div>
        <p class="college-name"><i class="fas fa-building-columns" style="margin-right:0.4rem"></i><?= COLLEGE_NAME ?></p>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
