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

        $data = $this->indicator_test_bar();
        $content .= $this->get_chart('bar', $data);

        $data = $this->indicator_test_bar();
        $content .= $this->get_chart('bar', $data, ['bar_horizontal' => true]);

        $data = $this->indicator_test_bar();
        $content .= $this->get_chart('bar', $data, ['bar_stacked' => true]);

        $data = $this->indicator_test_line();
        $content .= $this->get_chart('line', $data);

        $data = $this->indicator_test_line_smooth();
        $content .= $this->get_chart('line', $data, ['line_smooth' => true]);

        $data = $this->indicator_test_pie();
        $content .= $this->get_chart('pie', $data);

        $data = $this->indicator_test_doughnut();
        $content .= $this->get_chart('pie', $data, ['pie_doughnut' => true]);

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

    function indicator_test_bar()
    {
        global $DB, $USER;

        if (is_null($this->course)){
            return $this->course;
        }

        $data = array();
        $data['title'] = "BAR CHART";
        $data['labels'] = ['A','B','C'];
        $data['series'] = array(
            ["name" => "Ref 1", "values" => [150,280,310]],
            ["name" => "Ref 2", "values" => [160,290,320]],
            ["name" => "Ref 3", "values" => [170,200,330]]
        );

        return $data;
    }

    function indicator_test_line()
    {
        global $DB, $USER;

        if (is_null($this->course)){
            return $this->course;
        }

        $data = array();
        $data['title'] = "LINE CHART";
        $data['labels'] = ['A','B','C'];
        $data['series'] = array(
            ["name" => "Ref 1", "values" => [150,280,310]],
            ["name" => "Ref 2", "values" => [160,290,320]],
            ["name" => "Ref 3", "values" => [170,200,330]]
        );

        return $data;
    }

    function indicator_test_line_smooth()
    {
        global $DB, $USER;

        if (is_null($this->course)){
            return $this->course;
        }

        $data = array();
        $data['title'] = "LINE CHART";
        $data['labels'] = ['A','B','C'];
        $data['series'] = array(
            ["name" => "Ref 1", "values" => [150,280,310]],
            ["name" => "Ref 2", "values" => [160,290,320]],
            ["name" => "Ref 3", "values" => [170,200,330]]
        );

        return $data;
    }

    function indicator_test_pie()
    {
        global $DB, $USER;

        if (is_null($this->course)){
            return $this->course;
        }

        $data = array();
        $data['title'] = "PIE CHART";
        $data['labels'] = ['A','B','C'];
        $data['series'] = array("name" => "Ref", "values" => [15,25,60]);

        return $data;
    }

    function indicator_test_doughnut()
    {
        global $DB, $USER;

        if (is_null($this->course)){
            return $this->course;
        }

        $data = array();
        $data['title'] = "DOUGHNUT CHART";
        $data['labels'] = ['A','B','C'];
        $data['series'] = array("name" => "Ref", "values" => [15,25,60]);

        return $data;
    }
}
