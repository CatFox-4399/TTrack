<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$db = getDB();

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// ============================================================
// HANDLE POST ACTIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // ----- ADD USER -----
    if ($postAction === 'save_add') {
        $username  = trim($_POST['username'] ?? '');
        $fullName  = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $role      = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
        $tempPw    = trim($_POST['temp_password'] ?? '');
        $toiletIds = array_map('intval', $_POST['toilet_ids'] ?? []);

        if (empty($username) || empty($fullName) || empty($tempPw)) {
            setFlash('error', __('flash_user_req_fields'));
        } else {
            try {
                $hash = password_hash($tempPw, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, full_name, email, password_hash, role, must_change_password) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$username, $fullName, $email, $hash, $role]);
                $newId = (int)$db->lastInsertId();

                // Assign toilets
                if (!empty($toiletIds)) {
                    $ins = $db->prepare("INSERT IGNORE INTO user_toilets (user_id, toilet_id) VALUES (?, ?)");
                    foreach ($toiletIds as $tid) { $ins->execute([$newId, $tid]); }
                }
                setFlash('success', __('flash_user_created', $username));
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    setFlash('error', __('flash_username_taken', $username));
                } else {
                    setFlash('error', 'Database error: ' . $e->getMessage());
                }
            }
        }
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    }

    // ----- EDIT USER -----
    if ($postAction === 'save_edit') {
        $uid      = (int)($_POST['user_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
        $isActive = (int)!empty($_POST['is_active']);
        $toiletIds= array_map('intval', $_POST['toilet_ids'] ?? []);
        $newPw    = trim($_POST['new_password'] ?? '');

        if (empty($fullName)) {
            setFlash('error', __('flash_user_req_fields'));
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name=?, email=?, role=?, is_active=? WHERE id=?");
            $stmt->execute([$fullName, $email, $role, $isActive, $uid]);

            if (!empty($newPw)) {
                if (strlen($newPw) < 6) {
                    setFlash('error', __('cp_error_length'));
                    header('Location: ' . BASE_URL . '/admin/users.php');
                    exit;
                }
                $hash = password_hash($newPw, PASSWORD_DEFAULT);
                $db->prepare("UPDATE users SET password_hash=?, must_change_password=1 WHERE id=?")
                   ->execute([$hash, $uid]);
            }

            // Re-assign toilets
            $db->prepare("DELETE FROM user_toilets WHERE user_id=?")->execute([$uid]);
            if (!empty($toiletIds)) {
                $ins = $db->prepare("INSERT IGNORE INTO user_toilets (user_id, toilet_id) VALUES (?, ?)");
                foreach ($toiletIds as $tid) { $ins->execute([$uid, $tid]); }
            }
            setFlash('success', __('flash_user_updated'));
        }
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    }

    // ----- DELETE USER -----
    if ($postAction === 'delete') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $currentUser = getCurrentUser();
        if ($uid === (int)$currentUser['id']) {
            setFlash('error', __('flash_user_self_del'));
        } else {
            $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
            setFlash('success', __('flash_user_deleted'));
        }
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    }
}

// ============================================================
// FETCH DATA
// ============================================================
$allToilets = getAllToilets();
$search     = trim($_GET['search'] ?? '');

if ($search) {
    $stmt = $db->prepare("SELECT * FROM users WHERE (username LIKE ? OR full_name LIKE ? OR email LIKE ?) AND role='user' ORDER BY full_name ASC");
    $like = "%{$search}%";
    $stmt->execute([$like, $like, $like]);
    $users = $stmt->fetchAll();
} else {
    $stmt = $db->query("SELECT * FROM users ORDER BY role DESC, full_name ASC");
    $users = $stmt->fetchAll();
}

// For edit: fetch current user data
$editUser = null;
$editToiletIds = [];
if ($action === 'edit' && $editId) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();
    $editToiletIds = getUserToiletIds($editId);
}

$pageTitle = __('users_mgmt_title');
require_once __DIR__ . '/../includes/header.php';
?>

<?php renderFlash(); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-users" style="color:var(--primary)"></i> <?= e(__('users_mgmt_title')) ?></h1>
        <p class="page-subtitle"><?= e(__('users_mgmt_subtitle')) ?></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/admin/users.php?action=add" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> <?= e(__('add_new_user')) ?>
        </a>
    </div>
</div>

<!-- Search Bar -->
<form method="GET" action="" class="filter-bar">
    <div class="search-input-wrap">
        <i class="fas fa-search"></i>
        <input type="text" name="search" class="form-control" placeholder="<?= e(__('search_users_ph')) ?>"
               value="<?= e($search) ?>">
    </div>
    <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i> <?= e(__('action_search')) ?></button>
    <?php if ($search): ?>
        <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline"><i class="fas fa-times"></i> <?= e(__('action_clear')) ?></a>
    <?php endif; ?>
</form>

<!-- Users Table -->
<div class="table-wrapper">
    <table>
        <thead><tr>
            <th>#</th>
            <th><?= e(__('th_name')) ?></th>
            <th><?= e(__('th_username')) ?></th>
            <th><?= e(__('th_email')) ?></th>
            <th><?= e(__('th_role')) ?></th>
            <th><?= e(__('th_toilets')) ?></th>
            <th><?= e(__('th_status')) ?></th>
            <th><?= e(__('th_actions')) ?></th>
        </tr></thead>
        <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="8">
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-users-slash"></i></div>
                    <h3><?= e(__('no_users_found')) ?></h3>
                    <p><?= e(__('no_users_desc')) ?></p>
                </div>
            </td></tr>
        <?php else: ?>
            <?php foreach ($users as $i => $u): ?>
                <?php $toiletIds = getUserToiletIds($u['id']); ?>
                <tr>
                    <td class="td-muted"><?= $i + 1 ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:0.65rem">
                            <?= renderUserAvatar($u, '', 'width:32px;height:32px;font-size:0.75rem;flex-shrink:0') ?>
                            <strong><?= e($u['full_name']) ?></strong>
                        </div>
                    </td>
                    <td class="td-muted"><code><?= e($u['username']) ?></code></td>
                    <td class="td-muted"><?= $u['email'] ? e($u['email']) : '—' ?></td>
                    <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'admin' : 'user-role' ?>"><?= $u['role'] === 'admin' ? e(__('role_admin')) : e(__('role_student')) ?></span></td>
                    <td>
                        <?php if (empty($toiletIds)): ?>
                            <span class="td-muted"><?= e(__('badge_none_assigned')) ?></span>
                        <?php else: ?>
                            <span class="badge badge-closed"><?= count($toiletIds) ?> <?= e(strtolower(__('nav_toilets'))) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $u['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $u['is_active'] ? e(__('status_active')) : e(__('status_inactive')) ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="<?= BASE_URL ?>/admin/users.php?action=edit&id=<?= $u['id'] ?>"
                               class="btn btn-outline btn-sm btn-icon" title="<?= e(__('action_edit')) ?>">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm btn-icon"
                                        data-confirm="<?= e(__('confirm_delete_user', $u['full_name'])) ?>"
                                        title="<?= e(__('action_delete')) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ============================================================
     ADD USER MODAL
     ============================================================ -->
<div class="modal-overlay <?= $action === 'add' ? 'open' : '' ?>" id="modalAddUser">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-user-plus" style="color:var(--primary)"></i> <?= e(__('modal_add_user')) ?></div>
            <a href="<?= BASE_URL ?>/admin/users.php" class="modal-close" title="<?= e(__('action_close')) ?>">✕</a>
        </div>
        <form method="POST" novalidate>
            <input type="hidden" name="action" value="save_add">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="add_username"><?= e(__('form_username_req')) ?></label>
                    <input type="text" id="add_username" name="username" class="form-control"
                           placeholder="e.g. ali.hassan" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label" for="add_fullname"><?= e(__('form_fullname_req')) ?></label>
                    <input type="text" id="add_fullname" name="full_name" class="form-control"
                           placeholder="e.g. Ali Hassan" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="add_email"><?= e(__('form_email')) ?></label>
                    <input type="email" id="add_email" name="email" class="form-control"
                           placeholder="user@college.edu.my">
                </div>
                <div class="form-group">
                    <label class="form-label" for="add_role"><?= e(__('form_role')) ?></label>
                    <select id="add_role" name="role" class="form-control">
                        <option value="user" selected><?= e(__('role_student')) ?></option>
                        <option value="admin"><?= e(__('role_admin')) ?></option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="add_temppass"><?= e(__('form_temp_pass_req')) ?></label>
                <div class="password-wrap">
                    <input type="password" id="add_temppass" name="temp_password" class="form-control"
                           placeholder="<?= e(__('form_temp_pass_hint')) ?>" required>
                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                </div>
                <p class="form-hint"><?= e(__('form_temp_pass_hint')) ?></p>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(__('form_assign_toilets')) ?></label>
                <div style="max-height:150px;overflow-y:auto;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.5rem;">
                    <?php foreach ($allToilets as $t): ?>
                        <label style="display:flex;align-items:center;gap:0.5rem;padding:0.35rem 0.5rem;cursor:pointer;border-radius:6px;transition:background 0.15s" onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background=''">
                            <input type="checkbox" name="toilet_ids[]" value="<?= $t['id'] ?>" class="toilet-checkbox">
                            <span style="font-size:0.875rem"><?= e($t['name']) ?>
                                <?php if ($t['location']): ?><span style="color:var(--text-muted)"> — <?= e($t['location']) ?></span><?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                    <?php if (empty($allToilets)): ?>
                        <p style="color:var(--text-muted);font-size:0.82rem;padding:0.5rem"><?= e(__('form_no_toilets_yet')) ?> <a href="<?= BASE_URL ?>/admin/toilets.php?action=add"><?= e(__('add_new_toilet')) ?></a></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline"><?= e(__('action_cancel')) ?></a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= e(__('btn_create_user')) ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     EDIT USER MODAL
     ============================================================ -->
<?php if ($editUser): ?>
<div class="modal-overlay open" id="modalEditUser">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-pen" style="color:var(--primary)"></i> <?= e(__('modal_edit_user')) ?></div>
            <a href="<?= BASE_URL ?>/admin/users.php" class="modal-close" title="<?= e(__('action_close')) ?>">✕</a>
        </div>
        <form method="POST" novalidate>
            <input type="hidden" name="action" value="save_edit">
            <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">

            <div class="form-group">
                <label class="form-label"><?= e(__('th_username')) ?></label>
                <input type="text" class="form-control" value="<?= e($editUser['username']) ?>" disabled style="opacity:0.5">
                <p class="form-hint"><?= e(__('form_username_fixed')) ?></p>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="edit_fullname"><?= e(__('form_fullname_req')) ?></label>
                    <input type="text" id="edit_fullname" name="full_name" class="form-control"
                           value="<?= e($editUser['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_email"><?= e(__('form_email')) ?></label>
                    <input type="email" id="edit_email" name="email" class="form-control"
                           value="<?= e($editUser['email'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="edit_role"><?= e(__('form_role')) ?></label>
                    <select id="edit_role" name="role" class="form-control">
                        <option value="user" <?= $editUser['role'] === 'user' ? 'selected' : '' ?>><?= e(__('role_student')) ?></option>
                        <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>><?= e(__('role_admin')) ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_status"><?= e(__('th_status')) ?></label>
                    <select id="edit_status" name="is_active" class="form-control">
                        <option value="1" <?= $editUser['is_active'] ? 'selected' : '' ?>><?= e(__('status_active')) ?></option>
                        <option value="0" <?= !$editUser['is_active'] ? 'selected' : '' ?>><?= e(__('status_inactive')) ?></option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit_newpass"><?= e(__('form_reset_pass')) ?> <span style="color:var(--text-muted)"><?= e(__('form_reset_pass_opt')) ?></span></label>
                <div class="password-wrap">
                    <input type="password" id="edit_newpass" name="new_password" class="form-control"
                           placeholder="<?= e(__('form_reset_pass')) ?>">
                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                </div>
                <p class="form-hint"><?= e(__('form_reset_pass_hint')) ?></p>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(__('form_assign_toilets')) ?></label>
                <div style="max-height:150px;overflow-y:auto;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.5rem;">
                    <?php foreach ($allToilets as $t): ?>
                        <label style="display:flex;align-items:center;gap:0.5rem;padding:0.35rem 0.5rem;cursor:pointer;border-radius:6px;transition:background 0.15s" onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background=''">
                            <input type="checkbox" name="toilet_ids[]" value="<?= $t['id'] ?>"
                                   class="toilet-checkbox"
                                   <?= in_array($t['id'], $editToiletIds) ? 'checked' : '' ?>>
                            <span style="font-size:0.875rem"><?= e($t['name']) ?>
                                <?php if ($t['location']): ?><span style="color:var(--text-muted)"> — <?= e($t['location']) ?></span><?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                    <?php if (empty($allToilets)): ?>
                        <p style="color:var(--text-muted);font-size:0.82rem;padding:0.5rem"><?= e(__('form_no_toilets_yet')) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline"><?= e(__('action_cancel')) ?></a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= e(__('action_save_changes')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

