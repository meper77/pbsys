<?php
/**
 * Centralized EN/BM dictionary + helpers for NEO V-TRACK.
 *
 *   require_once includes/i18n.php;   // handles ?lang=, sets $lang, starts session
 *   echo t('common.save');            // -> "Save" / "Simpan"
 *   echo t('auth.code_sent', ['email' => $e]);  // {email} interpolation
 *
 * Professional register in both languages. Missing keys fall back to the key name
 * so gaps are visible but never fatal.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'bm';
}
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] === 'en') ? 'en' : 'bm';
    $parts = parse_url($_SERVER['REQUEST_URI'] ?? '');
    parse_str($parts['query'] ?? '', $q);
    unset($q['lang']);
    $target = ($parts['path'] ?? ($_SERVER['PHP_SELF'] ?? '/')) . (empty($q) ? '' : '?' . http_build_query($q));
    header('Location: ' . $target);
    exit;
}
$lang = $_SESSION['language'];

$GLOBALS['NV_I18N'] = [
    'en' => [
        // chrome / generic
        'brand.sub'        => 'Auxiliary Police · UiTM',
        'common.save'      => 'Save',
        'common.cancel'    => 'Cancel',
        'common.delete'    => 'Delete',
        'common.delete_selected' => 'Delete selected',
        'common.back'      => 'Back',
        'common.search'    => 'Search',
        'common.continue'  => 'Continue',
        'common.email'     => 'Email',
        'common.phone'     => 'Phone',
        'common.name'      => 'Name',
        'common.created'   => 'Created',
        'common.actions'   => 'Actions',
        'common.loading'   => 'Loading…',
        'common.required'  => 'This field is required.',
        'common.lang'      => 'Language',

        // auth (passwordless OTP)
        'auth.signin'        => 'Sign in to NEO V-TRACK',
        'auth.subhead'       => 'Enter your UiTM email and we will send you a one-time code.',
        'auth.email_label'   => 'UiTM email',
        'auth.email_ph'      => 'name@uitm.edu.my',
        'auth.send_code'     => 'Email me a code',
        'auth.code_label'    => 'One-time code',
        'auth.code_ph'       => '6-digit code',
        'auth.verify'        => 'Verify & continue',
        'auth.code_sent'     => 'We sent a 6-digit code to {email}. It expires in 10 minutes.',
        'auth.resend'        => 'Resend code',
        'auth.change_email'  => 'Use a different email',
        'auth.remember'      => 'Remember this device for 30 days',
        'auth.bad_domain'    => 'Use your UiTM email (@uitm.edu.my or @student.uitm.edu.my).',
        'auth.bad_code'      => 'Invalid or expired code. Please try again.',
        'auth.too_many'      => 'Too many attempts. Request a new code.',
        'auth.rate_limited'  => 'Please wait a moment before requesting another code.',
        'auth.send_failed'   => 'We could not send the email right now. Please try again shortly.',
        'auth.no_password'   => 'NEO V-TRACK is passwordless — there is nothing to remember and no password to reset.',
        'auth.signed_out'    => 'You have been signed out.',
        'auth.welcome_back'  => 'Welcome back',

        // profile
        'profile.title'         => 'My Profile',
        'profile.account'       => 'Account',
        'profile.photo'         => 'Profile photo',
        'profile.change_photo'  => 'Change photo',
        'profile.request_delete'=> 'Request account deletion',
        'profile.delete_confirm'=> 'Request deletion of your account? An administrator will review and remove it.',
        'profile.delete_pending'=> 'Account deletion requested. An administrator will process it.',
        'profile.delete_done'   => 'Your deletion request has been recorded.',
        'profile.saved'         => 'Profile updated.',
        'profile.role_admin'    => 'Administrator',
        'profile.role_user'     => 'User',

        // home
        'home.welcome'      => 'Welcome to NEO V-TRACK, {name}',
        'home.subhead'      => 'Vehicle tracking & reporting · Polis Bantuan UiTM Segamat.',
        'home.total_vehicles'   => 'Total vehicles',
        'home.staff'        => 'Staff vehicles',
        'home.student'      => 'Student vehicles',
        'home.visitor'      => 'Visitor vehicles',
        'home.contractor'   => 'Contractor vehicles',
    ],
    'bm' => [
        'brand.sub'        => 'Polis Bantuan · UiTM',
        'common.save'      => 'Simpan',
        'common.cancel'    => 'Batal',
        'common.delete'    => 'Padam',
        'common.delete_selected' => 'Padam pilihan',
        'common.back'      => 'Kembali',
        'common.search'    => 'Carian',
        'common.continue'  => 'Teruskan',
        'common.email'     => 'E-mel',
        'common.phone'     => 'No. Telefon',
        'common.name'      => 'Nama',
        'common.created'   => 'Dicipta',
        'common.actions'   => 'Tindakan',
        'common.loading'   => 'Memuatkan…',
        'common.required'  => 'Ruangan ini diperlukan.',
        'common.lang'      => 'Bahasa',

        'auth.signin'        => 'Log masuk ke NEO V-TRACK',
        'auth.subhead'       => 'Masukkan e-mel UiTM anda dan kami akan menghantar kod sekali guna.',
        'auth.email_label'   => 'E-mel UiTM',
        'auth.email_ph'      => 'nama@uitm.edu.my',
        'auth.send_code'     => 'Hantar kod ke e-mel',
        'auth.code_label'    => 'Kod sekali guna',
        'auth.code_ph'       => 'Kod 6 digit',
        'auth.verify'        => 'Sahkan & teruskan',
        'auth.code_sent'     => 'Kami telah menghantar kod 6 digit ke {email}. Ia tamat tempoh dalam 10 minit.',
        'auth.resend'        => 'Hantar semula kod',
        'auth.change_email'  => 'Guna e-mel lain',
        'auth.remember'      => 'Ingat peranti ini selama 30 hari',
        'auth.bad_domain'    => 'Gunakan e-mel UiTM anda (@uitm.edu.my atau @student.uitm.edu.my).',
        'auth.bad_code'      => 'Kod tidak sah atau telah tamat tempoh. Sila cuba lagi.',
        'auth.too_many'      => 'Terlalu banyak percubaan. Sila minta kod baharu.',
        'auth.rate_limited'  => 'Sila tunggu sebentar sebelum meminta kod baharu.',
        'auth.send_failed'   => 'Kami tidak dapat menghantar e-mel buat masa ini. Sila cuba sebentar lagi.',
        'auth.no_password'   => 'NEO V-TRACK tanpa kata laluan — tiada kata laluan untuk diingat atau ditetapkan semula.',
        'auth.signed_out'    => 'Anda telah log keluar.',
        'auth.welcome_back'  => 'Selamat kembali',

        'profile.title'         => 'Profil Saya',
        'profile.account'       => 'Akaun',
        'profile.photo'         => 'Foto Profil',
        'profile.change_photo'  => 'Tukar Foto',
        'profile.request_delete'=> 'Mohon pemadaman akaun',
        'profile.delete_confirm'=> 'Mohon pemadaman akaun anda? Pentadbir akan menyemak dan memadamkannya.',
        'profile.delete_pending'=> 'Permohonan pemadaman akaun direkodkan. Pentadbir akan memprosesnya.',
        'profile.delete_done'   => 'Permohonan pemadaman anda telah direkodkan.',
        'profile.saved'         => 'Profil dikemas kini.',
        'profile.role_admin'    => 'Pentadbir',
        'profile.role_user'     => 'Pengguna',

        'home.welcome'      => 'Selamat datang ke NEO V-TRACK, {name}',
        'home.subhead'      => 'Penjejakan & pelaporan kenderaan · Polis Bantuan UiTM Segamat.',
        'home.total_vehicles'   => 'Jumlah kenderaan',
        'home.staff'        => 'Kenderaan staf',
        'home.student'      => 'Kenderaan pelajar',
        'home.visitor'      => 'Kenderaan pelawat',
        'home.contractor'   => 'Kenderaan kontraktor',
    ],
];

if (!function_exists('t')) {
    function t(string $key, array $vars = []): string
    {
        $lang = $_SESSION['language'] ?? 'bm';
        $dict = $GLOBALS['NV_I18N'][$lang] ?? [];
        $val  = $dict[$key] ?? ($GLOBALS['NV_I18N']['en'][$key] ?? $key);
        foreach ($vars as $k => $v) {
            $val = str_replace('{' . $k . '}', (string) $v, $val);
        }
        return $val;
    }
    function nv_lang(): string
    {
        return $_SESSION['language'] ?? 'bm';
    }
}
