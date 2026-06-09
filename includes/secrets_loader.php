<?php
/**
 * Loads runtime secrets from includes/secrets.php (falls back to the committed
 * template so the app degrades gracefully if the real file is missing on a box).
 *
 *   nv_secrets()            -> full array
 *   nv_secret('smtp.host')  -> dotted lookup with optional default
 */
if (!function_exists('nv_secrets')) {
    function nv_secrets(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $real = __DIR__ . '/secrets.php';
        $tmpl = __DIR__ . '/secrets.example.php';
        $file = is_file($real) ? $real : (is_file($tmpl) ? $tmpl : null);
        $data = $file ? include $file : [];
        $cache = is_array($data) ? $data : [];
        return $cache;
    }

    function nv_secret(string $path, $default = null)
    {
        $node = nv_secrets();
        foreach (explode('.', $path) as $key) {
            if (is_array($node) && array_key_exists($key, $node)) {
                $node = $node[$key];
            } else {
                return $default;
            }
        }
        return $node;
    }
}
