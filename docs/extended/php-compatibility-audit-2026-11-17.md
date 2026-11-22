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

## Nullable sorting follow-up on 2026-11-19

Core `_strlen_sort` and the string-comparison path of `_multi_sort` implicitly passed null field values to strlen/strcasecmp. Null is now explicitly treated as empty text, including evaluated text objects. Existing numeric branches, direction, tie behavior and key handling are retained; this is not a comparator redesign or support for missing keys/arbitrary invalid values.

The registered CLI probe `_tests/libs/sort_boundary_probe.php` passed 1,048 checks on each of PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12, with strict diagnostics and empty stderr. All three changed PHP files passed lint. The baseline reproduced 240 nullable-string deprecations per runtime; fixed-path diagnostics were empty. Observation hashes match both the legacy baseline and all four runtimes.

Fixtures cover 144 length-comparison pairs, 870 map-comparison cases, 32 whole-sort runs and two explicit null-ordering checks. They include null/empty/case/numeric text, finite scalar values, evaluated nullable text, ascending/descending and secondary keys, associative/indexed rows, empty/single-row inputs and both sort modes. Exact hashes and limits are in `php-sort-boundary-review-2026-11-19.json`. Full CMS list-screen acceptance remains under R05; all workflow ledger cells remain not_run. The fix awaits the combined core release and no site deployment was performed.

## Guarded date-case formats follow-up on 2026-11-19

F03's native Linux formatter now handles %P and selected uppercase forms (%^a/%^A/%^b/%^B/%^h/%^p/%^P/%^Z) only with C/POSIX LC_CTYPE and ASCII output. Other character-type locales, non-ASCII text, combined flags/widths and unsupported directives retain the whole-format legacy fallback. The implementation only reads LC_CTYPE; it does not change application locale state. Percent escapes remain literal. Libc's unusual lowercase %^P result is preserved.

The expanded committed temporal regression test passed 819,867 assertions per runtime on PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12, adding 20,132 assertions to the previous suite. All seven time locales, five timezones and 61 timestamps ran. Both changed PHP files passed lint; strict fixed-path diagnostics and stderr were empty. Accepted formats explicitly require native replacement before comparison with libc. The test saves/restores LC_TIME, LC_CTYPE, timezone and language substitutions. Evidence: `php-date-modifier-review-2026-11-19.json`.

A proposed %s replacement was rejected: for timestamp -315619200 in the runner's PHP UTC timezone, libc returned -315597600 while date('U') returned -315619200. Do not replace this directive without accounting for the legacy libc/process-timezone interpretation. It remains on the existing fallback. Locale-specific E/O rules, other modifiers/extensions, non-Linux behavior and full CMS workflows remain open. No prerelease or website deployment was performed.

## Acceptance criteria

Close the compatibility work when all maintained-scope confirmed findings are resolved or have explicit approved exceptions with tracked follow-ups, remaining source candidates have documented dispositions, the four-version matrix has passed with deprecations captured, and the final packages contain the reviewed code. Suppression of diagnostics is insufficient evidence of API compatibility. PHP 8.6 requires a separate audit against its final behavior.

## References

- PHP 8.1 deprecations affecting the minimum supported runtime, including locale formatting and internal interface compatibility. https://www.php.net/manual/en/migration81.deprecated.php
- PHP 8.2 deprecated features. https://www.php.net/manual/en/migration82.deprecated.php
- PHP 8.3 deprecated features. https://www.php.net/manual/en/migration83.deprecated.php
- PHP 8.4 deprecated features. https://www.php.net/manual/en/migration84.deprecated.php
- PHP 8.5 deprecated features. https://www.php.net/manual/en/migration85.deprecated.php
- XML parser cleanup deprecation. https://www.php.net/manual/en/function.xml-parser-free.php


## Approved maintenance scope, 2026-11-20

This decision supersedes the earlier unresolved policy language for F04, F13 and R04 above; it does not turn an excluded or excepted API into a compatibility fix.

- F04: retain FirePHP's public assertion-registration behavior as an accepted deprecation exception. No repository caller was found; custom callers remain possible. Normal FirePHP logging and CMS workflows remain subject to acceptance. Consider eventual removal only after verifying browser-client requirements; that dependency has not been established by this audit.
- F13: exclude `composr_homesite_support_credits` and its MantisBT dependencies from maintained addon publication. Retain source for reference and preserve existing installations/data. The mirror will not use the tracker/support-credits. Eventual source removal is separate work.
- R04: policy is approved. Hub PR #8 implements automatic omission and rejects explicit or mixed requests before source loading or partial output. See https://github.com/jlverhagen/composrcms-v10-extended-hub/pull/8 and `hub/publisher/maintenance-scope.md` in that repository.

The standard installer inventory and addon selection are distinct checks. GitHub source archives can still contain the retained tracker tree. Final generated packages and catalogues still require inventory acceptance under R06. All 64 CMS workflow cells remain unexecuted; no new release or website deployment is implied.


## Local combined package inventory verification, 2026-11-20

At core commit c661ad575dcf14c40fb830f9376221723d74d767, PHP 8.4.27 CLI built the quick installer, manual installer, core TAR.GZ and upgrader CMS using the hub builder at 3189addf321f642fce43a23b734a3f58a2773ad0. The historical alpha.1 label was reused strictly for local validation; these artifacts were not published and are not a frozen deployment candidate.

All four formats agree on 6,265 core payload files. The regenerated integrity map has 6,264 verified CRC entries, and all seven enforcement checksums match. Quick installer index offsets and the stock installer suffix match their payloads. Two independent builds produced identical SHA256SUMS. No excluded tracker/support-credits, aps or mobiquo paths were present.

All 20 maintained optional addons owning audited paths built successfully. Their 1,382 payload files match source bytes, with zero excluded members or mismatches. This covers the audit-owned selection, not every optional addon. Four real-builder verifier tests, four addon-selection tests and nine existing release tests passed. Hub PR #9 adds the reusable verifier and corruption/exclusion negative controls.

See `php-package-inventory-review-2026-11-20.json` for exact hashes, runtime and commands. R04 has local package evidence; R06 remains open for final candidate/signing/catalogue inventory and installation/upgrade/restore acceptance. All 64 R05 workflow cells remain not_run. The test website and database were unchanged.


## Magic-setter review, 2026-11-20

A token scan of 3,668 PHP files in sources and sources_custom (excluding phpstub.php) found no direct anonymous class declarations and two magic setters: LightOpenID and VObject Component. Comments and whitespace were ignored; attributed anonymous declarations, generated code, tracker and tests are outside this bounded scan. Both setters route writes into declared state or child collections rather than creating arbitrary PHP properties.

The registered property_graph_probe now includes vobject-setter. On locally available PHP 8.4.27, all 13 checks passed with strict diagnostics and empty stderr: scalar creation/replacement, absence of PHP dynamic properties, component identity, parent links, nested clone independence, rejection of null/array/object values and detach behavior. The existing computed-guards mode also passed its 12 checks after the loader extension. Probe lint and git diff whitespace checks passed. No production fix was needed.

Evidence: php-magic-setter-review-2026-11-20.json. The new mode still requires PHP 8.2, 8.3 and 8.5 execution; previous four-version results are not transferred to this new coverage. R02, R05 and R06 remain open. No release or test-site deployment was performed.


### Expanded setter fixtures and local regression, 2026-11-20

The VObject setter mode now passes 21 checks, adding Property-object replacement/identity, empty text/zero/false/fractional scalar conversion, scalar removal and preservation of an existing child after an invalid replacement. All seven property_graph_probe modes passed on PHP 8.4.27 (75 total checks), with strict diagnostics, zero exit codes and empty stderr. Probe lint and git diff --check passed. The updated evidence retains every mode result and the current probe hash.

Remote PHP 8.2/8.3/8.5 follow-up is blocked pending host authorization: automatic approval review denied observation of the authenticated Webmin origin. No remote command, PHP selection, site or database change occurred. These versions remain pending for this new mode; R02 and all unexecuted workflow cells remain open.


### Four-version setter follow-up, 2026-11-21

After explicit Webmin host authorization, the current VObject setter probe passed 21 checks on each of PHP 8.2.36, 8.3.37, 8.4.28 and 8.5.13. All four lint checks passed; exit codes were zero, diagnostics and stderr were empty, and serialized observations matched across versions. The probe ran as nobody in a new isolated /var/tmp directory after verifying all 28 VObject PHP files against the local source SHA256 inventory. The probe SHA256 is recorded in the evidence.

This supersedes the pending/blocked matrix status above for this probe only. The first submission failed with Python SyntaxError because Webmin removed command-field newlines; it executed no test code. A single-line encoded submission then completed successfully. No website files, database, web PHP selection, services or release publication were changed. R02 remains open for broader computed/external writes; all 64 CMS workflow cells remain unexecuted. Full remote results are retained in php-magic-setter-review-2026-11-20.json.


## Computed property-write follow-up, 2026-11-21

A bounded textual search of sources and sources_custom, excluding phpstub.php, identified 12 ->$identifier assignment sites across nine files, including the generated Diff assignment string. Each site has a recorded disposition: declared or guarded state, intentional stdClass JSON records, or the two already-approved extensible LESS records. No additional production fix was needed. Braced/reflection/generated/aliased and out-of-tree writes are not exhaustively covered.

The new environment-copy property probe verifies all 15 fields copied by ILess_Environment::createCopy, distinct environment identity, replacement frames, an actual shared FunctionRegistry object, shared FileInfo identity and independent array updates. Its 35 checks passed on PHP 8.2.36, 8.3.37, 8.4.28 and 8.5.13 with identical observations, zero diagnostics and empty stderr. Probe lint passed on all four. Runs used nobody in an isolated directory with exact source/probe hashes.

All eight property probe modes also passed locally on PHP 8.4.27 (110 checks). This local total is not a four-version suite result; only the new 35-check mode was run remotely in this batch. Evidence: php-computed-write-review-2026-11-21.json. R02/R03 and full CMS/package acceptance remain open. No release, test-site file/database update or service/PHP setting change occurred.


## OpenID nullable realm follow-up, 2026-11-21

LightOpenID::__set passed null realm/trustRoot values into trim, producing PHP deprecations. The optional openid addon now explicitly converts only null to empty text before trimming. Other scalar/stringable conversion and invalid-input TypeError behavior remain unchanged. The property-graph probe adds openid-boundary fixtures for both aliases, default URL construction, null/empty/whitespace, integer/float/boolean/stringable values and invalid array/object assignments that must preserve existing state.

The 27 checks passed against baseline and fixed code on PHP 8.2.36, 8.3.37, 8.4.28 and 8.5.13. Each baseline produced exactly two deprecations; fixed diagnostics and all stderr were empty. Observations match before/after and across runtimes. Both changed PHP files passed lint on all four. Runs were isolated and unprivileged, with no provider request or CMS login. All nine property probe modes passed locally on PHP 8.4.27 (137 checks); that total is not a four-version suite claim. Evidence: php-openid-boundary-review-2026-11-21.json.

The fix awaits combined optional-addon publication. R03, full CMS workflow testing and final package acceptance remain open. No prerelease or live website/database change was made.


## Nullable JSON decoder follow-up, 2026-11-21

Both legacy Services_JSON copies passed null into preg_replace in reduce_string. Bundled sources/json_inner.php and optional facebook/jsonwrapper/JSON/JSON.php now normalize only null to empty text before comment stripping. Fixtures explicitly assert expected values/types for null, empty text, JSON scalars, arrays/objects, nested Unicode data, a legacy comment case, scalar coercion and rejected array/object arguments in object and loose-array modes.

Both copies passed 44 fixed checks each (88 per runtime) on PHP 8.2.36, 8.3.37, 8.4.28 and 8.5.13. Each baseline produced four null deprecations per copy; fixed diagnostics and stderr were empty. Baseline/fixed and cross-version observation hashes match. Baseline mode has 43 checks per copy because it skips the previously added explicit-property reflection assertion. Probe and both changed source files passed lint on all four versions. All nine property modes also passed locally on PHP 8.4.27 (213 checks).

Webmin readback timed out, but the initial execution completed; its saved report was recovered through normal Webmin navigation without rerunning the tests or altering security settings. Evidence: php-json-boundary-review-2026-11-21.json. Core and optional facebook changes await combined publication. Full CMS/provider workflows, remaining R03 candidates and R05/R06 acceptance remain open. No prerelease, live website/database or service configuration change was made.


## Consolidated post-fix package verification, 2026-11-21

Source commit 1227b4b59ea839076706dd9877680c61889843cb consolidates the reviewed property work and nullable JSON/OpenID fixes. All four rebuilt core formats agree on 6,265 payload files, with 6,264 verified integrity entries and seven matching guard checksums. The core JSON source matches the CMS payload byte-for-byte. All 21 audited optional addons built, including newly added openid; their 1,394 payload files match source bytes with no excluded tracker/support-credits/aps/mobiquo members or mismatches.

The existing alpha.1 label was reused strictly for isolated local package validation. These are not artifacts to publish under that existing tag, and no new prerelease or deployment candidate is declared. Evidence: php-combined-package-review-2026-11-21.json, including package hashes and builder hashes.

Remaining gates are explicit: F03 date fallback/locale/platform behavior; broader R02/R03 runtime/input review; all 64 R05 CMS workflow cells; and R06's uniquely versioned final candidate, signing/catalogue, installation/upgrade/restore and published inventory acceptance. CLI tests and package comparisons do not close these gates. No site or database changes occurred.


## Shipped date-format inventory follow-up, 2026-11-21

The registered CLI date_format_inventory_probe reads 20 shipped English global/calendar date keys and checks three direct literal format entries. It requires a native replacement before comparing both the helper and public formatter to the transformed legacy libc reference. LC_CTYPE is C and language substitution filters are empty; only the known deprecation around the reference strftime call is masked. The production path runs with strict diagnostics.

All 899,415 comparisons per runtime passed on PHP 8.2.36, 8.3.37, 8.4.28 and 8.5.13: 23 format entries, 869 installed locale names, five timezones and nine timestamps. No format fell back, no outputs differed and stderr was empty. The probe and formatter passed lint across all four. Total remote comparisons: 3,597,660. Local PHP 8.4.27 separately passed 197,685 comparisons over 191 locale names. Locale names include aliases/encodings and are not counts of distinct languages.

Evidence: php-shipped-date-formats-review-2026-11-21.json, with exact source/probe hashes, full remote result digests, local output and the bounded direct-call inventory. Remote language files contain only the selected original shipped date entries; they are fixture projections, not copies of complete language files. No formatter production change was needed. The testing_platform registry includes the new probe, and its addon rebuilt successfully.

F03 remains open: arbitrary custom formats (including configurable date symbols), locale-specific E/O rules and non-Linux behavior can still use the deprecated fallback. The local characterization records that epoch %s, unknown %Q and oversized %129Y are unsupported natively; it also records that %_0d is supported rather than assuming every combined-looking modifier is rejected. None of this closes full CMS workflows or final package acceptance. No prerelease, live site/database, service or PHP setting was changed.


## C UTF-8 ASCII case-format follow-up, 2026-11-21

The native Linux case formatter now accepts LC_CTYPE C.utf8, C.UTF-8 and C.UTF8 for its existing ASCII-only %P and uppercase a/A/b/B/h/p/P/Z forms. It continues rejecting non-ASCII output and unverified character-type locales; it does not change global locale state. Local negative controls show that these formats previously returned null (fallback), while revised ASCII January/AM fixtures return JANUARY/am.

The new registered date_ascii_case_probe passed 1,055,835 comparisons per runtime on PHP 8.2.36, 8.3.37, 8.4.28 and 8.5.13: 720,171 exact native/libc matches and 335,664 verified non-ASCII fallbacks, over 869 installed LC_TIME names, all three C UTF-8 aliases, five timezones and nine timestamps. Reference-only deprecation masking does not apply to the native/public code. Percent escapes and rejection of Turkish LC_CTYPE are also checked. Lint and stderr were clean. Locale counts include aliases/encodings.

Local PHP 8.4.27 separately passed 232,065 case comparisons over 191 locale names and all 819,867 assertions in the existing date regression suite. Its C UTF-8 month assertion now requires native JANUARY rather than fallback. The testing_platform addon rebuilt with the new probe. Evidence: php-c-utf8-case-review-2026-11-21.json.

This narrows F03 but does not close it: non-ASCII locale-specific casing, era/alternative-digit formats, %s/process timezone semantics, other custom formats and non-Linux behavior still require dispositions or implementation. Full CMS/final package acceptance remains open. No prerelease or live-site/database/configuration update occurred.


## Remaining date fallback policy review, 2026-11-21

Local PHP 8.4.27 fixtures confirm that remaining era/alternative-digit formats carry real output semantics: Japanese %Od on 2026-01-02 produces 二, Persian %Od produces ۰۲, and Thai %EY produces พ.ศ. 2569. Those native paths return null and correctly retain legacy formatting. This is local characterization only, not new four-version or non-Linux acceptance. The exposed ALT_DIGITS value in the Japanese fixture is the zero symbol; a complete alternate-number replacement was not established from this metadata.

PHP documents strftime as deprecated and names date/IntlDateFormatter alternatives. IntlDateFormatter uses ICU patterns and explicit calendar selection, so a full migration needs pattern/calendar/locale-data decisions and output comparisons; it is not assumed byte-compatible with libc. The local audit runtime has no IntlDateFormatter extension. See php-date-fallback-policy-review-2026-11-21.json for exact hex observations and primary documentation URLs.

Recommendation awaiting user decision: preserve the remaining legacy behavior as a documented temporary deprecation exception and continue the remaining audit/CMS workflow acceptance, while tracking a complete replacement separately. This would not make F03 fixed or justify a universal deprecation-free claim. Keeping F03 as a release blocker until full replacement remains an alternative. No exception is accepted by this report, and no formatter, published package or website was changed.


## Date fallback scope approved, 2026-11-21

The user approved retaining the remaining legacy date-format fallback as a documented temporary deprecation exception. This supersedes the pending-decision text above. F03 is now an accepted exception with open replacement work, not a fixed deprecation and not a standalone release blocker. Its scope is limited to formats/platforms outside verified native coverage; ordinary CMS date rendering and unrelated diagnostics still require acceptance.

Complete replacement is tracked in php-date-fallback-replacement.md, with locale/era/digit, timezone, dependency and platform requirements. The findings ledger and workflow scope gate record the approval. Existing evidence and historical package reports are unchanged. All 64 CMS workflow cells remain unexecuted; current final packages still need rebuilding and acceptance after the latest formatter changes. No release, deployment or website/database change was performed while recording approval.


## Increment shortlist and CMS preflight, 2026-11-21

A token inventory recorded 1,819 T_INC/T_DEC sites in 3,668 PHP files under sources/sources_custom, excluding phpstub.php. A supplementary same-file literal-initialization shortlist led to six inspected files: calendar, webstandards, sitemap_xml, cns_members2, Swift ByteArrayReplacementFilter and twitter_feed. Null values are replaced/guarded before the inspected arithmetic; Swift resets the reverse-loop operand from count(array)-1. The Twitter match is a comment false positive with no increment/decrement token in that file. No production change was indicated. This is not a type proof for all 1,819 sites or generated/external paths. Evidence: php-increment-shortlist-review-2026-11-21.json. R03 remains open.

Read-only Webmin preflight observed installed Extended version 2026.11.1-alpha.20 and verified all seven guarded files against the current enforcement manifest. Older backup pointers contain site/database archives, but their presence is not current-backup or successful-restore evidence. The limited pointer search is not an exhaustive backup inventory. Evidence: php-cms-preflight-2026-11-21.json.

Before an upgrade: freeze and verify a uniquely versioned combined candidate, record actual web SAPI/extensions and installed-addon state, and establish a current backup/disposable restore. All 64 CMS workflow cells remain not_run. No live website/database/service configuration or release was changed.

### 2026-11-21 - Frozen local alpha.22 candidate

Built `2026.11.1-alpha.22` from source commit `6d50a93adb288b0839df05b46f6b28733b08afc8`. All four core archive payloads match (6,265 files; 6,264 integrity entries; seven guarded files), and a second build produced identical hashes. Verified 21 addon archives containing 1,396 source-matching payload files. Exact hashes are in `php-candidate-alpha22-2026-11-21.json`. These are package checks, not CMS acceptance; all 64 workflow cells remain unrun. F03/F04 exceptions and broader R02/R03 review remain open as previously recorded.

A fresh Virtualmin domain backup was started in a protected root-only staging directory. Final archive readback timed out; recovery readiness and database restore remain unverified. The installed site remains alpha.20. No candidate deployment or release publication occurred. Automatic approval review rejected the audit branch push because explicit authorization to publish project/environment metadata to the GitHub destination was missing. No alternate publication route was attempted.

### 2026-11-21 - Backup archive content verification

Fresh Virtualmin backup completed successfully (1,980,413,478 bytes). Full gzip integrity passed; eight selected backed-up files match the installed guard/version files. The SQL dump is readable (6,454,508 bytes; 141 CREATE TABLE and 78 INSERT statements). Hashes and sanitized evidence are in `php-backup-verification-2026-11-21.json`. An isolated import attempt failed at the administrative command before fixture database/account creation; no import or full domain restore is claimed. Recovery readiness remains incomplete. Source tree publication succeeded through the connected GitHub app, retaining version/hash evidence while withholding the private backup directory. No candidate installation occurred and all 64 CMS workflow cells remain unrun.

### 2026-11-21 - Database access resolved and isolated restore passed

The server command shell already ran as root; plain MariaDB CLI authentication returned ERROR 1045 (28000). Reusing Webmin's existing configured database connection resolved access without changing an existing account or password. The backed-up SQL imported successfully into a disposable database using a temporary account restricted to that database (exit 0; 141 restored tables). The fixture database, account and private client credential files were removed. Sanitized evidence is in `php-backup-verification-2026-11-21.json`. This verifies an isolated SQL import, not full Virtualmin domain recovery or CMS workflow acceptance. The website remains unchanged and all 64 workflow cells remain unrun.

### 2026-11-21 - Alpha.22 test-site upgrade handoff staged

Rebuilt all core formats on the test server from the published audit branch using the reviewed builder. Package verification passed and the upgrader SHA256 exactly matches the frozen local candidate. Staged the versioned mirror downloads without replacing an existing release directory; the HTTPS-served upgrader is 55,644,160 bytes with SHA256 `358a8812dfd1c1d76ef177fa11ac43a3f6aa164c99f33366c79f8f7fe8fb5901`. All seven currently installed guard files match the active manifest. Fresh backup gzip/content checks and isolated 141-table SQL import passed as separately recorded. Candidate remains uninstalled; the next step is the user-operated site-close/file-upgrade/cache-clear boundary followed by post-upgrade verification. All 64 CMS workflow cells remain unrun.

### 2026-11-21 - Alpha.22 installed; initial smoke checks passed

After the user closed the site, applied the file upgrade and cleared caches, the installed version file matches alpha.22. Bundled JSON and temporal runtime files match reviewed source; all seven guarded files match the active manifest. OpenID and Facebook JSON files are absent on this installation because those are separate optional addons. Admin Dashboard reports alpha.22 and no newer Extended release; the guarded addon browser loads both verified pilot catalogues (four addons each). The authenticated homepage renders without a visible error or stack trace. Site remains closed. These observations are recorded in `php-alpha22-postupgrade-2026-11-21.json`; they do not mark complete workflows passed. Web PHP version and request diagnostics remain to establish; all 64 formal workflow results remain unrun.

### 2026-11-21 - Web runtime and scoped request diagnostics established

Authenticated PHP-Info confirms PHP 8.2.36, FPM/FastCGI, mysqlnd 8.2.36, error_reporting 32767 and enabled display/log errors. Recorded log offsets before dashboard, verified addon browser and authenticated homepage requests. All rendered without a visible error; application, PHP-FPM and Apache log deltas were empty and unrotated. This is partial W04 evidence, not completion: outbound update request tracing and upgrade-link verification remain outstanding. Evidence: `php82-web-runtime-review-2026-11-21.json`. All 64 formal workflow result statuses remain not_run; the PHP 8.2 W04 cell now records its partial procedure and remaining checks.

### 2026-11-21 - Integrity scan and float-field debug exit

Executed the upgrader integrity scan on PHP 8.2.36 without CSS merging or cleanup submission. No bundled Extended files were flagged unknown. Existing custom assets, sales portal files, backup files and the float hook override remain intact. Normalized comparison showed the float override differs only by removing bundled `fatal_exit('float field')`, which otherwise unconditionally terminates field input. Removed this debug exit from source as F15 for the next consolidated candidate; preserved the working installed override. The isolated baseline probe reproduces termination; fixed delegation passed 13 cases with no diagnostics and lint on local PHP 8.4.27. This is not full CMS form acceptance. Static update-source review confirms the Extended GitHub branch returns before legacy compo.sr handling; live outbound request tracing remains outstanding. Evidence: `php82-integrity-review-2026-11-21.json`. All 64 formal workflow statuses remain unrun, with partial W12 execution recorded.

### 2026-11-21 - Comcode workflow and cold-cache rendering passed on PHP 8.2

Created a dedicated Comcode fixture through the CMS, previewed and saved nested bold/italic formatting and Unicode, then edited and saved revision 2. Rendered R2 with R1 absent and expected nested markup. Cleared test-site caches through the upgrader and verified both first render and refresh. Application, PHP-FPM and Apache error logs had zero new bytes and no rotation over the scoped workflow. Retained only the named fixture `:audit-alpha22-php82-fixture` for cross-version checks while the site remains closed. Evidence and exact remaining procedure scope: `php82-comcode-workflow-2026-11-21.json`. W05/W11 record partial execution; news article and broader theme tests remain, so full-case statuses are unchanged.

### 2026-11-21 - W05 PHP 8.2 content workflow passed

Created unvalidated news fixture 13 with optional fields at defaults, previewed/saved/rendered Unicode content, then edited title/body and verified R2 with R1 absent. First render and refresh after cache clearing both passed. Application, PHP-FPM and Apache log deltas were empty and unrotated. Reconfirmed PHP 8.2.36 FPM/FastCGI with error_reporting 32767 and logging enabled. CMS reported its standard staff-validation notification when creating the draft; subscriber publication/delivery was not exercised. Combined with the prior Comcode workflow, this completes the stated W05 administrative content procedure on PHP 8.2.36. The matrix now has one pass and 63 remaining full-case results, with partial evidence retained separately. Fixtures remain identified for cross-version checks. Evidence: `php82-news-workflow-2026-11-21.json`.

### 2026-11-21 - Upload fixtures prepared; local-file selection blocked

Prepared deterministic PNG/JPEG/GIF source images (160x120 red/blue halves) with PHP 8.2 GD and created an isolated File/Media library folder. Gallery is not installed; its missing-page/sitemap response showed existing missing theme-icon warnings. The controlled PNG downloaded successfully on a fresh browser tab. The real file chooser rejected file selection because the ChatGPT Chrome extension requires Allow access to file URLs. Asked the user whether to enable that local-file permission; did not change it automatically. Browser transfer/reconnection timeouts also occurred. A subsequent native GD decode/resize/crop probe attempt has no verified result and must not count as passed. W06 PHP 8.2 is blocked; matrix is one pass, one blocked and 62 not-run cells. Evidence: `php82-image-workflow-preparation-2026-11-21.json`.
