# Verified local addon handoff prototype

The preview compares the Extended archive inventory with the stock TAR reader's entry offsets, paths and sizes. It checks the addon identity through the CMS metadata parser and returns the selectable files. Confirmation rechecks the digest, source-bound preview and selected paths. It rejects unknown, empty and duplicate selections and rejects additional path matches caused by the stock installer's loose comparisons.

The helper expects a protected local archive and trusted catalogue metadata from its caller. Store the preview on the server and associate it with the administrator, a short-lived confirmation token and the approved source. The browser should submit only the token and selected files. The caller must hold the archive immutable across verification, preview and the stock installer's eventual reopen.

The prototype is standalone. It does not publish an archive into the imports directory, alter the Admin Zone, or perform an install. The next integration must wire authenticated confirmation, cancellation, verified archive retention, and destination checks into the stock single-addon route. Test recovery from partial file and database changes on an isolated site before enabling that route.

## Verification

Run the isolated harness against the stock v10 `sources/tar.php` and the three original exports in flat, nested and theme order:

```sh
php hub/tests/check-stock-handoff.php /absolute/path/to/sources/tar.php \
    /path/to/flat.tar /path/to/nested.tar /path/to/theme.tar
```

This harness uses the stock TAR reader and a PHP INI parser shim. It checks one-file selection and rejects altered bytes, changed preview inventory and invalid selections. Actual CMS parsing, request integration, filesystem and database recovery require a disposable Composr instance.

## Server-side confirmation state

`extended_addon_confirmation.php` adds the state needed by a future Admin Zone controller. Creation returns a random token and the selectable paths. The private state file stores the administrator ID, absolute archive path, verified preview and a ten-minute expiry. Confirmation compares the authenticated administrator ID and rechecks the archive before consuming the token. Cancellation consumes it without selecting or modifying files. A file lock and an inode check prevent two requests from successfully consuming the same token. An invalid selection can be corrected within the original confirmation window.

Use a dedicated directory outside the web root, writable only by the application account. Expired tokens are removed when the owning administrator attempts to use them. A controller must handle cleanup for abandoned records and retention of the protected archive. The stock confirmation controller, session and CSRF checks, durable tracking of actual installation outcomes, and live rollback tests are still open. The supplied harness covers state transitions without making site changes.

## Admin Zone integration candidate

Apply [the stock integration patch](stock-local-handoff.patch) to the matching Composr v10 source and deploy the controller, updated Extended Admin Zone module, browser helper and cancel template on a disposable installation. The patch adds optional trusted local path arguments to the existing stock preview and installer functions. Existing stock callers continue to read from `imports/addons`. The Extended route passes the private archive directly, so the package never becomes an ordinary stock import that can be selected without the Extended confirmation.

The feature is read-only until the operator defines `EXTENDED_LOCAL_ADDON_DIRECTORY` alongside the existing `EXTENDED_BROWSER_CONFIG_PATH` in trusted server-side configuration. Use a dedicated mode-0700 directory outside the web root with protected parents. Stage an approved TAR as `<catalogue-sha256>.tar` with mode 0400 and a single link. Keep it immutable and keep its parent directories under trusted control until the operation finishes. The integration supports the local file manager on a non-shared installation. It uses Composr's existing Admin Zone CSRF validation and super-admin check. Review and cancel also use POST. The stock template carries the one-time confirmation token in its existing hidden file field.

The confirm route checks the submitted subset against the saved preview, revalidates the current signed catalogue and rejects symlinks, special files and hard-linked destinations before calling the stock installer. Take coordinated filesystem and database snapshots before trying the first install. The underlying stock installer writes files, database records and hooks separately. If it fails after any write, the consumed token cannot be used again. Review the stock error and restore or reconcile the filesystem and database before making a new confirmation.

The isolated controller harness exercises the real confirmation helper with a test TAR and records calls to the stock UI and installer. It covers cancellation, replay, catalogue changes, CSRF-disabled and non-POST requests, destination conflicts, local-only arguments, and a simulated installer exception. The full CMS rendering and actual filesystem/database recovery are still unverified.

## Disposable site verification

Virtualmin restore preview identified the home and MariaDB features for the test domain. The archive passed gzip validation; its nested home archive contained public_html/index.php and the database payload was nonempty. No restore was run. The stock patch and candidate PHP linted on the test server. The signed inert fixture rendered in the stock confirmation, cancellation succeeded, a fresh confirmation installed the fixture, and stock uninstall returned it to Not installed. The local-install opt-in was removed afterward and read-only rendering was verified. Candidate code and the private fixture remain on the test site. Live partial-failure recovery remains untested.

## Isolated interrupted-write recovery

On PHP 8.2.35, 8.3.36, 8.4.27 and 8.5.12, the stock TAR extraction routine wrote one selected theme file to a private temporary directory before an injected failure on the second write. The harness restored the first file from its baseline copy and verified its SHA-256 and the continued absence of the second file. The file manager adapter was stubbed. This exercised real fixture file recovery only; MariaDB restoration, recovery through Virtualmin, and interrupted live installation remain unverified.

Run `php hub/tests/check-stock-file-recovery.php /path/to/stock/sources/tar.php /path/to/theme-fixture.tar` with the original exported theme fixture.

## In-place Virtualmin restore rehearsal

Virtualmin created a 79.15 MiB pre-rehearsal snapshot of the Composr home and MariaDB database. Both archives passed gzip verification and included the home TAR and database dump. Verified copies were retained in a private directory outside the home being restored. Virtualmin restored the older snapshot on the original composr.duckdns.org domain and reported completion after reloading MariaDB. Composr served its login page. Virtualmin then restored the pre-rehearsal snapshot and reported completion. The Admin Zone loaded and the Extended catalogue rendered in read-only mode with its signed handoff fixture verified. This rehearsal verified the backup and restore procedure. The injected interrupted write was tested separately in the isolated harness, so live interruption followed by restore remains untested.
