<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();
$currentUser = getCurrentUser();
$db = getDB();
$error = '';
$success = '';
$isMustChange = !empty($currentUser['must_change_password']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPw  = $_POST['current_password'] ?? '';
    $newPw      = $_POST['new_password']     ?? '';
    $confirmPw  = $_POST['confirm_password'] ?? '';

    // Fetch actual hash from DB
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$currentUser['id']]);
    $row = $stmt->fetch();

    if (!$isMustChange && !password_verify($currentPw, $row['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPw) < 8) {
        $error = 'New password must be at least 8 characters long.';
    } elseif ($newPw !== $confirmPw) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($newPw, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?");
        $stmt->execute([$hash, $currentUser['id']]);

        // Update session flag
        $_SESSION['must_change_password'] = 0;

        setFlash('success', 'Password changed successfully!');
        if ($currentUser['role'] === ROLE_ADMIN) {
            header('Location: ' . BASE_URL . '/admin/index.php');
        } else {
            header('Location: ' . BASE_URL . '/user/dashboard.php');
        }
        exit;
    }
}

$pageTitle = 'Change Password';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-key" style="color:var(--primary)"></i> Change Password</h1>
        <p class="page-subtitle">
            <?= $isMustChange
                ? 'Welcome! Please set your own password before continuing.'
                : 'Update your account password.' ?>
        </p>
    </div>
</div>

<?php if ($isMustChange): ?>
    <div class="alert alert-warning">
        ⚠️ You are required to set a new password before accessing the system.
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">❌ <?= e($error) ?></div>
<?php endif; ?>

<div style="max-width:480px;">
    <div class="card">
        <div class="card-title"><i class="fas fa-lock"></i> Password Settings</div>
        <form method="POST" novalidate>

            <?php if (!$isMustChange): ?>
            <div class="form-group">
                <label class="form-label" for="current_password">Current Password</label>
                <div class="password-wrap">
                    <input type="password" id="current_password" name="current_password"
                           class="form-control" placeholder="Enter current password" required>
                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <div class="divider"></div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="new_password">New Password</label>
                <div class="password-wrap">
                    <input type="password" id="new_password" name="new_password"
                           class="form-control" placeholder="Minimum 8 characters" required>
                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                </div>
                <p class="form-hint">At least 8 characters. Use a mix of letters and numbers.</p>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm New Password</label>
                <div class="password-wrap">
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="form-control" placeholder="Re-enter new password" required>
                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                </div>
            </div>

            <div style="display:flex; gap:0.75rem; flex-wrap:wrap; margin-top:0.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Password
                </button>
                <?php if (!$isMustChange): ?>
                    <a href="<?= $currentUser['role'] === ROLE_ADMIN ? BASE_URL.'/admin/index.php' : BASE_URL.'/user/dashboard.php' ?>"
                       class="btn btn-outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
