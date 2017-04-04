<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Functions for component 'local_desempenho'.
 *
 * @package   local_desempenho
 * @copyright 2017 Michael Meneses  {@link http://michaelmeneses.com.br}
 * @license   UNIPRE {@link http://www.cursounipre.com.br}
 */

function local_desempenho_extend_navigation(global_navigation $navigation) {
    global $CFG, $PAGE, $COURSE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }

    $node_course = $navigation->add_course($COURSE);
    $node = navigation_node::create(
        get_string('myperformance','local_desempenho'),
        new moodle_url('/local/desempenho/index.php', ['courseid' => $PAGE->course->id]),
            navigation_node::TYPE_CUSTOM,
            'desempenho',
            'desempenho',
        new pix_icon('i/scales', get_string('pluginname', 'local_desempenho'))
    );
    $node_course->add_node($node, 'participants');
}


function local_desempenho_extend_settings_navigation($settingsnav, $context) {
    global $CFG, $PAGE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }

    if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $url = new moodle_url('/local/desempenho/index.php', array('courseid' => $PAGE->course->id));
        $node = navigation_node::create(
            get_string('pluginname', 'local_desempenho'),
            $url,
            navigation_node::NODETYPE_LEAF,
            'desempenho',
            'desempenho',
            new pix_icon('i/scales', get_string('pluginname', 'local_desempenho'))
        );
        if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
            $node->make_active();
        }
        $children = $settingnode->get_children_key_list();
        if ($children) {
            $settingnode->add_node($node, $children[0]);
        } else {
            $settingnode->add_node($node);
        }
    }
}
