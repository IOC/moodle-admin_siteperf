<?php

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('toolsiteperf');

$path = "/$CFG->admin/tool/siteperf";

$PAGE->requires->js( $path . '/lib/jquery.min.js');
$PAGE->requires->js( $path . '/lib/jquery.jqplot.min.js');
$PAGE->requires->js( $path . '/lib/plugins/jqplot.categoryAxisRenderer.min.js');
$PAGE->requires->js( $path . '/lib/plugins/jqplot.canvasTextRenderer.min.js');
$PAGE->requires->js( $path . '/lib/plugins/jqplot.canvasAxisTickRenderer.min.js');
$PAGE->requires->js( $path . '/lib/plugins/jqplot.highlighter.min.js');
$PAGE->requires->js( $path . '/lib/plugins/jqplot.cursor.min.js');
$PAGE->requires->js( $path . '/module.js');

$PAGE->requires->css( $path . '/lib/jquery.jqplot.min.css');
$PAGE->requires->css( $path . '/index.css');

echo $OUTPUT->header();

include 'index.html';

echo $OUTPUT->footer();