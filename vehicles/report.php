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
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Report Vehicle | NEO V-TRACK</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { background:#f4f6f9; }
    .report-card { max-width: 760px; margin: 40px auto; background:#fff; border-radius:10px; box-shadow:0 4px 20px rgba(0,0,0,.06); padding:32px; }
    h2 { margin-bottom: 20px; }
    .geo-status { font-size: 13px; color:#666; }
    .geo-status.ok { color:#198754; }
    .geo-status.err { color:#dc3545; }
    .photo-preview img { width:90px; height:90px; object-fit:cover; border-radius:6px; margin:6px 6px 0 0; border:1px solid #ddd; }
    .plate-search-wrapper { position: relative; }
    .plate-suggestions {
        position:absolute; top:100%; left:0; right:0; z-index:50;
        background:#fff; border:1px solid #ced4da; border-top:0; border-radius: 0 0 6px 6px;
        max-height: 280px; overflow-y:auto; box-shadow: 0 6px 16px rgba(0,0,0,.08);
        display:none;
    }
    .plate-suggestions .suggest-item { padding:8px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0; }
    .plate-suggestions .suggest-item:hover, .plate-suggestions .suggest-item.active { background:#eef4ff; }
    .plate-suggestions .suggest-empty { padding:10px 12px; color:#777; font-style:italic; }
    .plate-suggestions .badge-new { background:#dc3545; color:#fff; font-size:11px; padding:2px 6px; border-radius:4px; margin-left:6px; }
    .match-status { font-size:12px; margin-top:4px; }
    .match-status.found { color:#198754; }
    .match-status.new   { color:#dc3545; }
</style>
</head>
<body>
<div class="report-card">
    <h2><i class="fas fa-flag text-danger me-2"></i>Report Vehicle</h2>
    <p class="text-muted">Submit a vehicle offense report. Your account, location and photos are attached automatically.</p>

    <div id="alert-box"></div>

    <form id="reportForm" enctype="multipart/form-data" action="/api/report_vehicle_api.php" method="POST" novalidate>
        <input type="hidden" name="reporter_role" value="<?php echo htmlspecialchars($reporterRole); ?>">

        <div class="row">
            <div class="mb-3 col-md-6">
                <label class="form-label">Reporter Name</label>
                <input type="text" class="form-control" name="reporter_name" value="<?php echo htmlspecialchars($reporterName); ?>" readonly>
            </div>
            <div class="mb-3 col-md-6">
                <label class="form-label">Reporter Email</label>
                <input type="email" class="form-control" name="reporter_email" value="<?php echo htmlspecialchars($reporterEmail); ?>" readonly>
            </div>
        </div>

        <div class="mb-3 plate-search-wrapper">
            <label class="form-label">Plate Number <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="plate_number" id="plateInput" autocomplete="off" required style="text-transform:uppercase" placeholder="Type to search…">
            <div class="plate-suggestions" id="plateSuggestions"></div>
            <div class="match-status" id="matchStatus"></div>
        </div>

        <div class="row">
            <div class="mb-3 col-md-6">
                <label class="form-label">Owner Name</label>
                <input type="text" class="form-control" name="owner_name" id="ownerName">
            </div>
            <div class="mb-3 col-md-6">
                <label class="form-label">ID / Matric / Pass No.</label>
                <input type="text" class="form-control" name="id_number" id="idNumber">
            </div>
        </div>

        <div class="row">
            <div class="mb-3 col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" name="phone" id="phone">
            </div>
            <div class="mb-3 col-md-6">
                <label class="form-label">Vehicle Status</label>
                <select class="form-select" name="vehicle_status" id="vehicleStatus">
                    <option value="">-- Select --</option>
                    <option value="Staf">Staf</option>
                    <option value="Pelajar">Pelajar</option>
                    <option value="Pelawat">Pelawat</option>
                    <option value="Kontraktor">Kontraktor</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="mb-3 col-md-6">
                <label class="form-label">Vehicle Type</label>
                <select class="form-select" name="vehicle_type" id="vehicleType">
                    <option value="">-- Select --</option>
                    <option value="KERETA">KERETA</option>
                    <option value="MOTOSIKAL">MOTOSIKAL</option>
                    <option value="VAN">VAN</option>
                    <option value="LORI">LORI</option>
                    <option value="LAIN-LAIN">LAIN-LAIN</option>
                </select>
            </div>
            <div class="mb-3 col-md-6">
                <label class="form-label">Sticker</label>
                <select class="form-select" name="sticker" id="sticker">
                    <option value="">-- Select --</option>
                    <option value="ADA">ADA</option>
                    <option value="TIADA">TIADA</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Offense Details <span class="text-danger">*</span></label>
            <textarea class="form-control" name="offense_details" rows="3" required placeholder="Describe what happened…"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Photos <span class="text-danger">*</span></label>
            <input type="file" class="form-control" name="photos[]" id="photos" multiple accept="image/*" required>
            <small class="text-muted">JPG / PNG / WEBP. At least one photo required.</small>
            <div class="photo-preview" id="photoPreview"></div>
        </div>

        <div class="mb-3">
            <label class="form-label">Location (auto-detected)</label>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" class="form-control" id="coordsDisplay" readonly placeholder="Detecting…">
                <button type="button" class="btn btn-outline-secondary" id="retryGeo" title="Retry"><i class="fas fa-redo"></i></button>
            </div>
            <div class="geo-status" id="geoStatus">Requesting browser location permission…</div>
            <input type="hidden" name="latitude" id="latitude" required>
            <input type="hidden" name="longitude" id="longitude" required>
        </div>

        <button type="submit" class="btn btn-danger w-100" id="submitBtn">
            <i class="fas fa-paper-plane me-2"></i>Submit Report
        </button>
    </form>
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
            alertBox.innerHTML = '<div class="alert alert-warning">Location is required. Please allow location access and click retry.</div>';
            return;
        }
        submit.disabled = true;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting…';

        try {
            const fd = new FormData(form);
            const res = await fetch(form.action, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                alertBox.innerHTML = '<div class="alert alert-success">Report submitted (ID #' + data.report_id + '). Thank you.</div>';
                form.reset();
                preview.innerHTML = '';
                coordsEl.value = '';
                latEl.value = lngEl.value = '';
                matchStatus.textContent = '';
                requestLocation();
            } else {
                alertBox.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Submission failed.') + '</div>';
            }
        } catch (err) {
            alertBox.innerHTML = '<div class="alert alert-danger">Network error: ' + err.message + '</div>';
        } finally {
            submit.disabled = false;
            submit.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Report';
        }
    });
})();
</script>
</body>
</html>
