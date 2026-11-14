# Administrator-triggered catalogue refresh review

The current Admin Zone browser reads only local catalogue and signature paths. The existing HTTPS transport and snapshot stager can support an explicit refresh, but no route currently connects them.

1. Accept a configured source ID on an authenticated, CSRF-checked super-admin POST. Resolve both URLs and publisher pins from trusted operator configuration. Verify that the source is still approved before activation.
2. Download the manifest and detached signature to private temporary files with independent size limits. Preserve TLS and DNS pinning and reject redirects. Remove both temporary files on success and failure, including failures between the two downloads.
3. Verify the exact manifest bytes, source ID and freshness. Stage the signed pair and re-verify the stored bytes. Keep the active browser configuration pointing to the prior verified snapshot until the staged pair passes all checks.
4. Serialize activation per source. Recheck the current approval, prior snapshot and freshness under the activation lock. Catalogue freshness limits the replay window but does not prevent replacing a newer valid snapshot with an older valid one. Establish an authenticated ordering policy before allowing refresh to switch the active snapshot.
5. Record the source, prior and candidate digest, initiating administrator and outcome. Show a concise status in the Admin Zone. A failed refresh leaves the previous snapshot selected, subject to its original expiry.

The stager now verifies the pair in a private sibling directory and publishes it with a single rename. The transport checks the result of flushing each download. If an older stager left an incomplete digest directory, stop refreshes, confirm the directory is not referenced by the approval record, and move it aside before retrying with the original signed pair. If the approval record references it, recover the exact approved pair before resuming.

Test failed signature downloads, expiry, concurrent activation, source revocation between staging and activation, replay of an older valid snapshot, and retry after interruption. This review is based on the checkpoint source. It does not add a refresh route or enable network access.

## Independent catalogue approval prototype

Use `/var/lib/composr-extended/composr.duckdns.org/catalogue-high-water.json` on techie75, outside the Virtualmin home and MariaDB restore scope. Provision the site directory as root-owned and group-readable/traversable by the Composr runtime, with no group write. The record starts as `{"version":1,"sources":{}}` with mode 0640. Run approval only from trusted code outside the web tree as the directory owner or an independently provisioned writer. Keep the runtime's access read-only and obtain publisher pins from independent trusted configuration.

The CLI-only approval function validates the signed candidate and advances the record under a lock. It orders snapshots by signed `issued_at`, rejects an older candidate or different bytes with the same timestamp, and permits a repeat of the identical approval. The catalogue browser can enforce an exact approved digest by defining `EXTENDED_CATALOGUE_HIGH_WATER_PATH`. Every configured source requires an approved record when enforcement is on.

Before enabling refresh, keep the enforcement configuration and running code outside the rollback boundary or make restoring the current guard code a required recovery step before reopening the site. A restore of pre-enforcement PHP can otherwise skip the guard even while the independent record survives. The prototype has no privileged service, activation route, or deployed server state.

## Operator refresh command

Run `php hub/operator/refresh-catalogue.php TRUSTED_CONFIG STATE_RECORD SOURCE_ID` from an administrator-owned checkout. The config is JSON with a `sources` object keyed by source ID. Each entry supplies `enabled`, `manifest_url`, `signature_url`, `trusted_keys`, and `allowed_key_ids`. Keep this file and the checkout outside the web account's write access. Use the same publisher pins in the CMS configuration. The command requires a seeded source.

The command downloads and verifies the pair, stages it next to the independent record, makes it readable to the record's group, and advances the record last. The browser resolves the approved digest to that staged pair. Seeded catalogues can still use their original configured paths with exact-digest verification. Failed or older candidates cannot advance approval. Unreferenced snapshots can be retained for inspection or cleaned up by the operator.

For the test domain, a browser-code deployment requires updating the PHP-FPM guard's approved file hashes as part of the deployment. The operator command has no web trigger. The component matrix and approval harness passed on PHP 8.2–8.5. A live HTTPS refresh requires trusted manifest and signature endpoints.

## Disposable HTTPS rehearsal

On techie75, the operator command fetched and approved a newer signed fixture over the test domain HTTPS endpoint using a separate test record. It rejected an older signed snapshot, an invalid signature envelope, and a request for an absent signature that hit the download byte limit. Each rejection preserved the test record. The script verified there were no remaining temporary download files and removed the served fixtures on exit. The live approval record was outside this test. The browser reader and guard hash deployment command was submitted, but its terminal result and authenticated catalogue rendering could not be observed after the desktop browser connection became unavailable. Anonymous follow-up returned HTTP 200 for the homepage and HTTP 401 with the Composr login page for the Admin Zone.

Authenticated follow-up confirmed that all six deployed PHP files matched the PHP-FPM guard manifest and that the Admin Zone rendered all three test repositories as verified.
