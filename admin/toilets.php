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

    // ----- ADD TOILET -----
    if ($postAction === 'save_add') {
        $name     = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $status   = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if (empty($name)) {
            setFlash('error', __('flash_toilet_name_req'));
        } else {
            $stmt = $db->prepare("INSERT INTO toilets (name, location, description, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $location, $desc, $status]);
            setFlash('success', __('flash_toilet_added', $name));
        }
        header('Location: ' . BASE_URL . '/admin/toilets.php');
        exit;
    }

    // ----- EDIT TOILET -----
    if ($postAction === 'save_edit') {
        $tid      = (int)($_POST['toilet_id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $status   = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if (empty($name)) {
            setFlash('error', __('flash_toilet_name_req'));
        } else {
            $stmt = $db->prepare("UPDATE toilets SET name=?, location=?, description=?, status=? WHERE id=?");
            $stmt->execute([$name, $location, $desc, $status, $tid]);
            setFlash('success', __('flash_toilet_updated'));
        }
        header('Location: ' . BASE_URL . '/admin/toilets.php');
        exit;
    }

    // ----- DELETE TOILET -----
    if ($postAction === 'delete') {
        $tid = (int)($_POST['toilet_id'] ?? 0);
        // Check if any sessions are open
        $open = $db->prepare("SELECT COUNT(*) FROM toilet_sessions WHERE toilet_id=? AND status='open'");
        $open->execute([$tid]);
        if ($open->fetchColumn() > 0) {
            setFlash('error', __('flash_toilet_del_err'));
        } else {
            $db->prepare("DELETE FROM toilets WHERE id=?")->execute([$tid]);
            setFlash('success', __('flash_toilet_deleted'));
        }
        header('Location: ' . BASE_URL . '/admin/toilets.php');
        exit;
    }
}

// ============================================================
// FETCH DATA
// ============================================================
$stmt = $db->query("
    SELECT t.*,
           COUNT(DISTINCT ut.user_id) AS assigned_users,
           COUNT(DISTINCT ts.id) AS total_sessions,
           SUM(ts.status = 'open') AS open_sessions
    FROM toilets t
    LEFT JOIN user_toilets ut ON ut.toilet_id = t.id
    LEFT JOIN toilet_sessions ts ON ts.toilet_id = t.id
    GROUP BY t.id
    ORDER BY t.name ASC
");
$toilets = $stmt->fetchAll();

// Edit data
$editToilet = null;
if ($action === 'edit' && $editId) {
    $s = $db->prepare("SELECT * FROM toilets WHERE id=?");
    $s->execute([$editId]);
    $editToilet = $s->fetch();
}

// Per toilet: who is assigned
function getToiletUsers(PDO $db, int $toiletId): array {
    $stmt = $db->prepare("SELECT u.full_name FROM users u JOIN user_toilets ut ON ut.user_id=u.id WHERE ut.toilet_id=?");
    $stmt->execute([$toiletId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = __('toilets_mgmt_title');
require_once __DIR__ . '/../includes/header.php';
?>

<?php renderFlash(); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-toilet" style="color:var(--primary)"></i> <?= e(__('toilets_mgmt_title')) ?></h1>
        <p class="page-subtitle"><?= e(__('toilets_mgmt_subtitle')) ?></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/admin/toilets.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?= e(__('add_new_toilet')) ?>
        </a>
    </div>
</div>

<!-- Toilet Cards Grid -->
<?php if (empty($toilets)): ?>
    <div class="empty-state" style="margin-top:3rem">
        <div class="empty-state-icon"><i class="fas fa-toilet"></i></div>
        <h3><?= e(__('no_toilets_title')) ?></h3>
        <p><?= e(__('no_toilets_desc')) ?></p>
        <a href="<?= BASE_URL ?>/admin/toilets.php?action=add" class="btn btn-primary" style="margin-top:1rem">
            <i class="fas fa-plus"></i> <?= e(__('add_new_toilet')) ?>
        </a>
    </div>
<?php else: ?>
<div class="table-wrapper">
    <table>
        <thead><tr>
            <th>#</th>
            <th><?= e(__('th_name')) ?></th>
            <th><?= e(__('th_location')) ?></th>
            <th><?= e(__('th_assigned_users')) ?></th>
            <th><?= e(__('th_total_sessions')) ?></th>
            <th><?= e(__('th_active')) ?></th>
            <th><?= e(__('th_status')) ?></th>
            <th><?= e(__('th_actions')) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($toilets as $i => $t): ?>
            <?php $assignedNames = getToiletUsers($db, $t['id']); ?>
            <tr>
                <td class="td-muted"><?= $i + 1 ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:0.65rem">
                        <div style="width:36px;height:36px;background:linear-gradient(135deg,rgba(0,212,170,0.15),rgba(124,111,255,0.1));border:1px solid rgba(0,212,170,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0">🚽</div>
                        <div>
                            <strong><?= e($t['name']) ?></strong>
                            <?php if ($t['description']): ?>
                                <div style="font-size:0.78rem;color:var(--text-muted)"><?= e(substr($t['description'],0,60)) ?><?= strlen($t['description'])>60?'…':'' ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td class="td-muted"><?= $t['location'] ? e($t['location']) : '—' ?></td>
                <td>
                    <?php if (empty($assignedNames)): ?>
                        <span class="td-muted"><?= e(__('none')) ?></span>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:2px">
                        <?php foreach ($assignedNames as $name): ?>
                            <span style="font-size:0.8rem"><?= e($name) ?></span>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td class="td-muted"><?= $t['total_sessions'] ?></td>
                <td>
                    <?php if ($t['open_sessions'] > 0): ?>
                        <span class="badge badge-open">● <?= $t['open_sessions'] ?> <?= strtolower(__('status_active')) ?></span>
                    <?php else: ?>
                        <span class="td-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-<?= $t['status'] ?>">
                        <?= $t['status'] === 'active' ? e(__('status_active')) : e(__('status_inactive')) ?>
                    </span>
                </td>
                <td>
                    <div class="action-btns">
                        <a href="<?= BASE_URL ?>/admin/history.php?toilet_id=<?= $t['id'] ?>"
                           class="btn btn-outline btn-sm btn-icon" title="<?= e(__('action_view_history')) ?>">
                            <i class="fas fa-clock-rotate-left"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/toilets.php?action=edit&id=<?= $t['id'] ?>"
                           class="btn btn-outline btn-sm btn-icon" title="<?= e(__('action_edit')) ?>">
                            <i class="fas fa-pen"></i>
                        </a>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="toilet_id" value="<?= $t['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm btn-icon"
                                    data-confirm="<?= e(__('confirm_delete_toilet', $t['name'])) ?>"
                                    title="<?= e(__('action_delete')) ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ============================================================
     ADD TOILET MODAL
     ============================================================ -->
<div class="modal-overlay <?= $action === 'add' ? 'open' : '' ?>" id="modalAddToilet">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-plus" style="color:var(--primary)"></i> <?= e(__('modal_add_toilet')) ?></div>
            <a href="<?= BASE_URL ?>/admin/toilets.php" class="modal-close" title="<?= e(__('action_close')) ?>">✕</a>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_add">
            <div class="form-group">
                <label class="form-label" for="add_name"><?= e(__('form_toilet_name_req')) ?></label>
                <input type="text" id="add_name" name="name" class="form-control"
                       placeholder="<?= e(__('form_toilet_name_ph')) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="add_location"><?= e(__('th_location')) ?></label>
                <input type="text" id="add_location" name="location" class="form-control"
                       placeholder="<?= e(__('form_toilet_loc_ph')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="add_desc"><?= e(__('form_toilet_desc_ph')) ?></label>
                <textarea id="add_desc" name="description" class="form-control" rows="2"
                          placeholder="<?= e(__('form_toilet_desc_ph')) ?>"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="add_status"><?= e(__('th_status')) ?></label>
                <select id="add_status" name="status" class="form-control">
                    <option value="active"><?= e(__('status_active')) ?></option>
                    <option value="inactive"><?= e(__('status_inactive')) ?></option>
                </select>
            </div>
            <div class="modal-footer">
                <a href="<?= BASE_URL ?>/admin/toilets.php" class="btn btn-outline"><?= e(__('action_cancel')) ?></a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= e(__('action_add')) ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     EDIT TOILET MODAL
     ============================================================ -->
<?php if ($editToilet): ?>
<div class="modal-overlay open" id="modalEditToilet">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-pen" style="color:var(--primary)"></i> <?= e(__('modal_edit_toilet')) ?></div>
            <a href="<?= BASE_URL ?>/admin/toilets.php" class="modal-close" title="<?= e(__('action_close')) ?>">✕</a>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_edit">
            <input type="hidden" name="toilet_id" value="<?= $editToilet['id'] ?>">
            <div class="form-group">
                <label class="form-label" for="edit_name"><?= e(__('form_toilet_name_req')) ?></label>
                <input type="text" id="edit_name" name="name" class="form-control"
                       value="<?= e($editToilet['name']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit_location"><?= e(__('th_location')) ?></label>
                <input type="text" id="edit_location" name="location" class="form-control"
                       value="<?= e($editToilet['location'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="edit_desc"><?= e(__('form_toilet_desc_ph')) ?></label>
                <textarea id="edit_desc" name="description" class="form-control" rows="2"><?= e($editToilet['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit_status"><?= e(__('th_status')) ?></label>
                <select id="edit_status" name="status" class="form-control">
                    <option value="active"   <?= $editToilet['status'] === 'active' ? 'selected' : '' ?>><?= e(__('status_active')) ?></option>
                    <option value="inactive" <?= $editToilet['status'] === 'inactive' ? 'selected' : '' ?>><?= e(__('status_inactive')) ?></option>
                </select>
            </div>
            <div class="modal-footer">
                <a href="<?= BASE_URL ?>/admin/toilets.php" class="btn btn-outline"><?= e(__('action_cancel')) ?></a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= e(__('action_save_changes')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
