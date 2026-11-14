# v0.7 read-only Admin Zone integration (development)

The new `adminzone/pages/modules_custom/admin_extended_addons.php` module implements a separate **browse-only** page. It does not alter `admin_addons.php`, install add-ons, download packages, or access network locations. This is an integration candidate, **not yet tested inside a full running Composr installation**.

The page reads an operator-supplied JSON configuration file named by the server-side PHP constant `EXTENDED_BROWSER_CONFIG_PATH`. The path must be absolute and outside the document root. Define the constant from a trusted server-side configuration early in bootstrap; do not accept path or key values from URL/query parameters, and do not commit private deployment configuration.

Example config (test paths and public keys must be replaced):

```json
{
  "schema_version": 1,
  "sources": [
    {"id":"example-a","enabled":true,"manifest_path":"/private/catalogues/a.json","signature_path":"/private/catalogues/a.sig.json","trusted_keys":{"a-key":"<base64-public-key>"},"allowed_key_ids":["a-key"]},
    {"id":"example-b","enabled":true,"manifest_path":"/private/catalogues/b.json","signature_path":"/private/catalogues/b.sig.json","trusted_keys":{"b-key":"<base64-public-key>"},"allowed_key_ids":["b-key"]}
  ]
}
```

These are local signed catalogue snapshots. Downloading/updating those snapshots is deliberately out of scope until source registry trust and cache recovery have been integrated. Source errors fail closed, with no unverifiable catalogue records displayed. Duplicate addon IDs across different sources are preserved and identified as `source_id:addon_id`.

Security constraints: no user-controlled URLs, no remote HTML, escaped publisher metadata, and no install links. The source config's public keys and file paths are administrative trust configuration, not secrets; nevertheless the configuration file should be protected from modification by the web process. Local fixtures do not establish real-world publisher trust. `get_entry_points` and permissions are additionally guarded by a super-admin check inside `run()`.

Known integration risk: the Composr module discovery convention and permission error handler still need smoke testing against a disposable full v10.0.52 install. Confirm whether `make_string_tempcode` content is safely treated as intentional HTML in the chosen templating context. Existing `admin_addons.php` remains unchanged.

## Test installation results (12 November 2026)

A read-only prototype was installed on a Composr 10.0.52 test site running PHP 8.2.35. Two independently signed local catalogues with the same addon ID were displayed as separate entries. A well-formed invalid signature caused only that source to be unavailable, and restoring the signature restored its listing. A test name containing HTML script markup appeared as text; no script element was present in the rendered table. The unconfigured page and invalid action were also checked.

The test deployment used a temporary local configuration path in the module and test keys. That deployment-specific path and keys are not included in the scaffold. The module has a basic page heading; the Admin Zone navigation link and non-super-admin access checks remain outstanding. Full network retrieval and install integration remain untested.

The page-grouping hook was installed on the test site and the super-admin Structure page displayed the entry. The link opened the browser and both signed fixtures remained verified. The non-super-admin visibility and direct-access checks remain untested.

The package preflight now rejects multiple addon.inf name assignments and mismatched name quotes. The three identity regression cases passed with PHP 8.2, 8.3, and 8.5 on the test server. Package verification and extraction are not ready for installation.

On 12 November 2026 the 29 component tests passed independently using PHP 8.2.35, 8.3.36 and 8.5.12 CLI on the test server. The suite includes the manual preflight script. These are component tests, not complete CMS compatibility or HTTPS integration tests. PHP 8.2 address filters were found to accept IPv4-mapped loopback and non-public special ranges. The transport prototype explicitly blocks IPv4-mapped IPv6, 100.64.0.0/10, 192.0.0.0/24, and 198.18.0.0/15, with regression checks. This is not a complete special-address registry audit and the transport remains experimental.

The archive preflight additionally rejects ambiguous separators, file/directory path collisions, inconsistent directory markers, and non-ustar prefixes. Further extraction and race-condition review is required before enabling installation. The transport additionally rejects documentation and tunneling ranges and conservatively restricts IPv6 to selected global-unicast space, which can reject some legitimate globally reachable addresses. This is a defensive prototype, not a complete IANA registry audit; real HTTPS and DNS pinning integration remain untested.

Follow-up review: the transport now aborts before curl_exec if curl_setopt_array fails; previously a failed option could allow a request before the subsequent IP check. This change has not yet been executed under PHP or tested with a forced cURL option failure. Exercise that failure and real HTTPS/DNS pinning before integration.

On 12 November 2026, an isolated copy of the latest transport passed PHP syntax checks on 8.2.35, 8.3.36 and 8.5.12, and each successfully fetched https://example.org/ over HTTPS using the production DNS pinning code. A 10-byte download limit was enforced and temporary files were cleaned up. A modified copy with option setup forced to fail and curl_exec replaced with a throwing expression stopped at setup and cleaned up, on all three versions. This simulation does not reproduce an actual libcurl option failure. Redirect handling, malicious DNS responses, TLS failure, concurrency, and complete registry coverage remain unverified. The transport remains disabled for integration.

Additional server checks on 12 November 2026: PHP 8.2.35, 8.3.36 and 8.5.12 each rejected expired.badssl.com and httpbin.org/redirect/1, with no temporary files left. Independent curl checks confirmed the expired certificate and a 302 response. Simulated resolver answers in an isolated modified transport copy rejected mixed public/private addresses, IPv4-mapped loopback, and missing-address records while accepting a public-only address on all three PHP versions. This does not exercise live DNS rebinding or prove that every special address is covered.

On 12 November 2026, archive preflight was tightened to require a 512-byte-aligned TAR with two zero end blocks and only zero padding afterward. Nine package-identity component tests, including missing, incomplete, and hidden trailing content cases, and PHP lint passed under PHP 8.2.35, 8.3.36, and 8.5.12. This stricter format can reject otherwise readable nonstandard TAR archives; test real Composr-produced packages before enabling staging. Path replacement races and Composr extraction behavior remain unresolved.

Offline publisher review: package ID must now match exactly one addon.inf name assignment, following the current verifier rules, and compressed TAR input is rejected. Six publisher and three signing Python tests passed. Publisher inspection is not yet equivalent to the PHP verifier or a substitute for extraction review; alignment for TAR end markers, path conflicts, and full metadata remains outstanding.

The offline publisher now rejects file/directory ancestor collisions in either order, matching an existing PHP preflight check. Seven publisher tests and three signing tests passed locally. Full TAR-parser equivalence and server integration remain unverified.

## Test-site return visit (12 November 2026)

On the test server, all 12 checkpoint PHP files passed lint under PHP 8.2.35, 8.3.36 and 8.5.12. A PHP-focused subset of 26 unit tests and the standalone local preflight each passed on all three versions. This is not the complete component matrix: the server had Python 3.9 without jsonschema, and PHP 8.4 was unavailable.

The test site's Admin Dashboard initially failed because the test-only module defined EXTENDED_BROWSER_CONFIG_PATH unconditionally and Composr evaluated it again during sitemap discovery. The existing test-only definition was guarded with defined() through Webmin. The dashboard and Structure page then rendered; the Extended addon repositories link showed both locally signed fixtures; and the stock Addons page opened. This fix was applied only to the test site's temporary configuration line; the checkpoint module itself does not contain that line. This is not an installed or production-ready release.

Outstanding: delegated/non-super-admin permission tests, stock-generated export compatibility and stock install/uninstall smoke tests; the full test matrix and PHP 8.4; state storage and recovery authority decisions; extraction review and a safe bridge. Do not infer that server-side component tests establish real CMS or exporter compatibility.

## Stock exporter and guest-access follow-up

- The authenticated administrator opened the Extended catalogue from Structure. Both locally signed fixture sources were verified and their shared addon ID was listed twice. The invalid action returned the read-only rejection. The stock Addons page opened.
- Separate HTTP requests without an authenticated session received HTTP 401 with the login page for both Structure and the Extended module. Neither response contained the fixture catalogue. This exercises guest denial at the zone boundary; ordinary-member and delegated-member behavior is still unverified.
- Created a new inert, empty text file on the test domain and selected that file alone in the stock export form. The resulting `extended_export_smoke_20261112` archive contained the text file and the generated `addon.inf`. The Hub publisher accepted the unmodified stock TAR, recognized the addon ID, and emitted SHA-256 `7b3c99e12b5d58c10b5a9bf0bc892a3b1c8782618a914bc3d23bf1c18259dd59`. This is a single-file exporter test. Nested files and theme exports remain unverified.
- The stock import form was reached and Upload selected. Chrome's file-access setting blocked automated selection of the TAR. Import, install, uninstall and fixture cleanup are still pending. The text file and exported TAR were left on the test domain for that workflow.
- The attached Webmin session was scoped to the test domain owner. The separate terminal tab showed a browser security warning. No new PHP CLI run was performed, so the PHP preflight on this exact TAR, the latest shared catalogue fixtures and the full matrix remain unverified.

## Stock import and uninstall follow-up

- Submitted the previously exported fixture through the stock local-upload form. Composr reported a successful import into its addon imports directory. The installation preview listed only the empty smoke-test text file and indicated the existing copy would be backed up before replacement.
- Continued with the stock installer. Composr reported success and the Addons table showed `extended_export_smoke_20261112` version 1.0 as Installed. The success page also displayed a warning about the missing `icons/24x24/tool_buttons/realtime_rain_on` theme image, which was not part of the fixture.
- The stock uninstall stopped before its file-removal confirmation on Composr 10.0.52 / PHP 8.2.35 with `Undefined array key "requires"` in `sources/addons2.php:494`. The Addons page still showed the fixture as Installed afterward. Cleanup is pending.
- Read-only inspection of the v10 source shows that `find_addon_dependencies_on()` scans addon registry hooks, can assign an empty dependency array when the hook has no extractable dependency method, then accesses the `requires` key unconditionally. The exact on-site hook triggering the warning was not identified. Check the active hooks and test an empty-array fallback for a missing `requires` key while preserving existing dependency checks, then rerun the stock uninstall. The live core was not edited.

## Stock uninstall retest

- On the disposable Composr 10.0.52 site, Webmin saved a one-line change in `sources/addons2.php`: the registry dependency lookup now defaults an absent or null `requires` value to an empty array. Declared dependency lists are still used by the existing check. The precise registry hook that omitted the key was not identified.
- Reran stock uninstall for `extended_export_smoke_20261112`. Its confirmation listed only the inert text file. Composr reported success and listed the fixture as Not installed, with a reinstall TAR. An unauthenticated request to the former fixture file returned HTTP 404.
- The compatibility change remains on the disposable site. The accompanying patch documents the change against the stock core file. The original export and the uninstall archive were retained. Delegated and ordinary-member checks, PHP preflight on the original TAR, and the complete PHP matrix remain outstanding.

## Stock export PHP verification

The retained original `extended_export_smoke_20261112` export on the test server matched SHA-256 `7b3c99e12b5d58c10b5a9bf0bc892a3b1c8782618a914bc3d23bf1c18259dd59`. Isolated copies of the checkpoint PHP preflight accepted this exact two-entry, 2,560-byte TAR on PHP 8.2.35, 8.3.36 and 8.5.12, confirming its ID and digest. This uses a previously computed digest for a compatibility test, not an authenticated catalogue signature. The export contains one flat text file; nested and theme exports and the actual Extended installation bridge remain untested.

## Full PHP CLI component matrix

Ran the checkpoint's component runner with PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12. Each run passed PHP lint, all 55 discovered tests (including the shared signed catalogue fixtures), and the standalone local preflight. PHP came from the official CLI images, run in the workspace with networking disabled for the component runner. The first pass exposed five tests that assumed uncaught PHP exceptions always go to stderr. The package identity test harness now writes caught RuntimeException messages to stderr explicitly, so the tests check the intended rejection reason independently of PHP's error-output settings.

The original stock TAR also passed separate PHP preflight on all four versions with its recorded SHA-256, matching addon ID, two entries and 2,560 bytes. These are CLI component checks. The deployed site was not changed during this matrix run. Ordinary-member and delegated Admin Zone checks remain open because a suitable test account is unavailable.


## Nested-file and theme stock exports

Composr 10.0.52 generated both TARs through its stock exporter. The nested custom export selected only `icon/mime/readme.txt`. The theme export used the test site's News Site theme with 194 CSS, image and theme metadata selections. Sales Portal templates were excluded. Composr added companion files such as `.editfrom` where applicable.

| Addon ID | Entries | Bytes | SHA-256 |
| --- | ---: | ---: | --- |
| `extended_nested_smoke` | 2 | 3,072 | `88a2a5267c4570445d77989bdeb3c3359b1d76dade28612d99505cc3914e27b2` |
| `extended_theme_smoke` | 277 | 1,627,648 | `d5f7bbde7cb65d28f74ec5927f22dd6fcb3d74d8d64c8119fed9bc6efdef6b17` |

The Hub publisher and Extended PHP preflight accepted both original exports with matching IDs and hashes. PHP preflight passed on 8.2.35, 8.3.36, 8.4.27 and 8.5.12. These checks cover archive compatibility. No theme import or installation was performed on the active test theme. The generated export TARs remain on the test site.
