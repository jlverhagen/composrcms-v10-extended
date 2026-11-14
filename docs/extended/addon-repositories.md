# Multi-source protocol draft v0.1

The local source registry contains source IDs, manifest URLs, priorities, and public-key IDs. The registry itself must be authenticated by a pinned registry-signing key before it can update local source definitions. Initial keys must be distributed out-of-band with the CMS; never trust keys delivered only by the manifest they supposedly verify.

## Signed envelope (planned)

Use an envelope with `schema_version`, `payload` (canonical JSON object), `key_id`, and base64 Ed25519 signature. Define a single byte-level canonicalization standard before implementing signing; the present prototype **does not sign**. Do not enable remote installs until signatures are verified.

Each addon entry includes stable addon ID, source/publisher identity, version, CMS/PHP compatibility, license, package URL, SHA-256, and optional dependencies. Pin source IDs to keys. Treat a change of source or signing key as a separate administrative trust decision.

The CMS downloads to a private staging file, checks signature/hash, validates tar paths (no traversal, absolute paths, symlinks, hardlinks, special files, or duplicate normalized paths), compares `addon.inf` identity and compatibility, and then moves into Composr's import area. Run Composr's existing confirmation/install path. Package size and extracted size limits must be enforced.

## Failure behavior
- Unreachable source: show dated verified cache or offline message; never silently accept unverified data.
- Signature failure: block catalogue and installation.
- Package hash mismatch: delete staged file and block.
- Same addon ID from multiple sources: show both and require explicit choice.
- Existing addon installed from unknown publisher: do not auto-associate with a remote publisher.
- Core updates: separate manifest and signing trust root.
