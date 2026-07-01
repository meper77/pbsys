<?php
/**
 * RETIRED ENDPOINT.
 *
 * Self-service registration belonged to the old plaintext-password model and
 * built SQL by concatenating raw $_POST (unauthenticated SQL injection). The live
 * system is passwordless: sign-in is Google (UiTM) / email OTP, gated by
 * admin_allowlist (see includes/otp_auth.php, includes/google_auth.php,
 * auth/login.php). Accounts are provisioned there, so this endpoint is disabled.
 */
header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
    'success' => 0,
    'message' => 'This endpoint has been retired. Sign in with your UiTM Google account.',
]);
