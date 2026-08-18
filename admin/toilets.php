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
            setFlash('error', 'Toilet name is required.');
        } else {
            $stmt = $db->prepare("INSERT INTO toilets (name, location, description, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $location, $desc, $status]);
            setFlash('success', "Toilet '{$name}' added successfully.");
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
            setFlash('error', 'Toilet name is required.');
        } else {
            $stmt = $db->prepare("UPDATE toilets SET name=?, location=?, description=?, status=? WHERE id=?");
            $stmt->execute([$name, $location, $desc, $status, $tid]);
            setFlash('success', "Toilet updated successfully.");
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
            setFlash('error', 'Cannot delete: this toilet has active check-in sessions.');
        } else {
            $db->prepare("DELETE FROM toilets WHERE id=?")->execute([$tid]);
            setFlash('success', 'Toilet deleted successfully.');
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

$pageTitle = 'Toilet Management';
require_once __DIR__ . '/../includes/header.php';
?>

<?php renderFlash(); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-toilet" style="color:var(--primary)"></i> Toilet Management</h1>
        <p class="page-subtitle">Add, edit and manage all monitored toilets.</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/admin/toilets.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Toilet
        </a>
    </div>
</div>

<!-- Toilet Cards Grid -->
<?php if (empty($toilets)): ?>
    <div class="empty-state" style="margin-top:3rem">
        <div class="empty-state-icon"><i class="fas fa-toilet"></i></div>
        <h3>No Toilets Yet</h3>
        <p>Add your first toilet to start monitoring cleanliness.</p>
        <a href="<?= BASE_URL ?>/admin/toilets.php?action=add" class="btn btn-primary" style="margin-top:1rem">
            <i class="fas fa-plus"></i> Add Toilet
        </a>
    </div>
<?php else: ?>
<div class="table-wrapper">
    <table>
        <thead><tr>
            <th>#</th><th>Name</th><th>Location</th><th>Assigned Users</th>
            <th>Total Sessions</th><th>Active</th><th>Status</th><th>Actions</th>
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
                        <span class="td-muted">None</span>
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
                        <span class="badge badge-open">● <?= $t['open_sessions'] ?> active</span>
                    <?php else: ?>
                        <span class="td-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-<?= $t['status'] ?>">
                        <?= ucfirst($t['status']) ?>
                    </span>
                </td>
                <td>
                    <div class="action-btns">
                        <a href="<?= BASE_URL ?>/admin/history.php?toilet_id=<?= $t['id'] ?>"
                           class="btn btn-outline btn-sm btn-icon" title="View History">
                            <i class="fas fa-clock-rotate-left"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/toilets.php?action=edit&id=<?= $t['id'] ?>"
                           class="btn btn-outline btn-sm btn-icon" title="Edit">
                            <i class="fas fa-pen"></i>
                        </a>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="toilet_id" value="<?= $t['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm btn-icon"
                                    data-confirm="Delete toilet '<?= e($t['name']) ?>'? All session history will be lost."
                                    title="Delete">
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
            <div class="modal-title"><i class="fas fa-plus" style="color:var(--primary)"></i> Add New Toilet</div>
            <a href="<?= BASE_URL ?>/admin/toilets.php" class="modal-close" title="Close">✕</a>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_add">
            <div class="form-group">
                <label class="form-label" for="add_name">Toilet Name / Number *</label>
                <input type="text" id="add_name" name="name" class="form-control"
                       placeholder="e.g. T01, Block A Male, Ground Floor Female" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="add_location">Location</label>
                <input type="text" id="add_location" name="location" class="form-control"
                       placeholder="e.g. Block A, Ground Floor">
            </div>
            <div class="form-group">
                <label class="form-label" for="add_desc">Description</label>
                <textarea id="add_desc" name="description" class="form-control" rows="2"
                          placeholder="Additional notes about this toilet…"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="add_status">Status</label>
                <select id="add_status" name="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="modal-footer">
                <a href="<?= BASE_URL ?>/admin/toilets.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Toilet</button>
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
            <div class="modal-title"><i class="fas fa-pen" style="color:var(--primary)"></i> Edit Toilet</div>
            <a href="<?= BASE_URL ?>/admin/toilets.php" class="modal-close" title="Close">✕</a>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_edit">
            <input type="hidden" name="toilet_id" value="<?= $editToilet['id'] ?>">
            <div class="form-group">
                <label class="form-label" for="edit_name">Toilet Name / Number *</label>
                <input type="text" id="edit_name" name="name" class="form-control"
                       value="<?= e($editToilet['name']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit_location">Location</label>
                <input type="text" id="edit_location" name="location" class="form-control"
                       value="<?= e($editToilet['location'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="edit_desc">Description</label>
                <textarea id="edit_desc" name="description" class="form-control" rows="2"><?= e($editToilet['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit_status">Status</label>
                <select id="edit_status" name="status" class="form-control">
                    <option value="active"   <?= $editToilet['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $editToilet['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="modal-footer">
                <a href="<?= BASE_URL ?>/admin/toilets.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
