# Remaining work

- Run the [disposable Composr smoke tests](cms/docs/extended/disposable-smoke-tests.md) for delegated and ordinary-member access when test accounts are available. The flat stock export/import/install/uninstall case passed. The nested and theme stock exports passed archive preflight.
- Validate the optional Admin Zone registry review handoff and persistent receipts on Composr. The signed operator CLI passed a disposable MariaDB restore rehearsal. Implement key rotation and signed policy recovery after approval integration.
- Live cancellation, installation and uninstall passed. The original-domain Virtualmin restore rehearsal passed. Live installation fault injection followed by restore remains open.
- Implement administrator-triggered remote refresh after resolving the activation and retry gaps in the [refresh review](cms/docs/extended/remote-refresh-review.md). Use the [offline release tooling](cms/docs/extended/release-process.md#offline-prototype) with the full reviewed CMS tree once integration validation and release compliance checks are complete.
