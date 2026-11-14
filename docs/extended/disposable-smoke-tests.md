# Disposable Composr 10.0.52 smoke tests

Use a disposable ComposrCMS 10.0.52 instance with a database and filesystem snapshot. Record the CMS revision, web PHP version and CLI PHP version. Use test accounts, local signed fixtures and temporary Ed25519 keys. Keep fixtures and the browser configuration outside the document root. Follow [browser integration](browser-integration.md) for the overlay and configuration. The checkpoint contains an overlay, so begin with a separate working Composr installation.

## Admin Zone permissions

Copy the custom source files, the read-only module and its page-grouping hook to their corresponding paths in the disposable site. Use the installed module's actual URL from the Structure page as the direct link for all accounts. Use separate browser sessions and verify the active account on each.

| Check | Procedure | Expected result |
| --- | --- | --- |
| Super-admin baseline | Sign in as super-admin. Open Admin Zone → Structure, follow **Extended addon repositories**, and record the browse URL. | The entry appears and opens the read-only browser. Both signed test sources appear, including the same addon ID under distinct source IDs if using the collision fixture. |
| Delegated Admin Zone access | Use a member who can open Structure and has ordinary module/page access where configurable, while remaining outside the super-admin group. Inspect Structure and visit the recorded browse URL directly. | The entry is absent. Direct access is denied and the response body contains no catalogue data, configured paths or signing details. Record whether zone, page or module authorization denied the request. |
| Ordinary member and guest | Visit the same browse URL from each session. | The instance denies access or requires sign-in. Neither response exposes catalogue data or configuration. |
| Action dispatch | As super-admin, use the direct URL with `type=invalid`. Repeat as the delegated member. | The super-admin sees the read-only rejection. The delegated member is denied. |
| Permission regression | Reopen Structure as super-admin and open the stock Addons page. | The Extended entry is still available. The stock Addons page opens through its ordinary navigation. |

If the delegated account cannot open Structure, record zone denial and repeat with a legitimate delegated role that can open Structure. A blocked zone request alone does not exercise the hook or the module's own guard. If the browser link is missing for all users, confirm hook discovery and clear the instance's relevant caches, then repeat the baseline before interpreting the permission results.

## Composr exporter and stock import

1. On the disposable instance, create an inert custom addon with a unique ID, `addon.inf`, and at least one text file in a nested path. Export it through the stock addon tooling. If available, repeat with a representative theme addon with its normal directory layout. Keep the original bytes. Record the exact addon ID, export method, SHA-256 and PHP version.
2. Copy the TAR to the test runner and compare the offline publisher and PHP preflight on the same file. Use absolute paths. Run the publisher against a catalogue source JSON modeled on [the example](../../../hub/examples/catalogue-source.json), with matching ID and `package_file`.

   ```sh
   python3 hub/publisher/build_catalogue.py --input "$catalogue_source" \
       --package-root "$package_dir" --output "$unsigned_catalogue"
   php -r '
       require "cms/sources_custom/extended_addon_download.php";
       $digest = hash_file("sha256", $argv[1]);
       $result = extended_verify_local_addon_package($argv[1], $digest, $argv[2]);
       echo json_encode($result, JSON_THROW_ON_ERROR), PHP_EOL;
   ' "$archive_path" "$addon_id"
   ```

   These commands run from the checkpoint root. The self-computed digest in the PHP check isolates parser compatibility. A signed-catalogue test must instead take its expected digest from the verified catalogue. The two preflights should accept a conforming export, report the expected identity and agree on SHA-256. If a stock export is rejected, retain the unmodified TAR and record the exact rule and error before changing either parser.
3. Use the disposable site's ordinary local upload/import interface with the same inert export and an unused addon ID. Check the stock confirmation and the file inventory before confirming. Confirm the expected files are installed, exercise stock uninstall, and compare with the snapshot. Record validation or cleanup discrepancies. The Extended browser has no import path to exercise in this checkpoint.
4. Before wiring any Extended import bridge, compare the stock code's handling of member names, directory entries, existing destinations, and cleanup on a cancelled or failed import against the preflight assumptions. Use benign fixtures and an isolated snapshot. Repeat the expected install and cleanup checks through the bridge once it exists.

## Record results

For each row or export, record the account role or fixture, CMS/PHP versions, expected result, observed result, and pass/fail/blocked status. If an export fails, include the hash and exact error. Preserve the test snapshot until any failure has been reproduced and documented.
