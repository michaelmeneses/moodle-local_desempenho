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
        $content = '';

        $data = $this->indicator_grade_quiz();
        $content .= $this->get_chart('bar', $data);

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
            $grade = $DB->get_record('grade_grades', ['userid' => 75, 'itemid' => $grade_item->id]);
            $data['series'][] = array('name' => $grade_item->itemname, 'values' => [($grade->finalgrade / $grade_item->grademax * 100)]);
        }

        return $data;
    }
}
