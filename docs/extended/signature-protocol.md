# Signed manifest protocol v0.2 (prototype)

1. Publisher writes a UTF-8 JSON manifest to immutable bytes. Do not reformat after signing.
2. Publisher signs **the exact file bytes** with Ed25519 and writes adjacent `.sig.json` containing `algorithm`, `key_id`, and base64 `signature`.
3. Client loads pinned public keys from trusted local configuration, not from the downloaded manifest or its server.
4. Client verifies signature **before** JSON interpretation. It then checks source identity, schema, package URL, version, freshness/expiry and SHA-256 against the signed entry.
5. No unsigned fallback. Key rotation requires separately approved trust-store update.

Current prototype implements steps 1-3 and signature verification in PHP, but **does not implement** HTTP retrieval, expiry/replay protection, full schema checks, package hash verification, or Composr integration. Never install from it on a live site.

The signing tool uses the Python `cryptography` package. Keep private keys offline or in a protected release-signing environment. No private keys are included in this package.
