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
        $error = __('cp_error_current');
    } elseif (strlen($newPw) < 8) {
        $error = __('cp_error_length');
    } elseif ($newPw !== $confirmPw) {
        $error = __('cp_error_match');
    } else {
        $hash = password_hash($newPw, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?");
        $stmt->execute([$hash, $currentUser['id']]);

        // Update session flag
        $_SESSION['must_change_password'] = 0;

        setFlash('success', __('cp_success'));
        if ($currentUser['role'] === ROLE_ADMIN) {
            header('Location: ' . BASE_URL . '/admin/index.php');
        } else {
            header('Location: ' . BASE_URL . '/user/dashboard.php');
        }
        exit;
    }
}

$pageTitle = __('cp_title');
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-key" style="color:var(--primary)"></i> <?= e(__('cp_title')) ?></h1>
        <p class="page-subtitle">
            <?= $isMustChange
                ? e(__('cp_subtitle_must'))
                : e(__('cp_subtitle_normal')) ?>
        </p>
    </div>
</div>

<?php if ($isMustChange): ?>
    <div class="alert alert-warning">
        ⚠️ <?= e(__('cp_alert_must')) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">❌ <?= e($error) ?></div>
<?php endif; ?>

<div style="max-width:480px;">
    <div class="card">
        <div class="card-title"><i class="fas fa-lock"></i> <?= e(__('cp_card_title')) ?></div>
        <form method="POST" novalidate>

            <?php if (!$isMustChange): ?>
            <div class="form-group">
                <label class="form-label" for="current_password"><?= e(__('cp_current_password')) ?></label>
                <div class="password-wrap">
                    <input type="password" id="current_password" name="current_password"
                           class="form-control" placeholder="<?= e(__('cp_current_placeholder')) ?>" required>
                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <div class="divider"></div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="new_password"><?= e(__('cp_new_password')) ?></label>
                <div class="password-wrap">
                    <input type="password" id="new_password" name="new_password"
                           class="form-control" placeholder="<?= e(__('cp_new_placeholder')) ?>" required>
                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                </div>
                <p class="form-hint"><?= e(__('cp_hint')) ?></p>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password"><?= e(__('cp_confirm_password')) ?></label>
                <div class="password-wrap">
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="form-control" placeholder="<?= e(__('cp_confirm_placeholder')) ?>" required>
                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                </div>
            </div>

            <div style="display:flex; gap:0.75rem; flex-wrap:wrap; margin-top:0.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= e(__('cp_submit_btn')) ?>
                </button>
                <?php if (!$isMustChange): ?>
                    <a href="<?= $currentUser['role'] === ROLE_ADMIN ? BASE_URL.'/admin/index.php' : BASE_URL.'/user/dashboard.php' ?>"
                       class="btn btn-outline"><?= e(__('action_cancel')) ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
