<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require $_SERVER['DOCUMENT_ROOT'].'/includes/lang_switch.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/auth_guard.php';

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
nv_guard_page($con, 'reports');   // do-report is folded into the Laporan permission

$t = $lang === 'bm' ? [
    'eyebrow' => 'Laporan', 'title' => 'Lapor kenderaan',
    'sub' => 'Hantar laporan kesalahan kenderaan. Pelapor, lokasi dan foto dilampirkan secara automatik.',
    'back' => 'Kembali ke laporan',
    'find_eyebrow' => 'Cari Kenderaan', 'find_title' => 'Cari kenderaan',
    'find_sub' => 'Taip nombor plat untuk mengisi maklumat pemilik secara automatik, atau masukkan plat yang belum ada dalam rekod.',
    'plate' => 'Nombor plat', 'plate_ph' => 'Taip untuk mencari…',
    'reporter_name' => 'Nama pelapor', 'reporter_email' => 'Emel pelapor',
    'owner_name' => 'Nama pemilik', 'id_number' => 'No. ID / Matrik / Pas', 'phone' => 'Telefon',
    'vehicle_status' => 'Kategori kenderaan', 'vehicle_type' => 'Jenis kenderaan', 'select' => '-- Pilih --',
    'offense' => 'Butiran kesalahan', 'offense_ph' => 'Terangkan apa yang berlaku…',
    'photos' => 'Foto', 'photos_hint' => 'JPG / PNG / WEBP. Sekurang-kurangnya satu foto diperlukan.',
    'location' => 'Lokasi', 'loc_desc' => 'Keterangan lokasi / mercu tanda', 'loc_desc_ph' => 'cth. Parkir C, berhampiran Perpustakaan',
    'manual_help' => 'Pengesanan automatik memerlukan sambungan selamat (HTTPS). Tetapkan lokasi secara manual:',
    'use_campus' => 'Guna lokasi kampus UiTM Segamat', 'apply' => 'Guna koordinat',
    'submit' => 'Hantar laporan', 'geo_perm' => 'Meminta kebenaran lokasi pelayar…',
] : [
    'eyebrow' => 'Report', 'title' => 'Report a vehicle',
    'sub' => 'Submit a vehicle offence report. Reporter, location and photos are attached automatically.',
    'back' => 'Back to reports',
    'find_eyebrow' => 'Find Vehicle', 'find_title' => 'Find vehicle',
    'find_sub' => 'Type a plate to autofill owner details, or enter a plate that is not on file yet.',
    'plate' => 'Plate number', 'plate_ph' => 'Type to search…',
    'reporter_name' => 'Reporter name', 'reporter_email' => 'Reporter email',
    'owner_name' => 'Owner name', 'id_number' => 'ID / Matric / Pass No.', 'phone' => 'Phone',
    'vehicle_status' => 'Vehicle category', 'vehicle_type' => 'Vehicle type', 'select' => '-- Select --',
    'offense' => 'Offence details', 'offense_ph' => 'Describe what happened…',
    'photos' => 'Photos', 'photos_hint' => 'JPG / PNG / WEBP. At least one photo required.',
    'location' => 'Location', 'loc_desc' => 'Location description / landmark', 'loc_desc_ph' => 'e.g. Parking C, near Library',
    'manual_help' => 'Auto-detect needs a secure (HTTPS) connection. Set the location manually:',
    'use_campus' => 'Use UiTM Segamat campus', 'apply' => 'Apply coordinates',
    'submit' => 'Submit report', 'geo_perm' => 'Requesting browser location permission…',
];

// Strings used by the inline JS.
$jsL = $lang === 'bm' ? [
    'unsupported' => 'Geolokasi tidak disokong. Masukkan lokasi secara manual di bawah.',
    'insecure' => 'Pengesanan automatik memerlukan sambungan selamat (HTTPS). Masukkan lokasi secara manual di bawah.',
    'detecting' => 'Sedang mengesan lokasi…',
    'captured' => 'Lokasi diperoleh (ketepatan {m} m).',
    'failed' => 'Tidak dapat mengesan ({e}). Masukkan lokasi secara manual di bawah.',
    'campus' => 'Menggunakan lokasi kampus UiTM Segamat.',
    'applied' => 'Lokasi manual digunakan: {c}', 'set' => 'Lokasi ditetapkan: {c}',
    'invalid' => 'Masukkan latitud (-90..90) dan longitud (-180..180) yang sah.',
    'loc_required' => 'Lokasi diperlukan. Benarkan akses lokasi atau masukkannya secara manual.',
    'submitting' => 'Menghantar…', 'success' => 'Laporan dihantar (ID #{id}). Terima kasih.',
    'failed_submit' => 'Penghantaran gagal.', 'neterr' => 'Ralat rangkaian: {e}',
] : [
    'unsupported' => 'Geolocation is not supported. Enter the location manually below.',
    'insecure' => 'Auto-detect needs a secure (HTTPS) connection. Enter the location manually below.',
    'detecting' => 'Detecting location…',
    'captured' => 'Location captured (accuracy {m} m).',
    'failed' => 'Could not auto-detect ({e}). Enter the location manually below.',
    'campus' => 'Using UiTM Segamat campus location.',
    'applied' => 'Manual location applied: {c}', 'set' => 'Location set: {c}',
    'invalid' => 'Enter a valid latitude (-90..90) and longitude (-180..180).',
    'loc_required' => 'Location is required. Allow location access or enter it manually.',
    'submitting' => 'Submitting…', 'success' => 'Report submitted (ID #{id}). Thank you.',
    'failed_submit' => 'Submission failed.', 'neterr' => 'Network error: {e}',
];
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
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($t['title']) ?></h1>
      <p class="sub"><?= htmlspecialchars($t['sub']) ?></p>
    </div>
    <div class="actions">
      <a class="btn btn-ghost" href="/admin/reports.php"><i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['back']) ?></a>
    </div>
  </div>

  <div id="alert-box"></div>

  <form id="reportForm" enctype="multipart/form-data" action="/api/report_vehicle_api.php" method="POST" novalidate>
        <input type="hidden" name="reporter_role" value="<?php echo htmlspecialchars($reporterRole); ?>">

    <!-- HERO PLATE CARD -->
    <div class="card nv-stack" style="margin-bottom:20px;">
      <div>
        <span class="eyebrow"><?= htmlspecialchars($t['find_eyebrow']) ?></span>
        <h2 class="text-display" style="margin-top:4px;"><?= htmlspecialchars($t['find_title']) ?></h2>
        <p class="sub"><?= htmlspecialchars($t['find_sub']) ?></p>
      </div>
      <div class="field plate-search-wrapper">
        <label class="field-label" for="plateInput"><?= htmlspecialchars($t['plate']) ?> <span style="color:var(--status-bad);">*</span></label>
        <input type="text" class="input mono" name="plate_number" id="plateInput" autocomplete="off" required placeholder="<?= htmlspecialchars($t['plate_ph']) ?>" autofocus>
        <div class="plate-suggestions" id="plateSuggestions"></div>
        <div class="match-status" id="matchStatus"></div>
      </div>
    </div>

    <!-- REST-OF-REPORT CARD -->
    <div class="card nv-stack gap-6">
        <div class="nv-grid cols-2">
            <div class="field">
                <label class="field-label"><?= htmlspecialchars($t['reporter_name']) ?></label>
                <input type="text" class="input" name="reporter_name" value="<?php echo htmlspecialchars($reporterName); ?>" readonly>
            </div>
            <div class="field">
                <label class="field-label"><?= htmlspecialchars($t['reporter_email']) ?></label>
                <input type="email" class="input" name="reporter_email" value="<?php echo htmlspecialchars($reporterEmail); ?>" readonly>
            </div>
        </div>

        <div class="nv-grid cols-2">
            <div class="field">
                <label class="field-label"><?= htmlspecialchars($t['owner_name']) ?></label>
                <input type="text" class="input" name="owner_name" id="ownerName">
            </div>
            <div class="field">
                <label class="field-label"><?= htmlspecialchars($t['id_number']) ?></label>
                <input type="text" class="input" name="id_number" id="idNumber">
            </div>
        </div>

        <div class="nv-grid cols-2">
            <div class="field">
                <label class="field-label"><?= htmlspecialchars($t['phone']) ?></label>
                <input type="text" class="input" name="phone" id="phone">
            </div>
            <div class="field">
                <label class="field-label"><?= htmlspecialchars($t['vehicle_status']) ?></label>
                <select class="select" name="vehicle_status" id="vehicleStatus">
                    <option value=""><?= htmlspecialchars($t['select']) ?></option>
                    <option value="Staf">Staf</option>
                    <option value="Pelajar">Pelajar</option>
                    <option value="Pelawat">Pelawat</option>
                    <option value="Kontraktor">Kontraktor</option>
                </select>
            </div>
        </div>

        <div class="nv-grid cols-2">
            <div class="field">
                <label class="field-label"><?= htmlspecialchars($t['vehicle_type']) ?></label>
                <select class="select" name="vehicle_type" id="vehicleType">
                    <option value=""><?= htmlspecialchars($t['select']) ?></option>
                    <option value="KERETA">KERETA</option>
                    <option value="MOTOSIKAL">MOTOSIKAL</option>
                </select>
            </div>
        </div>

        <div class="field">
            <label class="field-label"><?= htmlspecialchars($t['offense']) ?> <span style="color:var(--status-bad);">*</span></label>
            <textarea class="input" name="offense_details" rows="3" required placeholder="<?= htmlspecialchars($t['offense_ph']) ?>"></textarea>
        </div>

        <div class="field">
            <label class="field-label"><?= htmlspecialchars($t['photos']) ?> <span style="color:var(--status-bad);">*</span></label>
            <input type="file" class="input" name="photos[]" id="photos" multiple accept="image/*" required>
            <small class="text-muted"><?= htmlspecialchars($t['photos_hint']) ?></small>
            <div class="photo-preview" id="photoPreview"></div>
        </div>

        <div class="field">
            <label class="field-label"><?= htmlspecialchars($t['location']) ?> <span style="color:var(--status-bad);">*</span></label>
            <div class="nv-row gap-2">
                <input type="text" class="input" id="coordsDisplay" readonly placeholder="<?= htmlspecialchars($t['geo_perm']) ?>">
                <button type="button" class="btn btn-ghost" id="retryGeo" title="Retry"><i data-lucide="refresh-cw"></i></button>
            </div>
            <div class="geo-status" id="geoStatus"><?= htmlspecialchars($t['geo_perm']) ?></div>

            <div id="manualGeo" style="display:none;margin-top:10px;padding:12px;border:1px dashed var(--border);border-radius:8px;">
                <div style="font-size:13px;margin-bottom:8px;color:var(--fg-2);"><?= htmlspecialchars($t['manual_help']) ?></div>
                <div class="nv-grid cols-2">
                    <div class="field"><label class="field-label">Latitude</label>
                        <input type="text" class="input mono" id="latManual" inputmode="decimal" placeholder="2.48796"></div>
                    <div class="field"><label class="field-label">Longitude</label>
                        <input type="text" class="input mono" id="lngManual" inputmode="decimal" placeholder="102.72929"></div>
                </div>
                <div class="nv-row gap-2" style="flex-wrap:wrap;margin-top:6px;">
                    <button type="button" class="btn btn-ghost" id="useCampus"><i data-lucide="map-pin"></i> <?= htmlspecialchars($t['use_campus']) ?></button>
                    <button type="button" class="btn btn-ghost" id="applyManual"><i data-lucide="check"></i> <?= htmlspecialchars($t['apply']) ?></button>
                </div>
            </div>

            <div class="field" style="margin-top:8px;">
                <label class="field-label"><?= htmlspecialchars($t['loc_desc']) ?></label>
                <input type="text" class="input" name="location_text" id="locationText" placeholder="<?= htmlspecialchars($t['loc_desc_ph']) ?>">
            </div>

            <input type="hidden" name="latitude" id="latitude" required>
            <input type="hidden" name="longitude" id="longitude" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;" id="submitBtn">
            <i data-lucide="send"></i> <?= htmlspecialchars($t['submit']) ?>
        </button>
    </div>
  </form>
</main>
</div>
<script>
const NVL = <?= json_encode($jsL, JSON_UNESCAPED_UNICODE) ?>;
const NV_SUBMIT_LABEL = <?= json_encode($t['submit'], JSON_UNESCAPED_UNICODE) ?>;
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
    const manualGeo = document.getElementById('manualGeo');
    const latManual = document.getElementById('latManual');
    const lngManual = document.getElementById('lngManual');
    const CAMPUS = { lat: 2.4879643974998853, lng: 102.72929186652237 }; // UiTM Cawangan Johor, Kampus Segamat

    function fmt(s, vars) { return s.replace(/\{(\w+)\}/g, (m, k) => (vars && vars[k] != null) ? vars[k] : m); }
    function setCoords(lat, lng, note) {
        latEl.value = Number(lat).toFixed(8);
        lngEl.value = Number(lng).toFixed(8);
        coordsEl.value = latEl.value + ', ' + lngEl.value;
        statusEl.textContent = note || fmt(NVL.set, { c: coordsEl.value });
        statusEl.className = 'geo-status ok';
    }
    function revealManual(msg) { manualGeo.style.display = 'block'; statusEl.textContent = msg; statusEl.className = 'geo-status err'; }

    function requestLocation() {
        if (!navigator.geolocation || !window.isSecureContext) {
            revealManual(!navigator.geolocation ? NVL.unsupported : NVL.insecure);
            return;
        }
        statusEl.textContent = NVL.detecting;
        statusEl.className = 'geo-status';
        navigator.geolocation.getCurrentPosition(
            (pos) => { manualGeo.style.display = 'none'; setCoords(pos.coords.latitude, pos.coords.longitude, fmt(NVL.captured, { m: Math.round(pos.coords.accuracy) })); },
            (err) => { revealManual(fmt(NVL.failed, { e: err.message })); },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }
    document.getElementById('retryGeo').addEventListener('click', requestLocation);
    document.getElementById('useCampus').addEventListener('click', () => {
        latManual.value = CAMPUS.lat.toFixed(5); lngManual.value = CAMPUS.lng.toFixed(5);
        setCoords(CAMPUS.lat, CAMPUS.lng, NVL.campus);
    });
    document.getElementById('applyManual').addEventListener('click', () => {
        const la = parseFloat(latManual.value), lo = parseFloat(lngManual.value);
        if (isNaN(la) || isNaN(lo) || la < -90 || la > 90 || lo < -180 || lo > 180) {
            statusEl.textContent = NVL.invalid; statusEl.className = 'geo-status err'; return;
        }
        setCoords(la, lo, fmt(NVL.applied, { c: la.toFixed(5) + ', ' + lo.toFixed(5) }));
    });
    requestLocation();

    // ===== Photo preview =====
    photoInp.addEventListener('change', () => {
        preview.innerHTML = '';
        Array.from(photoInp.files).forEach(f => { const img = document.createElement('img'); img.src = URL.createObjectURL(f); preview.appendChild(img); });
    });

    // ===== Plate autocomplete =====
    const plateInput  = document.getElementById('plateInput');
    const suggestBox  = document.getElementById('plateSuggestions');
    const matchStatus = document.getElementById('matchStatus');
    const ownerName   = document.getElementById('ownerName');
    const idNumber    = document.getElementById('idNumber');
    const phoneFld    = document.getElementById('phone');
    const vehicleType = document.getElementById('vehicleType');
    const vehicleStat = document.getElementById('vehicleStatus');
    let debounceTimer = null;

    function fillFromMatch(m) {
        ownerName.value = m.name || ''; idNumber.value = m.idnumber || ''; phoneFld.value = m.phone || '';
        vehicleType.value = matchOption(vehicleType, m.type) || ''; vehicleStat.value = matchOption(vehicleStat, m.status) || '';
    }
    function matchOption(selectEl, value) {
        if (!value) return ''; const v = value.toUpperCase();
        for (const o of selectEl.options) { if (o.value.toUpperCase() === v) return o.value; } return '';
    }
    function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    function renderSuggestions(items, q) {
        suggestBox.innerHTML = '';
        if (items.length === 0) { suggestBox.style.display = 'none'; matchStatus.textContent = ''; return; }
        items.forEach((m) => {
            const d = document.createElement('div');
            d.className = 'suggest-item';
            d.innerHTML = '<strong>' + escapeHtml(m.plate) + '</strong> &mdash; ' + escapeHtml(m.name || '') + ' <small class="text-muted">[' + escapeHtml(m.status || '') + ']</small>';
            d.addEventListener('mousedown', (e) => {
                e.preventDefault(); plateInput.value = m.plate; fillFromMatch(m);
                matchStatus.textContent = '✔ ' + (m.name || '') + ' (' + (m.status || '') + ')'; matchStatus.className = 'match-status found';
                suggestBox.style.display = 'none';
            });
            suggestBox.appendChild(d);
        });
        suggestBox.style.display = 'block';
    }
    plateInput.addEventListener('input', () => {
        const q = plateInput.value.trim(); clearTimeout(debounceTimer);
        if (q.length < 2) { suggestBox.style.display = 'none'; matchStatus.textContent = ''; return; }
        debounceTimer = setTimeout(async () => {
            try {
                const res = await fetch('/api/plate_search_api.php?q=' + encodeURIComponent(q));
                renderSuggestions(await res.json(), q);
            } catch (e) { matchStatus.textContent = ''; }
        }, 200);
    });
    plateInput.addEventListener('blur', () => { setTimeout(() => { suggestBox.style.display = 'none'; }, 150); });

    // ===== Submit =====
    form.addEventListener('submit', async (e) => {
        e.preventDefault(); alertBox.innerHTML = '';
        if (!latEl.value || !lngEl.value) { alertBox.innerHTML = '<div class="flash warn">' + NVL.loc_required + '</div>'; return; }
        submit.disabled = true; submit.innerHTML = '<i data-lucide="loader-2"></i> ' + NVL.submitting;
        try {
            const res = await fetch(form.action, { method: 'POST', body: new FormData(form) });
            const data = await res.json();
            if (data.success) {
                alertBox.innerHTML = '<div class="flash ok">' + fmt(NVL.success, { id: data.report_id }) + '</div>';
                form.reset(); preview.innerHTML = ''; coordsEl.value = ''; latEl.value = lngEl.value = ''; matchStatus.textContent = '';
                requestLocation();
            } else {
                alertBox.innerHTML = '<div class="flash bad">' + (data.message || NVL.failed_submit) + '</div>';
            }
        } catch (err) {
            alertBox.innerHTML = '<div class="flash bad">' + fmt(NVL.neterr, { e: err.message }) + '</div>';
        } finally {
            submit.disabled = false; submit.innerHTML = '<i data-lucide="send"></i> ' + NV_SUBMIT_LABEL;
            if (window.lucide) window.lucide.createIcons();
        }
    });
})();
</script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
