<?php
/**
 * Designated protected admin(s).
 *
 * These accounts are the permanent anchor: they cannot be deleted from the
 * admin table nor removed from the allowlist, so the system can never be locked
 * out of admin access. EVERY OTHER admin is deletable (still subject to the
 * can't-delete-yourself and can't-delete-the-last-admin guards).
 *
 * To change who is protected, edit this list (lowercase emails).
 */
function nv_protected_admins(): array
{
    return [
        'infostrukturjhr@uitm.edu.my',
    ];
}

/** True if $email is a designated protected admin (case-insensitive). */
function nv_is_protected_admin(?string $email): bool
{
    $email = strtolower(trim((string) $email));
    return $email !== '' && in_array($email, nv_protected_admins(), true);
}
