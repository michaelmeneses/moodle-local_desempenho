<?php

/**
 * Component 'local_desempenho'.
 *
 * @package   local_desempenho
 * @copyright 2017 Michael Meneses  {@link http://michaelmeneses.com.br}
 * @license   UNIPRE {@link http://www.cursounipre.com.br}
 */

require_once('../../config.php');

$courseid = optional_param('courseid', 0, PARAM_INT);

if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $context = context_course::instance($course->id, MUST_EXIST);
    require_login($course);
    $PAGE->set_pagelayout('incourse');
} else {
    $course = null;
    $context = context_system::instance();
    $PAGE->set_pagelayout('report');
}

$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading(get_string('myperformance', 'local_desempenho'));
$PAGE->set_title(get_string('myperformance', 'local_desempenho'));
$PAGE->set_url('/local/desempenho/index.php');

$output = $PAGE->get_renderer('local_desempenho');

echo $OUTPUT->header();

echo $output->print_header($course);
echo $output->content();

echo $OUTPUT->footer();
