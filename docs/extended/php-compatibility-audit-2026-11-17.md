# PHP 8.2–8.5 compatibility audit

Tracking issue https://github.com/jlverhagen/composrcms-v10-extended/issues/36.

Audit date 2026-11-17. Baseline release commit `ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14`.

The baseline contains 5,116 PHP files. This audit establishes a source inventory and a tracked backlog. Static API matches, syntax validation and focused runtime probes provide different levels of evidence. Full CMS workflow acceptance remains open.

## Syntax coverage

Each available binary checked every PHP file with `php -n -d error_reporting=-1 -d display_errors=1 -l PATH`. Disabling configuration avoids startup-extension noise. Linting parses files and does not execute application paths or load interface implementations.

| Runtime | Files checked | Passed | Diagnostic files | Result |
|---|---:|---:|---:|---|
| PHP 8.2.35 | 5,116 | 5,112 | 4 | Same intentional non-runtime files below |
| PHP 8.3.36 | 5,116 | 5,112 | 4 | Same intentional non-runtime files below |
| PHP 8.4.27 | 5,116 | 5,112 | 4 | Follow-up on 2026-11-18, same four non-runtime files |
| PHP 8.5.12 | 5,116 | 5,112 | 4 | Same intentional non-runtime files below |

The four diagnostic files are `sources_custom/phpstub.php:607`, a developer symbol stub declaring an exit function, and three Hack fixtures under `tracker/vendor/nikic/fast-route/test/HackTypechecker/fixtures/` named `all_options.php`, `empty_options.php`, and `no_options.php`. Preserve them as explicit lint exclusions when using PHP syntax results as a gate. No executable CMS syntax failure was found in this pass.

## PHP 8.4 follow-up on 2026-11-18

Installed the side-by-side Remi PHP 8.4.27 CLI runtime with mbstring, XML, GD, mysqlnd/PDO and intl support. This did not install a PHP-FPM service or change the website's selected PHP version. The [validation evidence](php84-validation-2026-11-18.json) records the loaded module list, raw diagnostics, probe results and staged fixture inventory hashes.

Before the 5,116-file syntax scan, every PHP path and Git blob hash was compared with the original audit baseline. The aggregate SHA256 matched `3bacdf708df5eefcaf82be153d132d0c281ed950271dbd744abe44753a3aeac9`. PHP 8.4.27 parsed 5,112 files successfully and reported exactly the same developer stub and three Hack fixtures as the other runtimes, with no additional diagnostic files.

Five retained focused suites passed with empty stderr: developer/test helpers (F14), ILess/Sabre interfaces and calendar behavior (F12), Latin-1 conversion (F06), Swift method dispatch/error behavior (F10), and the expanded date formatter (F03, 799,735 assertions). These fixtures exercise the merged fixes rather than the older syntax baseline. The evidence records hashes for each probe and its staged PHP inventory. This completes R01's baseline syntax and representative-probe requirement. It does not complete R02–R06, the unresolved F03/F04/F13 findings, full CMS/browser workflows or final combined package acceptance.

## Scan scope and limitations

All tracked `*.php` files in the exact checkout were inventoried, including 2,064 under sources, 1,605 under sources_custom, 852 under tracker, and 268 under _tests. The remainder includes Admin Zone, content/member modules, data scripts and root entry points.

A PHP token scan excluded comments and documentation when identifying selected deprecated global calls, constants, casts and interpolation forms. Source review distinguished explicit CSV escape arguments, supported API signatures and internal methods with similar names. Supplementary text searches covered deprecated settings, interface implementations, E_USER_ERROR, PDO constants and HTTP response-header access. The JSON companion records the grouped evidence, with paths and baseline line numbers.

The scan is a targeted audit, with explicit gaps below. It does not prove absence of dynamic properties, nullable values flowing into scalar parameters, floating-point truncation, string increments, null array offsets, invalid chr/ord input, output from output handlers, callable inheritance problems, or generated PHP/template issues. Such cases need type-aware review and representative runtime input. Calls using @ remain findings when their underlying API is deprecated.

## Confirmed findings and source groups

Source-confirmed means the deprecated API or incompatible signature is present at the recorded location. Its reachability depends on installed features and input. Runtime-confirmed F12 was reproduced by loading the four classes directly on both tested endpoints. Representative standalone probes also reproduced CSV, image cleanup, UTF-8, assertion and date-format deprecations, without changing site configuration or invoking CMS writes.

| ID | Priority | Finding | First deprecated |
|---|---|---|---|
| F01 | High | Bundled recommendation CSV import | 8.4 |
| F02 | High | Theme wizard and optional image cleanup | 8.5 |
| F03 | High | Locale formatting fallback and startup check | 8.1, affects 8.2–8.5 |
| F04 | Medium | FirePHP assertion registration | 8.3 |
| F05 | Medium | Optional CSV readers | 8.4 |
| F06 | Medium | Optional Latin-1 conversions | 8.2 |
| F07 | Medium | Optional cURL cleanup | 8.5 |
| F08 | Medium | getID3 image and XML cleanup | 8.5 |
| F09 | Medium | Program E XML cleanup | 8.5 |
| F10 | Medium | Swift Mailer fatal-error reporting | 8.4 |
| F11 | High | MySQL PDO driver constant | 8.5 |
| F12 | High | Library interface return types | 8.1, affects 8.2–8.5 |
| F13 | Scope review | Tracker and its supplied dependencies | 8.1–8.5 |
| F14 | Tooling | Developer and test tooling | 8.2–8.5 |

### F01 Bundled recommendation CSV import

Status open. Five CSV reads omit the escape argument. Preserve the existing backslash behavior and test quoted delimiters and multiline records.

- `fgetcsv` at [pages/modules/recommend.php, line 430](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/pages/modules/recommend.php#L430).
- `fgetcsv` at [pages/modules/recommend.php, line 434](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/pages/modules/recommend.php#L434).
- `fgetcsv` at [pages/modules/recommend.php, line 464](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/pages/modules/recommend.php#L464).
- `fgetcsv` at [pages/modules/recommend.php, line 516](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/pages/modules/recommend.php#L516).
- `fgetcsv` at [pages/modules/recommend.php, line 576](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/pages/modules/recommend.php#L576).

### F02 Theme wizard and optional image cleanup

Status open. Six imagedestroy calls remain across four files. Four calls belong to the bundled setup/theme wizard modules. Verify logo generation and saved theme images after removing the no-op calls.

- `imagedestroy` at [adminzone/pages/minimodules_custom/fix_partial_themewizard_css.php, line 166](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/adminzone/pages/minimodules_custom/fix_partial_themewizard_css.php#L166).
- `imagedestroy` at [adminzone/pages/modules/admin_setupwizard.php, line 918](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/adminzone/pages/modules/admin_setupwizard.php#L918).
- `imagedestroy` at [adminzone/pages/modules/admin_setupwizard.php, line 923](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/adminzone/pages/modules/admin_setupwizard.php#L923).
- `imagedestroy` at [adminzone/pages/modules/admin_themewizard.php, line 497](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/adminzone/pages/modules/admin_themewizard.php#L497).
- `imagedestroy` at [adminzone/pages/modules/admin_themewizard.php, line 511](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/adminzone/pages/modules/admin_themewizard.php#L511).
- `imagedestroy` at [data_custom/multi_domain_login.php, line 32](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/data_custom/multi_domain_login.php#L32).

### F03 Locale formatting fallback and startup check

Status partially resolved on 2026-11-18. The startup strftime('%M') probe has been removed: numeric minutes cannot validate locale text encoding. Locale selection and explicit language-pack locale_ctype_hack/locale_subst settings are preserved. One production strftime call remains in the compatibility fallback.

The Linux/nl_langinfo path now supports common numeric custom formats, full weekday names, ISO week dates, Sunday/Monday week numbers, timezones, newline/tab directives and literal percent escapes. Month/day names still come from LC_TIME, and locale_filter still runs. Escaped %%o, %%e and %%l are now treated as literals instead of being rewritten by the fallback's placeholder substitutions.

A committed regression test, `_tests/tests/unit_tests/temporal_format_compatibility.php`, compares 39 formats over 61 timestamps, seven installed locales and five timezones with the legacy Linux formatter. It also checks escaped custom directives and language-pack substitutions. Each of PHP 8.2.35, 8.3.36 and 8.5.12 passed 83,267 assertions with unsuppressed diagnostics promoted to exceptions. Three changed PHP files passed lint on each runtime. The reference strftime call is suppressed only inside the regression test; replacement calls run without suppression. Coverage includes pre-1970/post-2038 timestamps, leap years, ISO year boundaries, DST changes and fractional-hour offsets.

The follow-up composite migration supports %c, %x, %X and %r using D_T_FMT, D_FMT, T_FMT and T_FMT_AMPM from the selected LC_TIME locale. Internal %e/%l spacing is preserved, while top-level Composr directives retain their unpadded behavior. An empty T_FMT_AMPM uses glibc's conventional 12-hour default. Percent characters are not reinterpreted, recursion is bounded, and any unsupported nested directive returns the entire request to the unchanged platform fallback. Century (%C) and space-padded hour (%k) are also supported.

The expanded committed test passed 192,156 assertions per runtime on PHP 8.2.35, 8.3.36 and 8.5.12. It covers 45 formats across the same 61 timestamps, seven locales and five timezones, explicitly requiring the replacement helper to accept every tested format. The test also verifies unsupported modifiers and recursion limits return the fallback sentinel. Both changed PHP files passed lint on all three runtimes. This is focused formatter validation, not a full CMS workflow run.

A further migration supports numeric -, _ and 0 padding flags and field widths through 128 characters, preserving directive-specific minimum widths and the space padding used by %-3d. Era (E) and alternative-digit (O) forms use ordinary directives only when ERA/ALT_DIGITS are empty. Locales with nonempty metadata keep the platform fallback. Unsupported flag combinations, case flags, wider fields and negative numeric outputs also keep the fallback.

The expanded test covers 126 numeric modifier forms plus the original 45 formats. It adds six E forms and thirteen O forms where the locale metadata permits, and explicitly verifies fallback where locale-specific rules exist. Both changed PHP files passed lint, and the regression test passed 799,735 assertions per runtime on PHP 8.2.35, 8.3.36 and 8.5.12. Fixtures still cover seven locales, five timezones and 61 timestamps. Replacement calls run with diagnostics promoted to exceptions; only the deprecated reference is suppressed.

F03 remains open for locale-specific E/O rules, case/combined flags and remaining platform extensions, plus non-Linux behavior. Formats using unsupported nested locale directives retain the legacy fallback pending behavior-preserving migration. PHP 8.4 focused results are recorded below; full CMS startup/date-display workflows remain under R05. The locations below describe the original audit baseline, not the current source lines.

- `strftime` at [sources/global2.php, line 390](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources/global2.php#L390).
- `strftime` at [sources/temporal.php, line 309](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources/temporal.php#L309).

### F04 FirePHP assertion registration

Status partially resolved. The assertion callback now passes severity 0 explicitly to ErrorException, preserving its former null-to-zero conversion without a deprecated nullable argument. Eight focused assertions passed on PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12, covering converted logging, throw mode, raw logging, message/source locations and severity. The FirePHP file, committed regression test and ownership-inventory tool passed lint on all four runtimes.

Assertion registration still calls assert_options with ASSERT_CALLBACK. A whole-tree search found no callers of registerAssertionHandler outside its definition. The CMS administrator debug path loads FirePHP without registering it. The API remains available to custom callers, so it cannot be silently removed or replaced with a different global handler. A native AssertionError exception handler observes uncaught failures only, while the old callback can also observe failures caught by application code. F04 remains open for that behavior-changing API migration; the severity fix does not resolve registration.

- `assert_options` at [sources/firephp.php, line 767](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources/firephp.php#L767).

### F05 Optional CSV readers

Status open. Twenty reads in seven optional files omit the escape argument. Cover user import, location catalogue installation, sortable tables, nested CSV, Patreon/error service and free article import.

- `fgetcsv` at [data_custom/free_article_import.php, line 87](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/data_custom/free_article_import.php#L87).
- `fgetcsv` at [data_custom/free_article_import.php, line 88](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/data_custom/free_article_import.php#L88).
- `fgetcsv` at [sources_custom/blocks/main_sortable_table.php, line 166](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/blocks/main_sortable_table.php#L166).
- `fgetcsv` at [sources_custom/errorservice.php, line 43](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/errorservice.php#L43).
- `fgetcsv` at [sources_custom/errorservice.php, line 44](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/errorservice.php#L44).
- `fgetcsv` at [sources_custom/locations_catalogues_install.php, line 31](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/locations_catalogues_install.php#L31).
- `fgetcsv` at [sources_custom/locations_catalogues_install.php, line 33](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/locations_catalogues_install.php#L33).
- `fgetcsv` at [sources_custom/locations_catalogues_install.php, line 59](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/locations_catalogues_install.php#L59).
- `fgetcsv` at [sources_custom/locations_catalogues_install.php, line 61](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/locations_catalogues_install.php#L61).
- `fgetcsv` at [sources_custom/locations_catalogues_install.php, line 74](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/locations_catalogues_install.php#L74).
- `fgetcsv` at [sources_custom/locations_catalogues_install.php, line 76](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/locations_catalogues_install.php#L76).
- `fgetcsv` at [sources_custom/locations_catalogues_install.php, line 257](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/locations_catalogues_install.php#L257).
- `fgetcsv` at [sources_custom/locations_catalogues_install.php, line 259](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/locations_catalogues_install.php#L259).
- `fgetcsv` at [sources_custom/nested_csv.php, line 60](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/nested_csv.php#L60).
- `fgetcsv` at [sources_custom/nested_csv.php, line 72](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/nested_csv.php#L72).
- `fgetcsv` at [sources_custom/nested_csv.php, line 87](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/nested_csv.php#L87).
- `fgetcsv` at [sources_custom/patreons.php, line 21](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/patreons.php#L21).
- `fgetcsv` at [sources_custom/patreons.php, line 22](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/patreons.php#L22).
- `fgetcsv` at [sources_custom/user_import.php, line 65](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/user_import.php#L65).
- `fgetcsv` at [sources_custom/user_import.php, line 82](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/user_import.php#L82).

### F06 Optional Latin-1 conversions

Status open. Nine utf8_encode/utf8_decode calls remain in seven files including getID3, Sabre/VObject, Photobucket, Twitter and the Spout XLS writer. Some calls suppress diagnostics with @. Match Latin-1 conversion and invalid/unrepresentable-character behavior before replacing them.

- `utf8_encode` at [sources_custom/getid3/getid3.lib.php, line 860](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/getid3/getid3.lib.php#L860).
- `utf8_decode` at [sources_custom/getid3/getid3.lib.php, line 932](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/getid3/getid3.lib.php#L932).
- `utf8_encode` at [sources_custom/photobucket/OAuth/Utils.php, line 76](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/photobucket/OAuth/Utils.php#L76).
- `utf8_decode` at [sources_custom/photobucket/OAuth/Utils.php, line 97](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/photobucket/OAuth/Utils.php#L97).
- `utf8_encode` at [sources_custom/sabredav/lib/Sabre/DAV/StringUtil.php, line 84](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/sabredav/lib/Sabre/DAV/StringUtil.php#L84).
- `utf8_encode` at [sources_custom/sabredav/lib/Sabre/DAV/URLUtil.php, line 84](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/sabredav/lib/Sabre/DAV/URLUtil.php#L84).
- `utf8_encode` at [sources_custom/sabredav/vendor/sabre/vobject/lib/Sabre/VObject/StringUtil.php, line 50](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/sabredav/vendor/sabre/vobject/lib/Sabre/VObject/StringUtil.php#L50).
- `utf8_decode` at [sources_custom/spout/src/Spout/Writer/XLS.php, line 124](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/spout/src/Spout/Writer/XLS.php#L124).
- `utf8_encode` at [sources_custom/twitter.php, line 1017](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/twitter.php#L1017).

### F07 Optional cURL cleanup

Status open. Seven curl_close calls remain in five optional clients. Preserve request success and failure behavior while dropping the no-op API.

- `curl_close` at [sources_custom/Cloudinary/Uploader.php, line 291](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/Cloudinary/Uploader.php#L291).
- `curl_close` at [sources_custom/facebook/facebook.php, line 1034](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/facebook/facebook.php#L1034).
- `curl_close` at [sources_custom/facebook/facebook.php, line 1037](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/facebook/facebook.php#L1037).
- `curl_close` at [sources_custom/photobucket/PBAPI/Request/curl.php, line 67](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/photobucket/PBAPI/Request/curl.php#L67).
- `curl_close` at [sources_custom/twitter.php, line 103](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/twitter.php#L103).
- `curl_close` at [sources_custom/vimeo.php, line 243](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/vimeo.php#L243).
- `curl_close` at [sources_custom/vimeo.php, line 501](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/vimeo.php#L501).

### F08 getID3 image and XML cleanup

Status open. Two image cleanup and four XML cleanup calls remain. Exercise BMP/XMP parsing with valid and malformed fixtures.

- `imagedestroy` at [sources_custom/getid3/module.graphic.bmp.php, line 664](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/getid3/module.graphic.bmp.php#L664).
- `imagedestroy` at [sources_custom/getid3/module.graphic.bmp.php, line 669](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/getid3/module.graphic.bmp.php#L669).
- `xml_parser_free` at [sources_custom/getid3/module.tag.xmp.php, line 250](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/getid3/module.tag.xmp.php#L250).
- `xml_parser_free` at [sources_custom/getid3/module.tag.xmp.php, line 260](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/getid3/module.tag.xmp.php#L260).
- `xml_parser_free` at [sources_custom/getid3/module.tag.xmp.php, line 268](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/getid3/module.tag.xmp.php#L268).
- `xml_parser_free` at [sources_custom/getid3/module.tag.xmp.php, line 273](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/getid3/module.tag.xmp.php#L273).

### F09 Program E XML cleanup

Status open. Five XML parser cleanup calls remain in two Program E files. Verify successful and failed parser lifecycles.

- `xml_parser_free` at [sources_custom/programe/botloaderfuncs.php, line 964](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/programe/botloaderfuncs.php#L964).
- `xml_parser_free` at [sources_custom/programe/botloaderfuncs.php, line 1045](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/programe/botloaderfuncs.php#L1045).
- `xml_parser_free` at [sources_custom/programe/botloaderfuncs.php, line 1117](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/programe/botloaderfuncs.php#L1117).
- `xml_parser_free` at [sources_custom/programe/botloaderfuncs.php, line 1164](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/programe/botloaderfuncs.php#L1164).
- `xml_parser_free` at [sources_custom/programe/util.php, line 788](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/programe/util.php#L788).

### F10 Swift Mailer fatal-error reporting

Status open. The undefined-method path calls trigger_error with E_USER_ERROR. Preserve failure propagation and caller expectations when replacing it.

- `trigger_error(..., E_USER_ERROR)` at [sources_custom/swift_mailer/lib/classes/Swift/Transport/EsmtpTransport.php, line 289](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/swift_mailer/lib/classes/Swift/Transport/EsmtpTransport.php#L289).

### F11 MySQL PDO driver constant

Status open. The bundled alternate PDO driver evaluates PDO::MYSQL_ATTR_INIT_COMMAND. Select the version-appropriate constant while preserving connection initialization on PHP 8.2–8.5. Installer defined(...) checks are string queries and were not counted as constant evaluations.

- `PDO::MYSQL_ATTR_INIT_COMMAND` at [sources/database/mysql_pdo.php, line 78](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources/database/mysql_pdo.php#L78).

### F12 Library interface return types

Status open. Class-load probes on 8.2.35 and 8.5.12 produced 22 return-type diagnostics per runtime across ILess_Output and three Sabre VObject classes. Confirm subclass signatures and return values before adding compatible types.

- `ILess_Output::count` at [sources_custom/ILess/Output.php, line 58](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/ILess/Output.php#L58).
- `Sabre\VObject\Node` at [sources_custom/sabredav/vendor/sabre/vobject/lib/Sabre/VObject/Node.php, line 1](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/sabredav/vendor/sabre/vobject/lib/Sabre/VObject/Node.php#L1).
- `Sabre\VObject\ElementList` at [sources_custom/sabredav/vendor/sabre/vobject/lib/Sabre/VObject/ElementList.php, line 1](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/sabredav/vendor/sabre/vobject/lib/Sabre/VObject/ElementList.php#L1).
- `Sabre\VObject\RecurrenceIterator` at [sources_custom/sabredav/vendor/sabre/vobject/lib/Sabre/VObject/RecurrenceIterator.php, line 1](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/sabredav/vendor/sabre/vobject/lib/Sabre/VObject/RecurrenceIterator.php#L1).

### F13 Tracker and its supplied dependencies

Packaging scope classified on 2026-11-18; compatibility work remains open. All 852 tracker PHP files are owned by optional addon composr_homesite_support_credits (MantisBT 2.18.0). None appear in the standard installer data/files.bin inventory. Neither published hub addon release checked (addons-2026.11.1-alpha.1 and alpha.2) contains its TAR asset. These facts do not remove the tracker from the repository/source archives or establish its compatibility. The original support-credits addon is a separate optional maintenance scope, not an installed-core dependency. See the ownership evidence below.

Status open. 57 token matches in 19 files include MantisGraph date formatting, E_STRICT, SOAP_FUNCTIONS_ALL, ADOdb CSV/XML/session/LDAP paths, Guzzle cleanup and reflection access. Additional source review found FILTER_SANITIZE_STRING in tracker/config_defaults_inc.php:219, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY in tracker/vendor/adodb/adodb-php/drivers/adodb-pdo_mysql.inc.php:34, and E_USER_ERROR calls in tracker error paths. Establish which tracker components are shipped and maintained, then validate and fix the reachable set. These matches are not a claim that every tracker path was exercised.

- `SOAP_FUNCTIONS_ALL` at [tracker/api/soap/mantisconnect.php, line 88](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/api/soap/mantisconnect.php#L88).
- `E_STRICT` at [tracker/core/error_api.php, line 196](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/core/error_api.php#L196).
- `curl_close` at [tracker/core/url_api.php, line 69](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/core/url_api.php#L69).
- `strftime` at [tracker/plugins/MantisGraph/core/Period.php, line 68](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/plugins/MantisGraph/core/Period.php#L68).
- `strftime` at [tracker/plugins/MantisGraph/core/Period.php, line 69](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/plugins/MantisGraph/core/Period.php#L69).
- `strftime` at [tracker/plugins/MantisGraph/core/Period.php, line 108](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/plugins/MantisGraph/core/Period.php#L108).
- `strftime` at [tracker/plugins/MantisGraph/core/Period.php, line 109](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/plugins/MantisGraph/core/Period.php#L109).
- `strftime` at [tracker/plugins/MantisGraph/core/Period.php, line 138](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/plugins/MantisGraph/core/Period.php#L138).
- `strftime` at [tracker/plugins/MantisGraph/core/Period.php, line 150](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/plugins/MantisGraph/core/Period.php#L150).
- `strftime` at [tracker/plugins/MantisGraph/core/Period.php, line 151](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/plugins/MantisGraph/core/Period.php#L151).
- `strftime` at [tracker/plugins/MantisGraph/core/Period.php, line 181](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/plugins/MantisGraph/core/Period.php#L181).
- `strftime` at [tracker/plugins/MantisGraph/core/Period.php, line 192](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/plugins/MantisGraph/core/Period.php#L192).
- `strftime` at [tracker/plugins/MantisGraph/core/Period.php, line 193](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/plugins/MantisGraph/core/Period.php#L193).
- `strftime` at [tracker/plugins/MantisGraph/core/Period.php, line 213](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/plugins/MantisGraph/core/Period.php#L213).
- `strftime` at [tracker/plugins/MantisGraph/core/Period.php, line 249](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/plugins/MantisGraph/core/Period.php#L249).
- `strftime` at [tracker/plugins/MantisGraph/core/Period.php, line 258](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/plugins/MantisGraph/core/Period.php#L258).
- `fgetcsv` at [tracker/vendor/adodb/adodb-php/adodb-csvlib.inc.php, line 109](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-csvlib.inc.php#L109).
- `fgetcsv` at [tracker/vendor/adodb/adodb-php/adodb-csvlib.inc.php, line 213](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-csvlib.inc.php#L213).
- `strftime` at [tracker/vendor/adodb/adodb-php/adodb-time.inc.php, line 439](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-time.inc.php#L439).
- `gmstrftime` at [tracker/vendor/adodb/adodb-php/adodb-time.inc.php, line 1367](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-time.inc.php#L1367).
- `strftime` at [tracker/vendor/adodb/adodb-php/adodb-time.inc.php, line 1367](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-time.inc.php#L1367).
- `gmstrftime` at [tracker/vendor/adodb/adodb-php/adodb-time.inc.php, line 1379](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-time.inc.php#L1379).
- `gmstrftime` at [tracker/vendor/adodb/adodb-php/adodb-time.inc.php, line 1381](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-time.inc.php#L1381).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php, line 268](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php#L268).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php, line 274](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php#L274).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php, line 350](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php#L350).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php, line 708](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php#L708).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php, line 845](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php#L845).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php, line 1067](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php#L1067).
- `xml_parser_free` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php, line 1473](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php#L1473).
- `xml_parser_free` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php, line 1516](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php#L1516).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php, line 1638](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php#L1638).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php, line 1656](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php#L1656).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php, line 1661](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema.inc.php#L1661).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php, line 293](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php#L293).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php, line 299](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php#L299).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php, line 382](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php#L382).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php, line 749](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php#L749).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php, line 887](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php#L887).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php, line 1166](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php#L1166).
- `xml_parser_free` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php, line 1629](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php#L1629).
- `xml_parser_free` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php, line 1673](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php#L1673).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php, line 1795](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php#L1795).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php, line 1814](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php#L1814).
- `xml_set_object` at [tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php, line 1820](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php#L1820).
- `ldap_connect` at [tracker/vendor/adodb/adodb-php/drivers/adodb-ldap.inc.php, line 69](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/drivers/adodb-ldap.inc.php#L69).
- `session_set_save_handler` at [tracker/vendor/adodb/adodb-php/session/adodb-session.php, line 446](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/session/adodb-session.php#L446).
- `session_set_save_handler` at [tracker/vendor/adodb/adodb-php/session/adodb-session2.php, line 468](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/session/adodb-session2.php#L468).
- `session_set_save_handler` at [tracker/vendor/adodb/adodb-php/session/old/adodb-cryptsession.php, line 306](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/session/old/adodb-cryptsession.php#L306).
- `session_set_save_handler` at [tracker/vendor/adodb/adodb-php/session/old/adodb-session-clob.php, line 430](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/session/old/adodb-session-clob.php#L430).
- `session_set_save_handler` at [tracker/vendor/adodb/adodb-php/session/old/adodb-session.php, line 421](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/adodb/adodb-php/session/old/adodb-session.php#L421).
- `imagedestroy` at [tracker/vendor/dapphp/securimage/securimage.php, line 2156](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/dapphp/securimage/securimage.php#L2156).
- `curl_close` at [tracker/vendor/guzzlehttp/guzzle/src/Handler/CurlFactory.php, line 67](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/guzzlehttp/guzzle/src/Handler/CurlFactory.php#L67).
- `curl_close` at [tracker/vendor/guzzlehttp/guzzle/src/Handler/CurlMultiHandler.php, line 158](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/guzzlehttp/guzzle/src/Handler/CurlMultiHandler.php#L158).
- `setAccessible` at [tracker/vendor/sebastian/global-state/src/Restorer.php, line 82](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/sebastian/global-state/src/Restorer.php#L82).
- `setAccessible` at [tracker/vendor/sebastian/global-state/src/Restorer.php, line 106](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/sebastian/global-state/src/Restorer.php#L106).
- `setAccessible` at [tracker/vendor/sebastian/global-state/src/Snapshot.php, line 351](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/sebastian/global-state/src/Snapshot.php#L351).

### F14 Developer and test tooling

Status open. The token list records 12 matches in six test/helper files. Additional source review found two HTML-ENTITIES mb_convert_encoding calls in _tests/libs/mf_parse.php:96,115. Keep test-framework compatibility separate from CMS runtime acceptance, and fix tooling needed to run the regression suite.

- `curl_close` at [_tests/libs/mf_parse.php, line 77](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/_tests/libs/mf_parse.php#L77).
- `socket_set_timeout` at [_tests/simpletest/compatibility.php, line 167](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/_tests/simpletest/compatibility.php#L167).
- `E_STRICT` at [_tests/simpletest/errors.php, line 239](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/_tests/simpletest/errors.php#L239).
- `xml_set_object` at [_tests/simpletest/xml.php, line 520](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/_tests/simpletest/xml.php#L520).
- `setAccessible` at [sources_custom/spout/tests/Spout/ReflectionHelper.php, line 38](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/spout/tests/Spout/ReflectionHelper.php#L38).
- `setAccessible` at [sources_custom/spout/tests/Spout/ReflectionHelper.php, line 42](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/spout/tests/Spout/ReflectionHelper.php#L42).
- `setAccessible` at [sources_custom/spout/tests/Spout/ReflectionHelper.php, line 61](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/spout/tests/Spout/ReflectionHelper.php#L61).
- `setAccessible` at [sources_custom/spout/tests/Spout/ReflectionHelper.php, line 71](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/spout/tests/Spout/ReflectionHelper.php#L71).
- `setAccessible` at [sources_custom/spout/tests/Spout/ReflectionHelper.php, line 84](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/spout/tests/Spout/ReflectionHelper.php#L84).
- `setAccessible` at [sources_custom/spout/tests/Spout/ReflectionHelper.php, line 88](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources_custom/spout/tests/Spout/ReflectionHelper.php#L88).
- `setAccessible` at [tracker/vendor/pimple/pimple/src/Pimple/Tests/PimpleTest.php, line 267](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/pimple/pimple/src/Pimple/Tests/PimpleTest.php#L267).
- `setAccessible` at [tracker/vendor/pimple/pimple/src/Pimple/Tests/PimpleTest.php, line 271](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/tracker/vendor/pimple/pimple/src/Pimple/Tests/PimpleTest.php#L271).

## Completed work retained in the baseline

The whole-tree lint pass found no compile-time diagnostics for implicit nullable declarations, deprecated cast aliases, deprecated interpolation or case-semicolon syntax in the executable files. Runtime-sensitive issues remain tracked separately.

The recent spreadsheet batch is merged in core PRs #31–#35. It covers explicit CSV escaping, blank XLS/HTML cells, the XLSX shared-string stream declaration, and multiline XLSX cache records. Focused PHP 8.2.35/8.5.12 export/import validation and addon TAR publication were completed earlier. Excel Support and nuSearch are available in hub release `addons-2026.11.1-alpha.2`. F06 records the still-deprecated suppressed XLS conversion. Completing the earlier batch does not close that remaining conversion path.

Previously updated areas include main CSV import paths, Linux date formatting for common formats, core image cleanup, HTTP header retrieval, database reconnect behavior and diagnostic conversion/reflection paths. Their remaining or alternate paths appear in F01–F14 where found. Preserve the tested behavior when extending those fixes.

## Addon ownership follow-up on 2026-11-18

The CLI-only tool `_tests/libs/addon_scope_inventory.php` evaluates get_file_list on a trusted checkout and maps every finding path to its registry owner and standard installer membership. It loaded all 119 bundled and 118 optional hooks without errors, producing identical reports on PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12. The [recorded JSON](php-addon-scope-2026-11-18.json) identifies its source revision and installer inventory hash. Registry hooks and data/files.bin are unchanged between the original syntax baseline and that revision.

| Findings | Shipping ownership |
|---|---|
| F01 | Bundled recommend |
| F02 | Bundled setupwizard/themewizard; optional theme_debug/multi_domain_login |
| F03, F04 | Bundled core |
| F05 | Optional CSV/content/homesite addons, exact owners in JSON |
| F06–F10, F12 | Optional libraries and their owning addons, exact owners in JSON |
| F11 | Bundled core_database_drivers |
| F13 | Optional composr_homesite_support_credits, all 852 tracker PHP files |
| F14 | Optional testing_platform, excel_support and support-credits test dependencies |

All audited source paths have registry owners. The standard installer inventory contains 6,245 entries and no tracker PHP files. The tracker addon declares 1,602 payload entries overall. Publication checks cover the two explicitly named hub addon releases, not every source distribution or future release. R04 remains open for final supported-addon/publication policy and package acceptance; ownership alone does not grant maintenance approval.

The three new compatibility/inventory files are added to the testing_platform registry hook, including the temporal regression test introduced earlier. They remain optional tooling files and can be included in a future testing_platform package. The recorded ownership snapshot precedes those three additions; its 449-entry testing_platform count describes the pinned source revision.

## Inheritance and runtime-boundary follow-up on 2026-11-18

A lexer-assisted shortlist examined literal $this property writes in single-class files outside tracker and tests, followed by manual source/inheritance review. The [recorded evidence](php-inheritance-review-2026-11-18.json) contains six candidate dispositions, source hashes, negative-control diagnostics and fixed results. This shortlist is not a complete class graph or dataflow audit. Multi-class/anonymous-class files, traits, ambiguous or external parent resolution and computed property names still need review.

| Candidate | Disposition |
|---|---|
| Photobucket PBAPI::$response_string | Confirmed dynamic property; declared public with null default to preserve existing access/state |
| Sabre AbstractPrincipalCollection::$principalPrefix | Confirmed dynamic property; declared public on the parent class to preserve existing access |
| MySQL base cache_db/last_select_db writes | All three concrete MySQL drivers declare these properties; verified reflection and inherited cache resets without opening connections |
| ILess_Exception::$previous write | Guarded by PHP_VERSION_ID < 50300, so unreachable on PHP 8.2–8.5; retained |
| PBAPI_Exception code/message writes | Inherited protected Exception properties; verified by reflection and retained |
| Sabre lock exception message write | Inherited protected Exception property; verified by reflection and retained |

Runtime probes reproduced both dynamic-property deprecations and ILess's default null exception-message deprecation on PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12. The corresponding three source files are unchanged between the original syntax baseline and the pre-fix revision. ILess now supplies an empty string for its default/null message before formatting; explicit messages, exception codes and previous-exception links are preserved. Photobucket's two trim calls likewise supply empty text for an absent response while retaining its public null response state, raw bytes, parsed trimming, token fields and existing missing-token exception.

All five changed PHP files passed lint on each runtime. The committed `_tests/tests/unit_tests/optional_property_compatibility.php` test passed 36 assertions per runtime with unsuppressed diagnostics promoted to exceptions and empty stderr. Checks include property declaration/visibility, inherited exception state, three MySQL cache resets, WebDAV default/custom prefixes, listing/lookup/search and disabled-listing behavior, Photobucket raw/parsed/token behavior, and ILess default/explicit/chained exceptions. External database, HTTP and API connections were not opened. The test is registered to testing_platform.

These fixes belong to optional image_syndication, webdav and less addons and await combined addon publication. R02 and R03 remain open for the broader inheritance, class-load and runtime-boundary review. Full CMS integration and final package acceptance remain under R05/R06.

## Multi-class, trait and computed-write follow-up on 2026-11-18

The next lexer-assisted pass separated brace-delimited class bodies in 24 multi-class files outside the top-level tracker and _tests directories. Manual review checked candidate writes against parent declarations. Standard CRUD module state is supplied by Standard_crud_module, content API state by CMS_API_object, diff operation state by Text_Diff_Op/Text_Diff, and Facebook state by BaseFacebook. Those inherited declarations were retained. This is bounded source review, not a complete inheritance or dataflow proof.

| Confirmed area | Change and behavior preserved |
|---|---|
| Bundled and Facebook-copy Services_JSON | Public use flag declared in both copies; constructor flags and direct flag changes preserved |
| Bundled three-way diff operations and block builder | Seven public state fields declared; cached merge references, copy aliases, builder resets and conflict output preserved |
| ILess_FileInfo and ILess_Visitor_Arguments | These constructors deliberately accept arbitrary public plugin fields; targeted AllowDynamicProperties attributes preserve that existing record contract, including null fields, references, cloning and serialization |
| ILess_Node_Combinator | Default/null value normalized to empty text before trim; descendant-space and explicit combinators retain their output |

The ILess attributes apply only to the two intentionally open records, not the library or site generally. Guarded computed writes in ILess_Environment, getID3, Sabre DAV Client, LightOpenID and Text_Diff_Renderer were retained and tested. The one trait identified under sources/sources_custom is the Spout resource-test helper; its private properties and a path-only consumer were verified without filesystem writes.

The registered CLI-only `_tests/libs/property_graph_probe.php` has six isolated modes: bundled JSON, Facebook JSON, three-way diff, LESS, computed-write guards and trait state. It passed 54 checks per runtime on PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12 with no diagnostics or stderr. All eight changed PHP files passed lint on each runtime. Functional observations matched the old baseline and matched across all four runtimes, including JSON bytes, conflict markers, reference updates, plugin metadata and actual compiled CSS. The two JSON copies run in separate processes because they declare the same class name.

The [machine-readable evidence](php-property-graph-review-2026-11-18.json) includes source hashes, dependency overlays, per-mode checks, repeated baseline diagnostics summarized by message, normalized observations and their hashes. The negative checkout also emits the previously fixed ILess_Output return-type diagnostic; that diagnostic is not attributed to this batch. The fixed checkout overlays the current reviewed dependencies before testing. The default combinator warning was discovered by exercising the real parser and is now fixed alongside the property work.

JSON/three-way diff fixes belong to bundled core. Facebook's JSON copy belongs to facebook_support, and ILess fixes belong to less. The new probe is owned by testing_platform. Core fixes await the combined core release; optional fixes await combined addon publication. R02 remains open for broader class-loading, anonymous/external-object/computed-write paths and runtime reachability. R03 retains other boundary categories. Complete CMS and upgrade/package validation remain under R05/R06.

## Library declaration loading follow-up on 2026-11-18

The registered CLI-only `_tests/libs/library_class_load_probe.php` uses PHP tokens to discover named classes/interfaces/traits in explicitly selected production-library directories, builds an isolated autoload map, and loads each declaration with strict diagnostics. Tests/examples/demos are excluded. Duplicate symbols in different files and bracketed namespaces require explicit review rather than being silently skipped. Anonymous declarations, object construction and library workflows are outside this probe.

| Library group | PHP files inventoried | Named declarations loaded per runtime |
|---|---:|---:|
| ILess | 86 | 86 |
| Sabre DAV and VObject | 139 | 137 |
| Swift Mailer | 161 | 161 |
| Spout production source | 43 | 43 |
| getID3 | 81 | 86 |
| Photobucket/OAuth | 32 | 31 |
| Facebook SDK | 1 | 3 |
| Total | 543 | 547 |

All 547 declarations loaded on PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12 with no diagnostics, missing declarations, load failures or stderr. Inventories and reflected declaration lists matched across runtimes. Facebook's source explicitly requires Composr's json loader; the probe supplies a narrowly scoped adapter that loads the actual sources/json.php and records its hash. The initial missing-adapter failure was a harness limitation, not an SDK defect.

Loading Photobucket's SimpleXML strategy exposed a real fatal signature conflict on every runtime: the child accepted SimpleXMLElement while its parent accepted array. The override now accepts array|SimpleXMLElement, delegating arrays to the parent and preserving the existing XML branch. This satisfies the parent's input contract without removing type validation. Unsupported objects still throw TypeError.

The registered `_tests/libs/photobucket_response_probe.php` passed 16 checks on each runtime: full/content-only XML, attributes, XML error message/code, parent-array success/error handling, invalid-type rejection, and unchanged JSON/PHP-serialized response strategies. All four changed PHP files passed lint on all four runtimes. The [evidence](php-library-load-review-2026-11-18.json) records pre-fix fatal diagnostics, exact source hashes, dependency overlays, inventory/declaration hashes, load counts and parser results.

This completes the selected seven-library declaration-loading phase. It does not establish every constructor, method, provider integration or CMS workflow as compatible, and it does not close all of R02/R03. The production fix belongs to image_syndication and awaits combined addon publication. Both probes belong to testing_platform. The test site was not deployed or changed.

## Byte, numeric and callback boundaries follow-up on 2026-11-18

Five confirmed sites were made explicit without changing their observed legacy results:

| Site | Change and compatibility boundary |
|---|---|
| unichr | Mask the low byte before chr, preserving negative-integer wrap and the existing non-ASCII result format |
| cms_mb_substr fallback | Return an empty substring before ord; zero-length/past-end results remain empty |
| foxy_utf8_to_nce | Supply a zero byte when a truncated sequence lacks a byte, preserving the legacy zero-bit contribution without an out-of-range read; no new Unicode-validation or replacement policy |
| WordPress PasswordHash::gensalt_blowfish | Use integer division for the tens digit, preserving all tested cost/salt bytes; password algorithms, formats and costs are unchanged |
| ILess_DebugInfo callback | Replace the context-dependent self callback with __CLASS__, retaining declaring-class binding and filename/debug formatting |

The registered CLI-only `_tests/libs/input_boundary_probe.php` compares the real baseline files against the fixes. All six changed PHP files passed lint on PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12. Each runtime passed 2,401 explicit checks across extension-free characters, normal-extension characters, password fixtures and callbacks, with no fixed-path diagnostics or stderr. Every observation matched its own runtime's baseline.

Each character configuration covers 67,851 font-text fixtures, including every one-/two-byte combination, 2,048 seeded longer inputs and named valid/truncated/invalid samples; 16,506 substring cases; integer codepoints including negatives and integer extremes; and Latin-1 conversion behavior. Binary observations are compared through length-prefixed byte-stream hashes. The normal configuration uses available extensions; php -n explicitly verifies that neither mbstring nor iconv is present for the fallback run. Existing utf8tohtml replacement behavior is tested separately from the legacy font converter.

Password fixtures cover all integer bcrypt costs 4–31, deterministic salts and hashes, correct/wrong portable and bcrypt password verification. They use public test data and no accounts, sessions, imports or login requests. Callback checks cover filenames, comments, media queries, custom formats and a subclass override that must not change declaring-class binding. The bounded literal callback search excluded GeSHI language keyword lists; the remaining self callback was in ILess_DebugInfo. zones.php's parent:: check examines generated source rather than registering a callback. Computed/generated callback paths remain outside that search.

The [evidence](php-input-boundary-review-2026-11-18.json) records pre-fix diagnostics, source hashes, fixture digests and per-version/configuration results. It also records a pre-existing variation: the extension-backed substring fixture digest differs between PHP 8.2 and PHP 8.3–8.5, both before and after these changes. The exact differing calls/cause were not isolated here and remain a tracked R03 follow-up. This batch preserves each version's baseline and does not claim identical substring results across PHP versions. Password, callback and extension-free observations match across versions.

The character helpers belong to bundled core; WordPress compatibility belongs to bundled import; the debug callback belongs to optional less; and the probe belongs to testing_platform. Fixes await combined core/addon publication. R03 remains open for the native substring variation and other numeric, key, string-increment, dynamic-callback and output-handler boundaries. Full CMS and package/upgrade acceptance remain under R05/R06.

## Remaining review and runtime checklist

- [x] R01 Full baseline syntax scan and representative probes completed on PHP 8.4.27; extension configuration and fixture hashes recorded in the follow-up evidence.
- [ ] R02 Review class property assignments across inheritance and load maintained optional libraries to catch dynamic property warnings and additional interface signature issues.
- [ ] R03 Exercise null/scalar boundaries, invalid text/byte input, numeric truncation, null keys, string increments, relative callables and output handlers with meaningful fixtures. A clean syntax scan cannot close this item.
- [ ] R04 Record the supported optional addon and tracker scope from registry hooks and published packages, linking every open source group to that scope. Exclude aps and mobiquo as already decided.
- [ ] R05 Run CMS workflow regression on PHP 8.2, 8.3, 8.4 and 8.5. Include installer, login/logout, permissions, Admin Zone, content create/edit, uploads/GD, search/indexing, cron/tasks, mail handling, addon review/install/uninstall, themes/cache clearing, integrity scan and upgrade preservation of installed addons.
- [ ] R06 Validate the final combined packages and installed file inventories, then record the commit, package hashes, PHP versions and observed diagnostics. Stage optional fixes as addon updates where ownership requires it.

F01, F02, F03, F04 and F11 form the first bundled-code batch. F05–F10 and F12 form the optional/library batch. F13–F14 need an explicit shipping/tooling scope before they contribute to the core acceptance result. R01–R06 define the remaining compatibility verification. Check off a finding only after the fix is merged and its relevant regression check is recorded.

## Numeric and null-key follow-up on 2026-11-19

The registered CLI probe `_tests/libs/numeric_boundary_probe.php` passed 992 checks on each of PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12. All five changed PHP files passed lint on all four. Fixed-path diagnostics and stderr were empty; observations matched the legacy baseline and one another across runtimes. Exact source hashes and diagnostic negative controls are in `php-numeric-boundary-review-2026-11-19.json`.

- LESS native-number modulus now explicitly retains integer truncation and out-of-range float wraparound. The function registry retains the distinct numeric-string saturation behavior. Zero-divisor exception classes/messages, units and the helper's historical null zero-remainder result remain unchanged.
- LESS zero-base negative powers retain signed infinity using a reciprocal of the positive power. NAN retains the historical text without PHP 8.5's conversion warning. Ordinary power results and the bcmath path are retained.
- Self-learning cache null entry keys are explicitly normalized to the historical empty-string key. In-memory deduplication, null stored values and paused mutation behavior are covered with persistence disabled.

Fixtures cover 323 power combinations, 264 seeded/boundary float conversions, 196 native modulus pairs, 196 registry modulus pairs and additional explicit assertions. The float-wrap fixtures ran on the 64-bit VPS; no 32-bit runtime was available. The tests intentionally disable extensions for the native fallback and do not establish complete bcmath, LESS compiler, theme or CMS workflow compatibility.

A bounded direct-call review of sources and sources_custom found 25 output-buffer text candidates excluding phpstub.php. Removing two diagnostic strings and three commented-out calls leaves 20 active ob_start calls, all with zero arguments, and no ob_implicit_flush calls. These sites need no flags/argument conversion. Computed calls, tracker and full response-buffer workflows remain outside this check.

The prior substring variation is also isolated: all 16,506 fixtures were compared individually; 2,730 differing cases involve 129 malformed UTF-8 inputs, with zero valid UTF-8 fixture differences. Direct mb_substr calls reproduce the difference despite identical substitution settings. Preserve native malformed-input behavior; issue #36 records the exact reproduction. This does not change the existing Unicode-validation policy.

R03 remains open for other input/key/numeric/string-increment and computed callback paths. R05/R06 remain open for full CMS workflows and combined publication/package acceptance. No site deployment or new prerelease was performed.

## Computed callback follow-up on 2026-11-19

The registered CLI probe `_tests/libs/callback_boundary_probe.php` passed 24 checks per runtime on PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12, with strict diagnostics and empty stderr. All three changed PHP files passed lint on all four. Evidence and source/dependency scope are recorded in `php-callback-boundary-review-2026-11-19.json`.

LESS function dispatch retained identical results for named functions, static class methods, object methods, invokable objects and closures, including alias case folding, registry injection and a built-in method. Invalid callback rejection, importer context/return propagation and WebDAV mapped static property callbacks passed without source changes at those sites.

The missing-function alias path exposed a genuine formatting bug: three placeholders received two values. On every supported runtime the baseline throws ArgumentCountError instead of the intended InvalidArgumentException. Supplying the missing function name restores the declared exception and complete diagnostic message. This deliberately changes the broken failure path; valid registrations and callback results remain unchanged.

This bounded review does not close R03 or establish full generated-template, plugin, importer, WebDAV, theme or CMS workflow compatibility. The fix belongs to optional less and awaits combined addon publication. No prerelease or site deployment was performed.

## Workflow acceptance ledger prepared on 2026-11-19

`php-workflow-acceptance.md` defines 16 end-to-end cases and the candidate/environment/backup evidence needed to execute them. `php-workflow-acceptance.json` tracks all 64 PHP-version/workflow cells as not_run; none are inferred passed from syntax or focused CLI probes. Existing test helper paths were checked, and their important scope/side effects are documented. No legacy suite or live-site workflow was executed while preparing the ledger.

The selected calendar, GeSHi, Spout column-length and Comcode code-nesting increment/decrement candidates operate on initialized integer counters. These inspected sites require no production change; the ledger records the exact bounded scope without claiming an exhaustive increment audit. R02/R03 and F03/F04/F13 remain open alongside workflow/package acceptance.

## Acceptance criteria

Close the compatibility work when all maintained-scope confirmed findings are resolved, remaining source candidates have documented dispositions, the four-version matrix has passed with deprecations captured, and the final packages contain the reviewed code. Suppression of diagnostics is insufficient evidence of API compatibility. PHP 8.6 requires a separate audit against its final behavior.

## References

- PHP 8.1 deprecations affecting the minimum supported runtime, including locale formatting and internal interface compatibility. https://www.php.net/manual/en/migration81.deprecated.php
- PHP 8.2 deprecated features. https://www.php.net/manual/en/migration82.deprecated.php
- PHP 8.3 deprecated features. https://www.php.net/manual/en/migration83.deprecated.php
- PHP 8.4 deprecated features. https://www.php.net/manual/en/migration84.deprecated.php
- PHP 8.5 deprecated features. https://www.php.net/manual/en/migration85.deprecated.php
- XML parser cleanup deprecation. https://www.php.net/manual/en/function.xml-parser-free.php
