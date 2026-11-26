<?php
/** Standalone regression probe for request metadata field mappings. */
if (PHP_SAPI !== 'cli' || $argc !== 2) {
    exit(2);
}

function require_code($name) {}
function get_content_object($type) { return $GLOBALS['probe_content']; }
function do_lang($label) { return $label; }

class MetadataProbeContent
{
    public $fields;
    public function info() { return $this->fields; }
}

require $argv[1];
$GLOBALS['probe_content'] = new MetadataProbeContent();
$fields = array(
    'add_time_field' => null, 'submitter_field' => null,
    'edit_time_field' => null, 'title_field' => null,
    'description_field' => null, 'views_field' => null,
    'validated_field' => null, 'content_type_universal_label' => 'Fixture',
    'thumb_field' => null, 'alternate_icon_theme_image' => '',
    'content_type_label' => 'Fixture',
);

set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return true;
});

foreach (array(
    array(null, array('id' => 1), null),
    array(null, array('' => 7), '7'),
    array('view_count', array('view_count' => 5), '5'),
) as $case) {
    $fields['views_field'] = $case[0];
    $GLOBALS['probe_content']->fields = $fields;
    // Preexisting values ensure this case isolates the views mapping.
    $GLOBALS['METADATA'] = array_fill_keys(array('created', 'creator', 'publisher', 'modified', 'title', 'description', 'validated', 'type'), 'existing');
    set_extra_request_metadata(array(), $case[1], 'fixture', '1');
    $actual = $GLOBALS['METADATA']['views'] ?? null;
    if ($actual !== $case[2]) {
        throw new RuntimeException('Unexpected views metadata');
    }
}
restore_error_handler();
echo "metadata null-field probe passed\n";
