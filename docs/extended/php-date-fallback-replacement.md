# Replace the remaining legacy date-format fallback

Status: open follow-up; temporary exception approved by the user on 2026-11-21.

Preserve the remaining strftime fallback for custom formats and platforms outside the verified native coverage. It is not a standalone blocker for the current combined candidate. Do not describe the release as deprecation-free for all configurations. Approval evidence: php-date-fallback-policy-review-2026-11-21.json.

The replacement must address:

- Era names, era years and composite formats, including Japanese era transitions and Thai Buddhist years.
- Alternative digits and numeric representations, including Japanese and Persian fixtures.
- Non-ASCII case conversion and character-type locale rules.
- Epoch %s process/libc timezone behavior, historical dates and DST boundaries.
- Remaining widths/modifiers, unsupported directives, percent escapes and platform behavior.
- Non-Linux support with an actual platform test environment.

Decide whether to require or optionally use Intl/ICU, bundle locale data, or provide another maintained implementation. Translate format syntax deliberately and compare observed output; do not assume ICU or date() is a drop-in libc replacement. Document locale-data versions and extension requirements.

Close this follow-up after the replacement is tested on PHP 8.2-8.5 and the supported platform matrix, preserves the agreed behavior or has an explicitly approved migration, removes the production fallback call, and appears in verified final packages. Do not close it based solely on syntax scans, suppression of diagnostics or the already-passing shipped-format fixtures.

The current verified coverage is recorded in php-shipped-date-formats-review-2026-11-21.json and php-c-utf8-case-review-2026-11-21.json. CMS workflow acceptance remains separate.
