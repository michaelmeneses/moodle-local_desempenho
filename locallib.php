<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Functions local for component 'local_desempenho'.
 *
 * @package   local_desempenho
 * @copyright 2017 Michael Meneses  {@link http://michaelmeneses.com.br}
 * @license   UNIPRE {@link http://www.cursounipre.com.br}
 */

require_once($CFG->libdir . '/grade/grade_item.php');

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
function get_grade_quiz_average($course) {
    global $CFG, $DB, $USER;

    $data = array();
    $items = array();

    $mod = $DB->get_record('modules', ['name' => 'quiz']);
    $modules = $DB->get_records('course_modules', ['course' => $course->id, 'module' => $mod->id, 'visible' => 1]);
    if (count($modules) == 0) {
        return null;
    }
    foreach ($modules as $module) {
        $grade_item = $DB->get_record('grade_items', ['itemmodule' => 'quiz', 'iteminstance' => $module->instance]);
        $grade = $DB->get_record('grade_grades', ['userid' => $USER->id, 'itemid' => $grade_item->id]);
        $finalgrade = 0;
        if (isset($grade->finalgrade)) {
            $finalgrade = $grade->finalgrade;
        }
        $value = ($finalgrade / $grade_item->grademax * 100);
        $ids[] = $grade_item->id;
        $data['labels'][] = $grade_item->itemname;
        $items["Você"][] = number_format($finalgrade, 2);
    }

    // Limit to users with a gradeable role.
    list($gradebookrolessql, $gradebookrolesparams) = $DB->get_in_or_equal(explode(',', $CFG->gradebookroles), SQL_PARAMS_NAMED, 'grbr0');
    // Limit to users with an active enrollment.
    $context = context_course::instance($course->id);
    list($enrolledsql, $enrolledparams) = get_enrolled_sql($context, '', 0, 0);
    // We want to query both the current context and parent contexts.
    list($relatedctxsql, $relatedctxparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'relatedctx');
    $params = array_merge(array('courseid' => $course->id), $enrolledparams, $gradebookrolesparams, $relatedctxparams);

    $sql = "SELECT DISTINCT u.id FROM {user} u
            JOIN ($enrolledsql) je ON je.id = u.id
            JOIN {role_assignments} ra ON u.id = ra.userid
            WHERE ra.roleid $gradebookrolessql AND u.deleted = 0 AND ra.contextid $relatedctxsql";
    $selectedusers = $DB->get_records_sql($sql, $params);
    $totalusers = count($selectedusers);

    // Find sums of all grade items in course.
    $sql = "SELECT g.itemid, SUM(g.finalgrade) AS sum
                      FROM {grade_items} gi
                      JOIN {grade_grades} g ON g.itemid = gi.id
                      JOIN {user} u ON u.id = g.userid
                      JOIN ($enrolledsql) je ON je.id = u.id
                      JOIN (
                               SELECT DISTINCT ra.userid
                                 FROM {role_assignments} ra
                                WHERE ra.roleid $gradebookrolessql
                                  AND ra.contextid $relatedctxsql
                           ) rainner ON rainner.userid = u.id
                     WHERE gi.courseid = :courseid
                       AND u.deleted = 0
                       AND g.finalgrade IS NOT NULL
                       AND g.finalgrade != 0
                     GROUP BY g.itemid";
    $sumarray = array();
    if ($sums = $DB->get_records_sql($sql, $params)) {
        foreach ($sums as $itemid => $csum) {
            $sumarray[$itemid] = (float) $csum->sum;
        }
    }

    // This query returns a count of ungraded grades (NULL finalgrade OR no matching record in grade_grades table)
    $sql = "SELECT gi.id, COUNT(DISTINCT u.id) AS count
                      FROM {grade_items} gi
                      CROSS JOIN {user} u
                      JOIN ($enrolledsql) je
                           ON je.id = u.id
                      JOIN {role_assignments} ra
                           ON ra.userid = u.id
                      LEFT OUTER JOIN {grade_grades} g
                           ON (g.itemid = gi.id AND g.userid = u.id AND g.finalgrade IS NOT NULL AND g.finalgrade != 0)
                     WHERE gi.courseid = :courseid
                           AND ra.roleid $gradebookrolessql
                           AND ra.contextid $relatedctxsql
                           AND u.deleted = 0
                           AND g.id IS NULL
                  GROUP BY gi.id";
    $ungradedcounts = $DB->get_records_sql($sql, $params);

    $media = array();
    $sql = "SELECT gi.id FROM {grade_items} gi 
            JOIN {course_modules} cm
             ON cm.instance = gi.iteminstance AND cm.module = (SELECT id FROM {modules} WHERE name like 'quiz') AND gi.itemmodule = (SELECT name FROM {modules} WHERE name like 'quiz')
            WHERE gi.courseid = :courseid
            AND cm.visible = 1";
    $gradeitems = $DB->get_records_sql($sql, ['courseid' => $course->id]);

    foreach ($gradeitems as $itemid => $unused) {
        if (!isset($sumarray[$itemid])) {
            $sumarray[$itemid] = 0;
        }

        if (empty($ungradedcounts[$itemid])) {
            $ungradedcount = 0;
        } else {
            $ungradedcount = $ungradedcounts[$itemid]->count;
        }

        $meancount = $totalusers - $ungradedcount;

        if (!isset($sumarray[$itemid]) || $meancount == 0) {
            $media[] = number_format(0, 2);
        } else {
            $sum = $sumarray[$itemid];
            $avgradeval = $sum/$meancount;
            $media[] = number_format($avgradeval, 2);
        }
    }
    $items[get_string('classaverage', 'local_desempenho')] = $media;

    if (count($items)) {
        foreach ($items as $key => $item) {
            $data['series'][] = array('name' => $key, 'values' => $item);
        }
    }

    return $data;
}
function get_grade_quiz_ranking($course) {
    global $DB, $USER;

    $data = array();

    $sql = "SELECT cm.id, cm.instance FROM {course_modules} cm WHERE course = :courseid AND visible = 1 AND module = (SELECT id FROM {modules} WHERE name like 'quiz')";
    $cms = $DB->get_records_sql($sql, ['courseid' => $course->id]);

    if (!is_null($cms)) {
        foreach ($cms as $cm) {
            $aux = get_ranking_by_cm($course, $cm->instance);
            $total = count(get_ranking_by_cm($course, $cm->instance, true));
            foreach ($aux as $key => $value) {
                if ($value->userid == $USER->id) {
                    $pos = array_search($key, array_keys($aux)) + 1;
                    if ($pos > $total) {
                        $data[$value->name] = ['user' => null, 'total' => $total];
                    } else {
                        $data[$value->name] = ['user' => $pos, 'total' => $total];
                    }
                }
            }
        }
    }

    return $data;

}

function get_ranking_by_cm($course, $cmid, $notnull = false) {
    global $DB;

    $sqlwhere = '';
    if ($notnull) {
        $sqlwhere .= " AND gg.finalgrade IS NOT NULL ";
    }

    $sql = "SELECT gg.id, gi.itemname as name, gi.iteminstance, gg.userid, gg.finalgrade FROM {grade_grades} gg
            INNER JOIN {grade_items} gi ON gg.itemid = gi.id
            INNER JOIN {course_modules} cm  ON cm.instance = gi.iteminstance AND cm.module = (SELECT id FROM {modules} WHERE name = 'quiz')
            INNER JOIN {user} u  ON u.id = gg.userid
            WHERE cm.visible = 1 AND gi.itemmodule = 'quiz' AND gi.courseid = :courseid AND gi.iteminstance = :iteminstance $sqlwhere
            ORDER BY gi.iteminstance, gg.finalgrade DESC, u.firstname, u.lastname;";
    $result = $DB->get_records_sql($sql, ['courseid' => $course->id,'iteminstance' => $cmid]);

    return $result;

}
