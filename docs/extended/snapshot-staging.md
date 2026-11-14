# Offline content-addressed catalogue staging

`extended_stage_signed_catalogue_snapshot` validates an administrator-approved local manifest and detached signature using pinned Ed25519 keys, validates source identity, checks freshness and schema, then stores them in a hash-named directory under a protected, non-web-accessible root. The function returns paths for explicit configuration of the read-only browser.

It never updates the browser configuration, downloads anything, or enables an installer. Do not use an attacker-writable input or snapshot directory. A filesystem operator can still roll back or delete snapshots. An interrupted write can leave a partially populated hash directory that must be recovered by an administrator; this helper **fails closed** rather than silently repairing it. This is not a full trust-state transaction or a key-rotation mechanism.

**Threat-model limitation:** PHP local path checks are not race-free against a hostile local process with control over the filesystem. Store snapshots and pinned keys in an admin-controlled location outside the document root, with strict permissions.
