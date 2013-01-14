<?php

//  Site performance plugin for Moodle
//  Copyright © 2012  Institut Obert de Catalunya
//
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, either version 3 of the License, or
//  (at your option) any later version.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package    tool
 * @subpackage siteperf
 * @copyright  Marc Català <mcatala@ioc.cat>
 * @copyright  Albert Gasset <albert.gasset@gmail.com> 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/admin/tool/siteperf/lib.php');

admin_externalpage_setup('toolsiteperf');

$data = new object;

foreach (array('year', 'week', 'day', 'hour') as $param) {
    $data->$param = false;
    $value = optional_param($param, '', PARAM_RAW);
    if ($value !== '') {
        $data->$param = clean_param($value, PARAM_INT);
    }
}

$stats = new tool_siteperf_stats();

$data->years = $stats->years();
if (!empty($data->years) && !in_array($data->year, $data->years)) {
    $data->year = max($data->years);
}

$data->weeks = $stats->weeks($data->year);
if (!isset($data->weeks[$data->week])) {
    $data->week = false;
}

if ($data->week === false) {
    $data->day = false;
    $data->days = false;
} else {
    $data->days = $stats->days($data->year, $data->week);
    if (!isset($data->days[$data->day])) {
        $data->day = false;
    }
}

if ($data->day === false) {
    $data->hour = false;
    $data->hours = false;
} else {
    $data->hours = $stats->hours($data->year, $data->week, $data->day);
    if (!isset($data->hours[$data->hour])) {
        $data->hour = false;
    }
}


$r = $stats->fetch($data->year, $data->week, $data->day, $data->hour);
$data->time = (isset($r->time)?$r->time:'');
$data->hits = (isset($r->hits)?$r->hits:'');

if ($data->hour !== false) {
    $data->context = 'hour';
    $data->chart = false;
} elseif ($data->day !== false) {
    $data->context = 'day';
    $data->chart = $stats->fetch_hours($data->year, $data->week, $data->day);
} elseif ($data->week !== false) {
    $data->context = 'week';
    $data->chart = $stats->fetch_days($data->year, $data->week);
} else {
    $data->context = 'year';
    $data->chart = $stats->fetch_weeks($data->year);
}

$data->courses = $stats->fetch_courses($data->year, $data->week,
                                       $data->day, $data->hour);
$data->scripts = $stats->fetch_scripts($data->year, $data->week,
                                       $data->day, $data->hour);

$data->string = array('hits' => get_string('hits'),
                      'time' => get_string('time', 'tool_siteperf'));

echo json_encode($data);
