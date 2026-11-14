# Release process (draft)

1. Preserve upstream v10 Git history and tag the exact baseline commit.
2. Keep `cms_version_number()` and `cms_version_minor()` unchanged.
3. Set Extended release string separately (example `10.0.52-ext.1`).
4. Run PHP 8.2/8.3/8.4/8.5 lint and installation, login, addon install/uninstall, theme, and upgrade smoke tests before claiming support.
5. Produce reproducible release archive, source archive, SBOM/license inventory, changelog, SHA-256 hashes and detached signatures.
6. Publish an immutable release manifest using a separate core-update signing key.
7. Release first as administrator-notification-only; do not auto-apply core updates.

## Offline prototype

Run these commands from the checkpoint root using Python 3.11 or later. The packaging input is a stable, reviewed staging tree. For scaffold testing use `cms`; for an actual release use the complete, reviewed release tree. Every entry in the supplied tree is considered for inclusion, including hidden files and empty directories. Stage only release files and keep Git metadata, credentials, deployment configuration, and generated output outside that tree.

```sh
version=10.0.52-ext.1-alpha.1
release_dir="build/${version}"
archive_name="composr-v10-extended-${version}.tar.gz"
mkdir -p build
python3 hub/publisher/check_release_staging.py --source-dir cms > build/staging-report.json
python3 hub/publisher/build_release_package.py \
    --source-dir cms --output-dir "$release_dir" --version "$version"
(cd "$release_dir" && sha256sum -c SHA256SUMS)
```

Review the JSON staging report before running the builder. It lists directories and regular files in archive order, with normalized modes and file sizes. Exit code 1 means the report has findings, including Git metadata, likely private keys, or likely local deployment configuration. Exit code 2 means the scan could not complete. The report includes paths and reasons. Its key checks use common filenames and private-key headers within the first 8 KiB, so review the staged tree for other sensitive material. The preflight is a separate command and does not remove or exclude files from the builder.

The new output directory contains a versioned TAR.GZ, `inventory.json` with file paths, sizes, modes and SHA-256 hashes, and `SHA256SUMS` covering the archive and inventory. The archive preserves file contents, directory structure and executable status. It normalizes ownership, timestamps and permissions, and traverses entries in a stable order. Repeat builds of the same staged tree under the same Python/zlib environment should match byte for byte. Record runtime versions for release verification.

To produce core release metadata, set `UPSTREAM_COMMIT` to the full baseline commit from the reviewed upstream checkout and `RELEASE_URL` to the final immutable HTTPS archive URL. The generator checks the inputs and hashes the local file. The operator confirms that the recorded commit and URL correspond to the release.

```sh
python3 hub/publisher/build_release_manifest.py \
    --archive "$release_dir/$archive_name" --version "$version" \
    --upstream-base 10.0.52 --upstream-commit "$UPSTREAM_COMMIT" \
    --download-url "$RELEASE_URL" --output "$release_dir/releases.json"
```

The generated document contains a single release entry in the existing release manifest shape, with the baseline commit recorded in `upstream_commit`. Validate it against [the core release schema](../../../hub/schemas/release-manifest.schema.json) before signing. Use the existing Ed25519 signer with a dedicated core-release key. Set `CORE_RELEASE_KEY_FILE` to the protected seed file outside the staged tree and `CORE_RELEASE_KEY_ID` to its independently provisioned key ID.

```sh
python3 hub/publisher/sign_manifest.py \
    --manifest "$release_dir/releases.json" --private-key "$CORE_RELEASE_KEY_FILE" \
    --key-id "$CORE_RELEASE_KEY_ID" --signature-output "$release_dir/releases.sig.json"
```

The signature covers the exact manifest bytes, which include the archive digest. Publish versioned output only after baseline, licensing, full-tree inventory and integration checks. The package builder emits a file inventory for integrity checks. The SBOM, license inventory and full CMS release remain release-process work. The scaffold's CMS release client is still a stub.

## Verify a finished bundle

Use the expected Extended release version, the independently pinned core release key ID, and a file containing its base64 Ed25519 public key. Set `CORE_RELEASE_PUBLIC_KEY_FILE` to that file.

```sh
python3 hub/publisher/verify_release.py \
    --manifest "$release_dir/releases.json" --signature "$release_dir/releases.sig.json" \
    --public-key "$CORE_RELEASE_PUBLIC_KEY_FILE" --key-id "$CORE_RELEASE_KEY_ID" \
    --version "$version" --archive "$release_dir/$archive_name" \
    --inventory "$release_dir/inventory.json"
```

The verifier checks the detached signature before reading the manifest, matches the requested release and archive hash, and compares the regular files against the inventory by path, mode, length and SHA-256 without unpacking the TAR. It reports the verified version, archive digest and file count as JSON. The archive hash is authenticated by the signed manifest. The inventory is a separate consistency check against that archive. `sha256sum -c SHA256SUMS` checks the archive and inventory digests against the checksum list.
