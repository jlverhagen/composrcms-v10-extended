# ComposrCMS v10 Extended handoff - 2026-11-22

This is an unofficial maintenance fork of Composr 10.0.52. Continue the PHP 8.2-8.5 compatibility and workflow audit, tracking evidence and batching related changes. The test site is composr.duckdns.org; the user has authorized use of its existing Webmin session and test site. It was left closed after alpha.23 testing. Recheck its current state before changing it. The user operates the site-close, upgrader, and cache-clear step for a staged candidate.

## Source and release state

- Core repository: https://github.com/jlverhagen/composrcms-v10-extended, branch `audit/consolidated-package-inventory`.
- Hub repository: https://github.com/jlverhagen/composrcms-v10-extended-hub, branch `audit/installer-package-verification`, published commit `452924205ee4027d1f55104fb60b593d52e92b71` (tree `4f0546e3c5c3a72e9b187eeaaa47277037443dd6`).
- Last published core commit before this handoff: `ef53c50f30fb20940d51510a31f1474ae0cd86a4`. The local title-only fix was committed as `71361c1c3`; the published successor is based on the existing remote branch and may have a different commit ID. Use the branch head and inspect its content, rather than assuming this local SHA is a remote ancestor.
- The installed test candidate is `2026.11.1-alpha.23`. The staged test upgrader is https://composr.duckdns.org/extended-mirror/test-upgrades/2026.11.1-alpha.23/composr_upgrader-2026.11.1-alpha.23.cms, SHA-256 `b51f51f59cdc2247d9a43e8e91655c22adea8b0e439c4262e98dc3eae6033751`. It was built from the published core/hub trees preceding the title-only follow-up. It does not include the new title-only fix.
- The alpha.23 optional LESS changes are in the optional addon archive and are not automatically installed by the core upgrader. Its local package work built 21 addon TARs; do not claim these are published or installed without checking.
- The title-only fix was temporarily applied to the closed site for the recorded test, then the alpha.23 file was restored and hash-verified (`79bc9ea36c40f0e4daab8c14145815ffa632f3641ea044f5797046e929d56e0b`). The local proposed file hash is `f517411a2d13d0a4d58b8d7f7a47491489b19302f33c4b606ed75e7746b0f843`. Check state again before future modifications.

## Tested and limitations

See `php-alpha23-workflow-smoke-2026-11-22.json`, `php-candidate-alpha23-local-2026-11-22.json`, `php-workflow-acceptance.md`, `php-workflow-acceptance.json`, and `php-compatibility-audit-2026-11-17.md`. These documents record different points in time; do not treat an earlier baseline's status as the current state.

Alpha.23 dashboard reported the Extended version and no newer release; all seven enforcement files matched. The signed mirror GeSHi addon installed and uninstalled through the Admin Zone with ai_agent_builder present; GeSHi was confirmed not installed and main files absent. This is one lifecycle path, not full acceptance. Administrator search for `revised` found the fixture with relevance and date sorting. The alpha.23 title-only `fixture` search failed; the test-site database's two-row MyISAM title index returned zero natural-language matches and one boolean match. The follow-up source extends the narrowly guarded non-translated MySQL news fallback to title-only. On PHP 8.2 web, temporary candidate checks found `fixture` with relevance and date sorting, excluded body-only `revised` in title-only mode and found `revised` in ordinary search. Original source was restored; the original failure recurred. A stubbed PHP 8.4 query harness passed title/all-field, relevance/date SQL-generation checks and failed the baseline title-only boolean-filter check. A single matching fixture cannot establish multi-result ranking. Guest/restricted-category permissions, pagination, multilingual behavior, performance, and PHP 8.3-8.5 web workflows remain unverified.

The integrity scan reported existing a custom float override, site assets and backup files; it did not flag the protected Extended enforcement files. No cleanup was submitted. Do not bulk-delete files flagged by the scan.

A fresh full Virtualmin test-server backup before alpha.23 passed gzip verification and contained the home archive and database dump. Its SHA-256 is `5c662e92d75f7a1615b8720cb03add7d27f4c59c3a0c4370634ae3d34ac7e02a`; a full restore of this new backup was not tested. An earlier isolated restore tested the database from a different backup. Backup location is private and must not be published. The guard manifest hash was `16e4229919635518698054ae988761770d8caa7ffb79a003d5e283b26ecebc28`.

## Next work

1. Check repository branch heads and site state. Update the workflow ledger with the alpha.23 evidence without changing incomplete cells to pass. Validate the title fix with restricted/non-super-admin access, multiple matching fixtures and relevance order, pagination and relevant MySQL/MariaDB configurations when feasible, avoiding a new public test site. Preserve or clean up only explicitly created fixtures.
2. Continue the consolidated PHP 8.2-8.5 audit and web workflow matrix, including actual diagnostics, integrity, upgrade preservation, optional addons and the separately tracked cron failure. CLI evidence does not substitute for web acceptance. User approved specific temporary deprecation exceptions and optional publication exclusions; read the audit for exact scope.
3. Entity-encoded character search remains deliberately deferred for later design. Content may contain literal and encoded characters; do not assert that the current title fix solves that issue.
4. Bundle fixes and checks into larger batches. Before a future user-operated upgrade, prepare a verified candidate with exact version/hash, backup and guard status. Never publish backup directories, credentials or private server paths.

User authorized installation of needed PHP extensions on Webmin if they do not interfere with running sites; ask before removing any extensions. Avoid changes to unrelated websites. Preserve the user's backup and restore constraints. The user prefers minimal token use, meaningful batches, and concise progress updates.
