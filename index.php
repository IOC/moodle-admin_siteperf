<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package    tool
 * @subpackage siteperf
 * @author     Marc Català <mcatala@ioc.cat>
 * @author     Albert Gasset <albert.gasset@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

require('index.html');

echo $OUTPUT->footer();