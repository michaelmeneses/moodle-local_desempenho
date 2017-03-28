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
    function print_header($course = null)
    {
        $output = '';
        $output .= html_writer::tag('h1',get_string('myperformance', 'local_desempenho'));
        if ($course) {
            $output .= html_writer::tag('h3', $course->fullname);
        }
        return html_writer::tag('h1', $output);
    }

    function content()
    {
        $content = '';
        $content .= $this->get_chart();
        return html_writer::tag('div',$content, ['class' => 'desempenho']);
    }

    function get_chart()
    {
        global $OUTPUT;

        $chart = new core\chart_bar();
        $chart->set_horizontal(false);
        $chart->add_series(new core\chart_series('My series 1', [400, 460, 1120, 540]));
        $chart->add_series(new core\chart_series('My series 2', [450, 520, 1200, 0]));
        $chart->set_labels(['2004', '2005', '2006', '2007']);
        $output = $OUTPUT->render($chart);

        return html_writer::tag('div', $output, ['class' => 'desempenho_chart']);
    }
}
