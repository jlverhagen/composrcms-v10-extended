<?php

/** Read a single review exported by the operator into a protected directory. */
function extended_registry_admin_review($path, $expected_digest = null, $now = null)
{
    $bytes = @file_get_contents($path, false, null, 0, 2097153);
    if ($bytes === false || strlen($bytes) > 2097152) {
        throw new RuntimeException('Registry review unavailable');
    }
    $digest = hash('sha256', $bytes);
    if ($expected_digest !== null && !hash_equals($digest, $expected_digest)) {
        throw new RuntimeException('Registry review changed');
    }
    $review = json_decode($bytes, true, 64, JSON_THROW_ON_ERROR);
    $claims_bytes = $review['claims'] ?? null;
    if (!is_string($claims_bytes) || !is_int($review['expires_at'] ?? null) ||
        $review['expires_at'] <= ($now ?? time()) || !is_array($review['prior_sources'] ?? null) ||
        !is_array($review['proposed_sources'] ?? null)) {
        throw new RuntimeException('Registry review invalid or expired');
    }
    $claims = json_decode($claims_bytes, true, 16, JSON_THROW_ON_ERROR);
    if (($claims['schema_version'] ?? null) !== 1 ||
        ($claims['purpose'] ?? null) !== 'composr-registry-approval') {
        throw new RuntimeException('Invalid registry review claims');
    }
    return [$review, $digest];
}

/**
 * Persist the authenticated CMS confirmation. A hard link publishes complete
 * bytes without allowing a concurrent retry to replace the first receipt.
 */
function extended_store_registry_review_receipt($directory, array $receipt)
{
    if (!is_dir($directory) || is_link($directory) || !preg_match('/^[a-f0-9]{64}$/D', $receipt['review_sha256']) ||
        !is_int($receipt['member_id']) || $receipt['member_id'] < 1) {
        throw new RuntimeException('Invalid registry receipt destination or identity');
    }
    $path = $directory . '/' . $receipt['review_sha256'] . '-' . $receipt['member_id'] . '.json';
    $temp = tempnam($directory, '.receipt-');
    if ($temp === false || dirname($temp) !== rtrim($directory, '/')) {
        if ($temp !== false) {
            unlink($temp);
        }
        throw new RuntimeException('Cannot create pending registry receipt');
    }
    try {
        $file = fopen($temp, 'wb');
        if ($file === false) {
            throw new RuntimeException('Cannot open pending registry receipt');
        }
        try {
            $bytes = json_encode($receipt, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            if (fwrite($file, $bytes) !== strlen($bytes) || !fflush($file) || !fsync($file)) {
                throw new RuntimeException('Cannot persist registry receipt');
            }
        } finally {
            fclose($file);
        }
        if (!@link($temp, $path) && (!is_file($path) || is_link($path))) {
            throw new RuntimeException('Cannot publish registry receipt');
        }
        $dir = fopen($directory, 'r');
        if ($dir === false) {
            throw new RuntimeException('Cannot open registry receipt directory');
        }
        try {
            if (!fsync($dir)) {
                throw new RuntimeException('Cannot sync registry receipt directory');
            }
        } finally {
            fclose($dir);
        }
        $stored = file_get_contents($path, false, null, 0, 2097153);
        if ($stored === false || strlen($stored) > 2097152) {
            throw new RuntimeException('Cannot read stored registry receipt');
        }
        $confirmed = json_decode($stored, true, 16, JSON_THROW_ON_ERROR);
        foreach (['member_id', 'review_sha256', 'claims'] as $field) {
            if (($confirmed[$field] ?? null) !== $receipt[$field]) {
                throw new RuntimeException('Stored registry receipt conflicts with confirmation');
            }
        }
        if (!is_int($confirmed['confirmed_at'] ?? null)) {
            throw new RuntimeException('Invalid registry receipt timestamp');
        }
        return $confirmed;
    } finally {
        unlink($temp);
    }
}

function extended_run_registry_admin_review($type, $member)
{
    if ($type === 'approve_registry' &&
        (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($GLOBALS['CSRF_TOKENS']) || !csrf_filter_active())) {
        throw new RuntimeException('Registry approval requires a protected POST');
    }

    $expected = $type === 'approve_registry' ? post_param_string('review_digest') : null;
    [$review, $digest] = extended_registry_admin_review(EXTENDED_REGISTRY_REVIEW_PATH, $expected);
    if ($type === 'approve_registry') {
        $receipt = [
            'member_id' => (int)$member, 'review_sha256' => $digest,
            'claims' => $review['claims'], 'confirmed_at' => time(),
        ];
        if (defined('EXTENDED_REGISTRY_RECEIPT_DIRECTORY')) {
            $receipt = extended_store_registry_review_receipt(EXTENDED_REGISTRY_RECEIPT_DIRECTORY, $receipt);
        }
        $receipt = json_encode($receipt, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        return make_string_tempcode('<h1>Registry review confirmed</h1>' .
            '<p>Give this confirmation to the operator for verification and signing.</p><pre>' .
            escape_html($receipt) . '</pre>');
    }
    $url = build_url(['page' => '_SELF', 'type' => 'approve_registry'], '_SELF');
    $csrf = symbol_tempcode('INSERT_SPAMMER_BLACKHOLE');
    return make_string_tempcode('<h1>Review signed registry proposal</h1><pre>' .
        escape_html(json_encode($review, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) .
        '</pre><form method="post" action="' . escape_html($url->evaluate()) .
        '"><input type="hidden" name="review_digest" value="' . escape_html($digest) . '">' .
        $csrf->evaluate() . '<button type="submit">Confirm review</button></form>');
}
