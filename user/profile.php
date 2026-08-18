<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$currentUser = getCurrentUser();

// Only regular users (students) can customize profile picture
if ($currentUser['role'] !== ROLE_USER) {
    setFlash('info', 'Profile picture customization is only available for student accounts.');
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$db = getDB();
$userId = (int)$currentUser['id'];

// Fetch latest user details from DB
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userRecord = $stmt->fetch();

if (!$userRecord) {
    logoutUser();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_avatar') {
        if (!empty($_FILES['avatar']['name'])) {
            $result = uploadUserAvatar($_FILES['avatar'], $userId);
            if ($result['success']) {
                setFlash('success', 'Profile picture updated successfully!');
            } else {
                setFlash('error', $result['error'] ?? 'Failed to upload profile picture.');
            }
        } elseif (!empty($_POST['camera_avatar_data'])) {
            // Data URL from live camera modal
            $dataUrl = $_POST['camera_avatar_data'];
            if (preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $type)) {
                $data = substr($dataUrl, strpos($dataUrl, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, etc.

                if (!in_array($type, ['jpeg', 'jpg', 'png', 'webp'])) {
                    $type = 'jpg';
                }
                $ext = ($type === 'jpeg') ? 'jpg' : $type;
                $decoded = base64_decode($data);

                if ($decoded !== false) {
                    if (!is_dir(AVATAR_DIR)) {
                        mkdir(AVATAR_DIR, 0755, true);
                    }

                    // Remove old picture
                    if (!empty($userRecord['profile_picture']) && file_exists(AVATAR_DIR . $userRecord['profile_picture'])) {
                        @unlink(AVATAR_DIR . $userRecord['profile_picture']);
                    }

                    $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                    file_put_contents(AVATAR_DIR . $filename, $decoded);

                    $stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    $stmt->execute([$filename, $userId]);
                    $_SESSION['profile_picture'] = $filename;

                    setFlash('success', 'Profile picture captured and updated successfully!');
                } else {
                    setFlash('error', 'Invalid camera photo data.');
                }
            } else {
                setFlash('error', 'Failed to process camera photo.');
            }
        } else {
            setFlash('warning', 'Please select or take a photo first.');
        }

        header('Location: ' . BASE_URL . '/user/profile.php');
        exit;
    } elseif ($action === 'delete_avatar') {
        deleteUserAvatar($userId);
        setFlash('success', 'Profile picture removed. Reverted to default avatar.');
        header('Location: ' . BASE_URL . '/user/profile.php');
        exit;
    }
}

// Fetch assigned toilets for overview
$toilets = getAssignedToilets($userId);

$pageTitle = 'My Profile & Avatar';
require_once __DIR__ . '/../includes/header.php';
?>

<?php renderFlash(); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-user-circle" style="color:var(--primary)"></i> Profile Picture & Account</h1>
        <p class="page-subtitle">Customize your personal profile picture and manage your account details.</p>
    </div>
</div>

<div class="profile-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
    <!-- Profile Picture Box -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-image" style="color:var(--primary)"></i> Custom Profile Picture
            </div>
            <span class="badge <?= !empty($userRecord['profile_picture']) ? 'badge-open' : 'badge-closed' ?>">
                <?= !empty($userRecord['profile_picture']) ? 'Custom Photo' : 'Default Initial' ?>
            </span>
        </div>
        <div class="card-body" style="text-align:center;padding:2rem 1.5rem;">
            <!-- Avatar Display / Preview -->
            <div class="avatar-preview-wrapper" style="margin-bottom:1.5rem;position:relative;display:inline-block;">
                <div id="avatarDisplayBox" class="avatar-display-box" style="width:130px;height:130px;border-radius:50%;overflow:hidden;border:4px solid var(--border);box-shadow:0 0 20px rgba(0,212,170,0.15);margin:0 auto;background:var(--bg-elevated);display:flex;align-items:center;justify-content:center;">
                    <?php if (!empty($userRecord['profile_picture']) && file_exists(AVATAR_DIR . $userRecord['profile_picture'])): ?>
                        <img id="avatarImagePreview" src="<?= AVATAR_URL . e($userRecord['profile_picture']) ?>?v=<?= filemtime(AVATAR_DIR . $userRecord['profile_picture']) ?>"
                             alt="<?= e($userRecord['full_name']) ?>"
                             style="width:100%;height:100%;object-fit:cover;display:block;">
                    <?php else: ?>
                        <img id="avatarImagePreview" src="" alt="Preview" style="width:100%;height:100%;object-fit:cover;display:none;">
                        <span id="avatarInitialFallback" style="font-size:3rem;font-weight:800;color:var(--primary);line-height:1;">
                            <?= strtoupper(substr($userRecord['full_name'], 0, 1)) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div id="newPhotoBadge" class="badge badge-admin" style="display:none;position:absolute;bottom:0;right:50%;transform:translateX(50%);box-shadow:0 2px 8px rgba(0,0,0,0.5);">
                    New Preview
                </div>
            </div>

            <p style="font-size:0.875rem;color:var(--text-secondary);margin-bottom:1.25rem;">
                Upload a clear photo or take a selfie using your camera.<br>
                <small style="color:var(--text-muted)">Supported formats: JPG, PNG, WEBP, GIF (Max 5MB)</small>
            </p>

            <form method="POST" enctype="multipart/form-data" id="avatarForm">
                <input type="hidden" name="action" value="upload_avatar">
                <input type="hidden" name="camera_avatar_data" id="cameraAvatarData" value="">
                <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;">

                <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;margin-bottom:1rem;">
                    <!-- File Upload Button -->
                    <button type="button" class="btn btn-secondary" id="chooseFileBtn">
                        <i class="fas fa-folder-open"></i> Choose Image
                    </button>

                    <!-- Live Camera Button -->
                    <button type="button" class="btn btn-primary" id="openAvatarCamBtn">
                        <i class="fas fa-camera"></i> Take Photo
                    </button>
                </div>

                <!-- Submit Button (Enabled when new photo selected) -->
                <div id="saveAvatarArea" style="display:none;margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--border);">
                    <button type="submit" class="btn btn-success btn-lg" style="width:100%;">
                        <i class="fas fa-check"></i> Save Profile Picture
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancelAvatarBtn" style="width:100%;margin-top:0.5rem;">
                        Cancel
                    </button>
                </div>
            </form>

            <?php if (!empty($userRecord['profile_picture'])): ?>
                <form method="POST" onsubmit="return confirm('Remove custom profile picture and revert to initial avatar?');" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);">
                    <input type="hidden" name="action" value="delete_avatar">
                    <button type="submit" class="btn btn-danger btn-sm" style="opacity:0.85;">
                        <i class="fas fa-trash-can"></i> Remove Profile Picture
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Account Details & Assigned Toilets -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-id-card" style="color:var(--accent)"></i> Account Details
            </div>
            <a href="<?= BASE_URL ?>/change_password.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-key"></i> Change Password
            </a>
        </div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:1.5rem;">
                <div class="session-info-item">
                    <span class="session-info-label">Full Name</span>
                    <span class="session-info-value" style="font-weight:600;"><?= e($userRecord['full_name']) ?></span>
                </div>
                <div class="session-info-item">
                    <span class="session-info-label">Username</span>
                    <span class="session-info-value"><code><?= e($userRecord['username']) ?></code></span>
                </div>
                <div class="session-info-item">
                    <span class="session-info-label">Email Address</span>
                    <span class="session-info-value"><?= e($userRecord['email'] ?: 'Not specified') ?></span>
                </div>
                <div class="session-info-item">
                    <span class="session-info-label">Role</span>
                    <span class="session-info-value">
                        <span class="user-role-badge badge-user">Student</span>
                    </span>
                </div>
                <div class="session-info-item">
                    <span class="session-info-label">Member Since</span>
                    <span class="session-info-value"><?= fdt($userRecord['created_at'], 'd M Y') ?></span>
                </div>
            </div>

            <div class="divider"></div>

            <div class="section-label" style="margin-bottom:0.75rem;">
                <i class="fas fa-toilet"></i> Assigned Toilets (<?= count($toilets) ?>)
            </div>
            <?php if (empty($toilets)): ?>
                <p style="font-size:0.85rem;color:var(--text-muted);">No toilets currently assigned.</p>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                    <?php foreach ($toilets as $t): ?>
                        <a href="<?= BASE_URL ?>/user/toilet.php?id=<?= $t['id'] ?>"
                           style="display:flex;align-items:center;justify-content:space-between;padding:0.6rem 0.9rem;border-radius:var(--radius-sm);background:var(--bg-glass);border:1px solid var(--border);text-decoration:none;color:var(--text-primary);transition:all var(--transition);"
                           onmouseover="this.style.borderColor='var(--primary)'"
                           onmouseout="this.style.borderColor='var(--border)'">
                            <span style="font-size:0.875rem;font-weight:600;">
                                🚽 <?= e($t['name']) ?>
                            </span>
                            <span style="font-size:0.78rem;color:var(--text-secondary);">
                                <?= e($t['location'] ?: 'View Toilet') ?> &rarr;
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal for Profile Picture Live Camera -->
<div class="camera-modal-overlay" id="avatarCamModal" style="display:none;">
    <div class="camera-modal-box">
        <div class="camera-modal-header">
            <div class="camera-modal-title">
                <i class="fas fa-camera"></i> Profile Photo Camera
            </div>
            <button class="camera-modal-close" id="closeAvatarCamBtn" type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="camera-viewfinder">
            <video id="avatarCamVideo" autoplay playsinline muted style="transform:scaleX(-1);"></video>
            <canvas id="avatarCamCanvas" style="display:none;"></canvas>
            <div class="camera-flash" id="avatarCamFlash"></div>
        </div>

        <div class="camera-error" id="avatarCamError">
            <i class="fas fa-circle-exclamation"></i>
            <span id="avatarCamErrorText">Camera access denied. Please allow camera permission in your browser.</span>
        </div>

        <div class="camera-controls">
            <button class="flip-camera-btn" id="flipAvatarCamBtn" type="button" title="Flip Camera">
                <i class="fas fa-camera-rotate"></i>
            </button>
            <button class="shutter-btn" id="snapAvatarBtn" type="button" title="Capture Profile Picture"></button>
            <div style="width:44px"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const fileInput         = document.getElementById('avatarInput');
    const chooseFileBtn     = document.getElementById('chooseFileBtn');
    const openCamBtn        = document.getElementById('openAvatarCamBtn');
    const closeCamBtn       = document.getElementById('closeAvatarCamBtn');
    const camModal          = document.getElementById('avatarCamModal');
    const camVideo          = document.getElementById('avatarCamVideo');
    const camCanvas         = document.getElementById('avatarCamCanvas');
    const camFlash          = document.getElementById('avatarCamFlash');
    const camError          = document.getElementById('avatarCamError');
    const snapBtn           = document.getElementById('snapAvatarBtn');
    const flipCamBtn        = document.getElementById('flipAvatarCamBtn');
    const saveArea          = document.getElementById('saveAvatarArea');
    const cancelBtn         = document.getElementById('cancelAvatarBtn');
    const previewImg        = document.getElementById('avatarImagePreview');
    const initialFallback   = document.getElementById('avatarInitialFallback');
    const newBadge          = document.getElementById('newPhotoBadge');
    const cameraAvatarData  = document.getElementById('cameraAvatarData');

    let stream = null;
    let facing = 'user'; // Selfie front-camera default for profile pictures

    // Initial state preservation for Cancel
    const initialSrc = previewImg ? previewImg.getAttribute('src') : '';
    const initialHadImg = previewImg && previewImg.style.display !== 'none' && initialSrc !== '';

    chooseFileBtn.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
        if (!fileInput.files || !fileInput.files[0]) return;
        const file = fileInput.files[0];
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file (JPG, PNG, WEBP, GIF).');
            return;
        }

        const reader = new FileReader();
        reader.onload = e => {
            cameraAvatarData.value = ''; // Reset camera data
            previewImg.src = e.target.result;
            previewImg.style.display = 'block';
            if (initialFallback) initialFallback.style.display = 'none';
            if (newBadge) newBadge.style.display = 'inline-block';
            saveArea.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });

    cancelBtn.addEventListener('click', () => {
        fileInput.value = '';
        cameraAvatarData.value = '';
        saveArea.style.display = 'none';
        if (newBadge) newBadge.style.display = 'none';

        if (initialHadImg) {
            previewImg.src = initialSrc;
            previewImg.style.display = 'block';
            if (initialFallback) initialFallback.style.display = 'none';
        } else {
            previewImg.src = '';
            previewImg.style.display = 'none';
            if (initialFallback) initialFallback.style.display = 'block';
        }
    });

    // Camera handling
    openCamBtn.addEventListener('click', () => {
        camModal.style.display = 'flex';
        camModal.classList.add('open');
        startAvatarCam();
    });

    function closeCam() {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        camVideo.srcObject = null;
        camModal.classList.remove('open');
        camModal.style.display = 'none';
    }

    closeCamBtn.addEventListener('click', closeCam);
    camModal.addEventListener('click', e => {
        if (e.target === camModal) closeCam();
    });

    async function startAvatarCam() {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        try {
            const constraints = {
                video: {
                    facingMode: { ideal: facing },
                    width: { ideal: 720 },
                    height: { ideal: 720 }
                },
                audio: false
            };
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            camVideo.srcObject = stream;
            camVideo.style.display = 'block';
            camVideo.style.transform = (facing === 'user') ? 'scaleX(-1)' : 'none';
            camError.classList.remove('show');
            snapBtn.disabled = false;
        } catch (err) {
            camError.classList.add('show');
            snapBtn.disabled = true;
        }
    }

    flipCamBtn.addEventListener('click', () => {
        facing = (facing === 'user') ? 'environment' : 'user';
        if (stream) startAvatarCam();
    });

    snapBtn.addEventListener('click', () => {
        if (!stream) return;
        const size = Math.min(camVideo.videoWidth || 600, camVideo.videoHeight || 600);
        camCanvas.width = size;
        camCanvas.height = size;

        const ctx = camCanvas.getContext('2d');
        const startX = ((camVideo.videoWidth || size) - size) / 2;
        const startY = ((camVideo.videoHeight || size) - size) / 2;

        if (facing === 'user') {
            ctx.translate(size, 0);
            ctx.scale(-1, 1);
        }
        ctx.drawImage(camVideo, startX, startY, size, size, 0, 0, size, size);

        // Flash
        camFlash.classList.remove('flash');
        void camFlash.offsetWidth;
        camFlash.classList.add('flash');

        setTimeout(() => {
            const dataUrl = camCanvas.toDataURL('image/jpeg', 0.9);
            cameraAvatarData.value = dataUrl;
            fileInput.value = ''; // Reset standard file picker

            previewImg.src = dataUrl;
            previewImg.style.display = 'block';
            if (initialFallback) initialFallback.style.display = 'none';
            if (newBadge) newBadge.style.display = 'inline-block';
            saveArea.style.display = 'block';

            closeCam();
        }, 250);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
