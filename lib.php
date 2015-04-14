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

require_once($CFG->dirroot . '/lib/dmllib.php');
require_once($CFG->libdir . '/csvlib.class.php');

class tool_siteperf {

    private $timestamp;

    public function __construct() {
        $this->timestamp = microtime(true);
    }

    public function log() {
        global $COURSE, $DB;

        if (!$DB->get_manager()->table_exists('tool_siteperf_log')) {
            return false;
        }
        $time = microtime(true) - $this->timestamp;
        $record = new stdclass();
        $record->year = date('o', $this->timestamp);
        $record->week = date('W', $this->timestamp);
        $record->day = date('w', $this->timestamp) ?: 7;
        $record->hour = date('G', $this->timestamp);
        $record->course = (!empty($COURSE) ? $COURSE->shortname : '');
        $record->script = $this->script();
        $record->time = $time;
        if (!empty($COURSE)) {
            $DB->insert_record('tool_siteperf_log', $record);
        };
    }

    private function script() {
        global $CFG, $ME;

        $script = str_replace('https://', 'http://', $ME);
        $script = str_replace($CFG->wwwroot, '', $script);
        $script = trim($script, '/');

        if (strpos($script, '.php') === false) {
            $script .= '/index.php';
        } else {
            $parts = explode('.php', $script);
            $script = $parts[0] . '.php';
        }

        return trim($script, '/');
    }

    public static function init() {
        global $TOOLSITEPERF;

        $TOOLSITEPERF = new tool_siteperf;
    }

    public static function shutdown() {
        global $CFG, $TOOLSITEPERF;

        if (empty($CFG->tool_siteperf_disable)) {
            $TOOLSITEPERF->log();
        }
    }

}

class tool_siteperf_log {

    private function fetch_aggregate($maxid, $fields) {
        global $DB;
        $sql = 'SELECT MAX(id), ' . $fields . ', COUNT(*) AS hits, SUM(time) AS time'.
                ' FROM {tool_siteperf_log}'.
                ' WHERE id <= ?'.
                ' GROUP BY '. $fields;
        $records = $DB->get_records_sql($sql, array($maxid));
        return $records ? $records : array();
    }

    public function move_to_stats() {
        global $DB;
        $maxid = $DB->get_field('tool_siteperf_log', 'MAX(id)', array());

        $groups = array('year, week, day, hour, course',
                'year, week, day, hour, script',
                'year, week, day, hour',
                'year, week, day, course',
                'year, week, day, script',
                'year, week, day',
                'year, week, course',
                'year, week, script',
                'year, week',
                'year, course',
                'year, script',
                'year');

        if ($maxid) {
            $stats = new tool_siteperf_stats();
            foreach ($groups as $fields) {
                $records = $this->fetch_aggregate($maxid, $fields);
                $stats->add_records($records);
            }
            $DB->delete_records_select('tool_siteperf_log',
                    'id <= ?', array($maxid));
        }
    }
}

class tool_siteperf_stats {

    public static $daysofweek = array('', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');

    public function add($year, $week, $day, $hour, $course, $script, $hits, $time) {
        global $DB;

        $params = array();

        $where = new stdClass();
        $where->year = $year;
        $where->week = $week;
        $where->day = $day;
        $where->hour = $hour;
        $where->course = $course;
        $where->script = $script;

        list($select, $params) = tool_siteperf_array_to_select($where);
        $record = $DB->get_record_select('tool_siteperf_stats', $select, $params);
        if ($record) {
            $record->hits += $hits;
            $record->time += $time;
            $DB->update_record('tool_siteperf_stats', $record);
        } else {
            $record = $where;
            $record->hits = $hits;
            $record->time = $time;
            $DB->insert_record('tool_siteperf_stats', $record);
        }
    }

    public function add_records($records) {
        foreach ($records as $r) {
            $this->add(isset($r->year) ? $r->year : null,
                    isset($r->week) ? $r->week : null,
                    isset($r->day) ? $r->day : null,
                    isset($r->hour) ? $r->hour : null,
                    isset($r->course) ? $r->course : null,
                    isset($r->script) ? $r->script : null,
                    $r->hits, $r->time);
        }
    }

    public function fetch($year, $week=false, $day=false, $hour=false) {
        global $DB;

        $object = new stdClass();

        list($select, $params) = $this->where($year, $week, $day, $hour);
        if ($record = $DB->get_record_select('tool_siteperf_stats', $select, $params)) {
            $object->hits = (int) $record->hits;
            $object->time = (float) $record->time / $record->hits;
        }
        return $object;
    }

    public function export_csv($year, $week=false, $day=false, $hour=false) {
        global $DB, $SITE;

        $exportdata = array(
                get_string('date'),
                get_string('hits'),
                get_string('time', 'tool_siteperf')
        );
        $sql = "SELECT *" .
            " FROM {tool_siteperf_stats}";

        if ($week === false) {
            $downloadfilename = clean_filename($year . "_" . $SITE->shortname);
            $sql .= " WHERE year = :year
                AND week IS NOT NULL
                AND day IS NOT NULL
                AND hour IS NULL
                AND course IS NULL
                AND script IS NULL
                ORDER BY week ASC";
            $params = array('year' => $year);
        } else {
            $filename = $year . '_' . $week;
            if ($day !== false) {
                $filename .= '_' . $day;
            }
            if ($hour !== false) {
                $filename .= '_' . $hour;
            }
            $filename .= '_' . $SITE->shortname;
            $downloadfilename = clean_filename($filename);
            list($where, $params) = $this->where($year, $week, $day, $hour, true);
            $sql .= " WHERE " . $where . " ORDER BY week ASC";
            if ($hour === false and $day === false) {
                $exportdata[0] = get_string('week');
            }
            array_splice($exportdata, 1, 0, get_string('course'));
        }
        $rs = $DB->get_recordset_sql($sql, $params);
        if (!empty($rs)) {
            $csvexport = new csv_export_writer();
            $csvexport->set_filename($downloadfilename);
            $csvexport->add_data($exportdata);
            foreach ($rs as $row) {
                $exportdata = array();
                $date = new DateTime();
                $date->setISODate($row->year, $row->week, !is_null($row->day) ? $row->day : 1);
                if ($week === false) {
                    $exportdata[] = $date->format('Y/m/d');
                } else if ($day === false) {
                    $exportdata[] = date_format_string($date->getTimestamp(), '%Y-%d-%B');
                    $exportdata[] = $row->course;
                } else if ($hour === false) {
                    $exportdata[] = $date->format('Y/m/d');
                    $exportdata[] = $row->course;
                } else {
                    $exportdata[] = $date->format('Y/m/d') . ' ' . $row->hour . ':00';
                    $exportdata[] = $row->course;
                }
                $exportdata[] = $row->hits;
                $exportdata[] = $row->time / $row->hits;
                $csvexport->add_data($exportdata);
            }
            $rs->close();
            $csvexport->download_file();
        }
        $rs->close();
    }

    public function fetch_weeks($year) {
        $stats = array();
        foreach ($this->weeks($year) as $week => $label) {
            $record = $this->fetch($year, $week);
            $record->label = $label;
            $stats[$week] = $record;
        }
        return $stats;
    }

    public function fetch_days($year, $week) {
        $stats = array();
        foreach ($this->days($year, $week) as $day => $label) {
            $record = $this->fetch($year, $week, $day);
            $record->label = $label;
            $stats[$day] = $record;
        }
        return $stats;
    }

    public function fetch_hours($year, $week, $day) {
        $stats = array();
        foreach ($this->hours($year, $week, $day) as $hour => $label) {
            $record = $this->fetch($year, $week, $day, $hour);
            $record->label = $label;
            $stats[$hour] = $record;
        }
        return $stats;
    }

    public function fetch_courses($year, $week=false, $day=false, $hour=false) {
        global $DB;

        $courses = array();
        $params = array();

        list($select, $params) = $this->where($year, $week, $day, $hour, true, false);
        $records = $DB->get_records_select('tool_siteperf_stats', $select,
                $params, 'hits DESC', '*', 0, 20);
        foreach ($records as $record) {
            $object = new stdClass();
            $object->name = $record->course;
            $object->hits = (int) $record->hits;
            $object->time = (float) $record->time / $record->hits;
            $courses[] = $object;
        }
        return $courses;
    }

    public function fetch_scripts($year, $week=false, $day=false, $hour=false) {
        global $DB;

        $scripts = array();
        $params = array();
        list($select, $params) = $this->where($year, $week, $day, $hour, false, true);
        $records = $DB->get_records_select('tool_siteperf_stats', $select,
                $params, 'hits DESC', '*', 0, 20);
        foreach ($records as $record) {
            $object = new stdClass();
            $object->name = $record->script;
            $object->hits = (int) $record->hits;
            $object->time = (float) $record->time / $record->hits;
            $scripts[] = $object;
        }
        return $scripts;
    }

    public function years() {
        global $DB;

        $years = array();

        $sql = 'SELECT DISTINCT year'.
                ' FROM {tool_siteperf_stats}'.
                ' WHERE year IS NOT NULL'.
                ' GROUP BY year'.
                ' ORDER BY year ASC';

        if ($records = $DB->get_records_sql($sql)) {
            foreach ($records as $year => $record) {
                $years[(int) $year] = "$year";
            }
        }

        return $years;
    }

    public function weeks($year) {
        global $DB;

        $weeks = array();

        $sql = 'SELECT DISTINCT week'.
                ' FROM {tool_siteperf_stats}'.
                ' WHERE year=? AND week IS NOT NULL'.
                ' GROUP BY year, week'.
                ' ORDER BY week ASC';

        if ($records = $DB->get_records_sql($sql, array($year))) {
            foreach ($records as $week => $record) {
                $start = get_week_offset_timestamp($year, $week);
                $weeks[(int) $week] = userdate($start, '%d %B');
            }
        }

        return $weeks;
    }

    public function days($year, $week) {
        global $DB;

        $days = array();

        $sql = 'SELECT DISTINCT day'.
                ' FROM {tool_siteperf_stats}'.
                ' WHERE year=? AND week=? AND day IS NOT NULL'.
                ' GROUP BY year, week, day'.
                ' ORDER BY day ASC';

        if ($records = $DB->get_records_sql($sql, array($year, $week))) {
            foreach ($records as $day => $record) {
                $day = intval($day, 10);
                $dayofweek = self::$daysofweek[$day];
                $start = get_week_offset_timestamp($year, $week);
                $start = strtotime("+".($day - 1)." days", $start);
                $days[$day] = get_string($dayofweek, 'calendar') . ' ' . date('d', $start);
            }
        }

        return $days;
    }

    public function hours($year, $week, $day) {
        global $DB;

        $hours = array();

        $sql = 'SELECT DISTINCT hour'.
                ' FROM {tool_siteperf_stats}'.
                ' WHERE year=? AND week=? AND day=?'.
                ' AND hour IS NOT NULL'.
                ' GROUP BY year, week, day, hour'.
                ' ORDER BY hour ASC';

        if ($records = $DB->get_records_sql($sql, array($year, $week, $day))) {
            foreach ($records as $hour => $record) {
                $hours[(int) $hour] = sprintf("%02d", $hour);
            }
        }

        return $hours;
    }

    public function where($year, $week, $day, $hour, $courses=false, $scripts=false) {
        $params = array();
        $where = array();
        if ($year !== false) {
            $where[] = "year = :year";
            $params['year'] = $year;
        } else {
            $where[] = "year IS NULL";
        }
        if ($week !== false) {
            $where[] = "week = :week";
            $params['week'] = $week;
        } else {
            $where[] = "week IS NULL";
        }
        if ($day !== false) {
            $where[] = "day = :day";
            $params['day'] = $day;
        } else {
            $where[] = "day IS NULL";
        }
        if ($hour !== false) {
            $where[] = "hour = :hour";
            $params['hour'] = $hour;
        } else {
            $where[] = "hour IS NULL";
        }
        $where[] = ($courses !== false ? 'course IS NOT NULL' : "course IS NULL");
        $where[] = ($scripts !== false ? 'script IS NOT NULL' : "script IS NULL");
        return array(implode(' AND ', $where), $params);
    }
}

function tool_siteperf_array_to_select($object) {
    $select = array();
    $params = array();
    foreach ($object as $name => $value) {
        if ($value === null or $value === false) {
            $select[] = "$name IS NULL";
        } else if ($value === true) {
            $select[] = "$name IS NOT NULL";
        } else if (is_numeric($value)) {
            $select[] = "$name = ?";
            $params[] = $value;
        } else {
            $select[] = "$name = ?";
            $params[] = addslashes($value);
        }
    }
    return array(implode(' AND ', $select), $params);
}

function tool_siteperf_cron() {
    global $DB;

    if (!$DB->get_manager()->table_exists('tool_siteperf_log')) {
        return false;
    }
    try {
        $log = new tool_siteperf_log();
        $transaction = $DB->start_delegated_transaction();
        $log->move_to_stats();
        $transaction->allow_commit();
    } catch (Exception $e) {
        $transaction->rollback($e);
    }
}

function get_week_offset_timestamp($year, $week) {
    // According to ISO-8601, January 4th is always in week 1
    $halfwaytheweek = strtotime($year . "0104 +" . ($week - 1) . " weeks");

    // Subtract days to Monday
    $dayoftheweek = date("N", $halfwaytheweek);
    $daystosubtract = $dayoftheweek - 1;

    // Calculate the week's timestamp
    $unixtimestamp = strtotime("-$daystosubtract day", $halfwaytheweek);

    return $unixtimestamp;
}
