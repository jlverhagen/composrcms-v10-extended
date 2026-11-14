# Verified v10 source touchpoints (inspection of `jlverhagen/composr`, branch `v10`)

| Existing file | Existing symbol | Plan |
| --- | --- | --- |
| `sources/addons2.php` | `find_remote_addons()` (~202) | Retain legacy signature as compatibility adapter where possible; new typed catalogue service for Admin Zone |
| `sources/addons2.php` | `find_updated_addons()` (~229) | Replace remote PHP serialization / mtime heuristic with signed JSON and explicit publisher/version comparison |
| `sources/addons2.php` | `find_available_addons()` (~355) | Leave local archive discovery intact; verify imported remote packages before placement |
| `sources/addons2.php` | `install_addon()` (~631) | Preserve installer; do not bypass confirmation, backup, or existing checks |
| `adminzone/pages/modules/admin_addons.php` | `addon_import()` (~536) | Replace `choose_composr_homesite_addon` with source-aware catalogue UI; preserve upload |
| `adminzone/pages/modules/admin_addons.php` | `_addon_import()` (~581) | Remove numeric ID to `http://compo.sr/site/dload.php` conversion; route verified downloads into existing import path |
| `sources/version2.php` | `get_future_version_information()` (~44) | Replace remote HTML update lookup with verified Extended release metadata rendered locally |
| `sources/version.php` | `cms_version_minor()` | Do not change v10 version semantics |

**Important:** Code and call-site audits are still required before editing these files. The old remote addon code fetches an HTTP manifest and calls `@unserialize()`; do not feed remote serialized PHP to the new path.

## Proposed new files
- `sources_custom/extended_version.php`: independent distribution identity.
- `sources_custom/extended_addon_sources.php`: local source configuration and trusted keys.
- `sources_custom/extended_addon_catalogue.php`: verified catalogue fetch, merge, cache.
- `sources_custom/extended_addon_download.php`: validated remote package staging.
- `sources_custom/extended_release_client.php`: authenticated update notifications.

These names are provisional and need verification against Composr's `require_code()` loader and custom override conventions.
