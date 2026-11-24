# PHP 8.2–8.5 CMS workflow acceptance

Prepared 2026-11-19 at source checkpoint `a20c4ed50570477cf8e6aac713a5e69e9fb3f078`. Tracks R05/R06 in issue #36. This is an execution ledger, not evidence that the CMS workflows have passed. Of 64 version/workflow cells, W05 and W06 on PHP 8.2.36 passed, W10 on PHP 8.2 failed, and 61 remain **not run**. Partial procedure evidence is recorded separately. Earlier syntax scans, library loading and focused probes remain useful separate evidence.

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
| W06 | Uploads and GD | Pass | Not run | Not run | Not run |
| W07 | Search and indexing | Not run | Not run | Not run | Not run |
| W08 | Scheduled tasks | Not run | Not run | Not run | Not run |
| W09 | Mail handling | Not run | Not run | Not run | Not run |
| W10 | Addon lifecycle | Fail | Not run | Not run | Not run |
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

## Alpha.23 follow-up evidence, 2026-11-22

The earlier matrix and candidate metadata above describe prior checkpoints. Alpha.23 was subsequently installed and tested on PHP 8.2 web: the dashboard reported alpha.23 and no newer Extended release; seven enforcement files matched; and one signed mirror GeSHi install/uninstall lifecycle was exercised with ai_agent_builder present. An administrator's ordinary news search for `revised` returned the fixture using relevance and date sorting. These are partial observations, not full W04 or W10 acceptance. The alpha.23 integrity scan did not flag the protected Extended enforcement files; reported site-specific files were left untouched. See `php-alpha23-workflow-smoke-2026-11-22.json` for details.

Alpha.23 title-only news search for `fixture` failed. A follow-up source change was temporarily applied on the closed PHP 8.2 site: title-only search found the fixture with relevance and date sorting, excluded body-only `revised`, and ordinary search found `revised`. The original alpha.23 file was restored and the failure recurred. The fix is on the audit branch but is not in the alpha.23 upgrader. A PHP 8.4 CLI SQL-generation harness is supplementary evidence only. One matching result cannot establish ordering. Guest and restricted-category permissions, multiple matches, pagination, multilingual content, performance and other database configurations remain unverified, so W07 remains incomplete. Entity-encoded search is a separate deferred design issue.

The alpha.23 backup was gzip-verified and included a home archive and database dump; a full restore of that backup has not been tested. Optional LESS fixes were packaged separately and are not automatically installed by the core upgrader. No matrix status is promoted by this note.

## Site recheck, 2026-11-22

A fresh administrator dashboard request reported alpha.23 and no newer release. The site configuration showed Closed selected. On the same closed site, administrator news search for `fixture`, with title-only and date descending, returned no results; after clearing title-only and submitting, it returned the retained alpha.22 news fixture. This confirms the previously recorded baseline behavior, not a retest of the proposed fix. The active PHP web version, installed source hash, guard manifest and diagnostic logs were not independently verified in this recheck. Permissions, multi-result relevance, pagination and additional database configurations remain untested. Webmin command execution became unresponsive during the protected-file checks; no test fixtures or site files were modified.

## Two-result search and guard check, 2026-11-22

On the closed alpha.23 test site, the installed `sources/database_search.php` SHA-256 matched the restored alpha.23 value `79bc9ea36c40f0e4daab8c14145815ffa632f3641ea044f5797046e929d56e0b`. All seven files checked by the existing enforcement manifest matched. This checks the installed files, not backup restoration or the full guard behavior.

Created unvalidated test news fixture ID 14, “AUDIT searchbatch zirconium zirconium primary”, in General, with an explicitly marked test body. The CMS reported dispatching a staff validation notification; no further fixtures were created. Fixture 14 is retained for continued controlled tests and should be removed only as an explicitly identified test fixture after the work. Existing fixture ID 13 was preserved.

As administrator, ordinary news search for `audit` returned both fixtures. With relevance selected, the older ID 13 appeared before ID 14; with date descending, newer ID 14 appeared before ID 13. This establishes the observed multi-result ordering and date order on the installed alpha.23, but the fixtures were not designed to establish an expected relevance score or tie behavior. The title-only fix was not installed or tested in this batch. Guest/restricted permissions, pagination, controlled relevance, multilingual behavior, other database configurations, and PHP 8.3–8.5 web paths remain unverified. No diagnostic log deltas were collected; no workflow status is promoted.

## Temporary title-only candidate with multiple matches, 2026-11-23 UTC

The user confirmed that test-site staff validation notifications are acceptable. Created unvalidated test news ID 15, “AUDIT searchbatch fixture fixture secondary”, General category. Its title includes `fixture` twice; body includes it three times. Existing test ID 14 has the term in its body but not title; retained earlier ID 13 has it once in its title. IDs 14 and 15 are explicitly created test fixtures retained for further work.

With the site closed and authenticated as administrator, temporarily installed the exact proposed search source SHA-256 `f517411a2d13d0a4d58b8d7f7a47491489b19302f33c4b606ed75e7746b0f843`, with a timed restoration guard. Title-only news search for `fixture` returned IDs 13 and 15 and excluded body-only ID 14 in relevance and date-descending modes. Date descending ordered newer 15 before 13. Relevance ordered 13 before 15, despite the latter's two title occurrences; no expected-score oracle or SQL score inspection was captured, so this is an open ranking question, not a pass or proven bug. Restored original alpha.23 source and verified SHA-256 `79bc9ea36c40f0e4daab8c14145815ffa632f3641ea044f5797046e929d56e0b`. No PHP or server log deltas were collected. This is partial PHP 8.2 web evidence; guest/restricted permissions, pagination, multilingual behavior, other database configurations, and PHP 8.3–8.5 web paths remain unverified. Encoded-character search remains deferred.

## Relevance score investigation, 2026-11-24

The dashboard reported alpha.23 and the Open website task, consistent with the site remaining closed. A read-only PHP 8.2 CLI query against the site's news table on MariaDB 10.5.29 measured title-only `fixture` scores: ID 13 natural 0, boolean 1; ID 15 natural 0, boolean 1; body-only ID 14 natural 0, boolean 0. The candidate's natural-score/boolean-score/ID ordering therefore predicts 13 before 15 for this dataset; repeated title words do not create a higher boolean score here. This explains the previously observed ordering but does not establish general relevance quality or prove web behavior for other datasets.

A session-scoped temporary MyISAM table with three synthetic rows (one occurrence, two occurrences, and no occurrence) returned natural scores 0/0/0 and boolean scores 1/1/0, again tying the two matches. The temporary table was dropped. MariaDB rejected creation of a FULLTEXT index on a temporary InnoDB table, so no InnoDB score comparison was obtained; no persistent table or engine change was made. These are database/CLI probes, not CMS web acceptance. Pagination, guest/restricted permissions, InnoDB, multilingual content and PHP 8.3–8.5 web paths remain open. No candidate source was installed in this investigation.

## Search pagination checks, 2026-11-24

On the closed test site as administrator, ordinary news search for `audit`, date descending, `search_max=1`, returned IDs 15, 14 and 13 on successive pages. The approximate total and pagination links changed from 5 on page 1 to 4 on page 2 and 3 on page 3; page 1 exposed links for two extra pages. This is a reproducible count/navigation discrepancy on the installed alpha.23 baseline, not evidence of missing or duplicated results among the three pages visited. Source inspection suggests the separate keyword and content counts are added, but deduplication only considers fetched rows; this is a hypothesis pending query-level confirmation and broader permission/multi-hook coverage. Do not treat the displayed total as exact. No fix is included yet.

Then temporarily installed the exact candidate search file SHA-256 `f517411a2d13d0a4d58b8d7f7a47491489b19302f33c4b606ed75e7746b0f843`, with timed restoration. Title-only news search for `fixture`, `search_max=1`, returned 15 then 13 for date descending and 13 then 15 for relevance descending. Each page showed approximately 2 results; page 2 had no active Next link. The body-only fixture 14 was not returned. Restored alpha.23 source and verified SHA-256 `79bc9ea36c40f0e4daab8c14145815ffa632f3641ea044f5797046e929d56e0b`. This covers one administrator dataset and one page size, not guest/restricted permissions, other database configurations, or the full search workflow. No PHP/webserver log delta was captured.

## Pagination count diagnosis and InnoDB probe, 2026-11-24

On the closed site, the administrator's alpha.23 ordinary news search for `audit` with `search_max=1` reported approximately 5 results. Its displayed query used boolean full-text over title, news and news_article. A read-only query of fixture IDs 13, 14 and 15 found that every one matched both that full-text condition and its `audit` news keyword. Source inspection shows both total counts added, while only fetched duplicate rows decrement the total. In this dataset the two sets each contain the same three rows, and fetching one, two, then three overlapping rows explains the observed 5, 4, then 3 totals. Changing the page size to 5 in the CMS produced approximately 3 results. This establishes the cause for this fixture, not all drivers, hooks, access cases or broader pagination behavior. Fix design remains open: permissions, query limits, metadata-only and multilingual paths must not be regressed.

A separate PHP 8.2 CLI probe created a uniquely named, isolated InnoDB FULLTEXT test table in the test database with three synthetic rows and dropped exactly that table in a finally block; the command reported successful cleanup. On MariaDB 10.5.29 the repeated-word row scored approximately 0.0620163 and the single-word row 0.0310081 in both natural and boolean modes; the unmatched row scored zero. This contrasts with the earlier MyISAM tie for the same three synthetic strings. This is not an InnoDB CMS web test and does not establish full driver or workflow compatibility. No installed news table or source file was changed. No workflow status is promoted.

## Scheduler evidence correction, 2026-11-24

The earlier W08 ledger note inferred that the system schedule referenced the test site's bridge. A new read-only check does not substantiate that attribution and supersedes it. The closed site's administrator dashboard displayed the scheduler setup task. System crond and the PHP 8.2 FPM service were active. In the test site's database, the `last_cron` value and elective `last_cron_started`/`last_cron_finished` timestamps corresponded to 2026-11-22 08:50:51 UTC, about two days before this check. The stored CMS and forum version values were both 10.00; that alone does not prove the bridge version gate passed.

The recent crond journal contained commands referencing a Composr cron bridge, but the examined commands did not contain this test site's known document-root prefix or domain. A search of `/etc/crontab`, `/etc/cron.d` and `/var/spool/cron` found no uncommented bridge entry containing either identifier. This does not rule out indirect wrapper scripts, aliases, URL variants, systemd timers or execution elsewhere, and the command logs do not prove completion. The site-specific schedule and the reason for the old markers therefore remain unresolved. No scheduled job was added, no unrelated job was changed, and no full cron hooks were invoked. W08 remains not_run. Separate issue #61 tracks exception/lock handling; do not attribute this scheduler observation to that issue without further evidence.

## Test-site scheduler enabled, 2026-11-24

The user authorized a test-site cron entry. A bounded PHP 8.2 CLI preflight as the site account selected a nonexistent hook and exited successfully; it advanced the shared scheduler markers but did not execute normal hooks. A five-minute system crontab entry was then added for the test-site account and PHP 8.2, with command output routed to the system journal. Existing crontab bytes were verified unchanged, a restricted backup of the original was made, and crond reloaded the file.

System-wide cron journal records show the test-site entry starting and ending at 12:15 and 12:20 UTC. The site's database start and finish markers were 12:15:01 and 12:15:02 UTC when checked at 12:17:54; no output was found under the job's journal tag at that check. The preflight markers therefore must not be mistaken for proof of a normal run. The cron command pipes output to a logger, so the pipeline's status alone does not establish the PHP process exit status or that each hook succeeded. This is partial scheduled-CLI evidence only; individual hooks, failure reporting, lock behavior and web workflows remain unverified. W08 remains not_run; issue #61 remains open.

## Additional search page-size check, 2026-11-24

The administrator dashboard reported alpha.23 and indicated that the site was closed. On the closed site as administrator, ordinary news search for `audit` with date descending and `search_max=2` returned IDs 15 and 14 on page 1 with approximately 4 results, then ID 13 on page 2 with approximately 3 results. These three displayed records did not repeat across the two pages. A separate news search for `auditnonexistentqvzx` returned the no-results message. These are further PHP web observations for the installed baseline; the web SAPI version and diagnostic log delta were not independently rechecked in this batch. They do not establish non-administrator access, other drivers, or general search correctness. The count discrepancy and acceptance requirements are tracked in issue #65. W07 remains incomplete.

## Guest masquerade search checks, 2026-11-24

With the test site closed and alpha.23 reported by the administrator dashboard in the immediately preceding check, used the built-in SU control to masquerade as Guest. The page explicitly identified the Guest masquerade and explained that administrator SU allowed access while the site was closed; this is not a test of an unauthenticated session accessing an open site. Using ordinary news search and date descending, `audit` and `fixture` each returned the validated fixture ID 13 (approximately one result) and omitted the two unvalidated fixtures IDs 14 and 15. The administrator's preceding `audit` test had returned all three. Guest search for `zirconium`, which matched unvalidated fixture 14 in the administrator test, returned no results. The displayed main news query included `validated=1`. Guest title-only `fixture` returned no results on installed alpha.23, reproducing the known baseline failure; the proposed title-only fix was not installed or exercised for Guest in this batch.

These observations cover one validated public-category article and two unvalidated General-category fixtures in one SU session, not guest access to restricted categories, a separate ordinary-member account, unvalidated-only metadata matches, pagination with multiple guest-visible hits, direct article access, or other database/PHP configurations. SU's closed-site bypass must not be conflated with ordinary guest behavior on a closed site. No log delta or fresh web SAPI version was recorded. W03 and W07 remain incomplete.

## Guest direct-link checks, 2026-11-24

The dashboard still reported a closed test site, two unvalidated news fixtures, and a recent scheduler run. Through the administrator SU feature masquerading as Guest, followed the administrator search result links for validated fixture ID 13 and unvalidated fixtures IDs 14 and 15. All three article pages rendered; each unvalidated article displayed the CMS warning that it had not yet been validated. These were direct-link tests, distinct from the previous searches that omitted the two unvalidated items. Article view counters may have advanced as a result of opening these fixtures.

Source inspection of `site/pages/modules/news.php` shows an explicit `jump_to_unvalidated` privilege check followed by the warning for unvalidated direct views when the unvalidated addon is installed. The effective privilege configuration and whether SU's administrative context changes the result were not established in this batch. This observation alone therefore does not establish a guest authorization defect or behavior in a truly anonymous browser. No category restrictions or validation states were changed. Further checks should establish effective Guest privileges and compare a genuinely unauthenticated session when feasible; W03 and W07 remain incomplete.

## Guest validation privilege configuration, 2026-11-24

The test site's Admin Zone global privileges matrix showed the Guests group unchecked for `see_unvalidated` (see links to or embedded non-validated content) and checked for `jump_to_unvalidated` (see non-validated content when directed to it). This global configuration is consistent with the previous SU observations: guest search omitted unvalidated news while direct article links displayed it with a warning. The news module explicitly checks `jump_to_unvalidated` for direct access. This is configured behavior for this test site, not evidence of a PHP compatibility regression. The global matrix alone does not rule out page/category overrides or establish behavior for other installations, and the SU session is not a separate unauthenticated browser session. No privilege or category settings were changed. Restricted-category and multiple-guest-result tests remain open; W03 and W07 remain incomplete.

## Guest pagination with two validated news items, 2026-11-24

The CMS/Admin Zone requested high-security login confirmation, but the existing front-end administrator session remained active and reported that the site was closed. A read-only test-database inventory identified two validated news items, IDs 12 and 13, and unvalidated test fixtures 14 and 15. In the built-in SU Guest session on installed alpha.23, ordinary news search for `news`, date descending, page size 1, returned ID 13 on page 1 and ID 12 on page 2. The approximate count changed from 3 on page 1 to 2 on page 2. Following the page-3 link shown on page 1 yielded no article and an approximate count of 2. The displayed content query included `validated=1`. This establishes an extra empty page for this guest SU dataset in addition to the administrator count observations tracked in issue #65; it does not establish the exact overlap for this separate query or a permission leak.

No fixtures or category permissions were changed. Guest restricted-category behavior, a separate ordinary-member account, other databases and the title-only candidate under Guest remain untested. This is SU-assisted web evidence on a closed site, not a real anonymous session; the current web SAPI and diagnostic log delta were not independently verified. W03 and W07 remain incomplete.

## Restricted-category Guest search, 2026-11-24

The site dashboard reported alpha.23 and the closed site. Created explicitly labeled test news category ID 10, `AUDIT restricted search fixture`, and verified its edit form showed Guests access unchecked and Super-moderators checked. Created validated test news ID 16, `AUDIT restricted cobaltneedle fixture`, in category 10 with the synthetic marker in title and body. Both fixtures are retained for further work; do not remove unrelated content. This was a normal CMS web operation, not a core upgrader. The actual PHP web version and diagnostic deltas were not independently captured.

As administrator, ordinary news search `cobaltneedle`, date descending, found ID 16. In an administrator SU Guest session on the closed site, the same search without query diagnostics showed no results, and following its direct link denied Guest category access; the denial page included a shortened article title in its breadcrumb. With administrator-enabled `keep_show_query=1`, the Guest SU output displayed the underlying search query and row ID 16 before category filtering, but did not render the article as a search result. Whether that diagnostic exposure occurs for a genuinely anonymous request was not tested and must not be assumed.

With ordinary news search `audit`, date descending, and query diagnostics off, Guest page size 1 displayed the no-results message, even though public validated ID 13 matched. At page size 2 ID 13 appeared with approximately 2 results; at page size 5 ID 13 appeared with approximately 1 result. Restricted ID 16 did not render in those results. This establishes a missed visible result and page-size-dependent count on this mixed-access fixture. Post-fetch category filtering and bounded fetch are a possible explanation, not yet demonstrated by a controlled query trace. This is an SU Guest test, not a separate anonymous session; member access, other database configurations, and the proposed title-only fix remain untested. W03/W07 remain incomplete; issue #65 covers the related pagination/count work.
