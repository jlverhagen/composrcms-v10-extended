<?php
/**
 * Standalone verification helper; no remote fetching, installation or trust-on-first-use.
 * Load from a trusted local path using require_code after Composr integration review.
 */
function extended_verify_signed_manifest($manifest_bytes, $signature_json, $trusted_keys, $allowed_key_ids)
{
    if (!extension_loaded('sodium')) {
        throw new RuntimeException('Sodium extension is required');
    }
    if (!is_string($manifest_bytes) || strlen($manifest_bytes) > 2097152 || !is_string($signature_json) || strlen($signature_json) > 4096) {
        throw new RuntimeException('Manifest or signature exceeds limits');
    }
    $envelope = json_decode($signature_json, true);
    if (!is_array($envelope) || !isset($envelope['algorithm'], $envelope['key_id'], $envelope['signature']) || $envelope['algorithm'] !== 'Ed25519') {
        throw new RuntimeException('Invalid signature envelope');
    }
    $key_id = $envelope['key_id'];
    if (!is_string($key_id) || !in_array($key_id, $allowed_key_ids, true) || !array_key_exists($key_id, $trusted_keys)) {
        throw new RuntimeException('Untrusted signing key');
    }
    $public = base64_decode($trusted_keys[$key_id], true);
    $signature = base64_decode($envelope['signature'], true);
    if ($public === false || strlen($public) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || $signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
        throw new RuntimeException('Malformed signing key or signature');
    }
    if (!sodium_crypto_sign_verify_detached($signature, $manifest_bytes, $public)) {
        throw new RuntimeException('Manifest signature verification failed');
    }
    $data = json_decode($manifest_bytes, true);
    if (!is_array($data) || !isset($data['schema_version']) || $data['schema_version'] !== 1) {
        throw new RuntimeException('Invalid signed manifest schema version');
    }
    return $data; // Caller MUST validate schema, source ID, URLs, freshness and package hashes.
}
