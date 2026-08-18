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
            setFlash('error', 'Username, full name and temporary password are required.');
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
                setFlash('success', "User '{$username}' created successfully.");
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    setFlash('error', "Username '{$username}' is already taken.");
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
            setFlash('error', 'Full name is required.');
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name=?, email=?, role=?, is_active=? WHERE id=?");
            $stmt->execute([$fullName, $email, $role, $isActive, $uid]);

            if (!empty($newPw)) {
                if (strlen($newPw) < 6) {
                    setFlash('error', 'Password must be at least 6 characters.');
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
            setFlash('success', 'User updated successfully.');
        }
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    }

    // ----- DELETE USER -----
    if ($postAction === 'delete') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $currentUser = getCurrentUser();
        if ($uid === (int)$currentUser['id']) {
            setFlash('error', 'You cannot delete your own account.');
        } else {
            $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
            setFlash('success', 'User deleted successfully.');
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

$pageTitle = 'User Management';
require_once __DIR__ . '/../includes/header.php';
?>

<?php renderFlash(); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-users" style="color:var(--primary)"></i> User Management</h1>
        <p class="page-subtitle">Create and manage student/user accounts and toilet assignments.</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/admin/users.php?action=add" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add User
        </a>
    </div>
</div>

<!-- Search Bar -->
<form method="GET" action="" class="filter-bar">
    <div class="search-input-wrap">
        <i class="fas fa-search"></i>
        <input type="text" name="search" class="form-control" placeholder="Search users…"
               value="<?= e($search) ?>">
    </div>
    <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i> Search</button>
    <?php if ($search): ?>
        <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline"><i class="fas fa-times"></i> Clear</a>
    <?php endif; ?>
</form>

<!-- Users Table -->
<div class="table-wrapper">
    <table>
        <thead><tr>
            <th>#</th><th>Name</th><th>Username</th><th>Email</th>
            <th>Role</th><th>Toilets</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="8">
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-users-slash"></i></div>
                    <h3>No Users Found</h3>
                    <p>Add your first user to get started.</p>
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
                    <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'admin' : 'user-role' ?>"><?= ucfirst($u['role']) ?></span></td>
                    <td>
                        <?php if (empty($toiletIds)): ?>
                            <span class="td-muted">None assigned</span>
                        <?php else: ?>
                            <span class="badge badge-closed"><?= count($toiletIds) ?> toilet<?= count($toiletIds) > 1 ? 's' : '' ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $u['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="<?= BASE_URL ?>/admin/users.php?action=edit&id=<?= $u['id'] ?>"
                               class="btn btn-outline btn-sm btn-icon" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm btn-icon"
                                        data-confirm="Delete user '<?= e($u['full_name']) ?>'? This will also remove all their session history."
                                        title="Delete">
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
            <div class="modal-title"><i class="fas fa-user-plus" style="color:var(--primary)"></i> Add New User</div>
            <a href="<?= BASE_URL ?>/admin/users.php" class="modal-close" title="Close">✕</a>
        </div>
        <form method="POST" novalidate>
            <input type="hidden" name="action" value="save_add">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="add_username">Username *</label>
                    <input type="text" id="add_username" name="username" class="form-control"
                           placeholder="e.g. ali.hassan" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label" for="add_fullname">Full Name *</label>
                    <input type="text" id="add_fullname" name="full_name" class="form-control"
                           placeholder="e.g. Ali Hassan" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="add_email">Email</label>
                    <input type="email" id="add_email" name="email" class="form-control"
                           placeholder="user@college.edu.my">
                </div>
                <div class="form-group">
                    <label class="form-label" for="add_role">Role</label>
                    <select id="add_role" name="role" class="form-control">
                        <option value="user">User / Student</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="add_temppass">Temporary Password *</label>
                <div class="password-wrap">
                    <input type="password" id="add_temppass" name="temp_password" class="form-control"
                           placeholder="User must change on first login" required>
                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                </div>
                <p class="form-hint">The user will be required to change this on first login.</p>
            </div>
            <div class="form-group">
                <label class="form-label">Assign Toilets</label>
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
                        <p style="color:var(--text-muted);font-size:0.82rem;padding:0.5rem">No toilets created yet. <a href="<?= BASE_URL ?>/admin/toilets.php?action=add">Add one first.</a></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create User</button>
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
            <div class="modal-title"><i class="fas fa-pen" style="color:var(--primary)"></i> Edit User</div>
            <a href="<?= BASE_URL ?>/admin/users.php" class="modal-close" title="Close">✕</a>
        </div>
        <form method="POST" novalidate>
            <input type="hidden" name="action" value="save_edit">
            <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?= e($editUser['username']) ?>" disabled style="opacity:0.5">
                <p class="form-hint">Username cannot be changed.</p>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="edit_fullname">Full Name *</label>
                    <input type="text" id="edit_fullname" name="full_name" class="form-control"
                           value="<?= e($editUser['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" class="form-control"
                           value="<?= e($editUser['email'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="edit_role">Role</label>
                    <select id="edit_role" name="role" class="form-control">
                        <option value="user" <?= $editUser['role'] === 'user' ? 'selected' : '' ?>>User / Student</option>
                        <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_status">Status</label>
                    <select id="edit_status" name="is_active" class="form-control">
                        <option value="1" <?= $editUser['is_active'] ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= !$editUser['is_active'] ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit_newpass">Reset Password <span style="color:var(--text-muted)">(leave blank to keep)</span></label>
                <div class="password-wrap">
                    <input type="password" id="edit_newpass" name="new_password" class="form-control"
                           placeholder="Enter new password to reset">
                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                </div>
                <p class="form-hint">If set, user will be forced to change on next login.</p>
            </div>
            <div class="form-group">
                <label class="form-label">Assign Toilets</label>
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
                        <p style="color:var(--text-muted);font-size:0.82rem;padding:0.5rem">No toilets available.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
