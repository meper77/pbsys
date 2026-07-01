<?php
/**
 * RETIRED ENDPOINT.
 *
 * This endpoint let anyone overwrite any account's password with no identity
 * proof (unauthenticated account takeover) and concatenated raw $_POST into SQL
 * (SQL injection). The live system is passwordless — Google (UiTM) / email OTP
 * gated by admin_allowlist — so there is no stored password to reset. Disabled.
 */
header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
    'success' => 0,
    'message' => 'This endpoint has been retired. Sign in with your UiTM Google account.',
]);
