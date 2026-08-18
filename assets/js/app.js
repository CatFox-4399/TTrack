/* app.js — ToiletTrack Frontend Logic */

document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // Live Clock in footer
    // ============================================================
    const footerTime = document.getElementById('footerTime');
    if (footerTime) {
        function updateClock() {
            const now = new Date();
            footerTime.textContent = now.toLocaleString('en-MY', {
                weekday: 'short', year: 'numeric', month: 'short',
                day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        }
        updateClock();
        setInterval(updateClock, 1000);
    }

    // ============================================================
    // Mobile Nav Toggle
    // ============================================================
    const navToggle  = document.getElementById('navToggle');
    const navLinks   = document.getElementById('navLinks');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => {
            navLinks.classList.toggle('open');
        });
    }

    // ============================================================
    // Auto-dismiss flash alerts after 4 seconds
    // ============================================================
    document.querySelectorAll('.alert.auto-dismiss').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 4000);
    });

    // ============================================================
    // Modal System
    // ============================================================
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-modal-open');
            const modal = document.getElementById(id);
            if (modal) modal.classList.add('open');
        });
    });
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });
    document.querySelectorAll('.modal-close, [data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay').classList.remove('open');
        });
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
            closeLightbox();
            closeAllCameraModals();
        }
    });

    // ============================================================
    // Delete Confirmation
    // ============================================================
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            const msg = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // ============================================================
    // History Timeline — expand/collapse
    // ============================================================
    document.querySelectorAll('.history-item-header').forEach(header => {
        header.addEventListener('click', () => {
            const item = header.closest('.history-item');
            item.classList.toggle('expanded');
            const icon = header.querySelector('.expand-icon');
            if (icon) icon.textContent = item.classList.contains('expanded') ? '▲' : '▼';
        });
    });
    const firstHistory = document.querySelector('.history-item');
    if (firstHistory) {
        firstHistory.classList.add('expanded');
        const icon = firstHistory.querySelector('.expand-icon');
        if (icon) icon.textContent = '▲';
    }

    // ============================================================
    // Lightbox for photos
    // ============================================================
    const lightboxOverlay = document.getElementById('lightboxOverlay');
    const lightboxImg     = document.getElementById('lightboxImg');

    function openLightbox(src) {
        if (!lightboxOverlay || !lightboxImg) return;
        lightboxImg.src = src;
        lightboxOverlay.classList.add('open');
    }
    function closeLightbox() {
        if (!lightboxOverlay) return;
        lightboxOverlay.classList.remove('open');
        if (lightboxImg) lightboxImg.src = '';
    }

    document.querySelectorAll('.photo-gallery a, .lightbox-trigger').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const src = link.getAttribute('href') || link.getAttribute('data-src');
            if (src) openLightbox(src);
        });
    });

    if (lightboxOverlay) {
        lightboxOverlay.addEventListener('click', e => {
            if (e.target === lightboxOverlay) closeLightbox();
        });
        const lbClose = lightboxOverlay.querySelector('.lightbox-close');
        if (lbClose) lbClose.addEventListener('click', closeLightbox);
    }

    // ============================================================
    // Password Toggle
    // ============================================================
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const wrap  = btn.closest('.password-wrap');
            const input = wrap ? wrap.querySelector('input') : null;
            if (!input) return;
            const isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            btn.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    });

    // ============================================================
    // Select-All checkboxes
    // ============================================================
    const selectAll = document.getElementById('selectAllToilets');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.toilet-checkbox').forEach(cb => {
                cb.checked = this.checked;
            });
        });
    }

    // ============================================================
    // Auto-submit filter form on select change
    // ============================================================
    document.querySelectorAll('.auto-submit-select').forEach(sel => {
        sel.addEventListener('change', () => sel.closest('form').submit());
    });

    // ============================================================
    // Countdown for open sessions
    // ============================================================
    document.querySelectorAll('[data-checkin-time]').forEach(el => {
        const checkinTime = new Date(el.getAttribute('data-checkin-time').replace(' ', 'T'));
        function updateElapsed() {
            const diff = Math.floor((Date.now() - checkinTime.getTime()) / 1000);
            const h = Math.floor(diff / 3600);
            const m = Math.floor((diff % 3600) / 60);
            const s = diff % 60;
            el.textContent = (h ? h + 'h ' : '') + (m ? m + 'm ' : '') + s + 's elapsed';
        }
        updateElapsed();
        setInterval(updateElapsed, 1000);
    });


    /* ===========================================================
       PHOTO CAPTURE SYSTEM
       Handles:
         1. Photo upload area (file picker + drag-and-drop + paste)
         2. Live camera modal (MediaStream API)
         3. DataTransfer injection so captured blobs submit with form
       =========================================================== */

    /**
     * Master list of captured blobs per upload area.
     * Key = previewGridId, Value = Array<{blob, filename, dataUrl}>
     */
    const capturedFiles = {};

    /**
     * Build (or rebuild) a DataTransfer on the hidden input so the form
     * submits all files: those from the file picker + camera captures.
     */
    function syncFilesToInput(inputEl, gridId) {
        const extra = capturedFiles[gridId] || [];
        // Re-attach original selected files + extras via DataTransfer
        const dt = new DataTransfer();
        // Preserved picker files are stored on the input element itself
        const pickerFiles = inputEl._pickerFiles || [];
        pickerFiles.forEach(f => dt.items.add(f));
        extra.forEach(entry => dt.items.add(new File([entry.blob], entry.filename, { type: entry.blob.type })));
        try {
            inputEl.files = dt.files;
        } catch(e) {
            // Fallback: some browsers block setting files; hidden inputs will carry blobs
        }
    }

    /**
     * Render a preview thumbnail in the grid.
     * @param {string}   dataUrl  — image src
     * @param {string}   gridId   — preview grid element ID
     * @param {string}   label    — optional label (e.g. "Camera")
     * @param {Function} onRemove — called when ✕ clicked
     */
    function addThumb(dataUrl, gridId, label, onRemove) {
        const grid = document.getElementById(gridId);
        if (!grid) return;

        const thumb = document.createElement('div');
        thumb.className = 'photo-thumb';
        thumb.innerHTML = `
            <img src="${dataUrl}" alt="Photo preview">
            <button type="button" class="remove-photo" title="Remove">✕</button>
            ${label ? `<div class="photo-thumb-label">${label}</div>` : ''}
        `;
        thumb.querySelector('.remove-photo').addEventListener('click', () => {
            thumb.remove();
            if (onRemove) onRemove();
        });
        grid.appendChild(thumb);
    }

    /**
     * Add a Blob as a captured photo (from camera or paste).
     */
    function addCapturedBlob(blob, gridId, inputEl, label) {
        const filename = label.toLowerCase().replace(/\s+/g, '_') + '_' + Date.now() + '.jpg';
        if (!capturedFiles[gridId]) capturedFiles[gridId] = [];

        const entry = { blob, filename, dataUrl: null };
        capturedFiles[gridId].push(entry);

        const reader = new FileReader();
        reader.onload = ev => {
            entry.dataUrl = ev.target.result;
            addThumb(ev.target.result, gridId, label, () => {
                // Remove from capturedFiles array on ✕
                const idx = capturedFiles[gridId].indexOf(entry);
                if (idx !== -1) capturedFiles[gridId].splice(idx, 1);
                syncFilesToInput(inputEl, gridId);
            });
            syncFilesToInput(inputEl, gridId);
        };
        reader.readAsDataURL(blob);
    }

    // ---- Initialise each upload area ----
    document.querySelectorAll('.photo-upload-area').forEach(area => {
        const input  = area.querySelector('input[type="file"]');
        const gridId = area.getAttribute('data-preview');
        if (!input || !gridId) return;

        capturedFiles[gridId] = [];
        input._pickerFiles = [];

        // --- Upgrade the area HTML with tabs + paste hint ---
        // Move the hidden file input out so it doesn't intercept button clicks
        const uploadBody = area.querySelector('.upload-icon')
            ? area.querySelector('.upload-icon').parentNode
            : area;

        // Insert tab row before the upload icon
        const tabRow = document.createElement('div');
        tabRow.className = 'capture-tabs';
        tabRow.innerHTML = `
            <button type="button" class="capture-tab-btn active" data-tab="upload">
                <i class="fas fa-folder-open"></i> Gallery / File
            </button>
            <button type="button" class="capture-tab-btn" data-tab="camera">
                <i class="fas fa-camera"></i> Live Camera
            </button>
        `;
        area.insertBefore(tabRow, area.firstChild);

        // Move input to after the tab row (so clicks on area still trigger it in upload mode)
        area.appendChild(input);

        // Camera open button (hidden by default until camera tab is active)
        const camBtn = document.createElement('button');
        camBtn.type = 'button';
        camBtn.className = 'open-camera-btn';
        camBtn.style.display = 'none';
        camBtn.innerHTML = '<i class="fas fa-camera-retro"></i> Open Camera';
        area.appendChild(camBtn);

        // Paste hint
        const pasteHint = document.createElement('div');
        pasteHint.className = 'paste-hint';
        pasteHint.innerHTML = `<i class="fas fa-clipboard"></i> Or paste: <kbd>Ctrl</kbd>+<kbd>V</kbd>`;
        area.appendChild(pasteHint);

        // --- Tab switching ---
        let activeTab = 'upload';
        tabRow.querySelectorAll('.capture-tab-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                activeTab = btn.getAttribute('data-tab');
                tabRow.querySelectorAll('.capture-tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                if (activeTab === 'upload') {
                    input.style.pointerEvents = '';
                    camBtn.style.display = 'none';
                } else {
                    // Camera tab — file input must NOT intercept clicks
                    input.style.pointerEvents = 'none';
                    camBtn.style.display = 'inline-flex';
                }
            });
        });

        // --- Drag & Drop ---
        area.addEventListener('dragover', e => {
            e.preventDefault();
            area.classList.add('drag-over');
        });
        area.addEventListener('dragleave', e => {
            if (!area.contains(e.relatedTarget)) area.classList.remove('drag-over');
        });
        area.addEventListener('drop', e => {
            e.preventDefault();
            area.classList.remove('drag-over');
            const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
            if (files.length === 0) return;
            files.forEach(f => {
                input._pickerFiles.push(f);
                const reader = new FileReader();
                reader.onload = ev => addThumb(ev.target.result, gridId, 'Drop', () => {
                    const idx = input._pickerFiles.indexOf(f);
                    if (idx !== -1) input._pickerFiles.splice(idx, 1);
                    syncFilesToInput(input, gridId);
                });
                reader.readAsDataURL(f);
            });
            syncFilesToInput(input, gridId);
        });

        // --- File picker change ---
        input.addEventListener('change', function () {
            const files = Array.from(this.files);
            input._pickerFiles = files; // Replace (not accumulate) picker files
            const grid = document.getElementById(gridId);
            // Clear existing picker thumbs (leave camera thumbs)
            grid.querySelectorAll('.photo-thumb:not([data-source="camera"])').forEach(t => t.remove());

            files.forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = ev => {
                    addThumb(ev.target.result, gridId, '', () => {
                        const idx = input._pickerFiles.indexOf(file);
                        if (idx !== -1) input._pickerFiles.splice(idx, 1);
                        syncFilesToInput(input, gridId);
                    });
                };
                reader.readAsDataURL(file);
            });
        });

        // --- Camera modal open ---
        camBtn.addEventListener('click', e => {
            e.stopPropagation();
            openCameraModal(input, gridId);
        });
    });

    // ---- Clipboard Paste (Ctrl+V / Cmd+V) ----
    document.addEventListener('paste', e => {
        const items = Array.from(e.clipboardData.items || []);
        const imageItems = items.filter(i => i.type.startsWith('image/'));
        if (imageItems.length === 0) return;

        // Find focused / hovered upload area, or fall back to first
        const areas = document.querySelectorAll('.photo-upload-area');
        let target = document.querySelector('.photo-upload-area:hover') || areas[0];
        if (!target) return;

        const input  = target.querySelector('input[type="file"]');
        const gridId = target.getAttribute('data-preview');
        if (!input || !gridId) return;

        target.classList.add('paste-highlight');
        setTimeout(() => target.classList.remove('paste-highlight'), 700);

        imageItems.forEach((item, i) => {
            const blob = item.getAsFile();
            if (blob) addCapturedBlob(blob, gridId, input, 'Paste');
        });
    });


    /* ===========================================================
       CAMERA MODAL
       =========================================================== */

    // Build a single reusable camera modal DOM node
    const cameraModal = document.createElement('div');
    cameraModal.className = 'camera-modal-overlay';
    cameraModal.id = 'cameraModalOverlay';
    cameraModal.innerHTML = `
        <div class="camera-modal-box">
            <div class="camera-modal-header">
                <div class="camera-modal-title">
                    <i class="fas fa-camera"></i> Live Camera Capture
                </div>
                <button class="camera-modal-close" id="cameraModalCloseBtn" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="camera-viewfinder" id="cameraViewfinder">
                <video id="cameraVideo" autoplay playsinline muted></video>
                <canvas id="cameraCanvas"></canvas>
                <div class="camera-flash" id="cameraFlash"></div>
                <div class="camera-start-msg" id="cameraStartMsg">
                    <i class="fas fa-camera-retro"></i>
                    <span>Camera will appear here</span>
                    <small style="color:var(--text-muted);font-size:0.78rem">Allow camera permission when prompted</small>
                </div>
            </div>

            <div class="camera-error" id="cameraError">
                <i class="fas fa-circle-exclamation"></i>
                <span id="cameraErrorText">Camera access denied. Please allow camera permission in your browser.</span>
            </div>

            <div class="camera-controls">
                <button class="flip-camera-btn" id="flipCameraBtn" type="button" title="Flip Camera">
                    <i class="fas fa-camera-rotate"></i>
                </button>
                <button class="shutter-btn" id="shutterBtn" type="button" disabled title="Take Photo"></button>
                <div style="width:44px"></div><!-- spacer -->
            </div>

            <div class="captured-strip" id="capturedStrip" style="display:none">
                <div class="captured-strip-label">Captured Photos</div>
            </div>

            <button class="camera-add-btn" id="cameraAddBtn" type="button" disabled>
                <i class="fas fa-check"></i> Use <span id="cameraAddCount">0</span> Photo(s)
            </button>
        </div>
    `;
    document.body.appendChild(cameraModal);

    // Camera state
    let cameraStream    = null;
    let facingMode      = 'environment'; // 'environment' = rear, 'user' = front
    let cameraCaptured  = []; // Array<Blob>
    let cameraTargetInput  = null;
    let cameraTargetGridId = null;

    const videoEl      = document.getElementById('cameraVideo');
    const canvasEl     = document.getElementById('cameraCanvas');
    const shutterBtn   = document.getElementById('shutterBtn');
    const flipBtn      = document.getElementById('flipCameraBtn');
    const cameraFlash  = document.getElementById('cameraFlash');
    const capturedStrip= document.getElementById('capturedStrip');
    const cameraAddBtn = document.getElementById('cameraAddBtn');
    const cameraAddCnt = document.getElementById('cameraAddCount');
    const cameraError  = document.getElementById('cameraError');
    const cameraErrTxt = document.getElementById('cameraErrorText');
    const cameraStartMsg=document.getElementById('cameraStartMsg');
    const cameraCloseBtn=document.getElementById('cameraModalCloseBtn');

    function openCameraModal(inputEl, gridId) {
        cameraTargetInput  = inputEl;
        cameraTargetGridId = gridId;
        cameraCaptured     = [];
        updateCameraAddBtn();

        // Clear captured strip
        capturedStrip.style.display = 'none';
        Array.from(capturedStrip.querySelectorAll('.captured-thumb')).forEach(t => t.remove());

        cameraError.classList.remove('show');
        cameraStartMsg.style.display = 'flex';
        shutterBtn.disabled = true;

        cameraModal.classList.add('open');
        startCamera();
    }

    async function startCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(t => t.stop());
            cameraStream = null;
        }

        try {
            const constraints = {
                video: {
                    facingMode: { ideal: facingMode },
                    width:  { ideal: 1280 },
                    height: { ideal: 960 }
                },
                audio: false
            };
            cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
            videoEl.srcObject = cameraStream;
            videoEl.style.display = 'block';
            cameraStartMsg.style.display = 'none';
            cameraError.classList.remove('show');
            shutterBtn.disabled = false;
        } catch (err) {
            shutterBtn.disabled = true;
            videoEl.style.display = 'none';
            cameraStartMsg.style.display = 'none';
            cameraErrTxt.textContent = err.name === 'NotAllowedError'
                ? 'Camera access denied. Please allow camera permission in your browser settings.'
                : 'Camera not available: ' + err.message;
            cameraError.classList.add('show');
        }
    }

    function stopCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(t => t.stop());
            cameraStream = null;
        }
        videoEl.srcObject = null;
        videoEl.style.display = 'none';
        cameraStartMsg.style.display = 'flex';
        shutterBtn.disabled = true;
    }

    function closeCameraModal() {
        stopCamera();
        cameraModal.classList.remove('open');
    }

    function closeAllCameraModals() {
        closeCameraModal();
    }

    // Shutter — capture frame
    shutterBtn.addEventListener('click', () => {
        if (!cameraStream) return;

        const vw = videoEl.videoWidth  || 1280;
        const vh = videoEl.videoHeight || 960;
        canvasEl.width  = vw;
        canvasEl.height = vh;

        const ctx = canvasEl.getContext('2d');
        ctx.drawImage(videoEl, 0, 0, vw, vh);

        // Flash effect
        cameraFlash.classList.remove('flash');
        void cameraFlash.offsetWidth; // reflow
        cameraFlash.classList.add('flash');

        canvasEl.toBlob(blob => {
            if (!blob) return;
            cameraCaptured.push(blob);

            // Show thumb in captured strip
            const reader = new FileReader();
            reader.onload = ev => {
                capturedStrip.style.display = 'flex';

                const ct = document.createElement('div');
                ct.className = 'captured-thumb';
                const idx = cameraCaptured.length - 1;
                ct.innerHTML = `
                    <img src="${ev.target.result}" alt="Capture ${idx+1}">
                    <button type="button" class="del-capture" title="Remove">✕</button>
                `;
                ct.querySelector('.del-capture').addEventListener('click', () => {
                    cameraCaptured.splice(idx, 1);
                    ct.remove();
                    if (cameraCaptured.length === 0) capturedStrip.style.display = 'none';
                    updateCameraAddBtn();
                });
                // Insert after label
                const label = capturedStrip.querySelector('.captured-strip-label');
                capturedStrip.insertBefore(ct, label ? label.nextSibling : null);
                updateCameraAddBtn();
            };
            reader.readAsDataURL(blob);
        }, 'image/jpeg', 0.9);
    });

    // Flip camera
    flipBtn.addEventListener('click', () => {
        facingMode = facingMode === 'environment' ? 'user' : 'environment';
        if (cameraStream) startCamera();
    });

    // Close camera modal
    cameraCloseBtn.addEventListener('click', closeCameraModal);
    cameraModal.addEventListener('click', e => {
        if (e.target === cameraModal) closeCameraModal();
    });

    // Add captured photos to form
    cameraAddBtn.addEventListener('click', () => {
        if (!cameraTargetInput || !cameraTargetGridId) return;
        cameraCaptured.forEach((blob, i) => {
            addCapturedBlob(blob, cameraTargetGridId, cameraTargetInput, `Camera ${i + 1}`);
        });
        closeCameraModal();
    });

    function updateCameraAddBtn() {
        const count = cameraCaptured.length;
        cameraAddBtn.disabled = count === 0;
        cameraAddCnt.textContent = count;
    }

});
