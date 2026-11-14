<?php
/** Local verified addon confirmation using the stock single-addon template. */
function extended_run_local_addon_install($type, $admin_id)
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($GLOBALS['CSRF_TOKENS']) || !csrf_filter_active()) {
        access_denied('I_ERROR');
    }
    // Composr's bootstrap validates POST CSRF tokens before dispatch.
    if (!is_null($GLOBALS['CURRENT_SHARE_USER']) || get_file_base() !== get_custom_file_base()) {
        throw new RuntimeException('A shared installation cannot use the local addon handoff');
    }

    require_code('addons2');
    foreach (array('install_addon', 'inform_about_addon_install') as $function) {
        $parameters = (new ReflectionFunction($function))->getParameters();
        if (!isset($parameters[4]) || $parameters[4]->getName() !== 'verified_local_path') {
            throw new RuntimeException('The stock local addon handoff patch is required');
        }
    }
    require_code('extended_addon_confirmation');
    require_code('extended_addon_browser');
    $directory = extended_addon_confirmation_directory(EXTENDED_LOCAL_ADDON_DIRECTORY);
    $web_root = realpath(get_custom_file_base());
    if ($web_root === false || $directory === $web_root || str_starts_with($directory, $web_root . '/')) {
        throw new RuntimeException('Local addon files must be outside the web root');
    }

    $browse = build_url(array('page' => '_SELF', 'type' => 'browse'), '_SELF');
    if ($type === 'cancel_local_addon') {
        extended_finish_addon_confirmation($directory, $admin_id, post_param_string('file'), null);
        return redirect_screen(get_screen_title('ADDONS'), $browse, 'Addon installation cancelled.');
    }

    appengine_live_guard();
    if ($type === 'review_local_addon') {
        $addon = extended_find_local_install_entry(post_param_string('addon'));
        $archive = $directory . '/' . $addon['sha256'] . '.tar';
        extended_assert_private_addon_archive($archive);
        $confirmation = extended_create_addon_confirmation(
            $directory, $admin_id, $archive, $addon['source_id'], $addon['id'], $addon['sha256']
        );
        list($warnings, $files, $info) = inform_about_addon_install($addon['id'] . '.tar', null, null, true, $archive);
        $screen = do_template('ADDON_INSTALL_CONFIRM_SCREEN', array(
            'TITLE' => get_screen_title('ADDONS'),
            'FILE' => $confirmation['token'],
            'URL' => build_url(array('page' => '_SELF', 'type' => 'confirm_local_addon'), '_SELF'),
            'FILES' => $files,
            'WARNINGS' => $warnings,
            'NAME' => $info['name'],
            'AUTHOR' => $info['author'],
            'ORGANISATION' => $info['organisation'],
            'VERSION' => $info['version'],
            'CATEGORY' => $info['category'],
            'COPYRIGHT_ATTRIBUTION' => implode("\n", $info['copyright_attribution']),
            'LICENCE' => $info['licence'],
            'DESCRIPTION' => make_string_tempcode(escape_html($info['description'])),
        ));
        $screen->attach(do_template('EXTENDED_ADDON_INSTALL_CANCEL', array(
            'FILE' => $confirmation['token'],
            'CANCEL_URL' => build_url(array('page' => '_SELF', 'type' => 'cancel_local_addon'), '_SELF'),
        )));
        return $screen;
    }

    require_code('abstract_file_manager');
    if (get_value('uses_ftp') === '1' || !is_writable($web_root)) {
        throw new RuntimeException('The local addon handoff requires the local file manager');
    }
    $selected = array();
    foreach ($_POST as $key => $value) {
        if (preg_match('/^file_[0-9]+$/D', $key) === 1) {
            $selected[] = $value;
        }
    }

    $confirmed = extended_finish_addon_confirmation($directory, $admin_id, post_param_string('file'), $selected);
    $addon = extended_find_local_install_entry($confirmed['source_id'] . ':' . $confirmed['addon_id']);
    if (!hash_equals($addon['sha256'], $confirmed['sha256'])) {
        throw new RuntimeException('The approved catalogue entry has changed');
    }
    extended_assert_private_addon_archive($confirmed['archive_path']);
    foreach ($confirmed['selected_paths'] as $path) {
        extended_check_local_addon_destination($web_root, $path);
    }

    // AFM reads these POST fields internally. Force local mode for this request.
    $post = $_POST;
    $_POST = array('uses_ftp' => '0');
    try {
        install_addon($confirmed['addon_id'] . '.tar', $confirmed['selected_paths'], true, true, $confirmed['archive_path']);
    } finally {
        $_POST = $post;
    }
    return redirect_screen(get_screen_title('ADDONS'), $browse, do_lang_tempcode('SUCCESS'));
}

function extended_find_local_install_entry($key)
{
    $config = extended_browser_config_from_file(EXTENDED_BROWSER_CONFIG_PATH);
    $addons = extended_browser_load($config)['addons'];
    if (!isset($addons[$key])) {
        throw new RuntimeException('The approved catalogue entry is unavailable');
    }
    $addon = $addons[$key];
    require_code('version2');
    if (version_compare(PHP_VERSION, $addon['php_minimum'], '<')
        || version_compare(get_version_dotted(), $addon['cms_minimum'], '<')) {
        throw new RuntimeException('The addon requires a newer CMS or PHP version');
    }
    return $addon;
}

function extended_assert_private_addon_archive($path)
{
    clearstatcache(true, $path);
    $stat = @lstat($path);
    if ($stat === false || ($stat['mode'] & 0170377) !== 0100000 || $stat['nlink'] !== 1 || !is_readable($path)) {
        throw new RuntimeException('The staged addon must be a private, read-only regular file');
    }
}

function extended_check_local_addon_destination($root, $path)
{
    $parts = explode('/', $path);
    $file = array_pop($parts);
    foreach ($parts as $part) {
        $root .= '/' . $part;
        clearstatcache(true, $root);
        if (is_link($root) || (file_exists($root) && !is_dir($root))) {
            throw new RuntimeException('An addon destination parent is not a real directory');
        }
    }
    $destination = $root . '/' . $file;
    clearstatcache(true, $destination);
    $stat = @lstat($destination);
    if ($stat !== false && (($stat['mode'] & 0170000) !== 0100000 || $stat['nlink'] !== 1)) {
        throw new RuntimeException('An addon destination is not a regular file');
    }
}
