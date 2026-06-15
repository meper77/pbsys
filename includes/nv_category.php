<?php
/**
 * Canonical per-category colours — the single source of truth for category colour
 * everywhere (dashboard charts + search status pills) so the same category reads
 * the same colour across every page.
 */

/** Brand colour for a vehicle owner category (accepts BM or EN names, any case). */
function nv_category_color(string $status): string {
    static $map = [
        'staf'       => '#6b21a8', 'staff'      => '#6b21a8',  // purple
        'pelajar'    => '#f5c518', 'student'    => '#f5c518',  // yellow
        'pelawat'    => '#0ea5e9', 'visitor'    => '#0ea5e9',  // sky blue
        'kontraktor' => '#16a34a', 'contractor' => '#16a34a',  // green
        'pesara'     => '#ef4444', 'alumni'     => '#ef4444',  // red
    ];
    return $map[strtolower(trim($status))] ?? '#6b7280';        // neutral gray fallback
}

/** A status pill (tinted background + coloured text/dot) in the category's colour. */
function nv_category_pill(string $status, string $label): string {
    $c = nv_category_color($status);
    return '<span class="pill" style="background:' . $c . '1a;color:' . $c . ';">'
         . '<span class="dot" style="background:' . $c . ';"></span> '
         . htmlspecialchars($label) . '</span>';
}
