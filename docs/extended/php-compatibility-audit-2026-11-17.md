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
| PHP 8.4 | — | — | — | Binary unavailable on techie75 |
| PHP 8.5.12 | 5,116 | 5,112 | 4 | Same intentional non-runtime files below |

The four diagnostic files are `sources_custom/phpstub.php:607`, a developer symbol stub declaring an exit function, and three Hack fixtures under `tracker/vendor/nikic/fast-route/test/HackTypechecker/fixtures/` named `all_options.php`, `empty_options.php`, and `no_options.php`. Preserve them as explicit lint exclusions when using PHP syntax results as a gate. No executable CMS syntax failure was found in this pass.

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

F03 remains open for locale composites (%c/%x/%X/%r), E/O and width/case/padding modifiers, remaining platform extensions, and non-Linux behavior. Those formats retain the legacy fallback pending behavior-preserving migration. PHP 8.4 and full CMS startup/date-display workflows remain under R01/R05. The locations below describe the original audit baseline, not the current source lines.

- `strftime` at [sources/global2.php, line 390](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources/global2.php#L390).
- `strftime` at [sources/temporal.php, line 309](https://github.com/jlverhagen/composrcms-v10-extended/blob/ec0f8a7b8c1459f31bd10b0f3c1f3810bc174b14/sources/temporal.php#L309).

### F04 FirePHP assertion registration

Status open. Assertion registration still calls assert_options with ASSERT_CALLBACK. The earlier FirePHP compatibility work did not remove this path. Test diagnostics enabled and disabled.

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

## Remaining review and runtime checklist

- [ ] R01 Run the full syntax scan and representative probes on PHP 8.4, with the same baseline and recorded extension configuration.
- [ ] R02 Review class property assignments across inheritance and load maintained optional libraries to catch dynamic property warnings and additional interface signature issues.
- [ ] R03 Exercise null/scalar boundaries, invalid text/byte input, numeric truncation, null keys, string increments, relative callables and output handlers with meaningful fixtures. A clean syntax scan cannot close this item.
- [ ] R04 Record the supported optional addon and tracker scope from registry hooks and published packages, linking every open source group to that scope. Exclude aps and mobiquo as already decided.
- [ ] R05 Run CMS workflow regression on PHP 8.2, 8.3, 8.4 and 8.5. Include installer, login/logout, permissions, Admin Zone, content create/edit, uploads/GD, search/indexing, cron/tasks, mail handling, addon review/install/uninstall, themes/cache clearing, integrity scan and upgrade preservation of installed addons.
- [ ] R06 Validate the final combined packages and installed file inventories, then record the commit, package hashes, PHP versions and observed diagnostics. Stage optional fixes as addon updates where ownership requires it.

F01, F02, F03, F04 and F11 form the first bundled-code batch. F05–F10 and F12 form the optional/library batch. F13–F14 need an explicit shipping/tooling scope before they contribute to the core acceptance result. R01–R06 define the remaining compatibility verification. Check off a finding only after the fix is merged and its relevant regression check is recorded.

## Acceptance criteria

Close the compatibility work when all maintained-scope confirmed findings are resolved, remaining source candidates have documented dispositions, the four-version matrix has passed with deprecations captured, and the final packages contain the reviewed code. Suppression of diagnostics is insufficient evidence of API compatibility. PHP 8.6 requires a separate audit against its final behavior.

## References

- PHP 8.1 deprecations affecting the minimum supported runtime, including locale formatting and internal interface compatibility. https://www.php.net/manual/en/migration81.deprecated.php
- PHP 8.2 deprecated features. https://www.php.net/manual/en/migration82.deprecated.php
- PHP 8.3 deprecated features. https://www.php.net/manual/en/migration83.deprecated.php
- PHP 8.4 deprecated features. https://www.php.net/manual/en/migration84.deprecated.php
- PHP 8.5 deprecated features. https://www.php.net/manual/en/migration85.deprecated.php
- XML parser cleanup deprecation. https://www.php.net/manual/en/function.xml-parser-free.php
