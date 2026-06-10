<?php
/**
 * NEO V-TRACK runtime secrets — TEMPLATE.
 *
 * Copy this file to `includes/secrets.php` and fill in real values.
 * `includes/secrets.php` is gitignored (the repo is PUBLIC) and is NOT shipped by
 * the deploy sync; place it on the live host once via:
 *
 *   rclone copy includes/secrets.php hestia:web/neovtrack.uitm.edu.my/public_html/includes/ --sftp-disable-hashcheck
 *
 * Everything in here is read through nv_secrets()/nv_secret() (includes/secrets_loader.php).
 */
return [
    // Strong random string guarding api/migrate.php (schema runner / mail self-test).
    'migrate_key' => 'CHANGE_ME_migrate_key',

    // HMAC key for signing "remember this device" cookies + OTP hashing pepper.
    // Also derives the developer login-bypass token (foundation/login): open
    //   /auth/login.php?dev=1  and submit  hash_hmac('sha256',
    //   'nv-dev-bypass|2023818464@student.uitm.edu.my', app_secret).
    'app_secret'  => 'CHANGE_ME_app_secret',
    'otp_pepper'  => 'CHANGE_ME_otp_pepper',

    // Google Sign-In (UiTM). Create an OAuth 2.0 "Web application" Client ID in
    // Google Cloud Console; add https://neovtrack.uitm.edu.my as an Authorized
    // JavaScript origin. Requires the site to be served over HTTPS.
    'google_client_id' => '',                 // e.g. 1234-abcd.apps.googleusercontent.com
    'google_hd'        => '',                 // optional: 'uitm.edu.my' to hard-restrict the hosted domain

    // Outbound mail (OTP codes, notifications). UiTM relay.
    'smtp' => [
        'host'       => 'badang.uitm.edu.my',
        'port'       => 25,
        'secure'     => '',          // '' (none) | 'tls' (STARTTLS) | 'ssl' (implicit)
        'auth'       => true,
        'username'   => 'CHANGE_ME_smtp_user',
        'password'   => 'CHANGE_ME_smtp_pass',
        'from_email' => 'noreply@uitm.edu.my',
        'from_name'  => 'NEO V-TRACK',
        'timeout'    => 12,
    ],
];
