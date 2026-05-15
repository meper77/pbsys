<?php
/**
 * Renders call + WhatsApp action buttons for a Malaysian phone number.
 * Strips non-digits; converts leading 0 to country code 60 for wa.me.
 * Returns '' when $phone is empty so callers can echo unconditionally.
 */
function format_contact_links($phone) {
    $digits = preg_replace('/\D+/', '', (string)$phone);
    if ($digits === '') {
        return '';
    }
    $wa = (strpos($digits, '0') === 0) ? '60' . substr($digits, 1) : $digits;
    $tel = htmlspecialchars($digits, ENT_QUOTES);
    $wa  = htmlspecialchars($wa, ENT_QUOTES);
    $btn = 'display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;color:#fff;text-decoration:none;margin-right:4px;font-size:12px;';
    return
        '<a href="tel:+' . $tel . '" title="Call" aria-label="Call" style="' . $btn . 'background:#0d6efd;">'
      . '<i class="fas fa-phone"></i></a>'
      . '<a href="https://wa.me/' . $wa . '" target="_blank" rel="noopener" title="WhatsApp" aria-label="WhatsApp" style="' . $btn . 'background:#25D366;">'
      . '<i class="fab fa-whatsapp"></i></a>';
}
