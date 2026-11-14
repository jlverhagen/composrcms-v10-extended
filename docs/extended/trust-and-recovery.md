# Registry approval, rotation, and recovery

## Current implementation

The offline `extended_registry_accept()` prototype verifies a detached Ed25519 signature against caller-supplied root keys, checks the registry identity and freshness, and rejects a lower sequence or different bytes at the same sequence. It serializes writes with a file lock, writes the manifest cache first, then renames the state file. The cache contains the manifest bytes. The prototype does not persist the detached signature, local approval, or root policy with the pointer. Atomic rename does not guarantee crash durability or detect a coherent restore of older files. The read-only Admin Zone browser uses separately configured local catalogue snapshots.

## Proposed approval and state model

- Provision the initial registry root through an authenticated channel. Bind each key ID to the SHA-256 fingerprint of its raw Ed25519 public key. Pin catalogue keys per source ID and maintain separate core-release keys. A registry's source list and key IDs can propose changes, but cannot establish a public-key binding.
- Import exact manifest bytes and detached signature into a pending record. Validate size, signature, schema, registry ID, sequence, timestamps, source records and URL policy. Show the administrator the signing fingerprint, sequence, expiry, and changes from the active registry. Include source enablement, endpoint changes, and source key bindings. A valid registry signature authenticates the proposal. Local approval determines what becomes active.
- Bind an approval to the proposal digest, signer fingerprint, expected prior state, and exact source changes. Require the same authorized administrator capability for preparing and confirming changes, with the CMS's normal request protections. In the commit transaction, recheck the prior state, approval and freshness. A concurrent approval changes the prior state and forces the stale approval to be reviewed again. Resubmitting an already committed approval returns its existing result.
- Store exact manifest bytes and signature, digest, sequence, expiry, scoped source keys, active and revoked root bindings, and approval records in a durable transactional store. Use a monotonically increasing local generation for every trust change, including policy-only changes. A signed registry sequence and its digest continue to protect against registry rollback and equivocation. New root policy never lowers the registry sequence.
- Maintain an independent high-water record with the deployment identity, local generation, registry sequence and state digest. Provision the initial record explicitly. If rollback protection across backup restores is required, keep it outside the database's restore boundary with compare-and-set semantics. If both copies can be reverted together, local software cannot reliably detect a coherent rollback.

## Proposed commit and recovery behavior

Persist a complete pending generation before advancing the independent high-water record. Advance the record only from the expected prior value. Activate the generation after the high-water change is confirmed. Check both records before using active trust state. A digest mismatch, unavailable independent record, or missing initialized state blocks trust changes and registry-driven actions. Keep the existing local browser independent until the registry integration is enabled and verified.

| Interruption or input | Expected behavior |
| --- | --- |
| Failure before pending generation is durable | Keep the prior committed generation. |
| Pending generation durable, high-water record still at prior generation | Keep the prior active generation. An explicit retry may continue the pending approval after revalidation. |
| High-water record advanced, activation interrupted | Resume only the exact durable pending generation that matches the independent record. Recheck stored evidence and policy. If the registry has since expired, retain the accepted generation for rollback checks and block use of the expired catalogue until fresh metadata is approved. |
| Database restored behind the independent record | Block registry-driven actions. Restore the exact matching generation and evidence, or use an authenticated recovery procedure that advances state from the independent record. Keep revocation history and sequence floors. |
| Same registry sequence with different bytes | Reject the proposal and record an equivocation event. |
| Missing or corrupt evidence for the recorded generation | Enter recovery mode. Preserve both state records and require independently authenticated evidence before advancing trust. |

A recovery operation requires an administrator identity and separately authenticated trust material. Record the recovered state and authorizing evidence. Copying an older state file into place cannot authorize a recovery or reinstate a revoked key. Test interruption before and after each durable write, competing approvals, replay with identical bytes, equivocation, stale restores, expiry during recovery, and loss of the independent record.

## Proposed root rotation

1. Obtain the replacement public key and fingerprint through an independently authenticated channel. Record it as pending with the old and new key IDs.
2. During ordinary rotation, require both roots to sign the same transition bytes. The transition binds the registry ID, old and new fingerprints, effective sequence and expiry. The current single-signature registry format cannot carry that evidence as-is. Define a versioned transition record with two detached signatures before implementing rotation.
3. Approve the transition locally and commit it as a new trust generation. During a defined overlap, verify a higher-sequence registry signed by the replacement key.
4. Approve retirement of the old root after the replacement has been verified. Preserve its revoked binding in durable policy and recovery evidence. Reject further proposals signed only by that root.
5. If the old root is compromised or unavailable, use the separately authenticated recovery procedure. This case cannot rely on the ordinary dual-signature transition.

## Implementation baseline for the Virtualmin test domain

Use dedicated InnoDB tables in the Composr MariaDB database for approval history and staged generations. Keep the registry high-water record in an operator-owned directory under `/var/lib/composr-extended/composr.duckdns.org/`, separate from the existing per-catalogue approval record and outside the Virtualmin home and database restore. Scope it to the provisioned deployment ID and registry ID. The Composr runtime may read the record. The local operator is its sole writer and runs the first implementation from a protected CLI checkout.

The first implementation uses two distinct authorizations. A Composr super-admin reviews and records an approval through a CSRF-checked POST. The local operator commits the exact reviewed generation after comparing its digest and change summary with the approval they intend to apply. The operator obtains the initial root and recovery public key fingerprints independently and pins them in operator-owned configuration. Keep the recovery signing key offline under the deployment owner's control. Recovery requires the operator and a recovery-key signature over the exact deployment, current high-water tuple, proposed recovery state digest, purpose and expiry. Provisioning this key and recording its fingerprint are deployment steps.

### Stored records

| Record | Contents |
| --- | --- |
| Approval | Immutable approval ID, Composr member ID, approval time, deployment and registry IDs, expected generation and digest, exact candidate digest, signer fingerprint, digest of the reviewed source and policy changes |
| Generation | Deployment and registry IDs, local generation, signed registry sequence, manifest digest, exact manifest and signature bytes, expiry, normalized source bindings, root and revocation policy, approval ID and digest, prior state digest |
| Database head | Deployment and registry IDs, active generation and state digest |
| Independent record | Format version, deployment and registry IDs, generation, registry sequence, manifest digest and state digest |

Store exact wire bytes in binary columns. Define one versioned encoding for the generation digest before writing the schema. It must bind the exact wire bytes and authorization evidence as well as derived policy. Use a unique constraint on generation and approval identity within the deployment and registry. Persist the approval and staged generation together. Keep staged evidence available until the transaction is reconciled.

### Commit and resume

1. Serialize commits and reconciliation with an operator-owned per-deployment lock. Read the database head and independent tuple under the lock. For an initialized deployment, missing or inconsistent evidence enters recovery. Explicit bootstrap provisions generation zero under that same lock.
2. Recompute the candidate and review digests from the exact stored proposal and prior committed policy. Verify approval, pins, signature, source bindings, freshness and sequence. A different manifest at the current signed sequence is rejected. Treat an exact retry as the original operation only if its approval and committed generation agree.
3. Commit the complete generation to MariaDB with the database head still at its prior value. Use a transaction to verify the expected head and persist evidence. An interrupted commit is retried by querying the immutable approval ID and comparing the staged bytes and digests.
4. Advance the independent tuple only from the expected prior tuple, while still holding the operator lock. The writer must durably publish both file contents and directory entry before reporting success. If the result is ambiguous, reread the tuple and reconcile before allowing another approval.
5. Advance the database head in a second transaction using the expected prior head. If the operator process stops here, resume only the exact generation named by the independent tuple. Revalidate the stored approval and signature against its prior policy, then complete activation. If the manifest expired in the meantime, retain the generation and withhold its sources from use until fresh metadata is approved.
6. Registry consumers verify the independent tuple and a consistent database snapshot before using a generation. On disagreement, allow a bounded retry for an in-flight commit, then stop registry-driven operations. The existing local catalogue can continue using its own independent approval path.

For the first recovery implementation, support restoring the exact generation named by the independent tuple. Require recovery authorization for changes to the tuple or policy. If the independent tuple itself is lost, recover its exact last value from separately authenticated evidence or keep registry-driven operations blocked. A recovery signature alone cannot establish what the latest generation was.

### Implementation order

1. Add the generation encoding, schema and store operations with isolated transaction tests.
2. Add the operator commit and reconciliation entry points with an independent record writer. Exercise process interruption at each write boundary, concurrent approvals, and a restored database that is behind the independent record.
3. Add the super-admin review and approval UI and test permission, CSRF, and stale-review behavior on the test installation.
4. Add signed recovery and root rotation after the normal commit and exact-generation recovery path pass the restore rehearsal.

### Offline store implementation

`hub/operator/registry-store.sql` provisions the two InnoDB tables in a dedicated schema or a Composr database. The immutable generation row stores approval evidence with the proposal in one write. The generation identity includes its digest so multiple staged approvals for the same next generation can coexist. The head refers to one exact row. The approval ID is unique per deployment and registry.

The v1 state digest is binary SHA-256 of the domain prefix `composr-registry-generation-v1` followed by a NUL and ten length-prefixed byte strings. Each length is a four-byte unsigned big-endian integer. The fields in order are deployment ID, registry ID, generation and sequence as decimal ASCII, prior binary digest (32 zero bytes at bootstrap), approval ID, manifest bytes, signature bytes, serialized policy and serialized approval. The operator will define and validate the policy and approval payloads before calling the store. The store provides separate staging and activation transactions so the operator can publish the independent record between them.

To test the store, load the SQL into a disposable MariaDB schema and run `php hub/tests/check-registry-store.php` with `EXTENDED_REGISTRY_TEST_DSN`, `EXTENDED_REGISTRY_TEST_USER`, and `EXTENDED_REGISTRY_TEST_PASSWORD` set for that schema. The harness inserts isolated test rows. Without a DSN it checks the encoding alone. The operator commit entry point, independent record writer, and Admin Zone integration are pending.

The generation decoder checks the full record and its canonical encoding before activation. A disposable MariaDB 11.8.6 instance accepted the schema and preserved binary evidence and the prior head across an engine restart between staging and activation. These SQL checks ran through MariaDB bootstrap mode. The PHP/PDO integration harness subsequently passed on PHP 8.4.24 against disposable MariaDB 11.8.6 over loopback TCP. It exercised bootstrap, binary evidence round-trip, staging with the prior head still active, retry of the same approval, activation through a fresh store instance, repeated activation, and rejection of a competing activation. These checks cover the store. The independent-record commit and recovery workflow still needs implementation and its own interruption and restore tests.

### Operator commit coordinator

`hub/operator/registry-commit.php` implements the coordination layer for an already provisioned registry. It serializes commit and reconciliation, checks the expected head and sequence, durably replaces the independent record, and activates the exact staged generation. It requires an injected verifier to authenticate the candidate and prior policy. The verifier receives a resume flag to distinguish an already published approval from a new approval that must pass freshness checks. A missing generation blocks reconciliation.

The PHP/PDO harness passed against disposable MariaDB 11.8.6 on PHP 8.4.24. It tested denial before publication, normal commit, duplicate commit, reconciliation from the state after independent record publication, and refusal when the independent head names evidence missing from MariaDB. Interruption and restore states were modeled with fixtures. An actual backup restore, the signature and approval verifier, explicit bootstrap, and the protected CLI entry point remain pending.

### Signed operator CLI prototype

The CLI accepts an existing protected JSON configuration with `dsn`, `user`, `password`, `deployment`, `registry`, `record`, `root_keys`, and `approval_keys`. Both key maps bind IDs to base64 Ed25519 public keys. Store the configuration in an operator-owned directory outside the web tree and restrict the file to the operator. Use separate registry and approval keys. Run `php hub/operator/commit-registry.php CONFIG commit PRIOR_GENERATION PRIOR_DIGEST GENERATION DIGEST` for a reviewed, staged successor, or `php hub/operator/commit-registry.php CONFIG reconcile` after an interrupted activation. Digests are lowercase hexadecimal.

The approval signature covers the fixed-order JSON claims constructed in `registry-approval.php`, including the prior digest and exact manifest, signature envelope and policy digests. The operator approval key serves as the prototype's local authorization. The Admin Zone review workflow, structured change summary, explicit bootstrap and key rotation are pending. Normal approvals preserve the existing policy bytes. Signed fixture tests cover expiry, resumption after expiry, tampered wire bytes, deployment and prior-state binding, policy changes and incorrect approval keys. The signed CLI still needs a disposable MariaDB integration run.

### Explicit bootstrap

For a newly provisioned deployment, stage generation zero with a null prior head and a separately signed approval. `extended_registry_approval_claims()` produces the exact bytes the reviewer signs. Run `php hub/operator/commit-registry.php CONFIG bootstrap DIGEST` with the staged state digest. Bootstrap verifies the publisher and approval signatures under the operator lock and publishes the independent record before database activation. If interrupted, rerun bootstrap with the same digest. An initialized database with a missing independent record enters recovery and cannot be bootstrapped again. Use this command only for an independently confirmed new deployment or the exact retry. If both records have been lost, use the recovery procedure.

The disposable MariaDB harness exercised signed bootstrap, an exact retry and refusal to recreate a missing independent record for an initialized database. It called the coordinator directly. The review and signing interface, CLI process integration test, and backup restore rehearsal are pending.

### Offline review and staging

Run `php hub/operator/commit-registry.php CONFIG review ID MANIFEST SIGNATURE POLICY` to verify the publisher and inspect the proposed and prior sources, expiry, publisher fingerprint and exact approval claims. The current policy is an opaque file whose bytes must match the prior policy on normal updates. Save the decoded JSON string in the `claims` field as UTF-8 bytes with no trailing newline. Sign that file using `hub/publisher/sign_manifest.py` with the independent reviewer key. Pass the resulting envelope to `php hub/operator/commit-registry.php CONFIG stage ID MANIFEST SIGNATURE POLICY APPROVAL`. Staging recomputes the claims against the current database head and rejects a signature for a different head or proposal. Use the returned generation and digest for bootstrap or commit.

The CLI passed an end-to-end signed review, staging, bootstrap, successor commit and reconciliation run on disposable MariaDB 11.8.6 with PHP 8.4.24. The backup restore rehearsal and Admin Zone review integration remain pending.

### MariaDB backup and restore rehearsal

The signed operator CLI passed a logical backup and restore rehearsal on disposable MariaDB 11.8.6 with PHP 8.4.24. The rehearsal used separate fixture publisher and approval keys. The independent record stayed outside the database throughout.

1. Bootstrap generation zero and take a logical dump with `mariadb-dump --single-transaction --hex-blob`.
2. Stage a signed successor, take a second dump with the successor evidence present and the database head still at generation zero, then commit the successor. Save the exact bytes of the independent record for comparison.
3. Restore the older dump. Verify that both `reconcile` and retrying the successor `commit` exit with a missing generation evidence error. Compare the independent record byte for byte with the saved record.
4. Restore the second dump. Run `reconcile` twice. Both invocations must report the exact successor generation and digest, and the independent record must still match the saved bytes.

Both restore cases passed. The second dump was made after staging and contains the exact approval evidence required by the independent record. A restore without that evidence requires recovering the matching generation before registry activation can resume. The rehearsal covered the operator workflow and registry tables in isolation.

### Admin Zone review handoff

Set `EXTENDED_REGISTRY_REVIEW_PATH` to the absolute path of the JSON output from the operator's `review` command. Publish the file atomically into a directory outside the web root that the Composr user can read and only the operator can write. The Extended addon repositories page then offers **Review registry proposal**. A super-admin can confirm the displayed proposal by POST. Confirmation requires the existing Composr CSRF filter and an exact match to the displayed review file digest. Expired proposals are rejected.

The confirmation is a copyable receipt for manual handoff. It is unsigned and should be treated as informational by the operator. The operator must verify the confirmation through an authenticated channel and recheck the exact claims before signing. The independent CLI approval signature still authorizes staging and committing. An authenticated, persistent Admin Zone approval record and live Composr permission and CSRF tests remain pending.

### Persistent confirmation receipts

Optionally set `EXTENDED_REGISTRY_RECEIPT_DIRECTORY` to an existing absolute directory outside the web root. Give the Composr service account write access and restrict access to the service account and the operator. Keep it separate from the operator-owned review input and independent registry state. An authenticated confirmation writes a receipt keyed by the displayed proposal digest and member ID. Retries return the original timestamp and claims. The operator's independent signature continues to authorize staging and commit. A receipt records what the CMS accepted and inherits the trust of the CMS account that writes it.

For live validation, publish the operator's review JSON atomically, deploy the module and helper together, and update the PHP-FPM guard manifest for the changed module and new helper before enabling either option. Use a fresh signed fixture and a super-admin session to review and confirm it twice, comparing the stored receipt and timestamp. Replace the review file between display and confirmation and verify the stale confirmation fails. Check ordinary-member and delegated-admin access and verify a confirmation without a valid CSRF token leaves the receipt directory unchanged. Perform these checks with disposable registry evidence and no operator commit.

### Live Admin Zone rehearsal

On the Composr test site, an authenticated super-admin confirmed a disposable proposal twice and the service stored one receipt with the original timestamp. After atomic replacement of the proposal file, the stale confirmation failed and the receipt count remained one. The disposable review configuration was disabled after the test. The deployment initially replaced the existing module's site-specific catalogue configuration. Restoring its configuration lines restored catalogue rendering, and all seven guard hashes matched. Future deployments must preserve those local definitions and regenerate the guard manifest from the resulting files. No registry generation was advanced.

The local dispatch test uses Composr API stubs to check rejection of GET and an inactive CSRF filter without receipt writes, plus successful persistence for a protected POST. It does not exercise Composr's real token validation. Live negative CSRF and ordinary-member/delegated-admin tests remain pending.

### Checking the receipt before signing

Run `php hub/operator/check-registry-receipt.php REVIEW_JSON RECEIPT_JSON > claims.json` using the original review export bytes and the receipt produced by the CMS. The command checks the receipt against the exact review digest and claims, checks timestamps, and emits the signing bytes without a trailing newline. Continue to the independent signing step only after the check succeeds and the operator has authenticated the reviewer through the agreed channel. The receipt itself is unsigned. The signing key, the operator's decision, and the CLI's staging and commit validation remain independent of the CMS.

The checker was exercised on techie75 against a receipt generated by the authenticated Composr test Admin Zone. It emitted the exact claims bytes for the original disposable review. Appending one newline to the review caused rejection with empty standard output. The prior guard configuration was restored byte for byte, and the catalogue rendered with the review link disabled. No registry was signed or activated during this fixture test.
