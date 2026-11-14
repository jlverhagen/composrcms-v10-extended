# Architecture and invariants

1. CMS version remains Composr 10.0.52; distribution version is independent (example: `10.0.52-ext.1`).
2. Original `.tar` / `addon.inf` format and existing installer remain authoritative.
3. Remote catalogue data is untrusted until a manifest signature is verified against a locally trusted key. SHA-256 checks integrity against a trusted manifest, but does **not** authenticate the publisher.
4. Multi-source support is mandatory. Source priority is only a display preference; publisher identity is immutable for update decisions.
5. No unattended installation, no automatic core upgrades, no unsolicited background network traffic.
6. A locally bundled source registry provides offline bootstrap; administrators explicitly approve any added source and its signing key.
7. Catalogue IDs are namespaced by source (`source_id:addon_id`) for discovery. The underlying `addon.inf` addon name must still be checked for collisions before installation.
8. Remote URLs must be HTTPS, validated against SSRF and DNS rebinding, limited by size/time, and redirects revalidated.
9. Failed verification must block import. Existing local addon upload remains supported independently.
10. The hub uses a minimal Composr v10 site, but generated catalogue/release JSON should be served as static immutable assets.
