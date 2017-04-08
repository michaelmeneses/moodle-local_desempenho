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
                $data = $this->indicator_grade_quiz_average_simulado();
                $chart = $this->get_chart('line', $data);
                $tabs['tabs'][] = array('name' => "gradesimulado_line", 'displayname' => $data['title'], 'html' => $chart, 'active' => $active);
                $active = false;
            }
            if ($this->course) {
                $data = $this->indicator_grade_quiz($this->course);
                $chart1 = $this->get_chart('bar', $data);
                $data = $this->indicator_grade_quiz_average($this->course);
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

    function indicator_grade_quiz_average_simulado()
    {
        $courseid = get_config('local_desempenho', 'courseidsimulado');
        if ($courseid) {
            $course = get_course($courseid);
        } else {
            $course = $this->course;
        }

        $data = array();
        $data['title'] = $course->fullname;
        $data['info'] = "Esse indicador apresenta resultados dos questionários do curso ". $course->fullname.".";

        $result = get_grade_quiz_average($course);
        $data['labels'] = $result['labels'];
        $data['series'] = $result['series'];

        return $data;
    }

    function indicator_grade_quiz_average($course)
    {
        $data = array();
        $data['title'] = get_string('pluginname','mod_quiz') . "s em " . $this->course->fullname . " com média";

        $result = get_grade_quiz_average($course);
        $data['labels'] = $result['labels'];
        $data['series'] = $result['series'];

        return $data;

    }

    function indicator_grade_quiz($course)
    {
        $data = array();
        $data['title'] = get_string('pluginname','mod_quiz') . "s em " . $this->course->fullname;
        $data['labels'] = [get_string('pluginname','mod_quiz')];
        $data['series'] = get_grade_quiz($course);

        return $data;
    }
}
