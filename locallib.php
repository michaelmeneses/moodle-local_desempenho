<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Functions local for component 'local_desempenho'.
 *
 * @package   local_desempenho
 * @copyright 2017 Michael Meneses  {@link http://michaelmeneses.com.br}
 * @license   UNIPRE {@link http://www.cursounipre.com.br}
 */

function get_grade_quiz($course) {
    global $DB, $USER;

    $data = array();

    $sql = "SELECT * FROM {course_modules} WHERE course = :courseid AND visible = 1 AND module = (SELECT id FROM {modules} WHERE name like 'quiz')";
    $cms = $DB->get_records_sql($sql, ['courseid' => $course->id]);
    if (count($cms)) {
        foreach ($cms as $cm) {
            $grade_item = $DB->get_record('grade_items', ['itemmodule' => 'quiz', 'iteminstance' => $cm->instance]);
            $grade = $DB->get_record('grade_grades', ['userid' => $USER->id, 'itemid' => $grade_item->id]);
            $finalgrade = 0;
            if (isset($grade->finalgrade)) {
                $finalgrade = $grade->finalgrade;
            }
            $value = ($finalgrade / $grade_item->grademax * 100);
            $data[] = array('name' => $grade_item->itemname, 'values' => [$value]);
        }
    }

    return $data;
}
