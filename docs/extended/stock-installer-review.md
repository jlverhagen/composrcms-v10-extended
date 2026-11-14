# Stock Composr v10 addon installer review

Reviewed the v10 branch of the Composr fork against the existing Composr 10.0.52 test-site results. The source references below use Git blob IDs so the inspected contents can be identified if the branch moves.

| Source | Git blob | Relevant functions |
| --- | --- | --- |
| sources/addons2.php | `48e93b6baf471bfb344e1e6388d8756599a1421b` | `install_addon`, `inform_about_addon_install`, maintenance flag helpers |
| sources/tar.php | `61d038d727887a85757d88c65a82549ba0d0a023` | `tar_get_directory`, `tar_get_file`, `tar_extract_to_folder` |
| adminzone/pages/modules/admin_addons.php | `548a159394e748ae98fd085a0185d209d7b7f2f9` | `_addon_import`, `multi_action`, `_multi_action`, `addon_install`, `_addon_install` |
| sources/abstract_file_manager.php | `4707bffce1ab8b37f42d18dfaf4ff0bc9881f065` | `_rescope_path`, `afm_make_file` |

## What stock Composr does

- Import places the TAR in `imports/addons` and sends the administrator to the multi-action preview. Leaving the preview can therefore leave an imported TAR. The single-addon preview also reads from that directory. The preview and install POST open the archive in separate requests.
- The stock reader and the Extended verifier have different parsing rules. Stock parses names into its own directory and checks the header checksum. Extended additionally enforces canonical archive paths, supported member types, and strict end-marker framing. Compare the two readers' inventories and addon identity before exposing a handoff.
- The single-addon install POST passes selected paths to `install_addon`. The multi-action actualiser calls it in separate filesystem and database passes with a null file list. Use the single-addon confirmation workflow as the integration reference for preserving per-file selection. The installer can perform addon-wide actions in addition to file extraction, including module and block installation and addon install code.
- The extractor creates directories and writes files in order through the abstract file manager. Its timestamped backup behavior applies to overwritten lowercase `.txt` paths. It does not provide an archive-wide backup. Review the effective destination root and parent paths as well as archive member names. The stock file manager can write locally or through its configured FTP backend.
- Filesystem writes, database changes, cache operations and install hooks are separate steps. No common rollback surrounds them. A normal shutdown can clear the maintenance flag, which does not restore files or database state. Treat an interrupted install as an uncertain state requiring inspection and possible restoration before retrying.

## Checks run

Ran the stock TAR reader in isolation on the original flat, nested and theme exports. On PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12, it read `addon.inf` and listed 2, 2 and 277 entries respectively, matching the Extended preflight counts. With a stubbed file manager, a one-file selection produced one write for each TAR. An injected failure at the second selected theme file left the first write recorded on all four versions. This checks extraction control flow with a stubbed file manager. It does not exercise actual filesystem recovery, the FTP backend, database failures or installation of the active theme.

## Handoff to test next

Use a verified local archive and the stock single-addon confirmation flow. Retain the verified archive identity (source, addon ID and digest) and bind it to the current admin confirmation. Recheck the bytes and stock file inventory on the final POST before the first filesystem write. Keep the verified archive protected against replacement between the last check and the stock reader's open. Check the effective destination root, selected paths and conflicts. Start with the local file manager on an isolated test instance with both filesystem and database snapshots. Record which phase failed and verify restoration before retrying. Include cancel, second-write failure and a failure after filesystem work in the test plan. The review does not activate a bridge or change the live installer.
