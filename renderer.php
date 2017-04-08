<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Renderers for component 'local_desempenho'.
 *
 * @package   local_desempenho
 * @copyright 2017 Michael Meneses  {@link http://michaelmeneses.com.br}
 * @license   UNIPRE {@link http://www.cursounipre.com.br}
 */

class local_desempenho_renderer extends plugin_renderer_base
{
    public $course;

    function print_header($course = null)
    {
        if (!is_null($course)){
            $this->course = $course;
        }
        $output = '';
        $output .= html_writer::tag('h1',get_string('myperformance', 'local_desempenho'));
        if ($this->course) {
            $output .= html_writer::tag('h3', $this->course->fullname);
        }
        return html_writer::tag('h1', $output);
    }

    function content()
    {
        global $OUTPUT;

        $content = '';
        $courseidsimulado = get_config('local_desempenho', 'courseidsimulado');

        $tabs = array();
        $active = true;
        if ($this->course) {
            if ($this->course->id != $courseidsimulado) {
                $data = $this->indicator_grade_simulado();
                $chart = $this->get_chart('line', $data);
                $tabs['tabs'][] = array('name' => "gradesimulado_line", 'displayname' => $data['title'], 'html' => $chart, 'active' => $active);
                $active = false;
            }
            if ($this->course) {
                $data = $this->indicator_grade_quiz();
                $chart1 = $this->get_chart('bar', $data);
                $data = $this->indicator_grade_quiz_this();
                $chart2 = $this->get_chart('line', $data);
                $tabs['tabs'][] = array('name' => "gradequiz_line", 'displayname' => $data['title'], 'html' => $chart1 . $chart2, 'active' => $active);
                $active = false;
            }

            $content .= $OUTPUT->render_from_template('theme_boost/admin_setting_tabs', $tabs);
        } else {
            $content .= html_writer::tag('p',get_string('selectacourse'));
            $courses = enrol_get_my_courses();
            foreach ($courses as $course) {
                $link = html_writer::link(new moodle_url('/local/desempenho/index.php', ['courseid' => $course->id]), $course->fullname);
                $content .= html_writer::tag('h5', $link);
            }

            $data = $this->indicator_grade_simulado();
            $chart = $this->get_chart('line', $data);
            $tabs['tabs'][] = array('name' => "gradesimulado_line", 'displayname' => $data['title'], 'html' => $chart, 'active' => $active);
            $active = false;

            $content .= $OUTPUT->render_from_template('theme_boost/admin_setting_tabs', $tabs);
        }

        return html_writer::tag('div',$content, ['class' => 'desempenho']);
    }

    function get_chart($type, $data, $options = null)
    {
        global $OUTPUT;

        if (is_null($data)){
            return '';
        }

        $output = '';
        if (isset($data['info'])) {
            $output .= html_writer::tag('p', $data['info'], ['class' => 'info']);
        }

        $class = "core\\chart_$type";
        $chart = new $class();

        $chart->set_title($data['title']);
        $chart->set_labels($data['labels']);

        if ($type == 'bar') {
            if (isset($options['bar_horizontal'])) {
                $chart->set_horizontal($options['bar_horizontal']);
            }
            if (isset($options['bar_stacked'])) {
                $chart->set_stacked($options['bar_stacked']);
            }
            foreach ($data['series'] as $value) {
                $serie = new core\chart_series($value['name'], $value['values']);
                $chart->add_series($serie);
            }
        }
        if ($type == 'line') {
            if (isset($options['line_smooth'])) {
                $chart->set_smooth($options['line_smooth']);
            }
            foreach ($data['series'] as $value) {
                $serie = new core\chart_series($value['name'], $value['values']);
                $chart->add_series($serie);
            }
        }
        if ($type == 'pie') {
            if (isset($options['pie_doughnut'])) {
                $chart->set_doughnut($options['pie_doughnut']);
            }
            $serie = new core\chart_series($data['series']['name'], $data['series']['values']);
            $chart->add_series($serie);

        }
        $output .= $OUTPUT->render($chart, false);

        return html_writer::tag('div', $output, ['class' => 'desempenho_chart']);
    }

    function indicator_grade_simulado()
    {
        global $CFG, $DB, $USER;

        $courseid = get_config('local_desempenho', 'courseidsimulado');
        if ($courseid) {
            $course = get_course($courseid);
        } else {
            $course = $this->course;
        }

        $items = array();
        $data = array();
        $data['title'] = $course->fullname;
        $data['info'] = "Esse indicador apresenta resultados dos questionários do curso ". $course->fullname.".";

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

        $sql = "SELECT DISTINCT u.id
                       FROM {user} u
                       JOIN ($enrolledsql) je
                            ON je.id = u.id
                       JOIN {role_assignments} ra
                            ON u.id = ra.userid
                      WHERE ra.roleid $gradebookrolessql
                            AND u.deleted = 0
                            AND ra.contextid $relatedctxsql";
        $selectedusers = $DB->get_records_sql($sql, $params);
        $totalusers = count($selectedusers);

        // Find sums of all grade items in course.
        $sql = "SELECT g.itemid, SUM(g.finalgrade) AS sum, gi.grademax as grademax
                      FROM {grade_items} gi
                      JOIN {grade_grades} g ON g.itemid = gi.id
                      JOIN {course_modules} cm ON cm.instance = (SELECT id FROM {quiz} q WHERE q.id = gi.iteminstance)
                                               AND cm.module =  (SELECT id FROM {modules} m WHERE m.name like 'quiz')
                      JOIN {user} u ON u.id = g.userid
                     WHERE gi.courseid = :courseid
                       AND u.deleted = 0
                       AND g.finalgrade IS NOT NULL
                       AND gi.itemtype like 'mod'
                       AND gi.itemmodule like 'quiz'
                       AND cm.visible = 1
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
                           ON (g.itemid = gi.id AND g.userid = u.id AND g.finalgrade IS NOT NULL)
                       JOIN {course_modules} cm ON cm.instance = (SELECT id FROM {quiz} q WHERE q.id = gi.iteminstance)
                                           AND cm.module =  (SELECT id FROM {modules} m WHERE m.name like 'quiz')
                     WHERE gi.courseid = :courseid
                           AND u.deleted = 0
                           AND g.id IS NULL
                           AND gi.itemtype like 'mod'
                           AND gi.itemmodule like 'quiz'
                           AND cm.visible = 1
                  GROUP BY gi.id";
        $ungradedcounts = $DB->get_records_sql($sql, $params);

        $media = array();
        foreach ($ungradedcounts as $key => $value) {
            if (empty($sumarray[$key])) {
                $sum = 0;
            } else {
                $sum = $sumarray[$key];
            }
            $meancount = $totalusers - $value->count;
            if (!isset($sumarray[$key]) || $sum == 0) {
                $n = 0;

            } else {
                $sum = $sumarray[$key];
                $n = $sum / $meancount;
            }
            $media[] = number_format($n, 2);
        }
        $items[get_string('classaverage', 'local_desempenho')] = $media;

        if (count($items)) {
            foreach ($items as $key => $item) {
                $data['series'][] = array('name' => $key, 'values' => $item);
            }
        }

        return $data;
    }

    function indicator_grade_quiz_this()
    {
        global $CFG, $DB, $USER;

        if (is_null($this->course)){
            return $this->course;
        } else {
            $course = $this->course;
        }

        $items = array();
        $data = array();
        $data['title'] = get_string('pluginname','mod_quiz') . "s em " . $this->course->fullname . " com média";

        $mod = $DB->get_record('modules', ['name' => 'quiz']);
        $modules = $DB->get_records('course_modules', ['course' => $course->id, 'module' => $mod->id]);
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

        $sql = "SELECT DISTINCT u.id
                       FROM {user} u
                       JOIN ($enrolledsql) je
                            ON je.id = u.id
                       JOIN {role_assignments} ra
                            ON u.id = ra.userid
                      WHERE ra.roleid $gradebookrolessql
                            AND u.deleted = 0
                            AND ra.contextid $relatedctxsql";
        $selectedusers = $DB->get_records_sql($sql, $params);
        $totalusers = count($selectedusers);

        // Find sums of all grade items in course.
        $sql = "SELECT g.itemid, SUM(g.finalgrade) AS sum, gi.grademax as grademax
                      FROM {grade_items} gi
                      JOIN {grade_grades} g ON g.itemid = gi.id
                      JOIN {user} u ON u.id = g.userid
                     WHERE gi.courseid = :courseid
                       AND u.deleted = 0
                       AND g.finalgrade IS NOT NULL
                       AND gi.itemtype like 'mod'
                       AND gi.itemmodule like 'quiz'
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
                           ON (g.itemid = gi.id AND g.userid = u.id AND g.finalgrade IS NOT NULL)
                     WHERE gi.courseid = :courseid
                           AND u.deleted = 0
                           AND g.id IS NULL
                           AND gi.itemtype like 'mod'
                           AND gi.itemmodule like 'quiz'
                  GROUP BY gi.id";
        $ungradedcounts = $DB->get_records_sql($sql, $params);

        $media = array();
        foreach ($ungradedcounts as $key => $value) {
            if (empty($sumarray[$key])) {
                $sum = 0;
            } else {
                $sum = $sumarray[$key];
            }
            $meancount = $totalusers - $value->count;
            if (!isset($sumarray[$key]) || $sum == 0) {
                $n = 0;

            } else {
                $sum = $sumarray[$key];
                $n = $sum / $meancount;
            }
            $media[] = number_format($n, 2);
        }
        $items[get_string('classaverage', 'local_desempenho')] = $media;

        if (count($items)) {
            foreach ($items as $key => $item) {
                $data['series'][] = array('name' => $key, 'values' => $item);
            }
        }

        return $data;

    }

    function indicator_grade_quiz()
    {
        if (is_null($this->course)){
            return $this->course;
        }

        $data = array();
        $data['title'] = get_string('pluginname','mod_quiz') . "s em " . $this->course->fullname;
        $data['labels'] = [get_string('pluginname','mod_quiz')];

        $data['series'] = get_grade_quiz($this->course);

        return $data;
    }
}
