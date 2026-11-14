<?php
/** DEVELOPMENT MODULE: read-only by default; optional verified local addon installation. */
class Module_admin_extended_addons
{
    public function info()
    {
        return array('author' => 'ComposrCMS v10 Extended contributors', 'organisation' => 'Unofficial ComposrCMS v10 Extended',
            'hacked_by' => null, 'hack_version' => null, 'version' => 1, 'locked' => true, 'update_require_upgrade' => true);
    }

    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        return array('browse' => 'Extended addon repositories (read-only)');
    }

    public function pre_run()
    {
        return null;
    }

    public function run()
    {
        // Additional restriction on top of Admin Zone module permissions.
        if (!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) {
            access_denied('I_ERROR');
        }
        $type = get_param_string('type', 'browse');
        if (defined('EXTENDED_REGISTRY_REVIEW_PATH') && in_array($type, ['review_registry', 'approve_registry'], true)) {
            require_code('csrf_filter');
            require_code('extended_registry_admin_review');
            try {
                return extended_run_registry_admin_review($type, get_member());
            } catch (Throwable $error) {
                error_log('Extended registry review: ' . $error->getMessage());
                warn_exit('The registry review could not be completed. Reopen the current proposal before retrying.');
            }
        }
        $local_install = defined('EXTENDED_LOCAL_ADDON_DIRECTORY') && defined('EXTENDED_BROWSER_CONFIG_PATH');
        if ($type !== 'browse') {
            if (!$local_install || !in_array($type, array('review_local_addon', 'confirm_local_addon', 'cancel_local_addon'), true)) {
                warn_exit('This module is read-only');
            }
            require_code('csrf_filter');
            require_lang('addons');
            require_code('extended_addon_install_controller');
            try {
                return extended_run_local_addon_install($type, get_member());
            } catch (Throwable $e) {
                error_log('Extended local addon handoff: ' . $e->getMessage());
                warn_exit('The local addon action could not be completed. Check the server log before retrying.');
            }
        }
        require_code('extended_addon_browser');
        if (!defined('EXTENDED_BROWSER_CONFIG_PATH')) {
            $html = '<p>Read-only catalogue browser not configured. Set EXTENDED_BROWSER_CONFIG_PATH to an administrator-controlled JSON file outside the web root.</p>';
        } else {
            try {
                $config = extended_browser_config_from_file(EXTENDED_BROWSER_CONFIG_PATH);
                $result = extended_browser_load($config);
                $html = extended_browser_render_html($result, $local_install);
                if ($local_install && $result['addons'] !== array()) {
                    $url = build_url(array('page' => '_SELF', 'type' => 'review_local_addon'), '_SELF');
                    $html .= '<h2>Review a staged local addon</h2><form method="post" action="' .
                        escape_html($url->evaluate()) . '"><label for="extended_local_addon">Addon</label> ' .
                        '<select id="extended_local_addon" name="addon">';
                    foreach ($result['addons'] as $key => $addon) {
                        $html .= '<option value="' . escape_html($key) . '">' . escape_html($key) . '</option>';
                    }
                    $csrf = symbol_tempcode('INSERT_SPAMMER_BLACKHOLE');
                    $html .= '</select>' . $csrf->evaluate() . '<button type="submit">Review local addon</button></form>';
                }
            } catch (Throwable $e) {
                // No file paths, secrets or parser internals leak to the Admin Zone page.
                $html = '<p>Catalogue unavailable. Check the server logs and trusted source configuration.</p>';
                error_log('Extended addon browser: ' . $e->getMessage());
            }
        }
        if (defined('EXTENDED_REGISTRY_REVIEW_PATH')) {
            $review_url = build_url(['page' => '_SELF', 'type' => 'review_registry'], '_SELF');
            $html .= '<p><a href="' . escape_html($review_url->evaluate()) . '">Review registry proposal</a></p>';
        }
        return make_string_tempcode('<h1>Extended addon repositories</h1>' . $html);
    }
}
