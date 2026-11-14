<?php
/** Offline signed catalogue validation. Does not fetch URLs or install packages. */
function extended_validate_catalogue($data, $expected_source_id, $now = null)
{
    if (!is_array($data) || ($data['schema_version'] ?? null) !== 1 ||
        !isset($data['source_id'], $data['issued_at'], $data['expires_at'], $data['addons']) ||
        !is_string($data['source_id']) || !hash_equals($expected_source_id, $data['source_id']) ||
        !is_int($data['issued_at']) || !is_int($data['expires_at']) || !is_array($data['addons'])) {
        throw new RuntimeException('Invalid catalogue structure or source identity');
    }
    $now = $now ?? time();
    if ($data['issued_at'] > $now + 300 || $data['expires_at'] <= $now ||
        $data['expires_at'] <= $data['issued_at'] || $data['expires_at'] - $data['issued_at'] > 2592000) {
        throw new RuntimeException('Catalogue outside validity window');
    }
    if (count($data['addons']) > 2000) throw new RuntimeException('Too many addons');
    $seen = array();
    foreach ($data['addons'] as $addon) {
        if (!is_array($addon)) throw new RuntimeException('Invalid addon entry');
        foreach (array('id', 'name', 'version', 'download_url', 'sha256', 'license', 'cms_compatibility', 'php_compatibility') as $field) {
            if (!isset($addon[$field])) throw new RuntimeException('Missing addon field: ' . $field);
        }
        if (!is_string($addon['id']) || !preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.-]{0,127}$/D', $addon['id']) || isset($seen[$addon['id']])) {
            throw new RuntimeException('Invalid or duplicate addon ID');
        }
        $seen[$addon['id']] = true;
        foreach (array('name','version','license') as $field) {
            if (!is_string($addon[$field]) || $addon[$field] === '' || strlen($addon[$field]) > 512) throw new RuntimeException('Invalid addon metadata');
        }
        if (!is_string($addon['sha256']) || !preg_match('/^[a-f0-9]{64}$/D', $addon['sha256'])) throw new RuntimeException('Invalid SHA-256');
        $url = $addon['download_url'];
        if (!is_string($url) || strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false ||
            strtolower((string)parse_url($url, PHP_URL_SCHEME)) !== 'https' ||
            parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null ||
            parse_url($url, PHP_URL_FRAGMENT) !== null) throw new RuntimeException('Invalid download URL');
        if (!is_array($addon['cms_compatibility']) || ($addon['cms_compatibility']['major'] ?? null) !== 10 ||
            !isset($addon['cms_compatibility']['minimum']) || !is_string($addon['cms_compatibility']['minimum']) ||
            !is_array($addon['php_compatibility']) || !isset($addon['php_compatibility']['minimum']) ||
            !is_string($addon['php_compatibility']['minimum'])) throw new RuntimeException('Invalid compatibility metadata');
    }
    return $data;
}

function extended_verify_local_catalogue($manifest_path, $signature_path, $trusted_keys, $allowed_key_ids, $source_id, $now = null, $high_water_path = null)
{
    if (!is_file($manifest_path) || !is_file($signature_path)) throw new RuntimeException('Missing local catalogue');
    if (filesize($manifest_path) > 2097152 || filesize($signature_path) > 4096) throw new RuntimeException('Oversized catalogue');
    require_once __DIR__ . '/extended_manifest_verify.php';
    $bytes = file_get_contents($manifest_path);
    $data = extended_verify_signed_manifest($bytes, file_get_contents($signature_path), $trusted_keys, $allowed_key_ids);
    extended_validate_catalogue($data, $source_id, $now);
    if ($high_water_path !== null) {
        require_once __DIR__ . '/extended_catalogue_high_water.php';
        extended_assert_approved_catalogue($high_water_path, $source_id, $data['issued_at'], $bytes);
    }
    return $data;
}

/** Fetch a signed catalogue and detached signature; no installation or persistent cache. */
function extended_fetch_verified_catalogue($manifest_url, $signature_url, $temporary_directory, $trusted_keys, $allowed_key_ids, $source_id)
{
    require_once __DIR__ . '/extended_http_transport.php';
    $manifest = extended_transport_fetch($manifest_url, $temporary_directory, 2097152);
    try {
        $signature = extended_transport_fetch($signature_url, $temporary_directory, 4096);
        try {
            return extended_verify_local_catalogue($manifest, $signature, $trusted_keys, $allowed_key_ids, $source_id);
        } finally { @unlink($signature); }
    } finally { @unlink($manifest); }
}
