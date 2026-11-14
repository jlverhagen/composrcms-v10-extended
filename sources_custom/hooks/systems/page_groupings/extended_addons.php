<?php
/** Admin Zone entry for the read-only development browser. */
class Hook_page_groupings_extended_addons
{
    public function run($member_id = null, $extensive_docs = false)
    {
        if ($member_id === null) {
            $member_id = get_member();
        }
        if (!$GLOBALS['FORUM_DRIVER']->is_super_admin($member_id)) {
            return array();
        }
        return array(
            array(
                'structure',
                'menu/adminzone/structure/addons',
                array('admin_extended_addons', array('type' => 'browse'), 'adminzone'),
                make_string_tempcode('Extended addon repositories')
            )
        );
    }
}
