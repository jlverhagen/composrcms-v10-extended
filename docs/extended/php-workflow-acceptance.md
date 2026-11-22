# PHP 8.2–8.5 CMS workflow acceptance

Prepared 2026-11-19 at source checkpoint `a20c4ed50570477cf8e6aac713a5e69e9fb3f078`. Tracks R05/R06 in issue #36. This is an execution ledger, not evidence that the CMS workflows have passed. One of 64 version/workflow cells has passed: W05 on PHP 8.2.36. The remaining 63 full-case results are **not run**, with partial procedure evidence recorded separately. Earlier syntax scans, library loading and focused probes remain useful separate evidence.

## Execution order

1. Finish the bounded source-audit dispositions and decide the maintained optional publication scope. Record F03/F04 as approved temporary deprecation exceptions and F13/R04 as an approved publication exclusion; retain their follow-ups and do not treat exceptions as fixes. Do not silently remove APIs or optional tracker code.
2. Freeze one consolidated candidate source revision. Generate the core packages and selected addon TARs, record hashes and validate contents before asking for the user-operated upgrade. Avoid a prerelease for each small source fix.
3. Record the actual test environment, installed addons, backup/restore evidence and guard manifest. Use disposable installation directories/databases for installer and upgrade tests; a second public domain is not required and domain-dependent configuration must be preserved where needed.
4. Execute the cases below on each PHP version with diagnostic capture. Restore each version's fixture baseline and clear/rebuild appropriate caches so cached results from another PHP version cannot count as a pass. Record the real SAPI/extensions; a CLI result cannot stand in for the web request.
5. Repair confirmed failures in batches, rerun affected cases and freeze a new candidate only when necessary. Validate the exact final published artifacts under W16.

## Workflow matrix

| ID | Workflow | PHP 8.2 | PHP 8.3 | PHP 8.4 | PHP 8.5 |
|---|---|---|---|---|---|
| W01 | Fresh install | Not run | Not run | Not run | Not run |
| W02 | Session and authentication | Not run | Not run | Not run | Not run |
| W03 | Permissions | Not run | Not run | Not run | Not run |
| W04 | Admin Zone and update check | Not run | Not run | Not run | Not run |
| W05 | Content and rendering | Pass | Not run | Not run | Not run |
| W06 | Uploads and GD | Not run | Not run | Not run | Not run |
| W07 | Search and indexing | Not run | Not run | Not run | Not run |
| W08 | Scheduled tasks | Not run | Not run | Not run | Not run |
| W09 | Mail handling | Not run | Not run | Not run | Not run |
| W10 | Addon lifecycle | Not run | Not run | Not run | Not run |
| W11 | Themes and caches | Not run | Not run | Not run | Not run |
| W12 | Integrity inventory | Not run | Not run | Not run | Not run |
| W13 | Upgrade preservation | Not run | Not run | Not run | Not run |
| W14 | Database behavior | Not run | Not run | Not run | Not run |
| W15 | Optional maintained addons | Not run | Not run | Not run | Not run |
| W16 | Final package acceptance | Not run | Not run | Not run | Not run |

## Procedures and test scope

**W01: Fresh install.** Install quick and manual packages into disposable directories/databases; verify startup, schema and version. Disposable installation only; legacy helper can generate installer artifacts. Supplementary existing helper: `_tests/tests/unit_tests/_installer.php`.

**W02: Session and authentication.** Guest browsing, successful login, failed login, logout and session expiry; confirm redirects and session state. Use dedicated test account and retain the administrator recovery session.

**W03: Permissions.** Verify guest, ordinary-member and administrator access to representative front-end/Admin Zone actions. Existing test checks privilege language strings; it does not establish authorization behavior. Supplementary existing helper: `_tests/tests/unit_tests/permissions.php`.

**W04: Admin Zone and update check.** Open dashboard, navigate modules, verify Extended version/source, no compo.sr update request, and correct upgrade link. Record browser requests and PHP/webserver diagnostics.

**W05: Content and rendering.** Create/edit a fixture article and Comcode page; verify omitted fields, nested markup, Tempcode and clean-cache rendering. Use fixture content with recorded IDs; restore or remove only the recorded fixtures. Supplementary existing helper: `_tests/tests/unit_tests/comcode.php`.

**W06: Uploads and GD.** Upload fixture PNG/JPEG/GIF, resize/crop/thumbnail, download and compare dimensions/content. Use test-only media; inspect actual helper behavior before execution. Supplementary existing helper: `_tests/tests/unit_tests/images.php`.

**W07: Search and indexing.** Index fixture content, search ordinary/empty/Unicode terms, edit it and verify updated results. Legacy search setup can run the full-text indexer; isolate the fixture dataset. Supplementary existing helper: `_tests/tests/unit_tests/search.php`.

**W08: Scheduled tasks.** Execute a selected test task once, verify state, repeat safely and check failure reporting. Select a task with bounded effects; do not run every cron hook indiscriminately.

**W09: Mail handling.** Verify message composition, attachment cleanup, queued failure and local Postfix handoff. Helper disables email output and is not delivery proof; do not send another external message without a current need. Supplementary existing helper: `_tests/tests/unit_tests/mail.php`.

**W10: Addon lifecycle.** Browse verified catalogue, download/install a fixture addon, confirm/reject trust cases, uninstall and verify preservation. Static guard helper is supplementary; record installed-addon list before and after. Supplementary existing helper: `_tests/tests/unit_tests/addon_guards.php`.

**W11: Themes and caches.** Compile LESS, render front-end/Admin Zone, clear relevant caches, verify first request and refresh. Include awards/realtime_rain absent-addon warning reproduction as a tracked finding, not a hidden pass. Supplementary existing helper: `_tests/tests/unit_tests/theme_images.php`.

**W12: Integrity inventory.** Run upgrader integrity scan; verify bundled Extended files recognized and custom fixtures preserved. Record exact unknown/modified paths; do not bulk-delete reported files.

**W13: Upgrade preservation.** Apply full .cms to a disposable supported baseline; verify closed-site handling, selected files, settings and installed addons. Requires package hash, before/after inventory, database backup and tested restore path.

**W14: Database behavior.** Exercise representative create/read/edit/delete and failure handling using fixture records; verify no duplicate replay. Use the configured database driver and record server/extension versions.

**W15: Optional maintained addons.** Execute workflows for each optional addon included in the release candidate, including LESS and touched integrations. Maintain per-addon evidence; unavailable provider integrations remain not tested, not passed.

**W16: Final package acceptance.** Build combined packages and addon TARs; verify hashes, inventory, mirror/GitHub contents and rollback artifacts. Freeze source/package hashes; testing a different artifact does not qualify the published package.

## Recording a result

Update `php-workflow-acceptance.json` with the full PHP build, SAPI, extensions, database details, candidate hashes and backup reference. For each executed case, record pass/fail/blocked/not_applicable, durable evidence, observed diagnostics and fixture IDs. Explain every blocked or not-applicable result. An unavailable provider or skipped optional addon is not a compatibility pass. Preserve sensitive configuration and credentials outside public evidence.

The legacy test files named above are starting points, not an approved batch command. The installer helper can build installers, the search helper can run indexing, the mail helper suppresses outgoing mail, and the permissions helper checks language strings rather than access control. Inspect and isolate their effects before running them. No full legacy suite was run while preparing this ledger.

## Selected increment candidates reviewed

No production changes are needed at these inspected sites: `sources/calendar.php::find_abstract_day` decrements an explicit intval result; `sources_custom/geshi.php` initializes its shared-prefix position to integer zero before incrementing; `sources_custom/spout/src/Spout/Reader/Helper/XLSX/CellHelper.php` decrements strlen's integer length; and `sources/comcode_compiler.php` initializes the code-nesting counter to integer zero. This bounded source inspection does not establish that every increment in the repository is safe.

## Handoff boundary

When the candidate is concrete and staged, provide its version, exact .cms link/hash and backup/guard status. The user runs the agreed site-close/file-upgrade/cache-clear step. Resume verification and guard activation afterward. Preparation of this ledger did not change the website, its PHP selection, database or caches.


## Approved date fallback exception, 2026-11-21

The user approved preserving the remaining legacy date formatter fallback as a temporary deprecation exception. This path alone no longer blocks a test candidate, but it is not a resolved deprecation. Track complete replacement in php-date-fallback-replacement.md. Normal date workflows must still pass; unrelated warnings or failures are not covered by this exception. Workflow cells remain not_run until executed with evidence; see the current matrix above.
