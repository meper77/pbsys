<?php
/**
 * Dependency-free stacked bar charts for NEO V-TRACK.
 *
 * Renders inline SVG (no Chart.js / CDN) so it works on the intranet HTTP host.
 * Aggregates the unified `owner` table by month (for a year) or by year (all years),
 * split into stacked series by vehicle type or by category.
 */

/**
 * Aggregate owner rows into chart series.
 *
 * @param string $status     Category to scope to ('Staf'...), or '' for all.
 * @param int    $year       Specific year => 12 monthly buckets; 0 => one bucket per year.
 * @param string $seriesBy   'type' (UPPER(type)) or 'status'.
 * @param array  $allowed    Series keys to keep as their own stack; others lump into $lump.
 * @param string $lump       Label for the lumped "other" series ('' to drop others).
 * @return array ['x' => [labels...], 'keys' => [period => [seriesKey => count]]]
 */
function nv_chart_aggregate($con, string $status, int $year, string $seriesBy, array $allowed, string $lump = 'LAIN-LAIN', int $month = 0): array
{
    $eff       = "COALESCE(`date_taken`, `created_at`)";
    $seriesCol = ($seriesBy === 'status') ? "`status`" : "UPPER(`type`)";
    $where     = "$eff IS NOT NULL";
    if ($status !== '') { $where .= " AND `status` = '" . mysqli_real_escape_string($con, $status) . "'"; }
    // A specific month scopes every bucket to that month (single-month chart when a year is also set).
    if ($month >= 1 && $month <= 12) { $where .= " AND MONTH($eff) = " . (int) $month; }

    if ($year > 0) {
        $where    .= " AND YEAR($eff) = " . (int) $year;
        $periodSql = "MONTH($eff)";
        $periods   = ($month >= 1 && $month <= 12) ? [(int) $month] : range(1, 12);
    } else {
        $periodSql = "YEAR($eff)";
        $periods   = [];
        $py = mysqli_query($con, "SELECT DISTINCT YEAR($eff) y FROM `owner` WHERE $where ORDER BY y ASC");
        if ($py) { while ($r = mysqli_fetch_assoc($py)) { if ($r['y']) { $periods[] = (int) $r['y']; } } }
        if (!$periods) { $periods = [(int) date('Y')]; }
    }

    // period => seriesKey => count
    $matrix = [];
    foreach ($periods as $p) { $matrix[$p] = []; }

    $sql = "SELECT $periodSql AS p, $seriesCol AS s, COUNT(*) AS c
            FROM `owner` WHERE $where GROUP BY p, s";
    if ($res = mysqli_query($con, $sql)) {
        while ($row = mysqli_fetch_assoc($res)) {
            $p = (int) $row['p'];
            $key = (string) $row['s'];
            if (!in_array($key, $allowed, true)) { $key = $lump; }
            if ($key === '' ) { continue; }
            if (!isset($matrix[$p])) { $matrix[$p] = []; }
            $matrix[$p][$key] = ($matrix[$p][$key] ?? 0) + (int) $row['c'];
        }
    }
    return ['x' => $periods, 'keys' => $matrix];
}

/** HTML-escape helper local to this file. */
function nv_chart_esc($s): string { return htmlspecialchars((string) $s, ENT_QUOTES); }

/**
 * Per-category owner counts + grand total for a year (0 => all-time), using the
 * same effective date as the charts. Returns Staf/Pelajar/Pelawat/Kontraktor/Pesara
 * keys plus 'total' (sum of every status, so new categories still count).
 */
function nv_owner_year_counts($con, int $year): array
{
    $out = ['Staf' => 0, 'Pelajar' => 0, 'Pelawat' => 0, 'Kontraktor' => 0, 'Pesara' => 0, 'total' => 0];
    $eff   = "COALESCE(`date_taken`, `created_at`)";
    $where = "$eff IS NOT NULL";
    if ($year > 0) { $where .= " AND YEAR($eff) = " . (int) $year; }
    if ($res = @mysqli_query($con, "SELECT `status` s, COUNT(*) c FROM `owner` WHERE $where GROUP BY `status`")) {
        while ($r = mysqli_fetch_assoc($res)) {
            $s = (string) $r['s']; $c = (int) $r['c'];
            if (array_key_exists($s, $out)) { $out[$s] = $c; }
            $out['total'] += $c;
        }
    }
    return $out;
}

/** Distinct years present in owner (desc), guaranteeing $include is in the list. */
function nv_owner_years($con, int $include = 0): array
{
    $eff = "COALESCE(`date_taken`, `created_at`)";
    $years = [];
    if ($ry = @mysqli_query($con, "SELECT DISTINCT YEAR($eff) y FROM `owner` WHERE $eff IS NOT NULL ORDER BY y DESC")) {
        while ($r = mysqli_fetch_assoc($ry)) { if ($r['y']) { $years[] = (int) $r['y']; } }
    }
    if ($include > 0 && !in_array($include, $years, true)) { $years[] = $include; }
    if (!$years) { $years[] = (int) date('Y'); }
    rsort($years);
    return $years;
}

/**
 * Render a stacked bar chart as inline SVG.
 *
 * @param array $xLabels  Bottom-axis labels (one per bar).
 * @param array $series   [ ['label'=>..., 'color'=>'#rrggbb', 'data'=>[int per bar]], ... ]
 */
function nv_stacked_bar_svg(array $xLabels, array $series, array $opts = []): string
{
    $W = $opts['w'] ?? 760; $H = $opts['h'] ?? 300;
    $padL = 38; $padR = 12; $padT = 14; $padB = 34;
    $plotW = $W - $padL - $padR;
    $plotH = $H - $padT - $padB;
    $n = max(1, count($xLabels));

    // Max stacked total for the y-scale.
    $max = 0;
    foreach ($xLabels as $i => $_) {
        $sum = 0;
        foreach ($series as $s) { $sum += (int) ($s['data'][$i] ?? 0); }
        if ($sum > $max) { $max = $sum; }
    }
    if ($max <= 0) { $max = 1; }
    // Round the axis max up to something tidy.
    $step = max(1, (int) ceil($max / 4));
    $axisMax = $step * 4;

    $bandW = $plotW / $n;
    $barW  = min(46, $bandW * 0.6);
    $x0    = function ($i) use ($padL, $bandW, $barW) { return $padL + $bandW * $i + ($bandW - $barW) / 2; };
    $y     = function ($v) use ($padT, $plotH, $axisMax) { return $padT + $plotH * (1 - $v / $axisMax); };

    $svg  = '<svg viewBox="0 0 ' . $W . ' ' . $H . '" width="100%" role="img" style="max-width:100%;height:auto;font-family:inherit;">';

    // Y gridlines + labels.
    for ($g = 0; $g <= 4; $g++) {
        $val = $step * $g;
        $yy  = $y($val);
        $svg .= '<line x1="' . $padL . '" y1="' . $yy . '" x2="' . ($W - $padR) . '" y2="' . $yy . '" stroke="#e6e6ee" stroke-width="1"/>';
        $svg .= '<text x="' . ($padL - 6) . '" y="' . ($yy + 3) . '" text-anchor="end" font-size="10" fill="#888">' . $val . '</text>';
    }

    // Bars (stacked).
    foreach ($xLabels as $i => $lbl) {
        $acc = 0;
        foreach ($series as $s) {
            $v = (int) ($s['data'][$i] ?? 0);
            if ($v > 0) {
                $yTop = $y($acc + $v);
                $hh   = $y($acc) - $yTop;
                $svg .= '<rect class="nv-bar" x="' . round($x0($i), 1) . '" y="' . round($yTop, 1) . '" width="' . round($barW, 1) . '" height="' . round($hh, 1) . '" fill="' . nv_chart_esc($s['color'])
                      . '" data-s="' . nv_chart_esc($s['label']) . '" data-v="' . $v . '" data-x="' . nv_chart_esc($lbl)
                      . '"><title>' . nv_chart_esc($s['label'] . ': ' . $v . ' (' . $lbl . ')') . '</title></rect>';
                $acc += $v;
            }
        }
        $svg .= '<text x="' . round($x0($i) + $barW / 2, 1) . '" y="' . ($H - $padB + 14) . '" text-anchor="middle" font-size="10" fill="#666">' . nv_chart_esc($lbl) . '</text>';
    }

    $svg .= '</svg>';

    // Legend.
    $legend = '<div class="nv-row gap-3" style="flex-wrap:wrap;margin-top:8px;">';
    foreach ($series as $s) {
        $legend .= '<span class="nv-row gap-2" style="align-items:center;font-size:12px;color:var(--fg-2,#555);">'
                 . '<span style="width:12px;height:12px;border-radius:3px;background:' . nv_chart_esc($s['color']) . ';display:inline-block;"></span> '
                 . nv_chart_esc($s['label']) . '</span>';
    }
    $legend .= '</div>';

    return nv_chart_interactive_assets() . $svg . $legend;
}

/**
 * Shared interactivity for the SVG charts (emitted once per page): a floating
 * tooltip on hover and a "dim the others" highlight. Bars carry data-s/data-v/
 * data-x; print/no-JS still shows the native <title>.
 */
function nv_chart_interactive_assets(): string
{
    static $done = false;
    if ($done) { return ''; }
    $done = true;
    return <<<'HTML'
<style>
  .nv-bar { transition: opacity .12s ease; cursor: pointer; }
  svg:hover .nv-bar:not(:hover) { opacity: .35; }
  #nv-chart-tip { position: fixed; z-index: 9999; display: none; pointer-events: none;
    background: #1a1a1a; color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 12px;
    line-height: 1.3; box-shadow: 0 6px 18px rgba(0,0,0,.28); white-space: nowrap; }
  #nv-chart-tip .x { color: #c9c9d4; margin-left: 6px; }
  @media print { svg:hover .nv-bar:not(:hover) { opacity: 1; } #nv-chart-tip { display: none !important; } }
</style>
<div id="nv-chart-tip"></div>
<script>
(function () {
  if (window.__nvChartTip) { return; }
  window.__nvChartTip = true;
  var tip = document.getElementById('nv-chart-tip');
  function show(r, x, y) {
    tip.innerHTML = '<strong>' + r.getAttribute('data-s') + '</strong>: ' + r.getAttribute('data-v') +
                    '<span class="x">' + r.getAttribute('data-x') + '</span>';
    tip.style.display = 'block';
    tip.style.left = (x + 14) + 'px';
    tip.style.top  = (y + 14) + 'px';
  }
  document.addEventListener('mouseover', function (e) {
    var r = e.target.closest && e.target.closest('.nv-bar');
    if (r) { show(r, e.clientX, e.clientY); }
  });
  document.addEventListener('mousemove', function (e) {
    if (tip.style.display !== 'block') { return; }
    var r = e.target.closest && e.target.closest('.nv-bar');
    if (r) { tip.style.left = (e.clientX + 14) + 'px'; tip.style.top = (e.clientY + 14) + 'px'; }
    else { tip.style.display = 'none'; }
  });
  document.addEventListener('mouseout', function (e) {
    if (e.target.closest && e.target.closest('.nv-bar')) { tip.style.display = 'none'; }
  });
})();
</script>
HTML;
}

/**
 * High-level: aggregate + render a chart card. Returns HTML.
 *
 * $opts: status, year, seriesBy, series (key=>['label','color']), months (1=>'Jan'...),
 *        title, sub, empty.
 */
function nv_owner_chart_card($con, array $opts): string
{
    $status   = $opts['status'] ?? '';
    $year     = (int) ($opts['year'] ?? 0);
    $seriesBy = $opts['seriesBy'] ?? 'type';
    $defs     = $opts['series'] ?? [];
    $allowed  = array_keys($defs);
    $months   = $opts['months'] ?? [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];

    $month = (int) ($opts['month'] ?? 0);
    $agg = nv_chart_aggregate($con, $status, $year, $seriesBy, $allowed, $opts['lump'] ?? 'LAIN-LAIN', $month);

    // X labels.
    $xLabels = [];
    foreach ($agg['x'] as $p) { $xLabels[] = ($year > 0) ? ($months[$p] ?? $p) : $p; }

    // Build series aligned to x. Include lumped "LAIN-LAIN" if present in data.
    $defs2 = $defs;
    $hasLump = false;
    foreach ($agg['keys'] as $row) { if (isset($row[$opts['lump'] ?? 'LAIN-LAIN'])) { $hasLump = true; break; } }
    if ($hasLump && !isset($defs2[$opts['lump'] ?? 'LAIN-LAIN'])) {
        $defs2[$opts['lump'] ?? 'LAIN-LAIN'] = ['label' => ($opts['lump'] ?? 'LAIN-LAIN'), 'color' => '#9aa0a6'];
    }

    $series = [];
    $grand  = 0;
    foreach ($defs2 as $key => $def) {
        $data = [];
        foreach ($agg['x'] as $p) { $v = (int) ($agg['keys'][$p][$key] ?? 0); $data[] = $v; $grand += $v; }
        $series[] = ['label' => $def['label'], 'color' => $def['color'], 'data' => $data];
    }

    $svg = nv_stacked_bar_svg($xLabels, $series, ['h' => $opts['h'] ?? 300]);

    ob_start(); ?>
    <div class="card flat mt-4">
        <div class="nv-row between" style="align-items:flex-start;">
            <div>
                <span class="eyebrow"><?php echo nv_chart_esc($opts['title'] ?? 'Statistics'); ?></span>
                <?php if (!empty($opts['sub'])): ?><p class="text-muted" style="margin:2px 0 0;font-size:13px;"><?php echo nv_chart_esc($opts['sub']); ?></p><?php endif; ?>
            </div>
        </div>
        <div style="margin-top:10px;">
            <?php if ($grand === 0): ?>
                <p class="text-muted" style="padding:24px 0;text-align:center;"><?php echo nv_chart_esc($opts['empty'] ?? 'No data for this period.'); ?></p>
            <?php else: ?>
                <?php echo $svg; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
