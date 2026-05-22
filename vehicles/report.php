<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

$reporterName  = '';
$reporterEmail = '';
$reporterRole  = 'user';

if (!empty($_SESSION['email_Admin'])) {
    $reporterEmail = $_SESSION['email_Admin'];
    $reporterRole  = 'admin';
    $stmt = $con->prepare("SELECT name FROM `admin` WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $reporterEmail);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $reporterName = $row['name'] ?? '';
} elseif (!empty($_SESSION['email'])) {
    $reporterEmail = $_SESSION['email'];
    $reporterRole  = 'user';
    $stmt = $con->prepare("SELECT name FROM `user` WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $reporterEmail);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $reporterName = $row['name'] ?? '';
} else {
    header('Location: /auth/login.php');
    exit;
}
?>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php'; ?>
<style>
    .plate-search-wrapper { position: relative; }
    .plate-suggestions {
        position:absolute; top:100%; left:0; right:0; z-index:50;
        background:#fff; border:1px solid var(--border); border-top:0; border-radius: 0 0 6px 6px;
        max-height: 280px; overflow-y:auto; box-shadow: var(--shadow-2);
        display:none;
    }
    .plate-suggestions .suggest-item { padding:8px 12px; cursor:pointer; border-bottom:1px solid var(--neutral-100); }
    .plate-suggestions .suggest-item:hover, .plate-suggestions .suggest-item.active { background:var(--surface-tint); }
    .plate-suggestions .suggest-empty { padding:10px 12px; color:var(--fg-3); font-style:italic; }
    .plate-suggestions .badge-new { background:var(--status-bad); color:#fff; font-size:11px; padding:2px 6px; border-radius:4px; margin-left:6px; }
    .match-status { font-size:12px; margin-top:4px; }
    .match-status.found { color:var(--status-ok); }
    .match-status.new   { color:var(--status-bad); }
    .geo-status { font-size: 13px; color:var(--fg-3); }
    .geo-status.ok { color:var(--status-ok); }
    .geo-status.err { color:var(--status-bad); }
    .photo-preview img { width:90px; height:90px; object-fit:cover; border-radius:6px; margin:6px 6px 0 0; border:1px solid var(--border); }
</style>
<body>
<div class="nv-shell">
<?php $nv_active='reports'; include $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow">Laporan</span>
      <h1>Report a vehicle</h1>
      <p class="sub">Submit a vehicle offense report. Reporter, location, and photos are attached automatically.</p>
    </div>
    <div class="actions">
      <a class="btn btn-ghost" href="/admin/reports.php"><i data-lucide="arrow-left"></i> Back to reports</a>
    </div>
  </div>

  <div id="alert-box"></div>

  <form id="reportForm" enctype="multipart/form-data" action="/api/report_vehicle_api.php" method="POST" novalidate>
        <input type="hidden" name="reporter_role" value="<?php echo htmlspecialchars($reporterRole); ?>">

    <!-- HERO PLATE CARD -->
    <div class="card nv-stack" style="margin-bottom:20px;">
      <div>
        <span class="eyebrow">Cari Kenderaan</span>
        <h2 class="text-display" style="margin-top:4px;">Find vehicle</h2>
        <p class="sub">Type a plate to autofill owner details, or enter a plate that isn't on file yet.</p>
      </div>
      <div class="field plate-search-wrapper">
        <label class="field-label" for="plateInput">Plate number <span style="color:var(--status-bad);">*</span></label>
        <input type="text" class="input mono" name="plate_number" id="plateInput" autocomplete="off" required placeholder="Type to search…" autofocus>
        <div class="plate-suggestions" id="plateSuggestions"></div>
        <div class="match-status" id="matchStatus"></div>
      </div>
    </div>

    <!-- REST-OF-REPORT CARD -->
    <div class="card nv-stack gap-6">
        <div class="nv-grid cols-2">
            <div class="field">
                <label class="field-label">Reporter Name</label>
                <input type="text" class="input" name="reporter_name" value="<?php echo htmlspecialchars($reporterName); ?>" readonly>
            </div>
            <div class="field">
                <label class="field-label">Reporter Email</label>
                <input type="email" class="input" name="reporter_email" value="<?php echo htmlspecialchars($reporterEmail); ?>" readonly>
            </div>
        </div>

        <div class="nv-grid cols-2">
            <div class="field">
                <label class="field-label">Owner Name</label>
                <input type="text" class="input" name="owner_name" id="ownerName">
            </div>
            <div class="field">
                <label class="field-label">ID / Matric / Pass No.</label>
                <input type="text" class="input" name="id_number" id="idNumber">
            </div>
        </div>

        <div class="nv-grid cols-2">
            <div class="field">
                <label class="field-label">Phone</label>
                <input type="text" class="input" name="phone" id="phone">
            </div>
            <div class="field">
                <label class="field-label">Vehicle Status</label>
                <select class="select" name="vehicle_status" id="vehicleStatus">
                    <option value="">-- Select --</option>
                    <option value="Staf">Staf</option>
                    <option value="Pelajar">Pelajar</option>
                    <option value="Pelawat">Pelawat</option>
                    <option value="Kontraktor">Kontraktor</option>
                </select>
            </div>
        </div>

        <div class="nv-grid cols-2">
            <div class="field">
                <label class="field-label">Vehicle Type</label>
                <select class="select" name="vehicle_type" id="vehicleType">
                    <option value="">-- Select --</option>
                    <option value="KERETA">KERETA</option>
                    <option value="MOTOSIKAL">MOTOSIKAL</option>
                    <option value="VAN">VAN</option>
                    <option value="LORI">LORI</option>
                    <option value="LAIN-LAIN">LAIN-LAIN</option>
                </select>
            </div>
            <div class="field">
                <label class="field-label">Sticker</label>
                <select class="select" name="sticker" id="sticker">
                    <option value="">-- Select --</option>
                    <option value="ADA">ADA</option>
                    <option value="TIADA">TIADA</option>
                </select>
            </div>
        </div>

        <div class="field">
            <label class="field-label">Offense Details <span style="color:var(--status-bad);">*</span></label>
            <textarea class="input" name="offense_details" rows="3" required placeholder="Describe what happened…"></textarea>
        </div>

        <div class="field">
            <label class="field-label">Photos <span style="color:var(--status-bad);">*</span></label>
            <input type="file" class="input" name="photos[]" id="photos" multiple accept="image/*" required>
            <small class="text-muted">JPG / PNG / WEBP. At least one photo required.</small>
            <div class="photo-preview" id="photoPreview"></div>
        </div>

        <div class="field">
            <label class="field-label">Location (auto-detected)</label>
            <div class="nv-row gap-2">
                <input type="text" class="input" id="coordsDisplay" readonly placeholder="Detecting…">
                <button type="button" class="btn btn-ghost" id="retryGeo" title="Retry"><i data-lucide="refresh-cw"></i></button>
            </div>
            <div class="geo-status" id="geoStatus">Requesting browser location permission…</div>
            <input type="hidden" name="latitude" id="latitude" required>
            <input type="hidden" name="longitude" id="longitude" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;" id="submitBtn">
            <i data-lucide="send"></i> Submit report
        </button>
    </div>
  </form>
</main>
</div>
<script>
(function () {
    const statusEl = document.getElementById('geoStatus');
    const coordsEl = document.getElementById('coordsDisplay');
    const latEl    = document.getElementById('latitude');
    const lngEl    = document.getElementById('longitude');
    const form     = document.getElementById('reportForm');
    const submit   = document.getElementById('submitBtn');
    const alertBox = document.getElementById('alert-box');
    const preview  = document.getElementById('photoPreview');
    const photoInp = document.getElementById('photos');

    // ===== Geolocation =====
    function requestLocation() {
        if (!navigator.geolocation) {
            statusEl.textContent = 'Geolocation not supported by this browser.';
            statusEl.classList.add('err');
            return;
        }
        statusEl.textContent = 'Detecting location…';
        statusEl.className = 'geo-status';
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                latEl.value = pos.coords.latitude.toFixed(8);
                lngEl.value = pos.coords.longitude.toFixed(8);
                coordsEl.value = latEl.value + ', ' + lngEl.value;
                statusEl.textContent = 'Location captured (accuracy ' + Math.round(pos.coords.accuracy) + ' m).';
                statusEl.className = 'geo-status ok';
            },
            (err) => {
                statusEl.textContent = 'Could not get location: ' + err.message + '. Please allow location access and retry.';
                statusEl.className = 'geo-status err';
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }
    document.getElementById('retryGeo').addEventListener('click', requestLocation);
    requestLocation();

    // ===== Photo preview =====
    photoInp.addEventListener('change', () => {
        preview.innerHTML = '';
        Array.from(photoInp.files).forEach(f => {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(f);
            preview.appendChild(img);
        });
    });

    // ===== Plate autocomplete =====
    const plateInput   = document.getElementById('plateInput');
    const suggestBox   = document.getElementById('plateSuggestions');
    const matchStatus  = document.getElementById('matchStatus');
    const ownerName    = document.getElementById('ownerName');
    const idNumber     = document.getElementById('idNumber');
    const phoneFld     = document.getElementById('phone');
    const vehicleType  = document.getElementById('vehicleType');
    const vehicleStat  = document.getElementById('vehicleStatus');
    const stickerSel   = document.getElementById('sticker');

    let debounceTimer = null;
    let lastMatches   = [];

    function fillFromMatch(m) {
        ownerName.value   = m.name || '';
        idNumber.value    = m.idnumber || '';
        phoneFld.value    = m.phone || '';
        vehicleType.value = matchOption(vehicleType, m.type) || '';
        vehicleStat.value = matchOption(vehicleStat, m.status) || '';
        stickerSel.value  = matchOption(stickerSel, m.sticker) || '';
    }

    function matchOption(selectEl, value) {
        if (!value) return '';
        const v = value.toUpperCase();
        for (const o of selectEl.options) {
            if (o.value.toUpperCase() === v) return o.value;
        }
        return '';
    }

    function clearOwnerFields() {
        ownerName.value = '';
        idNumber.value  = '';
        phoneFld.value  = '';
        vehicleType.value = '';
        vehicleStat.value = '';
        stickerSel.value  = '';
    }

    function renderSuggestions(items, q) {
        suggestBox.innerHTML = '';
        if (items.length === 0) {
            suggestBox.innerHTML = '<div class="suggest-empty">No match — plate "<strong>' + escapeHtml(q) + '</strong>" will be saved as new.</div>';
            suggestBox.style.display = 'block';
            matchStatus.textContent = '✚ New plate — will be saved as a new entry.';
            matchStatus.className = 'match-status new';
            return;
        }
        items.forEach((m, idx) => {
            const d = document.createElement('div');
            d.className = 'suggest-item';
            d.innerHTML = '<strong>' + escapeHtml(m.plate) + '</strong> &mdash; ' +
                          escapeHtml(m.name || '(no name)') +
                          ' <small class="text-muted">[' + escapeHtml(m.status || '') + ']</small>';
            d.addEventListener('mousedown', (e) => {
                e.preventDefault();
                plateInput.value = m.plate;
                fillFromMatch(m);
                matchStatus.textContent = '✔ Existing record matched: ' + m.name + ' (' + m.status + ')';
                matchStatus.className = 'match-status found';
                suggestBox.style.display = 'none';
            });
            suggestBox.appendChild(d);
        });
        suggestBox.style.display = 'block';
        matchStatus.textContent = items.length + ' match(es) found — click to fill, or keep typing for a new plate.';
        matchStatus.className = 'match-status';
    }

    function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    plateInput.addEventListener('input', () => {
        const q = plateInput.value.trim();
        clearTimeout(debounceTimer);
        if (q.length < 2) {
            suggestBox.style.display = 'none';
            matchStatus.textContent = '';
            return;
        }
        debounceTimer = setTimeout(async () => {
            try {
                const res = await fetch('/api/plate_search_api.php?q=' + encodeURIComponent(q));
                const data = await res.json();
                lastMatches = data;
                renderSuggestions(data, q);
            } catch (e) {
                matchStatus.textContent = 'Search failed: ' + e.message;
                matchStatus.className = 'match-status new';
            }
        }, 200);
    });

    plateInput.addEventListener('blur', () => {
        setTimeout(() => { suggestBox.style.display = 'none'; }, 150);
    });

    // ===== Submit =====
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        alertBox.innerHTML = '';
        if (!latEl.value || !lngEl.value) {
            alertBox.innerHTML = '<div class="flash warn">Location is required. Please allow location access and click retry.</div>';
            return;
        }
        submit.disabled = true;
        submit.innerHTML = '<i data-lucide="loader-2"></i> Submitting…';

        try {
            const fd = new FormData(form);
            const res = await fetch(form.action, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                alertBox.innerHTML = '<div class="flash ok">Report submitted (ID #' + data.report_id + '). Thank you.</div>';
                form.reset();
                preview.innerHTML = '';
                coordsEl.value = '';
                latEl.value = lngEl.value = '';
                matchStatus.textContent = '';
                requestLocation();
            } else {
                alertBox.innerHTML = '<div class="flash bad">' + (data.message || 'Submission failed.') + '</div>';
            }
        } catch (err) {
            alertBox.innerHTML = '<div class="flash bad">Network error: ' + err.message + '</div>';
        } finally {
            submit.disabled = false;
            submit.innerHTML = '<i data-lucide="send"></i> Submit report';
        }
    });
})();
</script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
