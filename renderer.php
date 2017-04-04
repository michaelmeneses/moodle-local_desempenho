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

        if ($this->course) {
            $data = $this->indicator_grade_quiz();
            $chart = $this->get_chart('bar', $data);
            $tabs['tabs'][] = array('name' => "gradequiz_bar", 'displayname' => $data['title'], 'html' => $chart, 'active' => true);

            $data = $this->indicator_grade_quiz();
            $chart = $this->get_chart('bar', $data, ['bar_horizontal' => true]);
            $tabs['tabs'][] = array('name' => "gradequiz_barhorizontal", 'displayname' => $data['title'], 'html' => $chart, 'active' => false);

            $content .= $OUTPUT->render_from_template('theme_boost/admin_setting_tabs', $tabs);

            return $content;

        } else {
            $content .= html_writer::tag('p',get_string('selectacourse'));
            $courses = enrol_get_my_courses();
            foreach ($courses as $course) {
                $content .= html_writer::link(new moodle_url('/local/desempenho/index.php', ['courseid' => $course->id]), $course->fullname);
            }
        }

        return html_writer::tag('div',$content, ['class' => 'desempenho']);
    }

    function get_chart($type, $data, $options = null)
    {
        global $OUTPUT;

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
        $output = $OUTPUT->render($chart, false);

        return html_writer::tag('div', $output, ['class' => 'desempenho_chart']);
    }

    function indicator_grade_quiz()
    {
        global $DB, $USER;

        if (is_null($this->course)){
            return $this->course;
        }

        $data = array();
        $data['title'] = get_string('pluginname','mod_quiz');
        $data['labels'] = [get_string('pluginname','mod_quiz')];

        $mod = $DB->get_record('modules', ['name' => 'quiz']);
        $modules = $DB->get_records('course_modules', ['course' => $this->course->id, 'module' => $mod->id]);
        foreach ($modules as $module) {
            $grade_item = $DB->get_record('grade_items', ['itemmodule' => 'quiz', 'iteminstance' => $module->instance]);
            $grade = $DB->get_record('grade_grades', ['userid' => $USER->id, 'itemid' => $grade_item->id]);
            $finalgrade = 0;
            if (isset($grade->finalgrade)) {
                $finalgrade = $grade->finalgrade;
            }
            $value = ($finalgrade / $grade_item->grademax * 100);
            $data['series'][] = array('name' => $grade_item->itemname, 'values' => [$value]);
        }

        return $data;
    }
}
